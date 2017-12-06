<?php
  
  class Drucklauf
  {
  
      private $startzeit;
      private $endzeit;
      private $rechnungsnummer_von;
      private $rechnungsnummer_bis;
      
      private $db_handle;
      
      public function __construct()
      {
          $this->db_handle = DB::get_db();
      } 
      
      public function set_startzeit()
      {
          $this->startzeit = time();
      }
      
      public function set_endzeit()
      {
          $this->endzeit = time();
      }

      public function set_rechnungsnummer_von_bis($rechnungsnummer_von, $rechnungsnummer_bis) {
          $this->rechnungsnummer_von = $rechnungsnummer_von;
          $this->rechnungsnummer_bis = $rechnungsnummer_bis;
      }
      
      public function speichern()
      {

          $abfrage = $this->db_handle->prepare(
            "INSERT INTO drucklaeufe ( RechnungsnummerVon, RechnungsnummerBis, ". 
                                            "Startzeit, Endzeit ) " . 
            "VALUES (:1, :2, FROM_UNIXTIME(:3), FROM_UNIXTIME(:4))");
              
          $abfrage->execute($this->rechnungsnummer_von, $this->rechnungsnummer_bis, 
                            $this->startzeit, $this->endzeit);
      
      }
      
  }
  
?>
