<?php
  
  class Vein
  {
  
      public function starte_abrechnung($monat_von, $jahr_von, $monat_bis, $jahr_bis)
      {
          
          $abrechnungslauf = new Abrechnungslauf();
          
          $abrechnungslauf->set_startzeit();
          $abrechnungslauf->set_monatjahr_von_bis($monat_von, $jahr_von, $monat_bis, $jahr_bis);
          
          $start = new MonatJahr($monat_von, $jahr_von);
          $ende = new MonatJahr($monat_bis, $jahr_bis);

          $db_handle = DB::get_db();

          $bestellung = new Bestellung();

          $bestellung->initialisiere();
          
          while($bestellung->lade_naechste()) {
            $bestellung->erzeuge_posten($start, $ende); 
          }
          
          if(is_null(Rechnungseintrag::get_erste_eintragsnummer()))
          {
              $status['rechnungseintraege_erzeugt'] = false;
          } else {
              $status['rechnungseintraege_erzeugt'] = true;
              $status['erste_eintragsnummer'] = Rechnungseintrag::get_erste_eintragsnummer();
              $status['letzte_eintragsnummer'] = Rechnungseintrag::get_letzte_eintragsnummer();

              $abrechnungslauf->set_eintragsnummer_von_bis(Rechnungseintrag::get_erste_eintragsnummer(),
                                                           Rechnungseintrag::get_letzte_eintragsnummer());
              $abrechnungslauf->set_endzeit();
              $abrechnungslauf->speichern();
          
          }
      }
      
      
      public function starte_druck($datum, $testlauf) 
      {
          
          $drucklauf = new Drucklauf();
          $drucklauf->set_startzeit();
          
          $kundennummern = Rechnungseintrag::hole_kunden_mit_nicht_gedruckten();

          foreach($kundennummern as $kundennummer)
          {
              $rechnungsnummer = Nummernkreis::get_naechste_nummer('Rechnungsnummer');
              if(!isset($erste_rechnungsnummer)) {
                  $erste_rechnungsnummer = $rechnungsnummer;
              }
              $rechnung = new Rechnung($kundennummer, $rechnungsnummer , $datum);
              $rechnung->erzeuge_pdf($pfad);
              if($testlauf == false) {
                $rechnung->markiere_als_gedruckt();
              }
          }
         
          $drucklauf->set_rechnungsnummer_von_bis($erste_rechnungsnummer, $rechnungsnummer);
          $drucklauf->set_endzeit();
          $drucklauf->speichern();
         
      }      
      
  }
  
?>
