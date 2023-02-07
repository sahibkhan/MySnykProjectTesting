
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Jobexpencereport_Edit_Js",{},{



	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery("[name='assigned_user_id']").prop('disabled', true);

		//For Expense Block
		if ($("#Jobexpencereport_editView_fieldName_cf_1337").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1337").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1341").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1341").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1343").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1343").val('0.00');
		}
		//Exchange rate
		$('#Jobexpencereport_editView_fieldName_cf_1222').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1222").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1222").val();
		}

		$('#Jobexpencereport_editView_fieldName_cf_1347').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1347").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1347").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1349').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1349").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1349").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1351').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1351").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1351").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1353').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1353").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1353").val('0.00');
		}

		var e_vatincluded = $('select[name="cf_3293"]').val();

		if(e_vatincluded=='Yes')
		{
			//$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', true);
			//$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', false);

			$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', false);
			$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', true);
			//$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', false);
		}
		else if(e_vatincluded=='VAT Amount')
		{
			$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', false);
			$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false);
		}
		else{
			$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false);
			$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', true);
			$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', false);

			//$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false);
			//$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);
		}


		//For Selling
		if ($("#Jobexpencereport_editView_fieldName_cf_1357").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1357").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1230").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1230").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1232").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1232").val('0.00');
		}



		$('#Jobexpencereport_editView_fieldName_cf_1236').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1236").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1236").val();
		}

		$('#Jobexpencereport_editView_fieldName_cf_1238').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1238").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1238").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1240').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1240").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1240").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1242').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1242").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1242").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1244').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1244").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1244").val('0.00');
		}

		$('#Jobexpencereport_editView_fieldName_cf_1246').prop('readonly', true);
		if ($("#Jobexpencereport_editView_fieldName_cf_1246").val() == ''){
			$("#Jobexpencereport_editView_fieldName_cf_1246").val('0.00');
		}

		var vatincluded = $('select[name="cf_2695"]').val();
		if(vatincluded=='Yes')
		{
			$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', true);// Selling Customer Currency Net
			$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', false);// Sell Customer Currency Gross
			$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', true); // VAT
			//$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', true);  // VAT Rate
			$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', false);  // VAT Rate

		}
		else if(vatincluded =='VAT Amount')
		{
			$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', true);  // VAT Rate
			$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', false); // VAT
			$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', true);  // Sell Customer Currency Gross
			$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', false); // Selling Customer Currency Net
		}
		else{
			$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', false); // VAT Rate
			$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', false); // Selling Customer Currency Net
			$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', true);  // Sell Customer Currency Gross
			$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', true);  // VAT
		}



	 },

	 getExchangeRate : function(container){
		var self = this;

		//onchange on Buy (Vendor currency Net)
		$( 'input[name="cf_1337"]' ).on('blur', function() {

			var b_invoice_date = $('input[name="cf_1216"]').val();
			var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

			if(b_buy_vendor_currency_net && b_invoice_date)
			{
				var b_vendor_currency = $('select[name="cf_1345"]').val();
				var b_vat_rate = $('input[name="cf_1339"]').val();
				var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
				var job_id = $('input[name="sourceRecord"]').val();
				var record_id = $('input[name="record"]').val();
				var sub_file_title = $('#cf_2191').val();
				//var hidden_file_title = $('select[name="cf_2191"]').val();
				if ($('select[name="cf_2191"]').length > 0) {
					var sub_file_title = $('select[name="cf_2191"]').val();
				}
				var vat_included = $('select[name="cf_3293"]').val();
				var b_vat_amount = $('input[name="cf_1341"]').val();


				$.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat_rate : b_vat_rate, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
					var result=JSON.parse(data);

					$('[name="cf_1341"]').val( result['b_vat'] );
					$('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
					$('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
					$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

					$('[name="cf_1222"]').val( result['b_exchange_rate'] );
					$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
					$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
				});
			}
			})

			//blur on invoice date
			$( 'input[name="cf_1216"]' ).on('blur', function() {
				var b_invoice_date = $('input[name="cf_1216"]').val();
				var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

				if(b_buy_vendor_currency_net && b_invoice_date)
				{
					var b_vendor_currency = $('select[name="cf_1345"]').val();
					var b_vat_rate = $('input[name="cf_1339"]').val();
					var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
					var job_id = $('input[name="sourceRecord"]').val();
					var record_id = $('input[name="record"]').val();
					var sub_file_title = $('#cf_2191').val();
					if ($('select[name="cf_2191"]').length > 0) {
						var sub_file_title = $('select[name="cf_2191"]').val();
					}
					var vat_included = $('select[name="cf_3293"]').val();
					var b_vat_amount = $('input[name="cf_1341"]').val();

					$.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat_rate : b_vat_rate, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
						var result=JSON.parse(data);

						$('[name="cf_1341"]').val( result['b_vat'] );
						$('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
						$('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
						$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

						$('[name="cf_1222"]').val( result['b_exchange_rate'] );
						$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
						$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
					});
				}
			})

			$( 'input[name="cf_1339"]' ).on('blur', function() {


				var e_vat_included = $('select[name="cf_3293"]').val();

				if(e_vat_included=='Yes')
				{
					var b_invoice_date = $('input[name="cf_1216"]').val();
					var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();

					if(b_vendor_customer_currency_gross && b_invoice_date)
					{
						 var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();
						 var b_vendor_currency = $('select[name="cf_1345"]').val();
						 var b_vat_rate = $('input[name="cf_1339"]').val();
						 var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
						 var job_id = $('input[name="sourceRecord"]').val();
						 var record_id = $('input[name="record"]').val();
						 var sub_file_title = $('#cf_2191').val();
						 //var hidden_file_title = $('select[name="cf_2191"]').val();
						 if ($('select[name="cf_2191"]').length > 0) {
							var sub_file_title = $('select[name="cf_2191"]').val();
						 }


						 $.post('include/Exchangerate/buy_vendor_currency_gross.php',{ sub_file_title: sub_file_title,
																					   record_id: record_id,
																					   job_id: job_id,
																					   b_buy_vendor_currency_gross: b_vendor_customer_currency_gross,
																					   b_vendor_currency: b_vendor_currency,
																					   b_invoice_date: b_invoice_date,
																					   b_vat_rate : b_vat_rate,
																					   b_expected_buy_local_currency_net : b_expected_buy_local_currency_net},function(data){

							 var result=JSON.parse(data);

							  $('[name="cf_1341"]').val( result['b_vat'] );
							  $('[name="cf_1337"]').val( result['b_buy_vendor_currency_net'] );
							  //$('[name="cf_1343"]').val( result['b_buy_local_currency_gross'] );
							  $('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

							  $('[name="cf_1222"]').val( result['b_exchange_rate'] );
							  $('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
							  $('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
						});

					}
				}
				else{
					var b_invoice_date = $('input[name="cf_1216"]').val();
					var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

					if(b_buy_vendor_currency_net && b_invoice_date)
					{
						 var b_vendor_currency = $('select[name="cf_1345"]').val();
						 var b_vat_rate = $('input[name="cf_1339"]').val();
						 var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
						 var job_id = $('input[name="sourceRecord"]').val();
						 var record_id = $('input[name="record"]').val();
						 var sub_file_title = $('#cf_2191').val();
						 if ($('select[name="cf_2191"]').length > 0) {
							var sub_file_title = $('select[name="cf_2191"]').val();
						 }
						 var vat_included = $('select[name="cf_3293"]').val();
						 var b_vat_amount = $('input[name="cf_1341"]').val();

						 $.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat_rate : b_vat_rate, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
							 var result=JSON.parse(data);

							  $('[name="cf_1341"]').val( result['b_vat'] );
							  $('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
							  $('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
							  $('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

							  $('[name="cf_1222"]').val( result['b_exchange_rate'] );
							  $('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
							  $('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
						});
					}
				}
			});

			//blur on VAT
			$( 'input[name="cf_1341"]' ).on('blur', function() {
				var b_invoice_date = $('input[name="cf_1216"]').val();
				var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

				if(b_buy_vendor_currency_net && b_invoice_date)
				{
					var b_vendor_currency = $('select[name="cf_1345"]').val();
					var b_vat = $('input[name="cf_1341"]').val();
					var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
					var job_id = $('input[name="sourceRecord"]').val();
					var record_id = $('input[name="record"]').val();
					var sub_file_title = $('#cf_2191').val();
					if ($('select[name="cf_2191"]').length > 0) {
						var sub_file_title = $('select[name="cf_2191"]').val();
					}

					var vat_included = $('select[name="cf_3293"]').val();
					var b_vat_amount = $('input[name="cf_1341"]').val();

					if(vat_included =='VAT Amount')
					{
						$.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat : b_vat, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
						var result=JSON.parse(data);

						$('[name="cf_1341"]').val( result['b_vat'] );
						$('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
						$('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
						$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

						$('[name="cf_1222"]').val( result['b_exchange_rate'] );
						$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
						$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
						});
					}

				}
			})

			//onchange on Vendor Currency
			$( 'select[name="cf_1345"]' ).on('change', function() {

				var b_invoice_date = $('input[name="cf_1216"]').val();
				var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

				if(b_buy_vendor_currency_net && b_invoice_date)
				{

					var b_vendor_currency = $('select[name="cf_1345"]').val();
					var b_vat_rate = $('input[name="cf_1339"]').val();
					var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
					var job_id = $('input[name="sourceRecord"]').val();
					var record_id = $('input[name="record"]').val();
					var sub_file_title = $('#cf_2191').val();
					if ($('select[name="cf_2191"]').length > 0) {
						var sub_file_title = $('select[name="cf_2191"]').val();
					}

					var vat_included = $('select[name="cf_3293"]').val();
					var b_vat_amount = $('input[name="cf_1341"]').val();

					$.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title,  record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat_rate : b_vat_rate, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
						var result=JSON.parse(data);

						$('[name="cf_1341"]').val( result['b_vat'] );
						$('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
						$('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
						$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

						$('[name="cf_1222"]').val( result['b_exchange_rate'] );
						$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
						$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
					});
				}
			})

			//For Expense VAT include
			$('select[name="cf_3293"]').on('change',function(){
				var e_vat_included = $('select[name="cf_3293"]').val();

				if(e_vat_included=='Yes')
				{
					$('[name="cf_1339"]').val( '12.00' );
					$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', true); //Buy (Vendor currency Net)
					$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', false); //Buy (Vendor Currency Gross)
					$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', true); //VAT Amount
					//$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', true); //VAT  Rate
					$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', false); //VAT  Rate
				}
				else if(e_vat_included=='VAT Amount')
				{
					$('[name="cf_1339"]').val( '0.00' );
					$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', true); //VAT  Rate
					$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', false); //VAT Amount
					$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);  //Buy (Vendor Currency Gross)
					$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false); //Buy (Vendor currency Net)
				}
				else{
					$('[name="cf_1339"]').val( '0.00' );
					$('[name="cf_1341"]').val( '0.00' );
					$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false); //Buy (Vendor currency Net)
					$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);   //Buy (Vendor Currency Gross)
					$('#Jobexpencereport_editView_fieldName_cf_1341').prop('readonly', true); //VAT Amount
					$('#Jobexpencereport_editView_fieldName_cf_1339').prop('readonly', false);	//VAT  Rate
				}

				/*
				if(e_vat_included=='Yes')
				{
					$('[name="cf_1339"]').val( '12.00' );
					$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', true);
					$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', false);
				}
				else{
					$('[name="cf_1339"]').val( '0.00' );
					$('#Jobexpencereport_editView_fieldName_cf_1337').prop('readonly', false);
					$('#Jobexpencereport_editView_fieldName_cf_1343').prop('readonly', true);
				}
				*/

				if(e_vat_included=='Yes')
				{
							var b_invoice_date = $('input[name="cf_1216"]').val();
							var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();

							if(b_vendor_customer_currency_gross && b_invoice_date)
							{
								var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();
								var b_vendor_currency = $('select[name="cf_1345"]').val();
								var b_vat_rate = $('input[name="cf_1339"]').val();
								var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
								var job_id = $('input[name="sourceRecord"]').val();
								var record_id = $('input[name="record"]').val();
								var sub_file_title = $('#cf_2191').val();
								//var hidden_file_title = $('select[name="cf_2191"]').val();
								if ($('select[name="cf_2191"]').length > 0) {
									var sub_file_title = $('select[name="cf_2191"]').val();
								}


								$.post('include/Exchangerate/buy_vendor_currency_gross.php',{ sub_file_title: sub_file_title,
																							record_id: record_id,
																							job_id: job_id,
																							b_buy_vendor_currency_gross: b_vendor_customer_currency_gross,
																							b_vendor_currency: b_vendor_currency,
																							b_invoice_date: b_invoice_date,
																							b_vat_rate : b_vat_rate,
																							b_expected_buy_local_currency_net : b_expected_buy_local_currency_net},function(data){

									var result=JSON.parse(data);

									$('[name="cf_1341"]').val( result['b_vat'] );
									$('[name="cf_1337"]').val( result['b_buy_vendor_currency_net'] );
									//$('[name="cf_1343"]').val( result['b_buy_local_currency_gross'] );
									$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

									$('[name="cf_1222"]').val( result['b_exchange_rate'] );
									$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
									$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
								});

							}
						}
							else{
								var b_invoice_date = $('input[name="cf_1216"]').val();
								var b_buy_vendor_currency_net = $('input[name="cf_1337"]').val();

								if(b_buy_vendor_currency_net && b_invoice_date)
								{

									var b_vendor_currency = $('select[name="cf_1345"]').val();
									var b_vat_rate = $('input[name="cf_1339"]').val();
									var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
									var job_id = $('input[name="sourceRecord"]').val();
									var record_id = $('input[name="record"]').val();
									var sub_file_title = $('#cf_2191').val();
									if ($('select[name="cf_2191"]').length > 0) {
										var sub_file_title = $('select[name="cf_2191"]').val();
									}

									var vat_included = $('select[name="cf_3293"]').val();
									var b_vat_amount = $('input[name="cf_1341"]').val();

									$.post('include/Exchangerate/buy_vendor_currency_net.php',{ sub_file_title: sub_file_title,  record_id: record_id, job_id: job_id, b_buy_vendor_currency_net: b_buy_vendor_currency_net, b_vendor_currency: b_vendor_currency, b_invoice_date: b_invoice_date, b_vat_rate : b_vat_rate, b_expected_buy_local_currency_net : b_expected_buy_local_currency_net, vat_included: vat_included, b_vat_amount: b_vat_amount},function(data){
										var result=JSON.parse(data);

										$('[name="cf_1341"]').val( result['b_vat'] );
										$('[name="cf_1343"]').val( result['b_buy_vendor_currency_gross'] );
										$('[name="cf_1347"]').val( result['b_buy_local_currency_gross'] );
										$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

										$('[name="cf_1222"]').val( result['b_exchange_rate'] );
										$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
										$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
									});
								}
						}

				})


					//Buy Vendor Currency Gross = cf_1232
					$( 'input[name="cf_1343"]' ).on('blur', function() {

						var e_vat_included = $('select[name="cf_3293"]').val();

						if(e_vat_included=='Yes')
						{
							var b_invoice_date = $('input[name="cf_1216"]').val();
							var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();

							if(b_vendor_customer_currency_gross && b_invoice_date)
							{
								var b_vendor_customer_currency_gross = $('input[name="cf_1343"]').val();
								var b_vendor_currency = $('select[name="cf_1345"]').val();
								var b_vat_rate = $('input[name="cf_1339"]').val();
								var b_expected_buy_local_currency_net = $('input[name="cf_1351"]').val();
								var job_id = $('input[name="sourceRecord"]').val();
								var record_id = $('input[name="record"]').val();
								var sub_file_title = $('#cf_2191').val();
								//var hidden_file_title = $('select[name="cf_2191"]').val();
								if ($('select[name="cf_2191"]').length > 0) {
									var sub_file_title = $('select[name="cf_2191"]').val();
								}


								$.post('include/Exchangerate/buy_vendor_currency_gross.php',{ sub_file_title: sub_file_title,
																							record_id: record_id,
																							job_id: job_id,
																							b_buy_vendor_currency_gross: b_vendor_customer_currency_gross,
																							b_vendor_currency: b_vendor_currency,
																							b_invoice_date: b_invoice_date,
																							b_vat_rate : b_vat_rate,
																							b_expected_buy_local_currency_net : b_expected_buy_local_currency_net},function(data){

									var result=JSON.parse(data);

									$('[name="cf_1341"]').val( result['b_vat'] );
									$('[name="cf_1337"]').val( result['b_buy_vendor_currency_net'] );
									//$('[name="cf_1343"]').val( result['b_buy_local_currency_gross'] );
									$('[name="cf_1349"]').val( result['b_buy_local_currency_net'] );

									$('[name="cf_1222"]').val( result['b_exchange_rate'] );
									$('[name="cf_1353"]').val( result['b_variation_expected_and_actual_buying'] );
									$('[name="cf_1351"]').val( result['b_expected_buy_local_currency_net'] );
								});

							}
						}
					})


					// For Selling
					//On invoice date
					$( 'input[name="cf_1355"]' ).on('blur', function() {
						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_selling_customer_currency_net = $('select[name="cf_1357"]').val();

						if(s_selling_customer_currency_net && s_invoice_date)
						{
							var s_selling_customer_currency_net = $('select[name="cf_1357"]').val();
							var s_invoice_date = $('input[name="cf_1355"]').val();
							var s_customer_currency = $('select[name="cf_1234"]').val();
							var s_vat_rate = $('input[name="cf_1228"]').val();
							var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
							var job_id = $('input[name="sourceRecord"]').val();
							var record_id = $('input[name="record"]').val();

							var sub_file_title = $('#cf_2191').val();
							//var hidden_file_title = $('select[name="cf_2191"]').val();
							if ($('select[name="cf_2191"]').length > 0) {
								var sub_file_title = $('select[name="cf_2191"]').val();
							}

							var s_vat_included = $('select[name="cf_2695"]').val();
							var s_vat_amount = $('input[name="cf_1230"]').val();


							$.post('include/Exchangerate/sell_customer_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id,  job_id:job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount },function(data){
								var result=JSON.parse(data);

								$('[name="cf_1230"]').val( result['s_vat'] );
								$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
								$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
								$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
								$('[name="cf_1236"]').val( result['s_exchange_rate'] );
								$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

							});
						}
					})


					//On selling customer currency net
					$( 'input[name="cf_1357"]' ).on('blur', function() {
						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();

						if(s_selling_customer_currency_net && s_invoice_date)
						{
							var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();
							var s_invoice_date = $('input[name="cf_1355"]').val();
							var s_customer_currency = $('select[name="cf_1234"]').val();
							var s_vat_rate = $('input[name="cf_1228"]').val();
							var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
							var job_id = $('input[name="sourceRecord"]').val();
							var record_id = $('input[name="record"]').val();

							var sub_file_title = $('#cf_2191').val();
							//var hidden_file_title = $('select[name="cf_2191"]').val();
							if ($('select[name="cf_2191"]').length > 0) {
								var sub_file_title = $('select[name="cf_2191"]').val();
							}

							var s_vat_included = $('select[name="cf_2695"]').val();
							var s_vat_amount = $('input[name="cf_1230"]').val();

							$.post('include/Exchangerate/sell_customer_currency_net.php',{sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount},function(data){
								var result=JSON.parse(data);

								$('[name="cf_1230"]').val( result['s_vat'] );
								$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
								$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
								$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
								$('[name="cf_1236"]').val( result['s_exchange_rate'] );
								$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

							});
						}
					});

					//On vat rate
					$( '#Jobexpencereport_editView_fieldName_cf_1228' ).on('blur', function() {


						var vat_included = $('select[name="cf_2695"]').val();

						if(vat_included=='Yes')
						{

						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();

							if(s_selling_customer_currency_gross && s_invoice_date)
							{
								var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();
								var s_invoice_date = $('input[name="cf_1355"]').val();
								var s_customer_currency = $('select[name="cf_1234"]').val();
								var s_vat_rate = $('input[name="cf_1228"]').val();
								var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
								var job_id = $('input[name="sourceRecord"]').val();
								var record_id = $('input[name="record"]').val();

								var sub_file_title = $('#cf_2191').val();
								//var hidden_file_title = $('select[name="cf_2191"]').val();
								if ($('select[name="cf_2191"]').length > 0) {
									var sub_file_title = $('select[name="cf_2191"]').val();
								}

								$.post('include/Exchangerate/sell_customer_currency_gross.php',{sub_file_title: sub_file_title, record_id: record_id, job_id: job_id,
																								s_selling_customer_currency_gross: s_selling_customer_currency_gross,
																								s_customer_currency: s_customer_currency,
																								s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate,
																								s_expected_sell_local_currency_net : s_expected_sell_local_currency_net},function(data){
									var result=JSON.parse(data);

									$('[name="cf_1230"]').val( result['s_vat'] );
									$('[name="cf_1357"]').val( result['s_selll_customer_currency_net'] );
									$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
									$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
									$('[name="cf_1236"]').val( result['s_exchange_rate'] );
									$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

								});

							}
						}
						else{
							var s_invoice_date = $('input[name="cf_1355"]').val();
							var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();

							if(s_selling_customer_currency_net && s_invoice_date)
							{
								var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();
								var s_invoice_date = $('input[name="cf_1355"]').val();
								var s_customer_currency = $('select[name="cf_1234"]').val();
								var s_vat_rate = $('input[name="cf_1228"]').val();
								var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
								var job_id = $('input[name="sourceRecord"]').val();
								var record_id = $('input[name="record"]').val();

								var sub_file_title = $('#cf_2191').val();
								//var hidden_file_title = $('select[name="cf_2191"]').val();
								if ($('select[name="cf_2191"]').length > 0) {
									var sub_file_title = $('select[name="cf_2191"]').val();
								}
								var s_vat_included = $('select[name="cf_2695"]').val();
								var s_vat_amount = $('input[name="cf_1230"]').val();

								$.post('include/Exchangerate/sell_customer_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount},function(data){
									var result=JSON.parse(data);

									$('[name="cf_1230"]').val( result['s_vat'] );
									$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
									$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
									$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
									$('[name="cf_1236"]').val( result['s_exchange_rate'] );
									$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

								});
							}
						}
					});


				//On vat amount
				$( '#Jobexpencereport_editView_fieldName_cf_1230' ).on('blur', function() {

					var s_invoice_date = $('input[name="cf_1355"]').val();
					var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();

					if(s_selling_customer_currency_net && s_invoice_date)
					{
						var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();
						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_customer_currency = $('select[name="cf_1234"]').val();
						var s_vat_rate = $('input[name="cf_1228"]').val();
						var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
						var job_id = $('input[name="sourceRecord"]').val();
						var record_id = $('input[name="record"]').val();

						var sub_file_title = $('#cf_2191').val();
						//var hidden_file_title = $('select[name="cf_2191"]').val();
						if ($('select[name="cf_2191"]').length > 0) {
							var sub_file_title = $('select[name="cf_2191"]').val();
						}
						var s_vat_included = $('select[name="cf_2695"]').val();
						var s_vat_amount = $('input[name="cf_1230"]').val();

						if(s_vat_included=='VAT Amount')
						{
							$.post('include/Exchangerate/sell_customer_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount},function(data){
								var result=JSON.parse(data);

								$('[name="cf_1230"]').val( result['s_vat'] );
								$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
								$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
								$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
								$('[name="cf_1236"]').val( result['s_exchange_rate'] );
								$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

							});
						}
					}
				});

				//selling customer currency
				$( 'select[name="cf_1234"]' ).on('change', function() {
					var s_invoice_date = $('input[name="cf_1355"]').val();
					var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();

					if(s_selling_customer_currency_net && s_invoice_date)
					{
						var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();
						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_customer_currency = $('select[name="cf_1234"]').val();
						var s_vat_rate = $('input[name="cf_1228"]').val();
						var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
						var job_id = $('input[name="sourceRecord"]').val();
						var record_id = $('input[name="record"]').val();

						var sub_file_title = $('#cf_2191').val();
						//var hidden_file_title = $('select[name="cf_2191"]').val();
						if ($('select[name="cf_2191"]').length > 0) {
							var sub_file_title = $('select[name="cf_2191"]').val();
						}
						var s_vat_included = $('select[name="cf_2695"]').val();
						var s_vat_amount = $('input[name="cf_1230"]').val();

						$.post('include/Exchangerate/sell_customer_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount},function(data){
							var result=JSON.parse(data);

							$('[name="cf_1230"]').val( result['s_vat'] );
							$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
							$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
							$('[name="cf_1240"]').val( result['s_sell_local_currency_net']);
							$('[name="cf_1236"]').val( result['s_exchange_rate'] );
							$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

						});
					}
				});


				//For VAT included
				$( 'select[name="cf_2695"]' ).on('change', function() {
					var vat_included = $('select[name="cf_2695"]').val();

					if(vat_included=='Yes')
					{
						$('[name="cf_1228"]').val( '12.00' );
						$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', true);// Selling Customer Currency Net
						$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', false);// Sell Customer Currency Gross
						$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', true); // VAT
						//$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', true);  // VAT Rate
						$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', false);  // VAT Rate

					}
					else if(vat_included == 'VAT Amount')
					{
						$('[name="cf_1228"]').val( '0.00' );
						$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', true);  // VAT Rate
						$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', false); // VAT
						$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', true);  // Sell Customer Currency Gross
						$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', false); // Selling Customer Currency Net

					}
					else{
						$('[name="cf_1228"]').val( '0.00' );
						$('[name="cf_1230"]').val( '0.00' );
						$('#Jobexpencereport_editView_fieldName_cf_1228').prop('readonly', false); // VAT Rate
						$('#Jobexpencereport_editView_fieldName_cf_1357').prop('readonly', false); // Selling Customer Currency Net
						$('#Jobexpencereport_editView_fieldName_cf_1232').prop('readonly', true);  // Sell Customer Currency Gross
						$('#Jobexpencereport_editView_fieldName_cf_1230').prop('readonly', true);  // VAT
					}

					if(vat_included=='Yes')
					{


					var s_invoice_date = $('input[name="cf_1355"]').val();
					var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();

						if(s_selling_customer_currency_gross && s_invoice_date)
						{
							var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();
							var s_invoice_date = $('input[name="cf_1355"]').val();
							var s_customer_currency = $('select[name="cf_1234"]').val();
							var s_vat_rate = $('input[name="cf_1228"]').val();
							var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
							var job_id = $('input[name="sourceRecord"]').val();
							var record_id = $('input[name="record"]').val();

							var sub_file_title = $('#cf_2191').val();
							//var hidden_file_title = $('select[name="cf_2191"]').val();
							if ($('select[name="cf_2191"]').length > 0) {
								var sub_file_title = $('select[name="cf_2191"]').val();
							}

							$.post('include/Exchangerate/sell_customer_currency_gross.php',{sub_file_title: sub_file_title, record_id: record_id, job_id: job_id,
																							s_selling_customer_currency_gross: s_selling_customer_currency_gross,
																							s_customer_currency: s_customer_currency,
																							s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate,
																							s_expected_sell_local_currency_net : s_expected_sell_local_currency_net},function(data){
								var result=JSON.parse(data);

								$('[name="cf_1230"]').val( result['s_vat'] );
								$('[name="cf_1357"]').val( result['s_selll_customer_currency_net'] );
								$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
								$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
								$('[name="cf_1236"]').val( result['s_exchange_rate'] );
								$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

							});

						}

					}
					else{
						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();

						if(s_selling_customer_currency_net && s_invoice_date)
						{
							var s_selling_customer_currency_net = $('input[name="cf_1357"]').val();
							var s_invoice_date = $('input[name="cf_1355"]').val();
							var s_customer_currency = $('select[name="cf_1234"]').val();
							var s_vat_rate = $('input[name="cf_1228"]').val();
							var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
							var job_id = $('input[name="sourceRecord"]').val();
							var record_id = $('input[name="record"]').val();

							var sub_file_title = $('#cf_2191').val();
							//var hidden_file_title = $('select[name="cf_2191"]').val();
							if ($('select[name="cf_2191"]').length > 0) {
								var sub_file_title = $('select[name="cf_2191"]').val();
							}
							var s_vat_included = $('select[name="cf_2695"]').val();
							var s_vat_amount = $('input[name="cf_1230"]').val();

							$.post('include/Exchangerate/sell_customer_currency_net.php',{ sub_file_title: sub_file_title, record_id: record_id, job_id: job_id, s_selling_customer_currency_net: s_selling_customer_currency_net, s_customer_currency: s_customer_currency, s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate, s_expected_sell_local_currency_net : s_expected_sell_local_currency_net, s_vat_included: s_vat_included, s_vat_amount : s_vat_amount},function(data){
								var result=JSON.parse(data);

								$('[name="cf_1230"]').val( result['s_vat'] );
								$('[name="cf_1232"]').val( result['s_selll_customer_currency_gross'] );
								$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
								$('[name="cf_1240"]').val( result['s_sell_local_currency_net']);
								$('[name="cf_1236"]').val( result['s_exchange_rate'] );
								$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

							});
						}
					}


				});

					//Sell Customer Currency Gross = cf_1232
					$( 'input[name="cf_1232"]' ).on('blur', function() {

						var vat_included = $('select[name="cf_2695"]').val();

						if(vat_included=='Yes')
						{

						var s_invoice_date = $('input[name="cf_1355"]').val();
						var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();

							if(s_selling_customer_currency_gross && s_invoice_date)
							{
								var s_selling_customer_currency_gross = $('input[name="cf_1232"]').val();
								var s_invoice_date = $('input[name="cf_1355"]').val();
								var s_customer_currency = $('select[name="cf_1234"]').val();
								var s_vat_rate = $('input[name="cf_1228"]').val();
								var s_expected_sell_local_currency_net = $('input[name="cf_1242"]').val();
								var job_id = $('input[name="sourceRecord"]').val();
								var record_id = $('input[name="record"]').val();

								var sub_file_title = $('#cf_2191').val();
								//var hidden_file_title = $('select[name="cf_2191"]').val();
								if ($('select[name="cf_2191"]').length > 0) {
									var sub_file_title = $('select[name="cf_2191"]').val();
								}

								$.post('include/Exchangerate/sell_customer_currency_gross.php',{sub_file_title: sub_file_title, record_id: record_id, job_id: job_id,
																								s_selling_customer_currency_gross: s_selling_customer_currency_gross,
																								s_customer_currency: s_customer_currency,
																								s_invoice_date: s_invoice_date, s_vat_rate : s_vat_rate,
																								s_expected_sell_local_currency_net : s_expected_sell_local_currency_net},function(data){
									var result=JSON.parse(data);

									$('[name="cf_1230"]').val( result['s_vat'] );
									$('[name="cf_1357"]').val( result['s_selll_customer_currency_net'] );
									$('[name="cf_1238"]').val( result['s_sell_local_currency_gross'] );
									$('[name="cf_1240"]').val( result['s_sell_local_currency_net'] );
									$('[name="cf_1236"]').val( result['s_exchange_rate'] );
									$('[name="cf_1244"]').val( result['s_variation_expected_and_actual_selling'] );

								});

							}
						}

					});



	 },


	 registerBasicEvents: function(container){
        this._super(container);
		this.getExchangeRate(container);
	 },
});

////// invoice date change function ///////


$('[name="cf_1355"]').on('change',function(){

var returnrecord = $('[name="returnrecord"]').val();
var customer_id =	$('[name="cf_1445"]').val();
var invoice_date =	$('[name="cf_1355"]').val();
if(invoice_date){
var record_id =	$('[name="record"]').val();
var actions =	$('[name="returnview"]').val();

 // $("div .quickCreateContent table tbody").find( "td" ).eq(13).append('<i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
 // $("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(3).append('<br><i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
var vtigerurl = window.location.href;  //// ya hum na url get karna ka liya leekha ha//////
vtigerurl=vtigerurl.split("&");
vtigerurl=vtigerurl[0];
vtigerurl=vtigerurl.split("?");
var module = vtigerurl[1];
module = module.split("=");
module=module[1];
var newurl=vtigerurl[0];
newurl=newurl.split("index.php");
newurl=newurl[0];  ///// url ka code yahan par khatam hota ha ///////

$.ajax({
						type: "POST",
						'async': false,
						'global': false,
						 url: newurl+"index.php?module=Jobexpencereport&action=FindAgreement",
						 data: {
								 'customer_id' : customer_id,
								 'record_id' : record_id,
								 'returnrecord' : returnrecord,
								 'invoice_date' : invoice_date,
								 'actions' : actions,
							 },
							success: function(res){
								console.log(res);
								$("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(15).html(res);
								$("div .quickCreateContent table tbody").find( "td" ).eq(25).html(res);
								$(".agreementdropdown").select2();

						 }
				});
			}
});



//// invoice date change function end here //////


//// on page load  function//////



	//
	// var customer_id =	$('[name="cf_1445"]').val();
	// var invoice_date =	$('[name="cf_1355"]').val();
	// var record_id =	$('[name="record"]').val();
	// var actions =	$('[name="returnview"]').val();

	//// code to get a specific variable value from the url //////
	// var queryString = window.location.search;
	// var urlParams = new URLSearchParams(queryString);
	// var returnrecord = urlParams.get('record');
	//// end code to get a specific variable value from the url //////

	$("div .quickCreateContent table tbody").find( "td" ).eq(13).append('<i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
	$("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(3).append('<br><i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
	$("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(15).html('<select name="cf_7914" class="select2 agreementdropdown" style="width:75%"><option value="">Select Option</option></select>');
	$("div .quickCreateContent table tbody").find( "td" ).eq(25).html('<select name="cf_7914" class="select2 agreementdropdown" style="width:75%"><option value="">Select Option</option></select>');
	$(".agreementdropdown").select2();
	// var vtigerurl = window.location.href;  //// ya hum na url get karna ka liya leekha ha//////
	// vtigerurl=vtigerurl.split("&");
	// vtigerurl=vtigerurl[0];
	// vtigerurl=vtigerurl.split("?");
	// var module = vtigerurl[1];
	// module = module.split("=");
	// module=module[1];
	// var newurl=vtigerurl[0];
	// newurl=newurl.split("index.php");
	// newurl=newurl[0];  ///// url ka code yahan par khatam hota ha ///////
	//
	// $.ajax({
	// 						type: "POST",
	// 						'async': false,
	// 						'global': false,
	// 						 url: newurl+"index.php?module=Jobexpencereport&action=FindAgreement",
	// 						 data: {
	// 								 'customer_id' : customer_id,
	// 								 'record_id' : record_id,
	// 								 'returnrecord' : returnrecord,
	// 								 'invoice_date' : invoice_date,
	// 								 'actions' : actions,
	// 							 },
	// 							success: function(res){
	// 								alert("asdsad");
	// 								console.log(res);
	// 								$("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(15).html(res);
	// 								$("div .quickCreateContent table tbody").find( "td" ).eq(25).html(res);
	// 								$(".agreementdropdown").select2();
	// 						 }
	// 				});

//// onpage load function end here ///////


//// refresh button click function ///////


					$('#searchagreement').on('click',function(){

						var returnrecord = $('[name="returnrecord"]').val();
						var customer_id =	$('[name="cf_1445"]').val();
						var invoice_date =	$('[name="cf_1355"]').val();

						if(invoice_date!=''){
						var record_id =	$('[name="record"]').val();
						var actions =	$('[name="returnview"]').val();

						// $("div .quickCreateContent table tbody").find( "td" ).eq(13).append('<i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
						// $("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(3).append('<br><i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
						var vtigerurl = window.location.href;  //// ya hum na url get karna ka liya leekha ha//////
						vtigerurl=vtigerurl.split("&");
						vtigerurl=vtigerurl[0];
						vtigerurl=vtigerurl.split("?");
						var module = vtigerurl[1];
						module = module.split("=");
						module=module[1];
						var newurl=vtigerurl[0];
						newurl=newurl.split("index.php");
						newurl=newurl[0];  ///// url ka code yahan par khatam hota ha ///////

						$.ajax({
												type: "POST",
												'async': false,
												'global': false,
												 url: newurl+"index.php?module=Jobexpencereport&action=FindAgreement",
												 data: {
														 'customer_id' : customer_id,
														 'returnrecord' : returnrecord,
														 'record_id' : record_id,
														 'invoice_date' : invoice_date,
														 'actions' : actions,
													 },
													success: function(res){

														$("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(15).html(res);
														$("div .quickCreateContent table tbody").find( "td" ).eq(25).html(res);
														$(".agreementdropdown").select2();

												 }
										});
									}else{

app.helper.showAlertNotification({message:app.vtranslate('Please select invoice date')},{delay:3000});
									}
					});


///// refresh button click function end here ///////
