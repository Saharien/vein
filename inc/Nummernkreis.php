<?php
  
  require_once 'inc/DB.inc';
  
  class Nummernkreis
  {
      
      public static function get_naechste_nummer($nummernkreisname)
      {
          $db_handle = DB::get_db();   
          $abfrage = $db_handle->prepare("SELECT NaechsteNummer FROM nummernkreise WHERE Name = :1");
          $abfrage->execute($nummernkreisname);
          $row = $abfrage->fetch_assoc();
          $naechste_nummer = $row["NaechsteNummer"];
          $abfrage = $db_handle->prepare("UPDATE nummernkreise SET NaechsteNummer = :1 WHERE Name = :2");
          $abfrage->execute($naechste_nummer + 1, $nummernkreisname);
          return($naechste_nummer);
      }
      
  }
  
?>
