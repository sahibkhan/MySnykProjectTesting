
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

	puplateOnchange : function(container) {
		var self = this;

		var editId = jQuery('[name="record"]').val();
		var job_origin_countryid    =  jQuery('select[data-fieldname="cf_1504"]').val(); 
		var job_origin_states = jQuery('[name="job_origin_states"]').val();
      	var job_air_origin_state = jQuery('[name="job_air_origin_state"]').val();
      	var job_sea_loading_state = jQuery('[name="job_sea_loading_state"]').val();
		  if(job_origin_states=='' && (job_origin_countryid != "US" && job_origin_countryid != "CA"))
		  {
			   $('select[data-fieldname="job_origin_states"]').prop('disabled', true);
			   
		  }if(job_air_origin_state=='')
		  {
			   $('select[data-fieldname="job_air_origin_state"]').prop('disabled', true);
		  }
		  if(job_sea_loading_state=='')
		  {
			   $('select[data-fieldname="job_sea_loading_state"]').prop('disabled', true);
		  }
		  
		  var job_destination_countryid    =  jQuery('select[data-fieldname="cf_1506"]').val(); 
		  var job_destination_states = jQuery('[name="job_destination_states"]').val();
		  var job_air_destination_state = jQuery('[name="job_air_destination_state"]').val();
		  var job_sea_discharge_state = jQuery('[name="job_sea_discharge_state"]').val();
		  if(job_destination_states=='' && (job_destination_countryid != "US" && job_destination_countryid != "CA"))
		  {
			   $('select[data-fieldname="job_destination_states"]').prop('disabled', true);
			   
		  }if(job_air_destination_state=='')
		  {
			   $('select[data-fieldname="job_air_destination_state"]').prop('disabled', true);
		  }
		  if(job_sea_discharge_state=='')
		  {
			   $('select[data-fieldname="job_sea_discharge_state"]').prop('disabled', true);
		  }
		
		var mode = $('#Job_Edit_fieldName_cf_1711').val();
		$("div[data-block='Air Shipment']").hide();
		$("div[data-block='Sea Shipment']").hide();
		if (Array.isArray(mode)) {
			for(var i = 0; i < mode.length; i++)
			{
				
			if(mode[i] == 'Air'){
					//console.log(mode[i]);
					// hide BLOCK 
					$("div[data-block='Air Shipment']").show();
				}else if (mode[i] == 'Ocean'){
					// hide BLOCK       
					$("div[data-block='Sea Shipment']").show();
				}else if(mode[i] == 'Air/Sea'){
					$("div[data-block='Air Shipment']").show();
					$("div[data-block='Sea Shipment']").show();
				}
			}    
		}
		
		//jQuery('#Job_Edit_fieldName_cf_1711').on('change',function(){
		//	this.puplateOnchange();
		//})

	},

	
	modeOnchange : function(container) {
		var self = this;
		//alert('yes am here to');
		jQuery('#Job_Edit_fieldName_cf_1711').on('change',function(){
			//alert('yes am here');
			//ev.preventDefault();
			var mode = $('#Job_Edit_fieldName_cf_1711').val();
			$("div[data-block='Air Shipment']").hide();
			$("div[data-block='Sea Shipment']").hide();
			if (Array.isArray(mode)) {
				for(var i = 0; i < mode.length; i++)
				{
					
				if(mode[i] == 'Air'){
						//console.log(mode[i]);
						// hide BLOCK 
						$("div[data-block='Air Shipment']").show();
					}else if (mode[i] == 'Ocean'){
						// hide BLOCK       
						$("div[data-block='Sea Shipment']").show();
					}else if(mode[i] == 'Air/Sea'){
						$("div[data-block='Air Shipment']").show();
						$("div[data-block='Sea Shipment']").show();
					}
				}    
			}
		});


			//General Block 

			//General Origin States and General  Destination States disable by default 
			//Origin States and origin_cite select on the base General Origin Country
			jQuery('select[data-fieldname="cf_1504"]').on('change',function(){
				
				$('select[data-fieldname="job_origin_states"]').prop('disabled', true);
				$('select[data-fieldname="job_air_origin_state"]').prop('disabled', true);

				var countryId    =  $('select[data-fieldname="cf_1504"]').val(); 
    			var General_Code =  $('select[data-fieldname="cf_1504"]').val(); 

				if(General_Code!='')
    			{
					$('select[data-fieldname="job_air_origin_country"]').val(General_Code).attr("selected", "selected");
					$('[name="job_air_origin_country"]').trigger('change.select2');
					$('select[data-fieldname="job_sea_loading_country"]').val(General_Code).attr("selected", "selected");
					$('[name="job_sea_loading_country"]').trigger('change.select2');
					
				} 


				if(countryId == "US" || countryId == "CA")
				{
					$('select[data-fieldname="job_origin_states"]').prop('disabled', false);
					$('select[data-fieldname="job_air_origin_state"]').prop('disabled', false);
					$('select[data-fieldname="job_sea_loading_state"]').prop('disabled', false);

						$.ajax({
							type: "POST",
							url : "include/Job/getairdata.php",
							data:  {country_states:countryId} ,
							success : function(data) 
							{
			
								$('select[data-fieldname="cf_1508"]').val('');
								$('select[data-fieldname="cf_1508"]').prop('disabled', true);
								$('select[data-fieldname="job_origin_states"]').html(data);
								//air state
								$('select[data-fieldname="job_air_origin_city"]').val('');
								$('select[data-fieldname="job_air_origin_city"]').prop('disabled', true);
								$('select[data-fieldname="job_air_origin_state"]').html(data);
								//sea state
								//air state
								$('select[data-fieldname="job_sea_loading_city"]').val('');
								$('select[data-fieldname="job_sea_loading_city"]').prop('disabled', true);
								$('select[data-fieldname="job_sea_loading_state"]').html(data);
			
								$('select').select2();
							},error:function(e){
								alert("error");}
					});  
				}
				else{

					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {general_country_id:countryId} ,
						success : function(data) 
						{
		 
							 $('select[data-fieldname="job_origin_states"]').val('');
							 $('select[data-fieldname="job_origin_states"]').prop('disabled', true);
							 //air state
							 $('select[data-fieldname="job_air_origin_state"]').val('');
							 $('select[data-fieldname="job_air_origin_state"]').prop('disabled', true);
							 //Sea state
							 $('select[data-fieldname="job_sea_loading_state"]').val('');
							 $('select[data-fieldname="job_sea_loading_state"]').prop('disabled', true);
		 
		 
							 $('select[data-fieldname="cf_1508"]').html(data);
							 $('select').select2();
						 },error:function(e){
							alert("error");}
				 	});
				}

			});

			//Origin City select on the base of Origin States
			$('select[data-fieldname="job_origin_states"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="cf_1504"]').val(); 
				var origin_state_code =   $('select[data-fieldname="job_origin_states"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_origin_states"]').val();

				if(origin_state_code!='' && port_country_code!='')
				{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
						success : function(data) 
							 {
								 
								 $('select[data-fieldname="cf_1508"]').prop('disabled', false);
								 $('select[data-fieldname="job_air_origin_city"]').val('');
								 $('select[data-fieldname="job_air_origin_city"]').prop('disabled', false);
								 $('select[data-fieldname="job_sea_loading_city"]').prop('disabled', false);
								 $('select[data-fieldname="cf_1508"]').html(data); 
								 $('select').select2();
							 },error:function(e){
								alert("error");}
			 
					 });
				}

				if(air_origin_state_code_general!=''){

					$.ajax({
					  type: "POST",
					  url : "include/Job/getairdata.php",
					  data:  {air_origin_state_code_general:air_origin_state_code_general,port_country_code:port_country_code} ,
					  success : function(data) 
					  {
							 
						   $('select[data-fieldname="job_air_origin_state"]').html(data); 
						   $('select[data-fieldname="job_sea_loading_state"]').html(data); 
						   $('select').select2();
					   },error:function(e){
							  alert("error");}
		   
				   });
			   }
			});

			//Origin side End here Destination side start here

			$('select[data-fieldname="cf_1506"]').on('change',function(){

				$('select[data-fieldname="job_destination_states"]').prop('disabled', true);
				$('select[data-fieldname="job_air_destination_state"]').prop('disabled', true);

				var countryId =  $('select[data-fieldname="cf_1506"]').val(); 
				var Dest_General_Code =  $('select[data-fieldname="cf_1506"]').val(); 
				if(Dest_General_Code!='')
				{
					$('select[data-fieldname="job_air_destination_country"]').val(Dest_General_Code).attr("selected", "selected");
					$('[name="job_air_destination_country"]').trigger('change.select2');
					$('select[data-fieldname="job_sea_discharge_country"]').val(Dest_General_Code).attr("selected", "selected");
					$('[name="job_sea_discharge_country"]').trigger('change.select2');
				}

				if(countryId == "US" || countryId == "CA")
    			{
					$('select[data-fieldname="job_destination_states"]').prop('disabled', false);
					$('select[data-fieldname="job_air_destination_state"]').prop('disabled', false);
					$('select[data-fieldname="job_sea_discharge_state"]').prop('disabled', false);

					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {country_states:countryId} ,
						success : function(data) 
						{
						   $('select[data-fieldname="cf_1510"]').val('');
						   $('select[data-fieldname="cf_1510"]').prop('disabled', true);
						   $('select[data-fieldname="job_destination_states"]').html(data);
						   //air state
						   $('select[data-fieldname="job_air_destination_city"]').val('');
						   $('select[data-fieldname="job_air_destination_city"]').prop('disabled', true);
						   $('select[data-fieldname="job_air_destination_state"]').html(data);
						   //sea state
						   $('select[data-fieldname="job_sea_discharge_city"]').val('');
						   $('select[data-fieldname="job_sea_discharge_city"]').prop('disabled', true);
						   $('select[data-fieldname="job_sea_discharge_state"]').html(data);
						   $('select').select2();
						 },error:function(e){
							alert("error");}
				 	});  
				}
				else{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {general_country_id:countryId} ,
						success : function(data) 
						{
							 $('select[data-fieldname="job_destination_states"]').val('');
							 $('select[data-fieldname="job_destination_states"]').prop('disabled', true);
							 $('select[data-fieldname="cf_1510"]').html(data);       
							 //air state
							 $('select[data-fieldname="job_air_destination_state"]').val('');
							 $('select[data-fieldname="job_air_destination_state"]').prop('disabled', true);
							 //Sea state
							 $('select[data-fieldname="job_sea_discharge_state"]').val('');
							 $('select[data-fieldname="job_sea_discharge_state"]').prop('disabled', true);
							 $('select').select2();
						 },error:function(e){
							alert("error");}
		 
				 	});
				}	
			});

			//destination City select on the base of States
			$('select[data-fieldname="job_destination_states"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="cf_1506"]').val(); 
    			var origin_state_code =   $('select[data-fieldname="job_destination_states"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_destination_states"]').val();
				if(origin_state_code!='' && port_country_code!='')
    			{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
						success : function(data) 
						{
							 $('select[data-fieldname="cf_1510"]').prop('disabled', false);
							 $('select[data-fieldname="job_air_destination_city"]').val('');
							 $('select[data-fieldname="job_air_destination_city"]').prop('disabled', false);
							 $('select[data-fieldname="job_sea_discharge_city"]').prop('disabled', false);
							 $('select[data-fieldname="cf_1510"]').html(data); 
							 $('select').select2();
						 },error:function(e){
								alert("error");}
					 }); 
				}

				if(air_origin_state_code_general!=''){

					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {air_origin_state_code_general:air_origin_state_code_general,port_country_code:port_country_code} ,
						success : function(data) 
							{               
								 $('select[data-fieldname="job_air_destination_state"]').html(data); 
								 $('select[data-fieldname="job_sea_discharge_state"]').html(data); 
								 $('select').select2();
							 },error:function(e){
								alert("error");}
			 
					 });
				 } 

			});


			//Air Origin Country select the base of General Origin Country
			$('select[data-fieldname="job_air_origin_country"]').on('change',function(){
				var Origin_Country_Code =  $('select[data-fieldname="job_air_origin_country"]').val();
				var countryId    =  $('select[data-fieldname="job_air_origin_country"]').val();  

				if(countryId == "US" || countryId == "CA")
				{
				
					$('select[data-fieldname="job_air_origin_state"]').prop('disabled', false);
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {country_states:countryId} ,
						success : function(data) 
						{
							
									//air state
									$('select[data-fieldname="job_air_origin_port_code"]').val('');
									$('select[data-fieldname="job_air_origin_city"]').val('');
									$('select[data-fieldname="job_air_origin_city"]').prop('disabled', true);
									$('select[data-fieldname="job_air_origin_state"]').html(data);        
									$('select').select2();
							},error:function(e){
							alert("error");}
					});     

				}
				else{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {country_id:countryId} ,
						success : function(data) {
							 $('select[data-fieldname="job_air_origin_state"]').val('');
							 $('select[data-fieldname="job_air_origin_state"]').prop('disabled', true);
							 $('select[data-fieldname="job_air_origin_city"]').prop('disabled', false);
							 $('select[data-fieldname="job_air_origin_city"]').html(data);
							 $('select').select2();
						 },error:function(e){
							alert("error");}
					 });
				}
			});

			$('select[data-fieldname="job_air_origin_state"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="job_air_origin_country"]').val(); 
				var origin_state_code =   $('select[data-fieldname="job_air_origin_state"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_air_origin_state"]').val();

				if(origin_state_code!='' && port_country_code!='')
				{
					
					$.ajax({
					   type: "POST",
					   url : "include/Job/getairdata.php",
					   data:  {origin_state_code:origin_state_code,port_country_code:port_country_code} ,
					   success : function(data) 
					   {
							$('select[data-fieldname="job_air_origin_city"]').prop('disabled', false);
							$('select[data-fieldname="job_air_origin_city"]').html(data); 
							$('select').select2();
						},error:function(e){
							   alert("error");}
			
					});
				} 
			});

			$('select[data-fieldname="job_air_origin_city"]').on('change',function(){
				var cityId =  $('select[data-fieldname="job_air_origin_city"]').val();
				var country =  $('select[data-fieldname="job_air_origin_country"]').val();
				var origin_airport_code =  $('select[data-fieldname="job_air_origin_city"]').val();

				if(origin_airport_code!='')
				{
					
					$.ajax({
					type: "POST",
					url : "include/Job/getairdata.php",
					data:  {AirportName_CODE:origin_airport_code,country:country} ,
					success : function(data) 
					{
							$('select[data-fieldname="job_air_origin_port_code"]').html(data);
							$('select').select2();
						},error:function(e){
							alert("error");}

					});
				} 
			});


			//Air Destination Country select the base of General Origin Country
			$('select[data-fieldname="job_air_destination_country"]').on('change',function(){
				var Dest_Destination_Code =  $('select[data-fieldname="job_air_destination_country"]').val(); 
    			var countryId    =  $('select[data-fieldname="job_air_destination_country"]').val();  

				if(countryId == "US" || countryId == "CA")
				{
				   
					$('select[data-fieldname="job_air_destination_state"]').prop('disabled', false);
					$.ajax({
						   type: "POST",
						   url : "include/Job/getairdata.php",
						   data:  {country_states:countryId} ,
						   success : function(data) 
						   {
							  
								//air state
								$('select[data-fieldname="job_air_destination_port_code"]').val('');
								$('select[data-fieldname="job_air_destination_city"]').val('');
								$('select[data-fieldname="job_air_destination_city"]').prop('disabled', true);
								$('select[data-fieldname="job_air_destination_state"]').html(data);
						  
							  $('select').select2();
							},error:function(e){
							   alert("error");}
					});     
			
				}else
				{ 
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {country_id:countryId} ,
						success : function(data) 
						 {						
							  $('select[data-fieldname="job_air_destination_state"]').val('');
							  $('select[data-fieldname="job_air_destination_state"]').prop('disabled', true);
							  $('select[data-fieldname="job_air_destination_city"]').prop('disabled', false);
							  $('select[data-fieldname="job_air_destination_city"]').html(data);
							  $('select').select2();
						  },error:function(e){
							alert("error");}
				 	});
				}

			});

			$('select[data-fieldname="job_air_destination_state"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="job_air_destination_country"]').val(); 
				var origin_state_code =   $('select[data-fieldname="job_air_destination_state"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_air_destination_state"]').val();

				if(origin_state_code!='' && port_country_code!='')
				{
					
					$.ajax({
					   type: "POST",
					   url : "include/Job/getairdata.php",
					   data:  {origin_state_code:origin_state_code,port_country_code:port_country_code} ,
					   success : function(data) 
					   {
						  
							$('select[data-fieldname="job_air_destination_city"]').prop('disabled', false);
							$('select[data-fieldname="job_air_destination_city"]').html(data); 
							$('select').select2();
						},error:function(e){
							   alert("error");}
			
					});
				} 
			});

			$('select[data-fieldname="job_air_destination_city"]').on('change',function(){
				var country =  $('select[data-fieldname="job_air_destination_country"]').val();
				var cityId =  $('select[data-fieldname="job_air_destination_city"]').val(); 
				var origin_airport_code =  $('select[data-fieldname="job_air_destination_city"]').val(); 

				if(origin_airport_code!='')
				{
				
					$.ajax({
					   type: "POST",
					   url : "include/Job/getairdata.php",
					   data:  {AirportName_CODE:origin_airport_code,country:country} ,
					   success : function(data) 
					   {
							$('select[data-fieldname="job_air_destination_port_code"]').html(data);	
							$('select').select2();
						},error:function(e){
							   alert("error");}
			
					});
				} 
			});

			$('select[data-fieldname="job_sea_loading_country"]').on('change',function(){
				var Origin_Country_Code =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
    			var countryId    =  $('select[data-fieldname="job_sea_loading_country"]').val();  

				if(countryId == "US" || countryId == "CA")
				{
				   
					$('select[data-fieldname="job_sea_loading_state"]').prop('disabled', false);
					$.ajax({
						   type: "POST",
						   url : "include/Job/getairdata.php",
						   data:  {country_states:countryId} ,
						   success : function(data) 
						   {
							  
							  $('select[data-fieldname="job_sea_loading_city"]').val('');
							  $('select[data-fieldname="job_sea_loading_city"]').prop('disabled', true);
							  $('select[data-fieldname="job_sea_loading_state"]').html(data);
						  
							  $('select').select2();
							},error:function(e){
							   alert("error");}
					});     
			
				}
				else{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {sea_loading_country_id:countryId} ,
						success : function(data) 
						{
							 $('select[data-fieldname="job_sea_loading_state"]').val('');
							 $('select[data-fieldname="job_sea_loading_state"]').prop('disabled', true);
							 $('select[data-fieldname="job_sea_loading_city"]').prop('disabled', false);
							 $('select[data-fieldname="job_sea_loading_city"]').html(data);
							 $('select').select2();
						 },error:function(e){
							alert("error");}
				 	});
				}
			});

			$('select[data-fieldname="job_sea_loading_state"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
				var origin_state_code =   $('select[data-fieldname="job_sea_loading_state"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_sea_loading_state"]').val();

				if(origin_state_code!='' && port_country_code!='')
				{
					
					$.ajax({
					type: "POST",
					url : "include/Job/getairdata.php",
					data:  {origin_state_code_sea:origin_state_code,port_country_code:port_country_code} ,
					success : function(data) 
					{
							$('select[data-fieldname="job_sea_loading_city"]').prop('disabled', false);
							$('select[data-fieldname="job_sea_loading_city"]').html(data); 
							$('select').select2();
						},error:function(e){
							alert("error");}

					});
				} 
			});

			//puplate portCode on the base of  job_sea_loading_city 
			$('select[data-fieldname="job_sea_loading_city"]').on('change',function(){
				var cityId =  $('select[data-fieldname="job_sea_loading_city"]').val();
				var country =  $('select[data-fieldname="job_sea_loading_country"]').val();

				if(cityId!='')
				{
					
					$.ajax({
					type: "POST",
					url : "include/Job/getairdata.php",
					data: {city_id:cityId,country:country} ,
					success : function(data) 
					{
							$('select[data-fieldname="job_sea_loading_port_code"]').html(data);	
							$('select').select2();
						},error:function(e){
							alert("error");}

					});
				} 
			});

			 //discharge seablock start here   
			$('select[data-fieldname="job_sea_discharge_country"]').on('change',function(){
				var Origin_Country_Code =  $('select[data-fieldname="job_sea_discharge_country"]').val(); 
				var countryId           =  $('select[data-fieldname="job_sea_discharge_country"]').val();

				if(countryId == "US" || countryId == "CA")
				{
				
					$('select[data-fieldname="job_sea_discharge_state"]').prop('disabled', false);
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {country_states:countryId} ,
						success : function(data) 
						{
							
								$('select[data-fieldname="job_sea_discharge_city"]').val('');
								$('select[data-fieldname="job_sea_discharge_city"]').prop('disabled', true);
								$('select[data-fieldname="job_sea_discharge_state"]').html(data);
								$('select').select2();
							},error:function(e){
							alert("error");}
					});     

				}
				else{
					$.ajax({
						type: "POST",
						url : "include/Job/getairdata.php",
						data:  {sea_loading_country_id:countryId} ,
						success : function(data) 
						 {
		  
							  $('select[data-fieldname="job_sea_discharge_state"]').val('');
							  $('select[data-fieldname="job_sea_discharge_state"]').prop('disabled', true);
							  $('select[data-fieldname="job_sea_discharge_city"]').prop('disabled', false);
							  $('select[data-fieldname="job_sea_discharge_city"]').html(data);
							  $('select').select2();
						 },error:function(e){
							alert("error");}
				 	});
				}
			});

			$('select[data-fieldname="job_sea_discharge_state"]').on('change',function(){
				var port_country_code =  $('select[data-fieldname="job_sea_discharge_country"]').val(); 
				var origin_state_code =   $('select[data-fieldname="job_sea_discharge_state"]').val(); 
				var air_origin_state_code_general =   $('select[data-fieldname="job_sea_discharge_state"]').val();

				if(origin_state_code!='' && port_country_code!='')
				{
					
					$.ajax({
					   type: "POST",
					   url : "include/Job/getairdata.php",
					   data:  {origin_state_code_sea:origin_state_code,port_country_code:port_country_code} ,
					   success : function(data) 
					   {
							$('select[data-fieldname="job_sea_discharge_city"]').prop('disabled', false);
							$('select[data-fieldname="job_sea_discharge_city"]').html(data); 
							$('select').select2();
						},error:function(e){
							   alert("error");}
			
					});
				} 
			});

			//puplate portCode on the base of  job_sea_loading_city by sahib
			$('select[data-fieldname="job_sea_discharge_city"]').on('change',function(){
				var country =  $('select[data-fieldname="job_sea_discharge_country"]').val();
				var cityId =  $('select[data-fieldname="job_sea_discharge_city"]').val();
				if(cityId!='')
				{
					
					$.ajax({
					type: "POST",
					url : "include/Job/getairdata.php",
					data:  {city_id:cityId,country:country} ,
					success : function(data) 
					{
					
							$('select[data-fieldname="job_sea_discharge_port_code"]').html(data);	
							$('select').select2();
						},error:function(e){
							alert("error");}

					});
				}
			});
	},
	
	/**
	 * Function which will register basic events which will be used in quick create as well
	 *
	 */
	registerBasicEvents : function(container) {

		checkReconciled();

		this._super(container);
		jQuery("[name='assigned_user_id']").prop('disabled', true);
		jQuery('#Job_editView_fieldName_cf_6008').prop("readonly", true);
		jQuery("#Job_editView_fieldName_cf_1198").prop("readonly", true);
		jQuery("#Job_editView_fieldName_cf_4805").prop("disabled", true);
		

		$('[name="cf_6934"]').prop('disabled', true);
		$('[name="cf_6934"]').trigger('change.select2');
		$('[name="cf_6936"]').prop('disabled', true);
		$('[name="cf_6936"]').trigger('change.select2');

		//hide the block Air Shipment,Sea Shipment by default
		$("div[data-block='Air Shipment']").hide();
		$("div[data-block='Sea Shipment']").hide();



	 
		if(_USERMETA['id'] =='405' || _USERMETA['id']=='420')
		{
			$('#RequestForCancellation').prop('disabled', !$('#RequestForCancellation').prop('disabled'));
			$('#RequestForRevision').prop('disabled', !$('#RequestForRevision').prop('disabled'));
			$('#NoCosting').prop('disabled', !$('#NoCosting').prop('disabled'));
			$('#InProgress').prop('disabled', !$('#InProgress').prop('disabled'));
		}
		else{
			// {if $PICKLIST_VALUE eq "Request For Cancellation" || $PICKLIST_VALUE eq "Request For Revision" || $PICKLIST_VALUE eq "No Costing" || $PICKLIST_VALUE eq "In Progress"}
			$('#Cancelled').prop('disabled', !$('#Cancelled').prop('disabled'));
			$('#Revision').prop('disabled', !$('#Revision').prop('disabled'));
			$('#NoCosting').prop('disabled', !$('#NoCosting').prop('disabled'));
			$('#InProgress').prop('disabled', !$('#InProgress').prop('disabled'));
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
		this.puplateOnchange(container);
		this.modeOnchange(container);
	}













});


function checkReconciled(){

    var job_status = $('[name="cf_2197"] :selected').val();

    if (_USERMETA['id'] == '405' &&  (job_status == 'No Costing' || job_status == 'In Progress' || job_status == 'Request For Cancellation' || job_status == 'Cancelled' || job_status == 'Request For Revision' || job_status == 'Revision'))  {
        //If Bakhat Gul but Unable to select due to some specific status
        $('select[data-fieldname="cf_8710"]').prop('disabled', true);
    }
    /*
    if (_USERMETA['id'] == '405' &&  (job_status != 'No Costing' || job_status == 'In Progress' || job_status != 'Request For Cancellation' || job_status != 'Cancelled' || job_status != 'Request For Revision' || job_status != 'Revision'))  {
        //If Bakhat Gul but Unable to select No option
        $('select[data-fieldname="cf_8966"] option[value="No"]').attr('disabled', 'disabled');
    }
    */
    if (_USERMETA['id'] != '405'){
        //in case of any other user dropdwon will be disabled
        $('select[data-fieldname="cf_8710"]').prop('disabled', true);
    }

}

