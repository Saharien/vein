<?php

require('inc/fpdf/fpdf.php');

class PDF extends FPDF
{

  function Header()
  {
    //Logo
    $this->Image('logo.png',120,20,70);
  }

  function Footer()
  {
    $this->SetY(-25);
    $this->SetFont('Arial','',11);
    $this->Cell(0,6,'Bankverbindung:      IBAN DE68 7409 0000 0106 6067 68      BIC GENODEF1PA1      VR-Bank Passau',0,1,'C');
    $this->Cell(0,6,'USt.-Id.-Nr. DE201906512      StNr. 153/234/70380',0,1,'C');
    $this->SetFont('Arial','B',11);
    $this->Cell(0,6,'   Seite '.$this->PageNo().' von {nb}',0,0,'C');
  }

  function headerFirstPage($address, $invoice_no, $date, $ustid)
  {

    // Print reciever adress

    $this->SetXY(20, 50);
    $this->SetFont('Arial', '', 7);
    $this->Cell(78, 4, 'M. Karl Internetdienstleistungen - Am Schlosspark 7 - 94127 Neuburg', 'B', 1, 'L');
    $this->SetFont('Arial','',11);
    $this->Ln();
    $i=0;
    foreach($address as $row) {
      $i++;
      $this->SetX(20);
      $this->Cell(78, 5, utf8_decode($row), 0, 1, 'L');
    }
    $this->SetX(20);
    $this->Cell(78,4,'','B',1,'L');

    // Print sender adress

    $this->SetTextColor(110, 110, 110);
    $this->SetFont('Arial', '', 9);
    $this->SetXY(131, 50);
    $this->Cell(60, 4, 'Am Schlosspark 7', 0, 1, 'R');
    $this->SetX(131);
    $this->Cell(60, 4, '94127 Neuburg', 0, 1, 'R');
    $this->Ln();
    $this->SetX(131);
    $this->Cell(60, 4, 'Telefon: 08507 - 9238236', 0, 1, 'R');
    $this->SetX(131);
    $this->Cell(60, 4, 'Telefax: 08507 - 9238237', 0, 1, 'R');
    $this->Ln();
    $this->SetX(131);
    $this->Cell(60, 4, 'info@mkarl.de', 0, 1, 'R');
    $this->SetX(131);
    $this->Cell(60, 4, 'http://www.mkarl.de', 0, 1, 'R');

    // Print "Invoice", invoice number and date

    $this->SetTextColor(0, 0, 0);
    $this->SetFont('Arial', 'B', 11);
    $this->SetXY(20, 97);
    $this->Cell(60, 4, 'RECHNUNG', 0, 1, 'L');
    $this->SetFont('Arial', '', 11);
    $this->SetXY(125, 97);
    $this->Cell(60, 4, 'Rechnungsnummer', 0, 1, 'L');
    $this->SetX(125);
    $this->Cell(60, 4, 'Datum', 0, 1, 'L');
    if(strlen($ustid)>0) {
      $this->SetX(125);
      $this->Cell(60, 4, 'USt.-ID', 0, 1, 'L');
    }
    $this->SetXY(131, 97);
    $this->Cell(60, 4, $invoice_no, 0, 1, 'R');
    $this->SetX(131);
    $this->Cell(60, 4, $date, 0, 1, 'R');
    $this->SetX(131);
    $this->Cell(60, 4, $ustid, 0, 1, 'R');

  }

  function headerFurtherPages($invoice_no, $date)
  {

    // Print "Invoice", invoice number and date

    $this->SetFont('Arial', '', 11);
    $this->SetXY(20, 28);
    $this->Cell(60, 4, 'Rechnungsnummer', 0, 1, 'L');
    $this->SetX(20);
    $this->Cell(60, 4, 'Datum', 0, 1, 'L');
    $this->SetXY(26, 28);
    $this->Cell(60, 4, $invoice_no, 0, 1, 'R');
    $this->SetX(26);
    $this->Cell(60, 4, $date, 0, 1, 'R');

  }

  function writeColumnNames($y)
  {
    $this->Line(20, $y, 190, $y);

    $this->SetFont('Arial', 'B', 11);
    $this->SetXY(20, $y+1);
    $this->Cell(60, 4, 'Menge', 0, 1, 'L');

    $this->SetXY(135, $y+1);
    $this->Cell(60, 4, 'Einzelpreis', 0, 1, 'L');

    $this->SetXY(165, $y+1);
    $this->Cell(60, 4, 'Gesamtpreis', 0, 1, 'L');

    $this->SetXY(38, $y+1);
    $this->Cell(60, 4, 'Bezeichnung', 0, 1, 'L');

    $this->Line(20, $y+6, 190, $y+6);
  }

  function writeRow($y, $entry)
  {

    $this->SetFont('Arial', '', 11);
    $this->SetXY(20, $y);
    $this->Cell(60, 4, $entry['quantity'], 0, 1, 'L');

    $this->SetXY(135, $y);
    $this->Cell(22, 4, $entry['unitprice'], 0, 1, 'R');

    $this->SetXY(165, $y);
    $this->Cell(25, 4, $entry['totalprice'], 0, 1, 'R');

    $this->SetXY(38, $y);
    $this->MultiCell(95, 4, utf8_decode($entry['description']), 0, 'L', false);

  }

  function writeEntries($entries, $y, $start)
  {

    $this->writeColumnNames($y);

    $y = $y + 9;
    $continue = true;
    for($i = $start; $continue == true; $i++) {
      $this->writeRow($y, $entries[$i]);
      $y = $this->GetY() + 2;
      if( $y > 230 || $i + 2 > count($entries) ) {  // +2 weil: es geht um nächsten Lauf und start war bei 0
        $continue = false;
      }
    }

    $this->Line(20, $y, 190, $y);

    if ( $i + 2 > count($entries) ) {   // alle rechnungseintraege ausgegeben
      return(0);
    } else {                            // naechster auszugebender rechnungseintrag
      // wurde am Anfang von for-Schleife noch erhöht, muss hier nicht mehr erhoeht werden
      return($i);
    }

  }

  function writeSum($y, $net, $tax, $gross, $conditions)
  {

    $this->SetFont('Arial', '', 11);

    $this->SetXY(90, $y + 2);
    $this->Cell(60, 4, 'Netto-Rechnungsendbetrag:', 0, 1, 'R');
    $this->SetXY(165, $y + 2);
    $this->Cell(25, 4, $net, 0, 1, 'R');

    $this->SetXY(90, $y + 8);
    
    $this->Cell(60, 4, 'zzgl. 19% Mehrwertsteuer:', 0, 1, 'R');
    $this->SetXY(165, $y + 8);
    $this->Cell(25, 4, $tax, 0, 1, 'R');

    $this->SetFont('Arial', 'B', 11);
    $this->SetXY(90, $y + 14);
    $this->Cell(60, 4, 'Rechnungsendbetrag:', 0, 1, 'R');
    $this->SetXY(165, $y + 14);
    $this->Cell(25, 4, $gross, 0, 1, 'R');

    $this->Line(20, $y + 20, 190, $y + 20);

    $this->SetFont('Arial', '', 11);
    $this->SetXY(20, $y + 25);
    $this->MultiCell(170, 5, utf8_decode($conditions), 0, 'L', false);

  }

}

function createBill($address, $entries, $bill_no, $date, $net, $tax, $gross, $conditions, $ustid, $file)
{

  //Instanciation of inherited class
  $pdf=new PDF();
  $pdf->AliasNbPages();
  $pdf->AddPage();

  $pdf->headerFirstPage($address, $bill_no, $date, $ustid);

  $next_entry = $pdf->writeEntries($entries, 120, 0);

  $y = $pdf->GetY() + 2;

  while( $next_entry > 0 ) {
    $pdf->AddPage();
    $pdf->headerFurtherPages($bill_no, $date);
    $next_entry = $pdf->writeEntries($entries, 50, $next_entry);
    $y = $pdf->GetY() + 2;
  }

  $pdf->writeSum($y,  $net, $tax, $gross, $conditions);

  $pdf->Output($file, 'F');

}

?>