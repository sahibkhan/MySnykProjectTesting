
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

//Air Origin Country select the base of General Origin Country
$('select[data-fieldname="cf_1504"]').change(function(e){
   // e.preventDefault();

    var General_Code =  $('select[data-fieldname="cf_1504"]').val(); 
    console.log(General_Code);
    if(General_Code!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {General_Code:General_Code} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="job_air_origin_country"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
   

});

//origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1504"]').change(function(e){
   // e.preventDefault();

    var countryId =  $('select[data-fieldname="cf_1504"]').val(); 
    if(countryId!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {country_id:countryId} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="cf_1508"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }

});





//puplate origin city on the base of origin country by sahib Block LIFTSIDE
//origin_country=job_air_origin_country
$('select[data-fieldname="cf_1508"]').change(function(e){
   // e.preventDefault();

    var GenralCitySelect =  $('select[data-fieldname="cf_1508"]').val(); 
    if(GenralCitySelect!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {GenralCitySelect:GenralCitySelect} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="job_air_origin_city"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }


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


//General Destination Country: Destination_country 
$('select[data-fieldname="cf_1506"]').change(function(e){
   // e.preventDefault();

    var Dest_General_Code =  $('select[data-fieldname="cf_1506"]').val(); 
    console.log(Dest_General_Code);
    if(Dest_General_Code!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {Dest_General_Code:Dest_General_Code} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="job_air_destination_country"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
   

});


//Destination city select on the base General Destination Country
$('select[data-fieldname="cf_1506"]').change(function(e){
   // e.preventDefault();

    var countryId =  $('select[data-fieldname="cf_1506"]').val(); 
    if(countryId!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {country_id:countryId} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="cf_1510"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }

});





//puplate Destination  city on the base of Destination  country by sahib
$('select[data-fieldname="cf_1510"]').change(function(e){
    e.preventDefault();
    var DestSelectCity =  $('select[data-fieldname="cf_1510"]').val();
    //populating the Destination cities on the base of Dest.country
    if(DestSelectCity!='')
    {  
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {DestSelectCity:DestSelectCity} ,
	           success : function(data) {
	                   $('select[data-fieldname="job_air_destination_city"]').html(data);	
	                    $('select').select2();		
	                }
	    });
    }
  

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


//sea_loading_country=job_sea_loading_country
$('select[data-fieldname="job_sea_loading_country"]').change(function(e){
   // e.preventDefault();

    var port_country_code =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
    if(port_country_code!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {port_country_code:port_country_code} ,
	           success : function(data) {
	          //console.log(data);
	          
	            $('select[data-fieldname="job_sea_loading_city"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
   

});

//puplate portCode on the base of  job_sea_loading_city by sahib
$('select[data-fieldname="job_sea_loading_city"]').change(function(e){
    //e.preventDefault();

    var port_city_code =  $('select[data-fieldname="job_sea_loading_city"]').val();
    var portcountrycode =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
 
    if(port_city_code!='')
    {
    	
        $.ajax({
           type: "POST",
           url : "include/Job/getseadata.php",
           data:  {port_city_code:port_city_code,portcountrycode:portcountrycode} ,
           success : function(data) {
          
                   $('select[data-fieldname="job_sea_loading_port_code"]').html(data);	
                 	$('select').select2();
                },error:function(e){
	               alert("error");}

        });
    } 
  

});

//puplate value into read only field on the base of Air portName
//postcode= job_air_origin_airport_name
$('select[data-fieldname="job_sea_loading_port_code"]').change(function(e){
    e.preventDefault();
     var portcitycode =  $('select[data-fieldname="job_sea_loading_city"]').val();
     var portcountrycode =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
     var port_unlocode =  $('select[data-fieldname="job_sea_loading_port_code"]').val(); 
     console.log(port_unlocode);
    	//Fill value into read only field on the base of  Origin Air Port code
   if(port_unlocode!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {port_unlocode:port_unlocode,portcitycode:portcitycode,portcountrycode:portcountrycode} ,
	           dataType: 'json',
	           success : function(data) {
	           	  
	           //	alert(JSON.stringify(data));
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_country_id").value= data[0].port_country_code;
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_city_id").value= data[0].port_city_code;
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_unlocode").value= data[0].port_unlocode;
                $('select').select2();
	            }

	    });
    }
       
  

});

 //discharge seablock start here   
$('select[data-fieldname="job_sea_discharge_country"]').change(function(e){
   // e.preventDefault();
    
    var dis_country_code =  $('select[data-fieldname="job_sea_discharge_country"]').val(); 

    if(dis_country_code!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {dis_country_code:dis_country_code} ,
	           success : function(data) {
	          //console.log(data);
	          
	            $('select[data-fieldname="job_sea_discharge_city"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
   

});

//puplate portCode on the base of  job_sea_loading_city by sahib
$('select[data-fieldname="job_sea_discharge_city"]').change(function(e){
    //e.preventDefault();

    var dis_port_city_code =  $('select[data-fieldname="job_sea_discharge_city"]').val();
    var dis_portcountrycode =  $('select[data-fieldname="job_sea_discharge_country"]').val(); 
    if(dis_port_city_code!='')
    {
    	
        $.ajax({
           type: "POST",
           url : "include/Job/getseadata.php",
           data:  {dis_port_city_code:dis_port_city_code,dis_portcountrycode:dis_portcountrycode} ,
           success : function(data) {
          
                   $('select[data-fieldname="job_sea_discharge_port_code"]').html(data);	
                 	$('select').select2();
                },error:function(e){
	               alert("error");}

        });
    } 
  

});

$('select[data-fieldname="job_sea_discharge_port_code"]').change(function(e){
    e.preventDefault();
    var disport_city_code =  $('select[data-fieldname="job_sea_discharge_city"]').val();
    var disportcountrycode =  $('select[data-fieldname="job_sea_discharge_country"]').val();
    var dis_port_unlocode =  $('select[data-fieldname="job_sea_discharge_port_code"]').val(); 
     
    	//Fill value into read only field on the base of  code
   if(dis_port_unlocode!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {dis_port_unlocode:dis_port_unlocode,disport_city_code:disport_city_code,disportcountrycode:disportcountrycode},
	           dataType: 'json',
	           success : function(data) {
	           	  
	           //	alert(JSON.stringify(data));
	           	document.getElementById("Job_editView_fieldName_job_sea_discharge_country_id").value= data[0].port_country_code;
	           	document.getElementById("Job_editView_fieldName_job_sea_discharge_city_id").value= data[0].port_city_code;
	           	document.getElementById("Job_editView_fieldName_job_sea_discharge_unlocode").value= data[0].port_unlocode;
                 $('select').select2();
	            }

	    });
    }

  

});
