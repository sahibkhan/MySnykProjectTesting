
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
			$('#ArchiveReceived').prop('disabled', !$('#ArchiveReceived').prop('disabled'));
			$('#ReturnedforAdditionalUploading').prop('disabled', !$('#ReturnedforAdditionalUploading').prop('disabled'));
		}
		else{
			// {if $PICKLIST_VALUE eq "Request For Cancellation" || $PICKLIST_VALUE eq "Request For Revision" || $PICKLIST_VALUE eq "No Costing" || $PICKLIST_VALUE eq "In Progress"}
			$('#Cancelled').prop('disabled', !$('#Cancelled').prop('disabled'));
			$('#Revision').prop('disabled', !$('#Revision').prop('disabled'));
			$('#NoCosting').prop('disabled', !$('#NoCosting').prop('disabled'));
			$('#InProgress').prop('disabled', !$('#InProgress').prop('disabled'));

			//For Archive
			$('#PassedtoArchive').prop('disabled', !$('#PassedtoArchive').prop('disabled'));
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
    var editId = jQuery('[name="record"]').val();
	var mode = $('#Job_Edit_fieldName_cf_1711').val();
  
	
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


}

//hide the block Air Shipments,Sea Shipments by default
$("div[data-block='Air Shipments']").hide();
$("div[data-block='Sea Shipments']").hide();
//Disable Origin Country Id ,Origin City Code Origin UNLOCODE 
$('#Job_editView_fieldName_job_air_origin_country_id').prop('readonly', true);
$('#Job_editView_fieldName_job_air_origin_city_code').prop('readonly', true);
$('#Job_editView_fieldName_job_air_origin_unlocode').prop('readonly', true);
//Disable Destination Country Id ,Destination City Code Destination UNLOCODE 
$('#Job_editView_fieldName_job_air_destination_country_id').prop('readonly', true);
$('#Job_editView_fieldName_job_air_destination_city_code').prop('readonly', true);
$('#Job_editView_fieldName_job_air_destination_unlocode').prop('readonly', true);


  
//puplate function for show the block on the base of Mode Select by sahib khan
    $('#Job_Edit_fieldName_cf_1711').change(function(ev){
 	    ev.preventDefault();
        puplateOnchange();
    });


//puplate origin city on the base of origin country by sahib Block LIFTSIDE
//origin_country=job_air_origin_country
$('select[data-fieldname="job_air_origin_country"]').change(function(e){
   // e.preventDefault();

    var countryId =  $('select[data-fieldname="job_air_origin_country"]').val(); 
    if(countryId!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {country_id:countryId} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="job_air_origin_city"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
    //puplateOnchange();

});

//puplate portCode on the base of origin city by sahib
//origin_city =job_air_origin_city
$('select[data-fieldname="job_air_origin_city"]').change(function(e){
    //e.preventDefault();

    var cityId =  $('select[data-fieldname="job_air_origin_city"]').val();
    var country =  $('select[data-fieldname="job_air_origin_country"]').val(); 
 
    if(cityId!='')
    {
    	
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {city_id:cityId,country:country} ,
           success : function(data) {
          
                   $('select[data-fieldname="job_air_origin_port_code"]').html(data);	
                 	$('select').select2();
                },error:function(e){
	               alert("error");}

        });
    } 
    // puplateOnchange();

});
//puplate airPortName on the base of origin portCode by sahib
//postcode= job_air_origin_port_code
$('select[data-fieldname="job_air_origin_port_code"]').change(function(e){
   // e.preventDefault();
    var city =  $('select[data-fieldname="job_air_origin_city"]').val();
    var country =  $('select[data-fieldname="job_air_origin_country"]').val(); 
    var air_port_code_iata =  $('select[data-fieldname="job_air_origin_port_code"]').val(); 

   if(air_port_code_iata!='')
    {
      

	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {air_port_code_iata:air_port_code_iata,city:city,country:country } ,
	           success : function(data) {
	            $('select[data-fieldname="job_air_origin_airport_name"]').html(data);
	             $('select').select2();	
	            }

	    });
     }
   // puplateOnchange();

});

//puplate value into read only field on the base of Air portName
//postcode= job_air_origin_airport_name
$('select[data-fieldname="job_air_origin_airport_name"]').change(function(e){
    e.preventDefault();
     var air_port_code =  $('select[data-fieldname="job_air_origin_airport_name"]').val(); 

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
	           	document.getElementById("Job_editView_fieldName_job_air_origin_country_id").value= data[0].id;
	           	document.getElementById("Job_editView_fieldName_job_air_origin_city_code").value= data[0].city_code;
	           	document.getElementById("Job_editView_fieldName_job_air_origin_unlocode").value= data[0].air_unlocode;

	            }

	    });
    }
       
   // puplateOnchange();

});
//END LIEF SIDE BLOCK ORIGIN 


//puplate Destination  city on the base of Destination  country by sahib
$('select[data-fieldname="job_air_destination_country"]').change(function(e){
    e.preventDefault();
    var DestcountryId =  $('select[data-fieldname="job_air_destination_country"]').val();
    //populating the Destination cities on the base of Dest.country
    if(DestcountryId!='')
    {  
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destcountry_id:DestcountryId} ,
	           success : function(data) {
	                   $('select[data-fieldname="job_air_destination_city"]').html(data);	
	                    $('select').select2();		
	                }
	    });
    }
  //  puplateOnchange();

});

//puplate dest.IATA Code
$('select[data-fieldname="job_air_destination_city"]').change(function(e){
    e.preventDefault();
     var DestcountryId =  $('select[data-fieldname="job_air_destination_country"]').val();
    var DestcityCityCode =  $('select[data-fieldname="job_air_destination_city"]').val(); 

    //populating the IATA Destination Port Code on the base of city
    if(DestcityCityCode!='')
    {  

	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {DestcityCityCode:DestcityCityCode,DestcountryId:DestcountryId} ,
	           success : function(data) {
	                   $('select[data-fieldname="job_air_destination_port_code"]').html(data);	
	                    $('select').select2();		
	                }
	    });
    }
    //puplateOnchange();

});
//puplate dest.Airport Name
$('select[data-fieldname="job_air_destination_port_code"]').change(function(e){
    e.preventDefault();
    var DestcountryId =  $('select[data-fieldname="job_air_destination_country"]').val();
    var DestcityCityCodeID =  $('select[data-fieldname="job_air_destination_city"]').val(); 
    var destination_port_code =  $('select[data-fieldname="job_air_destination_port_code"]').val();

    //populating the Destination Air Port on the base of IATA Dest. Port Code 
    if(destination_port_code!='')
    {  
	     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destination_port_code:destination_port_code,DestcountryId:DestcountryId,DestcityCityCodeID:DestcityCityCodeID} ,
	           success : function(data) {
	           	
	                   $('select[data-fieldname="job_air_destination_airport_name"]').html(data);
	                   $('select').select2();			
	                }
	    });
    }
   // puplateOnchange();

});
//fill text  dest. on the base of Destination Airport
$('select[data-fieldname="job_air_destination_airport_name"]').change(function(e){
    e.preventDefault();
   //Fill value into read only field on the base of  Destination Air Port code
   var destairport_code =  $('select[data-fieldname="job_air_destination_airport_name"]').val();
   if(destairport_code!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {destairport_code:destairport_code} ,
	           dataType: 'json',
	           success : function(data) {
	           //	alert(JSON.stringify(data));

				document.getElementById("Job_editView_fieldName_job_air_destination_country_id").value= data[0].id;
				document.getElementById("Job_editView_fieldName_job_air_destination_city_code").value= data[0].city_code;
				document.getElementById("Job_editView_fieldName_job_air_destination_unlocode").value= data[0].air_unlocode;
				$('select').select2();
	        }

	    });
    }

});


//sea Block
function puplateSeaBlock(){
	var seacountryId =  $('select[data-fieldname="cf_8588"]').val(); 
    if(seacountryId!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {country_id:seacountryId} ,
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

