
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("BO_Edit_Js",{},{


		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery("[name='assigned_user_id']").prop('disabled', true);

		$('input[name="cf_1295"]').prop('readonly',true);
		$('input[name="cf_1793"]').prop('readonly',true);
		
	 },

		 
	 registerBasicEvents: function(container){
        this._super(container);
		
	 },
});




//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1281"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1281"]').val(); 
    if(countryId == "US" || countryId == "CA")
    {
        $('select[data-fieldname="bo_origin_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1461"]').val('');
                  $('select[data-fieldname="cf_1461"]').prop('disabled', true);
                  $('select[data-fieldname="bo_origin_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
        $('select[data-fieldname="cf_1461"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="bo_origin_state"]').val('');
	            $('select[data-fieldname="bo_origin_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1461"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});


//Origin Destination cities the base on Dest countries


//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1283"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1283"]').val(); 
    
    if(countryId == "US" || countryId == "CA")
    {
    	$('select[data-fieldname="bo_destination_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1463"]').val('');
                  $('select[data-fieldname="cf_1463"]').prop('disabled', true);
                  $('select[data-fieldname="bo_destination_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
       $('select[data-fieldname="cf_1463"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="bo_destination_state"]').val('');
	            $('select[data-fieldname="bo_destination_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1463"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});

//Origin City select on the base of Origin States
$('select[data-fieldname="bo_origin_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1281"]').val(); 
    var origin_state_code =   $('select[data-fieldname="bo_origin_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1461"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1461"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});


$('select[data-fieldname="bo_destination_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1283"]').val(); 
    var origin_state_code =   $('select[data-fieldname="bo_destination_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1463"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1463"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});