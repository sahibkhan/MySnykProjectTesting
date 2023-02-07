
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("RRSInsurance_Edit_Js",{},{


		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery("[name='assigned_user_id']").prop('disabled', true);
		$('input[name="name"]').prop('readonly',true);

		$('[name="cf_5257"]').attr("value", '1'); //Exchange Rate

		$('input[name="cf_5241"]').prop('readonly', true); //Total Insured Sum
		$('input[name="cf_5257"]').prop('readonly', true); //Exchange Rate based on Declaration Date
		$('input[name="cf_5243"]').prop('readonly', true); //Globalink selling rate
		$('input[name="cf_5245"]').prop('readonly', true); //Globalink premium
		
		$('input[name="cf_5231"]').prop('readonly', true); //transit rate
		$('input[name="cf_5247"]').prop('readonly', true); //transit Premium
		
		$('input[name="cf_5233"]').prop('readonly', true); //Storage rate
		$('input[name="cf_5249"]').prop('readonly', true); //Storage premium
	
	
		
	 },

	 
	 getDeductibleRate : function(container){
		var self = this;

		jQuery('[name="cf_5223"], [name="cf_5221"], [name="cf_5225"], [name="cf_5229"], [name="cf_5235"], [name="cf_5237"], [name="cf_5239"], [name="cf_5339"]').on('change', function(){
		
			var declaration_type = $('[name="cf_5223"]').val();
			var zone = $('[name="cf_5221"]').val();	
			
			if(declaration_type != "Permanent Storage")
			{
				if($('select[name="cf_5225"]').is(':disabled'))
				{
					$('select[name="cf_5225"]').prop('disabled', false); //mode
					$('[name="cf_5225"]').trigger('liszt:updated');
				}
				if($('select[name="cf_5229"]').is(':disabled'))
				{		
					$('select[name="cf_5229"]').prop('disabled', false); //deductible
					$('[name="cf_5229"]').trigger('liszt:updated');
				}
				
				if($('select[name="cf_5227"]').is(':disabled'))
				{
					$('select[name="cf_5227"]').prop('disabled', false); //commodity
					$('[name="cf_5227"]').trigger('liszt:updated');	 
				}
				
				
				$('[name="cf_5227"]  option[value="HOUSEHOLD GOODS/AUTOS"]').prop("selected", true);
				$('[name="cf_5227"]').trigger('change.select2');
				if(zone=="DOMESTIC TRUCK")
				{				
					$('[name="cf_5225"]  option[value="Domestic Truck"]').prop("selected", true);
					$('[name="cf_5225"]').trigger('change.select2');
									
					//$('[name="cf_5229"]  option[value="USD $0 DA"]').prop("selected", true); //deductible
					//$('[name="cf_5229"]').trigger('liszt:updated');
				}
			}
			else{			
				$('select[name="cf_5225"]').prop('disabled', true);//mode
				$('[name="cf_5225"]').trigger('change.select2');
				$('select[name="cf_5229"]').prop('disabled', true); //deductible
				$('[name="cf_5229"]').trigger('change.select2');	
				$('select[name="cf_5227"]').prop('disabled', true); //commodity
				$('[name="cf_5227"]').trigger('change.select2');		
			}
			
			var mode = $('[name="cf_5225"]').val();	
			//var commudity = $('[name="cf_5227"]').val();	
			var deductible = $('[name="cf_5229"]').val();
			var household_goods = $('[name="cf_5235"]').val();
			var hight_value_items = $('[name="cf_5237"]').val();
			var automobile = $('[name="cf_5239"]').val();
			var discounted_glk_rate = $('[name="cf_5339"]').val();
			
			var total_insured_sum = (Number(household_goods) +  Number(hight_value_items) + Number(automobile));				
			$('[name="cf_5241"]').val(total_insured_sum.toFixed(2)); //total sum to be insured
			
			//var total_insured_sum = $('[name="cf_5241"]').val();
			
			
			$.post('include/RRSInsurance/deductible_rate_info.php',{ declaration_type:declaration_type, zone:zone, mode:mode, deductible:deductible, total_insured_sum:total_insured_sum, discounted_glk_rate:discounted_glk_rate  },function(data){					
				 var result=JSON.parse(data);
				 
				 
				 $('[name="cf_5233"]').attr("value", result['storage_rate']); //Storage Rate
				 $('[name="cf_5231"]').attr("value", result['transit_rate']); //Transit Rate
				 
				 $('[name="cf_5243"]').attr("value", result['globalink_rate']); //globalink Selling Rate
				 
				 $('[name="cf_5245"]').attr("value", result['globalink_premium']); //Globalink Premium
				 $('[name="cf_5247"]').attr("value", result['net_premium']); //Net Premium
				 $('[name="cf_5249"]').attr("value", result['storage_premium']); //Storage Premium
				 
			});
			
			
		})
			
	 },
	

		 
	 registerBasicEvents: function(container){
        this._super(container);
		this.getDeductibleRate(container);
	 },
});



//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_5199"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_5199"]').val(); 
    if(countryId == "US" || countryId == "CA")
    {
        $('select[data-fieldname="rrsinsurance_origin_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_5195"]').val('');
                  $('select[data-fieldname="cf_5195"]').prop('disabled', true);
                  $('select[data-fieldname="rrsinsurance_origin_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
        $('select[data-fieldname="cf_5195"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="rrsinsurance_origin_state"]').val('');
	            $('select[data-fieldname="rrsinsurance_origin_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_5195"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});


//Origin Destination cities the base on Dest countries


//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_5207"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_5207"]').val(); 
    
    if(countryId == "US" || countryId == "CA")
    {
    	$('select[data-fieldname="rrsinsurance_destination_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_5203"]').val('');
                  $('select[data-fieldname="cf_5203"]').prop('disabled', true);
                  $('select[data-fieldname="rrsinsurance_destination_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
       $('select[data-fieldname="cf_5203"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="rrsinsurance_destination_state"]').val('');
	            $('select[data-fieldname="rrsinsurance_destination_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_5203"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});

//Origin City select on the base of Origin States
$('select[data-fieldname="rrsinsurance_origin_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_5199"]').val(); 
    var origin_state_code =   $('select[data-fieldname="rrsinsurance_origin_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_5195"]').prop('disabled', false);
                 $('select[data-fieldname="cf_5195"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});


$('select[data-fieldname="rrsinsurance_destination_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_5207"]').val(); 
    var origin_state_code =   $('select[data-fieldname="rrsinsurance_destination_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_5203"]').prop('disabled', false);
                 $('select[data-fieldname="cf_5203"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});