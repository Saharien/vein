<?php
  
  class Abrechnungslauf
  {
  
      private $laufnummer;
      private $startzeit;
      private $endzeit;
      private $monat_von;
      private $jahr_von;
      private $monat_bis;
      private $jahr_bis;
      private $eintragsnummer_von;
      private $eintragsnummer_bis;
      
      private $db_handle;
      
      public function __construct()
      {
          $this->db_handle = DB::get_db();
      } 
      
      public function set_monatjahr_von_bis($monat_von, $jahr_von, $monat_bis, $jahr_bis)
      {
          $this->monat_von = $monat_von;
          $this->jahr_von = $jahr_von;
          $this->monat_bis = $monat_bis;
          $this->jahr_bis = $jahr_bis;
      }
      
      public function set_eintragsnummer_von_bis($eintragsnummer_von, $eintragsnummer_bis) {
          $this->eintragsnummer_von = $eintragsnummer_von;
          $this->eintragsnummer_bis = $eintragsnummer_bis;
      }
      
      public function set_startzeit()
      {
          $this->startzeit = time();
      }
      
      public function set_endzeit()
      {
          $this->endzeit = time();
      }
      
      public function speichern()
      {

          $abfrage = $this->db_handle->prepare(
            "INSERT INTO abrechnungslaeufe ( startzeit, endzeit, monatjahr_von, monatjahr_bis, ". 
                                            "eintragsnummer_von, eintragsnummer_bis) " . 
            "VALUES (FROM_UNIXTIME(:1), FROM_UNIXTIME(:2), :3, :4, :5, :6)");
              
          $abfrage->execute($this->startzeit, $this->endzeit, $this->jahr_von . $this->monat_von, 
                            $this->jahr_bis . $this->monat_bis, $this->eintragsnummer_von,
                            $this->eintragsnummer_bis);
      
      }
      
  }
  
?>
