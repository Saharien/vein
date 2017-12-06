<?php
  
  class MonatJahr
  {
      private $monat;
      private $jahr;
      
      public function __construct($monat, $jahr)
      {
          $this->monat = $monat;
          $this->jahr = $jahr;
      }
      
      public function get_monat()
      {
          return($this->monat);
      }
      
      public function get_jahr()
      {
          return($this->jahr);
      }      
      
      public function set($monat, $jahr)
      {
          $this->monat = $monat;
          $this->jahr = $jahr;
      }
      
      public function plus_monat()
      {
          if($this->monat == 12) {
              $this->jahr++;
              $this->monat = 1;
          } else {
            $this->monat++;
          }
      }
      
      public function plus_jahr()
      {
          $this->jahr++;
      }
      
      public function plus($monat, $jahr)
      {
          if($monat) { $this->plus_monat(); }
          if($jahr) { $this->plus_jahr(); }
      }

      public function minus_monat()
      {
          if($this->monat == 1) {
              $this->jahr--;
              $this->monat = 12;
          } else {
            $this->monat--;
          }
      }
      
      public function erzeuge_vergleichswert()
      {
          return($this->jahr * 100 + $this->monat);
      }

      public function ist_groesser($monatjahr)
      {
        return($this->erzeuge_vergleichswert() > $monatjahr->erzeuge_vergleichswert());
      }

      public function ist_kleiner($monatjahr)
      {
        return($this->erzeuge_vergleichswert() < $monatjahr->erzeuge_vergleichswert());
      }
      
      public function ist_kleiner_gleich($monatjahr)
      {
        return($this->erzeuge_vergleichswert() <= $monatjahr->erzeuge_vergleichswert());
      }

      public function ist_groesser_gleich($monatjahr)
      {
        return($this->erzeuge_vergleichswert() >= $monatjahr->erzeuge_vergleichswert());
      }
      
  }
  
?>
