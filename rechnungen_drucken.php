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

  echo '<html><head><title>Rechnungsdruck</title></head><body>';
  echo '<br /><table border="1" cellpadding="5" cellspacing="0">';
  echo '<thead><tr><th>Rechnungsdatum</th><th>Rechnungsnummer</th><th>Name</th><th>Rechnungsendbetrag</th></tr></thead>';
  echo '<tbody>';

  $vein = new Vein();  
  $vein->starte_druck('13.09.2025', false);  // Rechnungsdatum, Testlauf

  echo '</tbody></table>';
  echo '<br />fertig';
  echo '</body></html>';

?>
