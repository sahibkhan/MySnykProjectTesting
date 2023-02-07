<?php
require_once 'libraries/tcpdf/tcpdf.php';
 $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
 // set document information
 $pdf->SetCreator(PDF_CREATOR);
 $pdf->SetAuthor('KHANSA TRADER');
 $pdf->SetTitle('Surat perjanjian');
 // set default header data
 $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
 // set header and footer fonts
 $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
 $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
 // set default monospaced font
 $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
 // set margins
 $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
 $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
 $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
 // set auto page breaks
 $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
 // set image scale factor
 $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
 // set some language-dependent strings (optional)

 // ---------------------------------------------------------
 // set font
 $pdf->SetFont('helvetica', '', 10);
 // add a page
 $pdf->AddPage();
 $html = 'test data';
 // Print text using writeHTMLCell()
 $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, 'J', true);
 $pdf->lastPage();
 $pdf->Output('KHANSA-TRADER - Surat perjanjian.pdf', 'I');
?>