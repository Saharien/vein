<?php

require_once 'inc/DB.inc';

class Bestellung
{

  private $db_handle;
  private $alle_bestellungen;
  private $position;

  private $bestellungsnummer;
  private $kundennummer;
  private $rechnungsadressennummer;
  private $angebot;
  private $domainname;
  private $anzahl;
  private $servernummer;
  private $bankverbindungsnummer;
  private $zahlungsrhytmus;
  private $zahlungsart;
  private $bestellkontaktart;
  private $bestelldatum;
  private $ausfuehrungsdatum;
  private $kuendigungskontaktart;
  private $kuendigungsdatum;

  public function __construct()
  {
      $this->db_handle = DB::get_db();
  }  
  
  public function initialisiere()
  {
    $abfrage = $this->db_handle->prepare("SELECT Bestellungsnummer, Kundennummer, Rechnungsadressennummer, Angebot, Domainname, Anzahl, " .
                                          "Servernummer, Bankverbindungsnummer, Zahlungsrhytmus, Zahlungsart, Bestellkontaktart, " .
                                          "UNIX_TIMESTAMP(Bestelldatum) AS Bestelldatum, UNIX_TIMESTAMP(Ausfuehrungsdatum) AS Ausfuehrungsdatum, " .
                                          "Kuendigungskontaktart, UNIX_TIMESTAMP(Kuendigungsdatum) AS Kuendigungsdatum FROM bestellungen");
    $abfrage->execute();
    $this->alle_bestellungen = $abfrage->fetchall_assoc();
    $this->position = 0;
    
    $this->angebot = new Angebot();
  }

  public function lade_naechste()
  {
    if(!isset($this->alle_bestellungen[$this->position]))
      return false;
    
    $this->bestellungsnummer = $this->alle_bestellungen[$this->position]['Bestellungsnummer'];
    $this->kundennummer = $this->alle_bestellungen[$this->position]['Kundennummer'];
    $this->rechnungsadressennummer = $this->alle_bestellungen[$this->position]['Rechnungsadressennummer'];
    $this->angebot->lade($this->alle_bestellungen[$this->position]['Angebot']);
    $this->domainname = $this->alle_bestellungen[$this->position]['Domainname'];
    $this->anzahl = $this->alle_bestellungen[$this->position]['Anzahl'];
    $this->servernummer = $this->alle_bestellungen[$this->position]['Servernummer'];
    $this->bankverbindungsnummer = $this->alle_bestellungen[$this->position]['Bankverbindungsnummer'];
    $this->zahlungsrhytmus = $this->alle_bestellungen[$this->position]['Zahlungsrhytmus'];
    $this->zahlungsart = $this->alle_bestellungen[$this->position]['Zahlungsart'];
    $this->bestellkontaktart = $this->alle_bestellungen[$this->position]['Bestellkontaktart'];
    $this->bestelldatum = $this->alle_bestellungen[$this->position]['Bestelldatum'];
    $this->ausfuehrungsdatum = $this->alle_bestellungen[$this->position]['Ausfuehrungsdatum'];
    $this->kuendigungskontaktart = $this->alle_bestellungen[$this->position]['Kuendigungskontaktart'];
    $this->kuendigungsdatum = $this->alle_bestellungen[$this->position]['Kuendigungsdatum'];

    if($this->zahlungsrhytmus != Konst::$ZAHLUNGSRHYTMUS_JAEHRLICH &&
       $this->zahlungsrhytmus != Konst::$ZAHLUNGSRHYTMUS_MONATLICH)
    {
        Throw new Exception("Zahlungsrhytmus nicht gefuellt bei Bestellung Nr. " . $this->bestellungsnummer);
    }
    
    $this->position++;

    return true;
  }
  
  private function generiere_text_einmalig()
  {
      return(str_replace("\$DOMAIN", $this->domainname, $this->angebot->get_text_einmalig()));
  }
  
  private function generiere_text_regelmaessig($von, $bis)
  {
      $text = str_replace("\$DOMAIN", $this->domainname, $this->angebot->get_text_regelmaessig());
      
      if($this->zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_MONATLICH) {
          $text = str_replace("\$NAMEZEITRAUMGEBUEHR", "Monatsgebuehr", $text);
      } elseif($this->zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_JAEHRLICH) {
          $text = str_replace("\$NAMEZEITRAUMGEBUEHR", "Jahresgebuehr", $text);
      }
      
      $text = str_replace("\$DATUMVON", $von, $text);
      
      $text = str_replace("\$DATUMBIS", $bis, $text);
      
      return($text);
  }

  private function erzeuge_posten_einmalig()
  {
    if(Rechnungseintrag::existiert($this->bestellungsnummer, '0', '0'))
        return;

    $rechnungseintrag = new Rechnungseintrag($this->bestellungsnummer, $this->kundennummer, $this->anzahl, $this->generiere_text_einmalig(), $this->angebot->get_einmalige_gebuehr(), '0');
    $rechnungseintrag->speichern();
    
  }
  
  private function erzeuge_posten_regelmaessig($lauf_start, $lauf_ende)
  {
      
//    MonatJahr der Bestellung/Ausf�hrung bestimmen (je nachdem, was sp�ter ist)
      if($this->bestelldatum > $this->ausfuehrungsdatum) {
          $bestellung_start = new MonatJahr(date("m", $this->bestelldatum), date("Y", $this->bestelldatum));
          $bestelltag = date("d", $this->bestelldatum);
      } else {
          $bestellung_start = new MonatJahr(date("m", $this->ausfuehrungsdatum), date("Y", $this->ausfuehrungsdatum));
          $bestelltag = date("d", $this->ausfuehrungsdatum);
      }

//    Festlegen wie hochgez�hlt werden soll
//    Start der Berechnung festlegen
      if($this->zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_JAEHRLICH) {
          $monatlich = false;
          $jaehrlich = true;

          if($lauf_start->ist_kleiner_gleich($bestellung_start)) {
              $effektiv_start = clone $bestellung_start;
          } else {
              // Wenn Bestelldatum vor Programmlaufstartdatum ist, wird als effektives
              // Start-MonatJahr das Monat der Bestellung und das erste Jahr des Laufes benutzt.
              // Wenn diese Kombi ausserhalb des Laufes liegt, wird als Jahr stattdessen das
              // Folgejahr benutzt.
              $effektiv_start = new MonatJahr($bestellung_start->get_monat(), $lauf_start->get_jahr());
              if($effektiv_start->ist_kleiner($lauf_start)) {
                  $effektiv_start->set($bestellung_start->get_monat(), $lauf_start->get_jahr() + 1);
              }
          }
      } elseif($this->zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_MONATLICH) {
          $monatlich = true;
          $jaehrlich = false;
          
          if($lauf_start->ist_kleiner_gleich($bestellung_start)) {
              $effektiv_start = clone $bestellung_start;
          } else {
              $effektiv_start = clone $lauf_start;
          }
      }
      
      if($effektiv_start->ist_groesser($lauf_ende)) {
          return;
      }
      
//    Für jedes Jahr/Monat ab Bestellung

      for($monatjahr = clone $effektiv_start;
          $monatjahr->ist_kleiner_gleich($lauf_ende);
          $monatjahr->plus($monatlich, $jaehrlich))
      {
          
          $datum_von = mktime(0, 0, 0, $monatjahr->get_monat(), $bestelltag, $monatjahr->get_jahr());
          
          if($monatlich) {
              // Datum bis = Datum von + 1 Monat - 1 Tag
              $datum_bis = mktime(0, 0, 0, date("m", $datum_von) + 1, date("d", $datum_von), date("Y", $datum_von)) - 86400;
          } elseif ($jaehrlich) {
              // Datum bis = Datum von + 1 Jahr - 1 Tag
              $datum_bis = mktime(0, 0, 0, date("m", $datum_von), date("d", $datum_von), date("Y", $datum_von) + 1) - 86400;
          }
          
          // Wenn gekündigt, Schleife verlassen
          if($this->kuendigungsdatum <> 0) {
              if($this->kuendigungsdatum <= ( $datum_bis - Konst::$KUENDIGUNGSFRIST * 86400)) {
                  break;
              }
          }

        //   // Temporäre Lösung zur temporären USt-Senkung (TEMPUST): Nur Einträge für 19% erstellen
        //   if($datum_bis >= mktime(0, 0, 0, 7, 1, 2020) && $datum_bis < mktime(0, 0, 0, 1, 1, 2021)) {
        //     continue;
        //   }

        // Temporäre Lösung zur temporären USt-Senkung (TEMPUST): Nur Einträge für 16% erstellen
        if(!($datum_bis >= mktime(0, 0, 0, 7, 1, 2020) && $datum_bis < mktime(0, 0, 0, 1, 1, 2021))) {
            continue;
        }

          if(!Rechnungseintrag::existiert($this->bestellungsnummer, $monatjahr->get_monat(), $monatjahr->get_jahr())) {

              $rechnungseintrag = new Rechnungseintrag(
                $this->bestellungsnummer,
                $this->kundennummer,
                $this->anzahl,
                $this->generiere_text_regelmaessig(date("d.m.Y", $datum_von), date("d.m.Y", $datum_bis)),
                $this->angebot->ermittle_regelmaessige_gebuehr($this->zahlungsrhytmus),
                $monatjahr->get_jahr() . str_pad($monatjahr->get_monat(), 2, '0', STR_PAD_LEFT) );
              
              $rechnungseintrag->speichern();

          }
          

      }
  }

  public function erzeuge_posten($start, $ende)
  {
    if($this->angebot->ist_einmalig()) {
        $this->erzeuge_posten_einmalig();
    }    
    if($this->angebot->ist_regelmaessig()) {
        $this->erzeuge_posten_regelmaessig($start, $ende);
    }
  }

}
               

?>