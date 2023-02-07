<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class InvoiceGLK_Print_View extends Vtiger_Print_View {
	
	/**
	 * Temporary Filename
	 *
	 * @var string
	 */
	private $_tempFileName;
	function __construct()
	{
		parent::__construct();
		ob_start();
	}

	function checkPermission (Vtiger_Request $request)	{
		return true;
	}

	function preProcessTplName(Vtiger_Request $request) {


		if($request->get('print_type') == 'word')
		{
		$this->word_print($request);
		exit;
		}
	}


	public function template ($strFilename) {
		$path = dirname($strFilename);

		// $this->_tempFileName = $path.time().'.docx';
		// $this->_tempFileName = $path.'/'.time().'.txt';

		$this->_tempFileName = $strFilename;

		// copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File

		$this->_documentXML = file_get_contents($this->_tempFileName);
	}

	/**
	 * Set a Template value
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 */
	public function setValue ($search, $replace)	{
		if (substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
			$search = '${' . $search . '}';
		}

		// $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");

		if (!is_array($replace)) {

			// $replace = utf8_encode($replace);

			$replace = iconv('utf-8', 'utf-8', $replace);
		}

		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);
	}

	/**
	 * Save Template
	 *
	 * @param string $strFilename
	 */
	public function save ($strFilename) {
		if (file_exists($strFilename)) {
			unlink($strFilename);
		}

		// $this->_objZip->extractTo('fleet.txt', $this->_documentXML);

		file_put_contents($this->_tempFileName, $this->_documentXML);

		// Close zip file

		/* if($this->_objZip->close() === false) {
			throw new Exception('Could not close zip file.');
		}*/
		rename($this->_tempFileName, $strFilename);
	}

	public function loadTemplate ($strFilename) {
		if (file_exists($strFilename)) {
			$template = $this->template($strFilename);
			return $template;
		} else {
			trigger_error('Template file ' . $strFilename . ' not found.', E_ERROR);
		}
	}


	
	public function word_print(Vtiger_Request $request){

/* 
		ini_set('display_errors', 1);
		error_reporting(E_ALL); */
		//set data in template file using PHPWord library

		require_once 'libraries/PHPWord/PHPWord.php';		

		$PHPWord = new PHPWord();
		$section = $PHPWord->createSection();

		$PHPWord->setDefaultFontName('Arial');
		$PHPWord->setDefaultFontSize(9);

		// $section->addText("Привет мир Hello world", ['bold' => true]);

		// template variables
		$job = '${job}';
		$agreement = '${agreement}';
		$contactName = 'Ruslan G';
		$contactType = '${contactType}';
		$appType = '${appType}';

		$textCenter = array('align' => 'center');
		$boldText = array('bold' => true);

		// title
		$section->addText("Приложение $job", $boldText, $textCenter);
		$section->addText("к Договору на транспортно-экспедиторское обслуживание", $boldText, $textCenter);
		$section->addText($agreement, $boldText, $textCenter);
		$section->addTextBreak();
		$section->addTextBreak();
		
		$textRun = $section->createTextRun();
		
		$textRun->addText("г. Алматы", $boldText);
		$textRun->addText('');
		$textRun->addText("«»              201  г.		", $boldText);
		
		$section->addTextBreak();
		
		$textRun1 = $section->createTextRun();

		$textRun1->addText($contactName, $boldText);
		$textRun1->addText(" именуемое в дальнейшем ");
		$textRun1->addText("«Клиент», ", $boldText);
		$textRun1->addText("в лице $contactType, действующего на основании $appType, с одной стороны, и ");
		$textRun1->addText("ТОО «Глобалинк Транспортэйшн энд Лоджистикс Ворлдвайд»,", $boldText);
		$textRun1->addText("именуемое в дальнейшем ");
		$textRun1->addText("«Экспедитор», ", $boldText);
		$textRun1->addText("в лице Директора Балаева Р.О., действующего на основании Устава, с другой стороны, совместно именуемые ");
		$textRun1->addText("«Стороны», ", $boldText);
		$textRun1->addText("заключили настоящее приложение о нижеследующем:");
		$section->addTextBreak();
		
		$section->addText("1. Экспедитор принимает на себя обязательство организовать и произвести  следующие услуги:");
		$section->addText("Транспортировка груза, страхование", $boldText);
		$section->addText("а Клиент обязуется оплатить выполненные Экспедитором услуги. ");
		$section->addTextBreak();


		// Common table styles
		$tableStyle = array();
		$firstRowStyle = array('borderBottomSize' => 18, 'borderBottomColor' => '000000', 'bgColor' => '000000');

		$PHPWord->addTableStyle('tableStyle', $styleTable, $styleFirstRow);

		// product table
		$productTable = $section->addTable('tableStyle');
		
		$styleCell = array('valign' => 'center', 'borderSize' => 6, 'borderColor' => '000000');
		$styleCellBTLR = array('valign' => 'center', 'textDirection' => PHPWord_Style_Cell::TEXT_DIR_BTLR);
		
		$productTable->addRow(900);
		
		$productTable->addCell(0, $styleCell)->addText('Номер работы', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('Наименование товара', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('Кол-во мест', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('Оплачиваемый вес, кг', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('Прочие условия', [], $textCenter);

		$productTable->addRow(900);
		
		$productTable->addCell(0, $styleCell)->addText($job, $boldText, $textCenter);
		$productTable->addCell(0, $styleCell)->addText('${goods}', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('${counts}', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('${weight}', [], $textCenter);
		$productTable->addCell(0, $styleCell)->addText('Нет', [], $textCenter);
	
		$section->addTextBreak();


		// checkout table

		$checkoutTable = $section->addTable('tableStyle');

		$checkoutTable->addRow(600);
		
		$checkoutTable->addCell(0, $styleCell)->addText('', [], $textCenter);
		$checkoutTable->addCell(0, $styleCell)->addText('Стоимость услуг, тенге', [], $textCenter);
		
		$checkoutTable->addRow(600);
		$checkoutTable->addCell(0, $styleCell)->addText('Транспортировка груза от двери в г ${fromTo}', $boldText);
		$checkoutTable->addCell(0, $styleCell)->addText('price1');
		
		for($i = 1; $i <= 5; $i++) {
			$checkoutTable->addRow(600);
			$checkoutTable->addCell(0, $styleCell)->addText('Страхование', $boldText);
			$checkoutTable->addCell(0, $styleCell)->addText('price1');
		}

		
		// ======				
		$section->addTextBreak();

		$textRun2 = $section->createTextRun();

		$textRun2->addText("2. Стоимость услуг, осуществляемых в рамках Договора ");
		$textRun2->addText($agreement, $boldText);
		$textRun2->addText(" тенге ______ 00 тиын ", $boldText);
		$textRun2->addText("(включая страхование). Клиент производит предоплату в размере 100 % от общей стоимости услуг  по настоящему Приложению, что составляет сумму в размере: ");
		$textRun2->addText("тенге ______ 00 тиын ", $boldText);
		$textRun2->addText("путем перечисления денежных средств на расчетный счет Экспедитора в течение 3 (трех) банковских дней с даты подписания соответствующего Приложения и получения счета на предоплату.");
		
		$section->addTextBreak();
		
		$section->addText("Для взаиморасчетов учитывается курс Национального Банка/KASE/Народного Банка на дату:", ['bold' => true, 'italic' => true]);
		$section->addListItem('Выставления счета на оплату, при условии оплаты на предоплатной основе;', 0, ['bold' => true, 'italic' => true]);
		$section->addListItem('Совершения оборота по реализации (дата акта выполненных работ , при условии оплаты по факту оказания услуг.', 0, ['bold' => true, 'italic' => true]);
		
		$section->addTextBreak();
		$section->addText("3. Грузополучателем груза по настоящему Приложению №__ А является:");
		$section->addText("___", $boldText);
		$section->addTextBreak();
		$section->addText("4. Прочие условия и пункты, не оговоренные в настоящем Приложении № __ А, действуют в соответствии с Договором № _________________________");
		$section->addTextBreak();
		$section->addText("5.  Данное Приложение №__ А является неотъемлемой частью Договора   № ____________________________");
		$section->addTextBreak();
		$section->addText("Данное Приложение № __А к  Договору  № _________________  года вступает в силу с момента его подписания сторонами. Срок действия данного Приложения истекает вместе со сроком действия Договора    № ____________________________________");
		$section->addTextBreak();
		$section->addText("7. Услуги Экспедитора регламентируются генеральными условиями, которые могут ограничить ответственность Экспедитора в случае утраты или порчи Груза. Ознакомиться с генеральными условиями можно на веб-сайте: http://globalinklogistics.com/Trading-Terms-and-Conditions. В случае частичной или полной утраты или повреждения Груза, произошедшей в процессе транспортировки, Экспедитор содействует в возмещении Клиенту стоимости нанесенного материального ущерба страховой компанией. В случае отказа страховой компании от выплаты возмещения Клиенту, а также если страховой случай произошел по доказанной вине Экспедитора, Экспедитор возмещает утрату или повреждение груза в соответствии с  применимыми международными Конвенциями и Соглашениями в сфере транспорта, включая, но не ограничиваясь КДПГ, СМГС, Варшавская Конвенция 1929 г., Монтреальская Конвенция, и.т.д. Экспедитор освобождается от любой ответственности в случае, если Клиенту было отказано в возмещении по правилам/договору страхования. Ни при каких обстоятельствах, Экспедитор не несет ответственность за косвенные убытки, задержки, потерю прибыли, потерю рынка и ликвидные убытки.");
		$section->addTextBreak();
		$section->addText("8. В случае, девальвации тенге к доллару США / Евро более чем на 5% в период между датой выдачи счета-фактуры до даты получения платежа в банковский счет экспедитора, экспедитор имеет право произвести перерасчёт суммы счет-фактуры с применением коэффициента индексации девальвации.");
		$section->addTextBreak();
		$section->addText("Примечание* Дата электронной счет-фактуры не является основанием для пересчета суммы по курсу.", ['underline' => 'signle']);
		$section->addTextBreak();
		$section->addTextBreak();
		$section->addText("9. Юридические адреса и банковские реквизиты сторон:", $boldText);


		// requisites table
		$requisitesTable = $section->addTable('tableStyle');

		$requisitesTable->addRow(600);
		
		$clientColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$clientColumnCellTextRun = $clientColumnCell->createTextRun();
		$clientColumnCellTextRun->addText("Клиент: ", $boldText);
		$clientColumnCellTextRun->addText("Клиент: ", $boldText);
		$clientColumnCellTextRun->addText("Клиент: ", $boldText);
		$clientColumnCellTextRun->addText("Клиент: ", $boldText);
		
		$forwarderColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$forwarderColumnCellTextRun = $forwarderColumnCell->createTextRun();
		$forwarderColumnCellTextRun->addText("Клиент: ", $boldText);
		$forwarderColumnCellTextRun->addText("Клиент: ", $boldText);
		$forwarderColumnCellTextRun->addText("Клиент: ", $boldText);
		$forwarderColumnCellTextRun->addText("Клиент: ", $boldText);

		// $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000'])->addText("Стоимость услуг, тенге");
		// $requisitesTable->addRow(600);
		
		// $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000'])->addText("Клиент: ", $boldText);
		// $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000'])->addText("Стоимость услуг, тенге");


		// $productTable->addRow();
		// $productTable->addCell(1800)->addText($job);
		// $productTable->addCell(1800)->addText('${goods}');
		// $productTable->addCell(1800)->addText('${counts}');
		// $productTable->addCell(1800)->addText('${weight}');
		// $productTable->addCell(1800)->addText('нет');

		// // Define table style arrays
		// $styleTable = array('borderSize'=>6, 'borderColor'=>'006699', 'cellMargin'=>80);
		// $styleFirstRow = array('borderBottomSize'=>18, 'borderBottomColor'=>'0000FF', 'bgColor'=>'66BBFF');
		
		// // Define cell style arrays
		// $styleCell = array('valign'=>'center');
		// $styleCellBTLR = array('valign'=>'center', 'textDirection'=>PHPWord_Style_Cell::TEXT_DIR_BTLR);
		
		// // Define font style for first row
		// $fontStyle = array('bold'=>true, 'align'=>'center');
		
		// // Add table style
		// $PHPWord->addTableStyle('myOwnTableStyle', $styleTable, $styleFirstRow);
		
		// // Add table
		// $table = $section->addTable('myOwnTableStyle');
		
		// // Add row
		// $table->addRow(900);
		
		// // Add cells
		// $table->addCell(2000, $styleCell)->addText('Привет Мир', $fontStyle);
		// $table->addCell(2000, $styleCell)->addText('Row 2', $fontStyle);
		// $table->addCell(2000, $styleCell)->addText('Row 3', $fontStyle);
		// $table->addCell(2000, $styleCell)->addText('Row 4', $fontStyle);
		// $table->addCell(500, $styleCellBTLR)->addText('Row 1445656', $fontStyle);
		
		// // Add more rows / cells
		// for($i = 1; $i <= 5; $i++) {
		// 	$table->addRow();
		// 	$table->addCell(2000)->addText("Cell $i");
		// 	$table->addCell(2000)->addText("Cell $i");
		// 	$table->addCell(2000)->addText("Cell $i");
		// 	$table->addCell(2000)->addText("Cell Привет $i");
			
		// 	$text = ($i % 2 == 0) ? 'X' : '';
		// 	$table->addCell(500)->addText($text);
		// }		


		$filename = "2.docx";
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
		$objWriter->save("php://output");
	

	
	 }

	
}

// word
/* https://docs.google.com/document/d/16O0PzeLAtgohwCRqVlX5VlmUqc99PGDXTi39_C0xews/edit */

/* http://localhost/crm/index.php?module=MyFirstModule&view=Print&print_type=word&record=106&app=TOOLS */
/* http://localhost/crm/index.php?module=MyFirstModule&view=Print&print_type=word&record=106&app=TOOLS */