<?php

  require_once 'inc/Bestellung.php';
  require_once 'inc/Angebot.php';
  require_once 'inc/Rechnungseintrag.php';
  require_once 'inc/Tools.php';
  require_once 'inc/Konfiguration.php';
  require_once 'inc/DB.inc';
  require_once 'inc/Abrechnungslauf.php';
  require_once 'inc/Drucklauf.php';
  require_once 'inc/Vein.php';
  require_once 'inc/Rechnung.php';
  require_once 'inc/Nummernkreis.php';
  require_once 'inc/bill_pdf_output.php';
  
  $vein = new Vein();  
  $vein->starte_druck('13.09.2025', false);  // Rechnungsdatum, Testlauf
  echo 'fertig';

?>
