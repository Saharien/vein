<?php

require_once 'inc/DB.inc';  
  
class Angebot
{
  
  private $db_handle;
  private $alle_angebote;
  
  private $angebot;
  private $angebotstyp;
  private $jahresgebuehr;
  private $einmalige_gebuehr;
  private $text_regelmaessig;
  private $text_einmalig;
  private $beschreibung;
      
  public function __construct()
  {
      $this->db_handle = DB::get_db();
      
      // Wir holen einfach mal alle Angebote, da beim Abarbeiten der Bestellungen jedes
      // Angebot X-Mail benötigt wird.

      $abfrage = $this->db_handle->prepare
        ("SELECT Angebot, Angebotstyp, Jahresgebuehr, Einmalige_Gebuehr, Text_regelmaessig, " .
         "Text_einmalig, Beschreibung FROM angebote");
      
      $abfrage->execute();
            
      while($row = $abfrage->fetch_assoc()) {
        $this->alle_angebote[$row['Angebot']] = $row;
      }
  }
  
  public function get_text_einmalig()
  {
      return($this->text_einmalig);
  }
  
  public function get_text_regelmaessig()
  {
      return($this->text_regelmaessig);
  }
  
  public function get_jahresgebuehr()
  {
      return($this->jahresgebuehr);
  }

  public function get_einmalige_gebuehr()
  {
      return($this->einmalige_gebuehr);
  }
  
  public function ermittle_regelmaessige_gebuehr($zahlungsrhytmus)
  {
      if($zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_JAEHRLICH) {
          return ($this->jahresgebuehr);
      } elseif($zahlungsrhytmus == Konst::$ZAHLUNGSRHYTMUS_MONATLICH) {
          return ($this->jahresgebuehr / 12);
      }
      
  }
  
  public function lade($angebot)
  {
      $this->angebot           = $this->alle_angebote[$angebot]['Angebot'];
      $this->angebotstyp       = $this->alle_angebote[$angebot]['Angebotstyp'];
      $this->jahresgebuehr     = $this->alle_angebote[$angebot]['Jahresgebuehr'];
      $this->einmalige_gebuehr = $this->alle_angebote[$angebot]['Einmalige_Gebuehr'];
      $this->text_regelmaessig = $this->alle_angebote[$angebot]['Text_regelmaessig'];
      $this->text_einmalig     = $this->alle_angebote[$angebot]['Text_einmalig'];
      $this->beschreibung      = $this->alle_angebote[$angebot]['Beschreibung'];
  }
  
  public function ist_einmalig()
  {
      if($this->einmalige_gebuehr>0) {
          return true;
      } else {
          return false;
      }
  }
  
  public function ist_regelmaessig()
  {
      if($this->jahresgebuehr>0) {
          return true;
      } else {
          return false;
      }
  }
    
}  
  
?>
