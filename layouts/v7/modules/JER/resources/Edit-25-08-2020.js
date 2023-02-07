
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("JER_Edit_Js",{},{

		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery('#JER_editView_fieldName_name').prop("readonly", true);
		jQuery("[name='assigned_user_id']").prop('disabled', true);
		jQuery("#JER_editView_fieldName_cf_1158").prop('disabled', true);//Cost Exchange RAte
		jQuery("#JER_editView_fieldName_cf_6352").prop('disabled', true); //Buy local currency gross
		jQuery("#JER_editView_fieldName_cf_1160").prop('disabled', true); // Cost(local currency)

		//Revenue
		jQuery("#JER_editView_fieldName_cf_1166").prop('disabled', true);//Revenue exchange rate
		jQuery("#JER_editView_fieldName_cf_6356").prop('disabled', true);//sell local currency gross
		jQuery("#JER_editView_fieldName_cf_1168").prop('disabled', true);//sell local currency
		
	 },

	 getExchangeRate : function(container){
		var self = this;
		//module JER expected cost
		jQuery('[name="cf_1154"],[name="cf_6350"]').blur(function(){
			var cost_vendor = $('#JER_editView_fieldName_cf_1154').val();
			var cost_vat_rate = $('#JER_editView_fieldName_cf_6350').val();
			var pay_to_currency = container.find('[name="cf_1156"]').val();
			var job_id = $('input[name="returnrecord"]').val();

			$.post('include/Exchangerate/cost_local_currency.php',{ job_id: job_id, cost_vendor: cost_vendor, cost_vat_rate:cost_vat_rate, pay_to_currency: pay_to_currency},function(data){			
				//console.log(data);
				var result=JSON.parse(data);
				
				$('#JER_editView_fieldName_cf_1160').val( result['cost_local_currecny'] );
				$('#JER_editView_fieldName_cf_1158').val( result['cost_exchange_rate'] );
				$('#JER_editView_fieldName_cf_6352').val( result['cost_local_currecny_gross'] );
			 });
			
		})

		jQuery('[name="cf_1156"]').change(function(){
			var cost_vendor = $('#JER_editView_fieldName_cf_1154').val();
			var cost_vat_rate = $('#JER_editView_fieldName_cf_6350').val();
			var pay_to_currency = container.find('[name="cf_1156"]').val();
			var job_id = $('input[name="returnrecord"]').val();

			$.post('include/Exchangerate/cost_local_currency.php',{ job_id: job_id, cost_vendor: cost_vendor, cost_vat_rate:cost_vat_rate, pay_to_currency: pay_to_currency},function(data){			
				var result=JSON.parse(data);
				
				$('[name="cf_1160"]').val( result['cost_local_currecny'] );
				$('[name="cf_1158"]').val( result['cost_exchange_rate'] );
				$('[name="cf_6352"]').val( result['cost_local_currecny_gross'] );
			 
	  		 });
		}) 

		//module JER expected revenue
		jQuery( '[name="cf_1162"], [name="cf_6354"]' ).blur(function() {

			var cost_vendor = $('#JER_editView_fieldName_cf_1162').val();
			var cost_vat_rate = $('#JER_editView_fieldName_cf_6354').val();
			var pay_to_currency = container.find('[name="cf_1164"]').val();
			var job_id = $('input[name="returnrecord"]').val();
	   			
			   $.post('include/Exchangerate/cost_local_currency.php',{ job_id: job_id, cost_vendor: cost_vendor, cost_vat_rate:cost_vat_rate, pay_to_currency: pay_to_currency},function(data){			
						var result=JSON.parse(data);
						$('[name="cf_1168"]').val( result['cost_local_currecny'] );
						$('[name="cf_1166"]').val( result['cost_exchange_rate'] );
						$('[name="cf_6356"]').val( result['cost_local_currecny_gross'] );					
				});
		  })

		  jQuery('[name="cf_1164"]').change(function(){

			var cost_vendor = $('#JER_editView_fieldName_cf_1162').val();
			var cost_vat_rate = $('#JER_editView_fieldName_cf_6354').val();
			var pay_to_currency = container.find('[name="cf_1164"]').val();
			var job_id = $('input[name="returnrecord"]').val();		
			 
			 $.post('include/Exchangerate/cost_local_currency.php',{ job_id: job_id, cost_vendor: cost_vendor, cost_vat_rate:cost_vat_rate, pay_to_currency: pay_to_currency},function(data){			
					var result=JSON.parse(data);
					$('[name="cf_1168"]').val( result['cost_local_currecny'] );
					$('[name="cf_1166"]').val( result['cost_exchange_rate'] );
					$('[name="cf_6356"]').val( result['cost_local_currecny_gross'] );
					
			  });
		});


	},
	 
	 registerBasicEvents: function(container){
        this._super(container);
		this.getExchangeRate(container);
	 },
});