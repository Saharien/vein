<?php
  
  class Rechnung 
  {
      
      private $kundennummer;
      private $rechnungsnummer;
      private $posten;
      private $netto_endbetrag;
      private $netto_betrag;
      private $steuer;
      private $datum;
      private $adresse;
      private $zahlungsart;
      private $bankverbindung;
      
      private $db_handle;
      
      public function __construct($kundennummer, $rechnungsnummer, $datum)
      {
          
          $this->db_handle = DB::get_db();
          
          $this->kundennummer = $kundennummer;
          $this->rechnungsnummer = $rechnungsnummer;
          $this->datum = $datum;
          $this->lade_posten();       
          $this->lade_adresse();
          $this->ermittle_conditions();
       
      }
      
      private function lade_posten()
      {
          $posten = Rechnungseintrag::hole_nicht_gedruckte($this->kundennummer);

          $this->netto_endbetrag = 0;
          
          foreach($posten as $einzelposten)
          {
              
              $posten_aufbereitet['Eintragsnummer'] = $einzelposten['Eintragsnummer'];
              
              $posten_aufbereitet['quantity'] = $einzelposten['Anzahl'];
              $posten_aufbereitet['description'] = $einzelposten['Text'];
              
              $nettoeinzelpreis = round($einzelposten['Einzelpreis']/ 1.19, 4);
              $nettogesamtpreis = round($einzelposten['Anzahl']*$nettoeinzelpreis, 4);
              
              $posten_aufbereitet['unitprice'] = 
                number_format($nettoeinzelpreis, 4, ',', '') . ' EUR';

              $posten_aufbereitet['totalprice'] =
                number_format($nettogesamtpreis, 4, ',', '') . ' EUR';
              
              $this->posten[] = $posten_aufbereitet;
              
              $this->netto_endbetrag = $this->netto_endbetrag + $nettogesamtpreis;
          
          }
          
      }
      
      // ToDo: Eigentlich gehört sowas in eine Klasse Kunde...
      private function lade_adresse()
      {
          $abfrage = $this->db_handle->prepare("SELECT Rechnungsadressennummer FROM kunden WHERE Kundennummer = :1");
          $abfrage->execute($this->kundennummer);
          $row = $abfrage->fetch_assoc();
          $rechnungsadressennummer = $row["Rechnungsadressennummer"];
          
          $abfrage = $this->db_handle->prepare("SELECT Firmenname, Anrede, Vorname, Name, StrassePostfach, Land, PLZ, Ort, UStID FROM adressen WHERE Adressennummer = :1");
          $abfrage->execute($rechnungsadressennummer);
          $this->adresse = $abfrage->fetch_assoc();
      }
      
      // ToDo: Eigentlich gehört sowas in eine Klasse Kunde...
      private function ermittle_conditions()
      {
          $abfrage = $this->db_handle->prepare("SELECT Zahlungsart, Bankverbindungsnummer FROM kunden WHERE Kundennummer = :1");
          $abfrage->execute($this->kundennummer);
          $row = $abfrage->fetch_assoc();
          $this->zahlungsart = $row['Zahlungsart'];
          $bankverbindungsnummer = $row['Bankverbindungsnummer'];
          
          if($this->zahlungsart==Konst::$ZAHLUNGSART_LASTSCHRIFT)
          {
            $abfrage = $this->db_handle->prepare("SELECT Kontoinhaber, IBAN, BIC, Bank, Mandatsreferenz FROM bankverbindungen WHERE Bankverbindungsnummer = :1");
            $abfrage->execute($bankverbindungsnummer);
            $this->bankverbindung = $abfrage->fetch_assoc();
          }
      }
      
      
      public function erzeuge_pdf()
      {
  
            $line = 0;
            if($this->adresse['Firmenname']!=NULL)
            {
                $address[$line] = $this->adresse['Firmenname'];
                $line++;
            }
            if($this->adresse['Anrede']!=NULL || $this->adresse['Vorname'] != NULL || $this->adresse['Name']) {
                $address[$line] = $this->adresse['Anrede'] . ' ' . $this->adresse['Vorname'] . ' ' . $this->adresse['Name'];
                $line++;
            }
            $address[$line] = $this->adresse['StrassePostfach'];
            $line++;
            
            $address[$line] = '';   // Leerzeile vor PLZ
            $line++;
            
            if($this->adresse['Land']!='DE') {
                $address[$line] = $this->adresse['Land'] . '-' . $this->adresse['PLZ'] . ' ' . $this->adresse['Ort'];
            } else {
                $address[$line] = $this->adresse['PLZ'] . ' ' . $this->adresse['Ort'];
            }



            if($this->zahlungsart==Konst::$ZAHLUNGSART_RECHNUNNG) {
                $conditions = 'Bitte ueberweisen Sie den Betrag auf unser Konto.';
            }  elseif($this->zahlungsart==Konst::$ZAHLUNGSART_LASTSCHRIFT) {
                $conditions = 'Wir buchen den Betrag von Ihrem Konto mit der IBAN ' . $this->bankverbindung['IBAN'] . ' bei der ' . 
                              $this->bankverbindung['Bank'] . ' (BIC ' . $this->bankverbindung['BIC'] . ') ab. Die zugrundeliegende Mandatsreferenz ' .
                              'lautet ' . $this->bankverbindung['Mandatsreferenz'] . '.';
            }

            $netto_endbetrag = number_format(round($this->netto_endbetrag, 4), 4, ',', '') . ' EUR';
            $mwst = number_format(round($this->netto_endbetrag * 0.19, 4), 4, ',', '') . ' EUR';
            $brutto_endbetrag = number_format(round($this->netto_endbetrag * 1.19, 2), 2, ',', '') . ' EUR';
            
            createBill($address,
                       $this->posten,
                       $this->rechnungsnummer,
                       $this->datum,
                       $netto_endbetrag,
                       $mwst,
                       $brutto_endbetrag,
                       $conditions,
                       $this->adresse['UStID'],
                       'rechnungen/' . $this->rechnungsnummer . '.pdf');
      
  }
  
  public function get_rechnungsdaten_fuer_ausgabe()
  {
      $kundeninfo_parts = [];
      if (!empty($this->adresse['Firmenname'])) {
          $kundeninfo_parts[] = $this->adresse['Firmenname'];
      }
      if (!empty($this->adresse['Vorname'])) {
          $kundeninfo_parts[] = $this->adresse['Vorname'];
      }
      if (!empty($this->adresse['Name'])) {
          $kundeninfo_parts[] = $this->adresse['Name'];
      }
      $kundeninfo = implode(' ', $kundeninfo_parts);

      $brutto_endbetrag = number_format(round($this->netto_endbetrag * 1.19, 2), 2, ',', '.') . ' €';

      return [
          'datum' => $this->datum,
          'rechnungsnummer' => $this->rechnungsnummer,
          'kunde' => $kundeninfo,
          'brutto' => $brutto_endbetrag
      ];
  }
  
  public function markiere_als_gedruckt()
  {
    foreach($this->posten as $einzelposten)
        {
            $abfrage = $this->db_handle->prepare
              ("UPDATE rechnungseintraege SET Rechnungsnummer = :1 WHERE Eintragsnummer = :2");
    
            $abfrage->execute($this->rechnungsnummer, $einzelposten['Eintragsnummer']);
        }
      }
  }

?>