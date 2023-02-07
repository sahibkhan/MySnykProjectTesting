
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("CargoInsurance_Edit_Js",{},{


		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery("[name='assigned_user_id']").prop('disabled', true);

		$('input[name="name"]').prop('readonly',true);

		$('input[name="cf_3639"]').prop('readonly', true); //Total Insured Sum
		$('input[name="cf_3673"]').prop('readonly', true); //Exchange Rate based on WIS Date
		$('input[name="cf_3641"]').prop('readonly', true); //Globalink selling rate
		$('input[name="cf_3645"]').prop('readonly', true); //Globalink premium
		
		$('input[name="cf_3635"]').prop('readonly', true); //WIS Rate
		$('input[name="cf_3637"]').prop('readonly', true); //WIS Premium
		
		$('input[name="cf_3665"]').prop('readonly', true); //Agent Comission
		$('input[name="cf_3667"]').prop('readonly', true); //Agent Premium
		
		$('input[name="cf_3669"]').prop('readonly', true); //Centras Rate
		$('input[name="cf_3671"]').prop('readonly', true); //Centras Premium
		
		//wis info disable 
		$('input[name="cf_3621"]').prop('readonly', true); //WIS Ref
		$('input[name="cf_3623"]').prop('readonly', true); //WIS Date
		$('input[name="cf_8364"]').prop('readonly', true); //WIS Status
		
		var assured_company = $('select[name="cf_3599"]').val();
		if(assured_company!='85757')
		{
			$('[name="cf_3603"]').attr("value", '-');
		}
		
	 },

	 getCargoRate : function(container){
		var self = this;

		jQuery('[name="cf_4559"]').change(function(){
			var beneficiary = container.find('[name="cf_3601"]').val();
			var glk_company = container.find('[name="cf_3599"]').val();
			if (this.value == 'No' && glk_company=='85757' && beneficiary!='1667'){
				
				$.post('include/CargoInsurance/fsl_black_info.php',{ beneficiary: beneficiary},function(data){
							if(data =='failed')
							{
								app.helper.showAlertBox({'message':app.vtranslate("The beneficiary is not registered for FSL Black, please contact insurance team.")});
								container.find("[name='cf_4559']").focus();

							//$('#cf_4559').show();	 
							//$('#cf_4559').text('The beneficiary is not registered for FSL Black, please contact insurance team.'); //globalink selling rate
							}
							
				});				
			}
			
		})

		//On change of description of cargo
		jQuery('[name="cf_3625"]').on('change',function(){
				var commodity_type_id = $('[name="cf_3625"]').val();

				var insurance_type =  container.find('select[name="cf_6298"]').val();	//Insurance Type
				var assured_company =  container.find('select[name="cf_3599"]').val();	//File title
				var departure_date =  container.find('[name="cf_3613"]').val();		//From Date
				
				if(commodity_type_id!='')
				{
					$.post('include/CommodityRates/commodityrates.php',{ insurance_type:insurance_type, commodity_type_id: commodity_type_id, assured_company: assured_company, departure_date: departure_date},function(data){
						console.log("Return Data");
						console.log(data);
						
						$('[name="cf_3627"]').empty();
								$('[name="cf_3627"]').append(
									'<option value="">--Select Special Range--</option>'
								);
						if (data) {		
									$.each(data.specialrange, function(i, rangemethod){
										
										$('[name="cf_3627"]').append(
											'<option  data-picklistvalue="' + rangemethod.specialrangeid + '" value="' + rangemethod.specialrangeid + '">' + rangemethod.name + '</option>'
										);
									});
										
								}
								$('[name="cf_3627"]').trigger('change.select2');
							
													
					
					}, 'json');
				}
			})

		// change in total insurance ammount
		jQuery('[name="cf_3629"]').on('change',function(){
			//var tiamt = $('[name="cf_3639"]').val();
			
			//app.helper.showErrorNotification({message:"Change in total insurance222"});
		});

		// on change calculation fields
		jQuery('[name="cf_3613"], [name="cf_3629"], [name="cf_3631"], [name="cf_3633"], [name="cf_3643"], [name="cf_3625"], [name="cf_3627"], [id="CargoInsurance_Edit_fieldName_cf_3619"]').on('change', function(){
		
			//alert("change field ");
			var invoice_sum =  container.find('[name="cf_3629"]').val();
			var transportat_cost_for_inv =  container.find('[name="cf_3631"]').val();
			var other_charges =  container.find('[name="cf_3633"]').val();
			
			var discounted_glk_rate =  container.find('[name="cf_3643"]').val();
			
			var total_insured_sum = (Number(invoice_sum) +  Number(transportat_cost_for_inv) + Number(other_charges));				
			$('[name="cf_3639"]').val(total_insured_sum.toFixed(2)); //Fuel at the end
			//app.helper.showErrorNotification({message:"Change in total insurance"});
			
			var rate_id =  container.find('[name="cf_3625"]').val();
			var special_range_id =  container.find('[name="cf_3627"]').val();	//premium calculator	
			var total_insured_sum =  container.find('[name="cf_3639"]').val();			
			var mode_arr =  container.find('#CargoInsurance_Edit_fieldName_cf_3619').val();			
			var assured_company =  container.find('select[name="cf_3599"]').val();	//File title
			var insurance_type =  container.find('select[name="cf_6298"]').val();	//Insurance Type
			var departure_date =  container.find('[name="cf_3613"]').val();		//From Date
			//var beneficiary = container.find('[name="cf_3601"]').val();
			
			
			var alto_date = new Date().toISOString().slice(0, 10);
			//getting exchange rate 
			$.post('include/Insurance/exchange_rate.php',{ alto_date: alto_date},function(data){					
				 var result=JSON.parse(data);
				 $('[name="cf_3673"]').attr("value", result['exchange_rate']); //Exchange Rate
			});
			
			var exchange_rate =  container.find('[name="cf_3673"]').val();		//Exchange Rate
			console.log("Exchange Rate");
			console.log(exchange_rate);
			
			if(rate_id!='' && special_range_id!='' )
			{
				//alert("Ajax");
				var ratechkurl= "include/CargoInsurance/cargoinsurance_rate_info.php";
				if(insurance_type=="WIS Insurance")
				{
					//ratechkurl= "include/CargoInsurance/cargoinsurance_wis_rate_info.php";
				}
				console.log(ratechkurl);
				
				 $.post(ratechkurl,{ insurance_type: insurance_type, assured_company: assured_company, rate_id: rate_id, special_range_id:special_range_id, total_insured_sum: total_insured_sum, discounted_glk_rate:discounted_glk_rate, mode_arr:mode_arr, departure_date: departure_date,exchange_rate:exchange_rate },function(data){					
							 var result=JSON.parse(data);
							 
							 $('[name="cf_3641"]').attr("value", result['globalink_rate']); //globalink selling rate
							 $('[name="cf_3635"]').attr("value", result['wis_rate']); //FP rate
							 $('[name="cf_3665"]').attr("value", result['agent_rate']); //Agent Comission
							 $('[name="cf_3669"]').attr("value", result['centras_rate']); // Centras Rate
							 
							 $('[name="cf_3645"]').attr("value", result['globalink_premium']); //globalink premium
							 $('[name="cf_3637"]').attr("value", result['wis_premium']); //FP premium
							 $('[name="cf_3667"]').attr("value", result['agent_premium']); //Agent premium
							 $('[name="cf_3671"]').attr("value", result['centras_premium']); // Centras premium
							 // show limit msg - azhar 19 jan 2022
							 /*
							 var msg=result['message'];
							 if(msg!="")
							 {
								app.helper.showErrorNotification({message:msg});
								
							 }
							 $('#errmsg').html(msg);
							 */
							 
				 });
			}
			
		})

		jQuery('[name="cf_3623"]').on('change',function(){
			var alto_date = container.find('[name="cf_3623"]').val();
			if(alto_date!='')
			{
				$.post('include/Insurance/exchange_rate.php',{ alto_date: alto_date},function(data){					
					 var result=JSON.parse(data);
					 $('[name="cf_3673"]').attr("value", result['exchange_rate']); //Exchange Rate
				});
			}
			
		})
	 },

		 
	 registerBasicEvents: function(container){
        this._super(container);
		this.getCargoRate(container);
	 },
});