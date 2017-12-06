<?php
  
require_once 'inc/DB.inc';

class Rechnungseintrag
{

    private static $erste_eintragsnummer;
    private static $letzte_eintragsnummer;
    
    private $eintragsnummer;
    private $bestellungsnummer;
    private $kundennummer;
    private $anzahl;
    private $text;
    private $einzelpreis;
    private $monatjahr;
    private $rechnungsnummer;
    
    private $bereits_gespeichert;  // muss beim speichern upgedated oder inserted werden
    
    public function __construct($bestellungsnummer, $kundennummer, $anzahl, $text,
                                $einzelpreis, $monatjahr)
    {
        
        $this->db_handle =  DB::get_db(); 
        
        $this->bestellungsnummer = $bestellungsnummer;
        $this->kundennummer = $kundennummer;
        $this->anzahl = $anzahl;
        $this->text = $text;
        $this->einzelpreis = $einzelpreis;
        $this->monatjahr = $monatjahr;
       
    }
    
    public static function get_erste_eintragsnummer()
    {
        return(Rechnungseintrag::$erste_eintragsnummer);
    }
    
    public static function get_letzte_eintragsnummer()
    {
        return(Rechnungseintrag::$letzte_eintragsnummer);
    }
    
    public function speichern()
    {
        if($this->bereits_gespeichert) {
            // Update - TODO
        } else {

            // N�chste freie Nummer herausfinden
            
            $abfrage = $this->db_handle->prepare("SELECT max(Eintragsnummer) FROM rechnungseintraege");
            $abfrage->execute();
            $row = $abfrage->fetch_assoc();
            $this->eintragsnummer = $row["max(Eintragsnummer)"] + 1;
            
            // Speichern
            
            $abfrage = $this->db_handle->prepare(
              "INSERT INTO rechnungseintraege (Eintragsnummer, Bestellungsnummer, Kundennummer, Anzahl, " .
              "                    Text, Einzelpreis, Monatjahr, Rechnungsnummer) " . 
              "VALUES (:1, :2, :3, :4, :5, :6, :7, :8)");
            
            $abfrage->execute($this->eintragsnummer, $this->bestellungsnummer, $this->kundennummer,
                              $this->anzahl, $this->text, $this->einzelpreis, $this->monatjahr,
                              $this->rechnungsnummer);
                              
            // Erste und letzte vergebene Eintragsnummer updaten
            
            if(is_null(Rechnungseintrag::$erste_eintragsnummer)) {
                Rechnungseintrag::$erste_eintragsnummer = $this->eintragsnummer;
            }
            
            if(is_null(Rechnungseintrag::$letzte_eintragsnummer) || 
               $this->eintragsnummer > Rechnungseintrag::$letzte_eintragsnummer) 
            {
                Rechnungseintrag::$letzte_eintragsnummer = $this->eintragsnummer;
            }
        
        }
    }
    
    public static function existiert($bestellungsnummer, $monat, $jahr)
    {

        $db_handle = DB::get_db();
        
        $abfrage = $db_handle->prepare
            ("SELECT Eintragsnummer FROM rechnungseintraege WHERE Bestellungsnummer = :1 AND " .
             "  Monatjahr = :2");
    
        $abfrage->execute($bestellungsnummer, $jahr . str_pad($monat, 2 ,'0', STR_PAD_LEFT));
       
        if($abfrage->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
           
    }
    
    public static function hole_kunden_mit_nicht_gedruckten()
    {
        $db_handle = DB::get_db(); 
        
        $abfrage = $db_handle->prepare("SELECT Kundennummer FROM rechnungseintraege " .
                                       "WHERE Rechnungsnummer IS NULL " .
                                       "GROUP BY Kundennummer");
        $abfrage->execute();
        
        while($row = $abfrage->fetch_assoc()) {
           $kunden[] = $row['Kundennummer'];
        }
        
        return($kunden);
        
    }
    
    public static function hole_nicht_gedruckte($kundennummer)
    {
        $db_handle = DB::get_db();
        
        $abfrage = $db_handle->prepare("SELECT * FROM rechnungseintraege WHERE Kundennummer = :1 AND " .
                                       "Rechnungsnummer IS NULL");
        
        $abfrage->execute($kundennummer);

        return($abfrage->fetchall_assoc());
    }    
    
}

?>
