
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Job_Edit_Js",{

 
       
	//jQuery('[name="cf_6008"]').prop("readonly", true);
	//jQuery("[name='cf_1198']").prop("readonly", true);

	//jQuery("[data-name='assigned_user_id']").prop('disabled', true);
	//

	//var cur_record = jQuery('[name="record"]').val();
	//if (cur_record.length > 0){
	//	jQuery('[name="cf_3527"]').prop('disabled', true);
	////	jQuery('[name="cf_3527"]').trigger('liszt:updated');
	//}



},{


	/**
	 * Function which will map the address details of the selected record
	 */
	registerRecordPreSaveJob : function(container){

		var thisInstance = this;
		var swapMode;
		jQuery('form[id="EditView"]').on('submit',function(e){
             
			//cf_5986 :: time of arrival(TA)
			//cf_4805 :: Completion date
			var f_name_i = "";
			var f_label_i = "";

			var job_status = $('[name="cf_2197"] :selected').val();
			if (job_status === 'Completed') {

				if ($("[name='cf_5986']").val().length == 0){
					f_name_i = "Time of Arrival (TA)";
					f_label_i = "cf_5986";
				}

				if (f_name_i.length > 0){
				//var editViewForm = this.getForm();
				//editViewForm.find('.saveButton').attr('disabled',false);
				//alert("please fills");
				//$('.saveButton').prop('disabled',false);

				//app.helper.showAlertBox({'message':app.vtranslate("Please fill " + f_name_i + " field")});
				//$('form[id="EditView"]').find('.saveButton').attr('disabled',false);
				//alert("close me");
				//container.find("[name='cf_5986']").focus();
				return false;
				}

			}
		});
	},

	getJobStatus : function(container) {
		var self = this;
       
		jQuery('[name="cf_2197"]').change(function(){
			var job_status = $('[name="cf_2197"] :selected').val();
			if (job_status === 'Completed') {
				$('[name="cf_5986"]').attr("required", "required");

				if ($("[name='cf_1190']").val() == 85836){
					$('[name="cf_4935"]').attr("required", "required");
					$('[name="cf_4925"]').attr("required", "required");
					$('[name="cf_4945"]').attr("required", "required");
					$('[name="cf_1096"]').attr("required", "required");
					$('[name="cf_2387"]').attr("required", "required");
					$('[name="cf_4923"]').attr("required", "required");
					$('[name="cf_1591"]').attr("required", "required");
					$('[name="cf_5417"]').attr("required", "required");
					$('[name="cf_4805"]').attr("required", "required");
				}

			}else{
				$('[name="cf_5986"]').removeAttr("required");
                $('[name="cf_4935"]').removeAttr("required");
                $('[name="cf_4925"]').removeAttr("required");
                $('[name="cf_4945"]').removeAttr("required");
                $('[name="cf_1096"]').removeAttr("required");
                $('[name="cf_2387"]').removeAttr("required");
                $('[name="cf_4923"]').removeAttr("required");
                $('[name="cf_1591"]').removeAttr("required");
                $('[name="cf_5417"]').removeAttr("required");
                $('[name="cf_4805"]').removeAttr("required");
			}
		});
	},
	// change by Azhar 13 July 2022
	getSeaAirBlock : function(container) {

		var self = this;
		jQuery('[name="cf_2197"]').change(function(){
			
		});
		
	},
	/**
	 * Function which will register basic events which will be used in quick create as well
	 *
	 */
	registerBasicEvents : function(container) {
	// puplate funtion call here for edit document by sahib khan
		puplateOnchange();
  
		this._super(container);
		jQuery("[name='assigned_user_id']").prop('disabled', true);
		jQuery('#Job_editView_fieldName_cf_6008').prop("readonly", true);
		jQuery("#Job_editView_fieldName_cf_1198").prop("readonly", true);
		jQuery("#Job_editView_fieldName_cf_4805").prop("disabled", true);


		$('[name="cf_6934"]').prop('disabled', true);
		$('[name="cf_6934"]').trigger('change.select2');
		$('[name="cf_6936"]').prop('disabled', true);
		$('[name="cf_6936"]').trigger('change.select2');



		if(_USERMETA['id'] =='405' || _USERMETA['id']=='420')
		{
			$('#RequestForCancellation').prop('disabled', !$('#RequestForCancellation').prop('disabled'));
			$('#RequestForRevision').prop('disabled', !$('#RequestForRevision').prop('disabled'));
			$('#NoCosting').prop('disabled', !$('#NoCosting').prop('disabled'));
			$('#InProgress').prop('disabled', !$('#InProgress').prop('disabled'));

			//For Archive
			$('#PassedtoArchive').prop('disabled', !$('#PassedtoArchive').prop('disabled'));
		}
		else{
			// {if $PICKLIST_VALUE eq "Request For Cancellation" || $PICKLIST_VALUE eq "Request For Revision" || $PICKLIST_VALUE eq "No Costing" || $PICKLIST_VALUE eq "In Progress"}
			$('#Cancelled').prop('disabled', !$('#Cancelled').prop('disabled'));
			$('#Revision').prop('disabled', !$('#Revision').prop('disabled'));
			$('#NoCosting').prop('disabled', !$('#NoCosting').prop('disabled'));
			$('#InProgress').prop('disabled', !$('#InProgress').prop('disabled'));

			//For Archive
			$('#ArchiveReceived').prop('disabled', !$('#ArchiveReceived').prop('disabled'));
			$('#ReturnedforAdditionalUploading').prop('disabled', !$('#ReturnedforAdditionalUploading').prop('disabled'));
		}
		$('[name="cf_2197"]').trigger('change.select2');
		//$('select').select2();

		//jQuery("[name='assigned_user_id']").prop('disabled', true);
		//jQuery("[name='assigned_user_id']").trigger('select2');
		var cur_record = jQuery('[name="record"]').val();
		//alert('yes here'+cur_record);
		if (cur_record.length > 0){
			jQuery('[name="cf_3527"]').prop('disabled', true);
			jQuery('[name="cf_3527"]').trigger('change.select2');
		}
		//this.registerRecordPreSaveJob(container);
		this.getJobStatus(container);
	}


});


//Added onchange function for the  populating the block on the base of mode by Sahib Khan
function puplateOnchange(){
    //populating the block on the base of mode 
	var mode = $('#Job_Edit_fieldName_cf_1711').val();

	//variable populating the Origin cities on the base of country
	var countryId =  $('select[data-fieldname="cf_8512"]').val(); 
	var cityId =  $('select[data-fieldname="cf_8652"]').val(); 
	var air_unlocodeId =  $('select[data-fieldname="cf_8560"]').val(); 
    var air_port_code =  $('select[data-fieldname="cf_8564"]').val(); 
	//variable populating the Destination cities on the base of country
	var DestcountryId =  $('select[data-fieldname="cf_8514"]').val();
	var DestcityId =  $('select[data-fieldname="cf_8518"]').val(); 
	var destAircodeId =  $('select[data-fieldname="cf_8562"]').val(); 
	var destair_port_code =  $('select[data-fieldname="cf_8566"]').val();
	// console.log(mode);
    $("div[data-block='Air Shipments']").hide();
    $("div[data-block='Sea Shipments']").hide();
    if (Array.isArray(mode)) {
	    for(var i = 0; i < mode.length; i++)
	    {
	    	
		   if(mode[i] == 'Air'){
		    	//console.log(mode[i]);
		        // hide BLOCK 
		        $("div[data-block='Air Shipments']").show();
		    }else if (mode[i] == 'Ocean'){
		        // hide BLOCK       
		        $("div[data-block='Sea Shipments']").show();
		    }else if(mode[i] == 'Air/Sea'){
		    	$("div[data-block='Air Shipments']").show();
		    	$("div[data-block='Sea Shipments']").show();
		    }
		}    
    }
   
    if(countryId!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {country_id:countryId} ,
	           success : function(data) {
	           //	console.log(data);
	                   $('select[data-fieldname="cf_8652"]').html(data);		
	                }

	    });
    }
//populating the IATA Origin Port Code on the base of city
    if(cityId!='')
    {
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {city_id:cityId} ,
           success : function(data) {
           		//console.log(data);
                   $('select[data-fieldname="cf_8560"]').html(data);		
                }

        });
    }
//populating the Air Port on the base of IATA Origin Port code
   if(air_unlocodeId!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {air_unlocode:air_unlocodeId} ,
	           success : function(data) {
	           	//console.log(data);
	                   $('select[data-fieldname="cf_8564"]').html(data);		
	                }

	    });
    }

    //Fill value into read only field on the base of  Origin Air Port code
   if(air_port_code!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {air_port_code:air_port_code} ,
	           dataType: 'json',
	           success : function(data) {
	           //	alert(JSON.stringify(data));
	           	console.log(data[0].country_name);
	           	document.getElementById("Job_editView_fieldName_cf_8568").value= data[0].id;
	           	document.getElementById("Job_editView_fieldName_cf_8650").value= data[0].city_code;
	           	document.getElementById("Job_editView_fieldName_cf_8576").value= data[0].air_unlocode;

	            }

	    });
    }


  //Destination start from here  
//populating the Destination cities on the base of Dest.country
    if(DestcountryId!='')
    {  
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destcountry_id:DestcountryId} ,
	           success : function(data) {
	                   $('select[data-fieldname="cf_8518"]').html(data);		
	                }
	    });
    }

//populating the IATA Destination Port Code on the base of city
    if(DestcityId!='')
    {  

	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destcity_id:DestcityId} ,
	           success : function(data) {
	                   $('select[data-fieldname="cf_8562"]').html(data);		
	                }
	    });
    }
//populating the Destination Air Port on the base of IATA Dest. Port Code 
    if(destAircodeId!='')
    {  
	     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destAircodeId:destAircodeId} ,
	           success : function(data) {
	           //	console.log(data);
	                   $('select[data-fieldname="cf_8566"]').html(data);		
	                }
	    });
    }


  //Fill value into read only field on the base of  Destination Air Port code
   if(destair_port_code!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destair_port_code:destair_port_code} ,
	           dataType: 'json',
	           success : function(data) {
	           //	alert(JSON.stringify(data));
	           	console.log(data[0].country_name);
	           	 document.getElementById("Job_editView_fieldName_cf_8570").value= data[0].id;
	           	 document.getElementById("Job_editView_fieldName_cf_8574").value= data[0].city_code;
	           	 document.getElementById("Job_editView_fieldName_cf_8578").value= data[0].air_unlocode;

	            }

	    });
    }


}

//hide the block Air Shipments,Sea Shipments by default
$("div[data-block='Air Shipments']").hide();
$("div[data-block='Sea Shipments']").hide();
//Disable Origin Country Id ,Origin City Code Origin UNLOCODE 
$('#Job_editView_fieldName_cf_8568').prop('readonly', true);
$('#Job_editView_fieldName_cf_8650').prop('readonly', true);
$('#Job_editView_fieldName_cf_8576').prop('readonly', true);
//Disable Destination Country Id ,Destination City Code Destination UNLOCODE 
$('#Job_editView_fieldName_cf_8570').prop('readonly', true);
$('#Job_editView_fieldName_cf_8574').prop('readonly', true);
$('#Job_editView_fieldName_cf_8578').prop('readonly', true);


  
//puplate function for show the block on the base of Mode Select by sahib khan
    $('#Job_Edit_fieldName_cf_1711').change(function(ev){
 	    ev.preventDefault();
        puplateOnchange();
    });


//puplate origin city on the base of origin country by sahib
//origin_country=cf_8512
$('select[data-fieldname="cf_8512"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});

//puplate portCode on the base of origin city by sahib
//origin_city =cf_8652
$('select[data-fieldname="cf_8652"]').change(function(e){
    e.preventDefault();
     puplateOnchange();

});
//puplate airPortName on the base of origin portCode by sahib
//postcode= cf_8560
$('select[data-fieldname="cf_8560"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});

//puplate value into read only field on the base of Air portName
//postcode= cf_8564
$('select[data-fieldname="cf_8564"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});



//puplate Destination  city on the base of Destination  country by sahib
$('select[data-fieldname="cf_8514"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});

//puplate dest.IATA Code
$('select[data-fieldname="cf_8518"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});
//puplate dest.IATA Code
$('select[data-fieldname="cf_8562"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});
//fill text  dest. on the base of Destination Airport
$('select[data-fieldname="cf_8566"]').change(function(e){
    e.preventDefault();
    puplateOnchange();

});


//sea Block
function puplateSeaBlock(){
	var countryId =  $('select[data-fieldname="cf_8588"]').val(); 
    if(countryId!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {country_id:countryId} ,
	           success : function(data) {
	           		//console.log(data);
	                   $('select[data-fieldname="cf_8592"]').html(data);		
	                }
	                //,error:function(e){
	               // alert("error");}

	        });
    }
}
//Block Sea onchange base on load country and discharge country

$('select[data-fieldname="cf_8588"]').change(function(e){
    e.preventDefault();
    puplateSeaBlock();
});
$('select[data-fieldname="cf_8596"]').change(function(e){
    e.preventDefault();
    puplateSeaBlock();
});

