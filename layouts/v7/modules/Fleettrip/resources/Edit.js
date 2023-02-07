
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Fleettrip_Edit_Js",{},{


		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		jQuery("[name='assigned_user_id']").prop('disabled', true);
        $('input[name="cf_3283"]').prop('readonly', true); // Fleet Ref No 
		$('input[name="cf_3253"]').prop('readonly', true); // Standard Days
		$('input[name="cf_3255"]').prop('readonly', true); // Total Allowed Days
		$('input[name="cf_3261"]').prop('readonly', true); // Standard Distance(km)
		$('input[name="cf_3263"]').prop('readonly', true); // Total KM Traveled During Trip
		$('input[name="cf_3271"]').prop('readonly', true); // Standard Fuel
		//$('input[name="cf_2033"]').prop('readonly', true); // Fuel Used During Trip
		$('input[name="cf_3275"]').prop('readonly', true); // Average Consumption
		$('input[name="cf_3277"]').prop('readonly', true); // Average Allowed Consumption
		$('input[name="cf_5159"]').prop('readonly', true); // standard indirect cost
	
		
	 },

	 
	 getFleetRate : function(container){
		var self = this;

		jQuery('[name="cf_3165"]').on('change', function(){
				
			var truck_id = $('[name="cf_3165"]').val();				
			
			if(truck_id!='')
			{
				
				 $.post('include/Fleettrip/driver_info.php',{ truck_id: truck_id
															 },function(data){
				
					 var result=JSON.parse(data);
					  $('[name="cf_3167"]').attr("value", result['driver_id']);
					  $('[name="cf_3167"]').trigger('liszt:updated');
					  });
					  
				 $.post('include/Fleettrip/indirectcost_info.php',{ truck_id: truck_id
															 },function(data){
				
					 var result=JSON.parse(data);
					  $('[name="cf_5159"]').attr("value", result['indirect_cost']);
					  });	  
					  
					  
				$.post('include/Fleettrip/trailer_info.php',{ truck_id: truck_id
															 },function(data){
				
					 var result=JSON.parse(data);
					  $('[name="cf_4951"]').attr("value", result['trailer_id']);
					  $('[name="cf_4951"]').trigger('liszt:updated');
					  });
							
				
				$.post('include/TripTemplates/trip_template_info.php',{ truck_id: truck_id}, function(data){
					$('[name="cf_4517"]').empty();
					$('[name="cf_4517"]').append(
						'<option value="">--Select Trip Template--</option>'
					);
					if (data) {
						$.each(data.triptemplate, function(i, trucktypetriptemplate){
							
							$('[name="cf_4517"]').append(
								'<option  data-picklistvalue="' + trucktypetriptemplate.triptemplatesid + '" value="' + trucktypetriptemplate.triptemplatesid + '">' + trucktypetriptemplate.label +'</option>'
							);
						});		
					}
					$('[name="cf_4517"]').trigger('liszt:updated');
					 
				}, 'json');					
			}
			
		})
		
		
		jQuery('[name="cf_4517"]').on('change', function(){
			var trip_template_id = $('[name="cf_4517"]').val();
			
			var truck_id = $('[name="cf_3165"]').val();				
			var from_date = $('[name="cf_3245"]').val();
			var to_date = $('[name="cf_3247"]').val();
			
			var expected_from_date = $('#cf_3249').val();
			
			if(trip_template_id!='')
			{
				 $.post('include/Fleettrip/truck_trip_info.php',{
														 truck_id: truck_id,	 
													   trip_template_id: trip_template_id, 
													  from_date:from_date, 
													  to_date:to_date, 
													  expected_from_date : expected_from_date
													  },function(data){
				
							   var result=JSON.parse(data);
						  $('[name="cf_3277"]').val(result['average_allowed_consumption']);
						  $('[name="cf_3257"]').val(result['due_leave_km']);
						  $('[name="cf_3253"]').val(result['standard_days']);
						  $('[name="cf_3261"]').val(result['standard_distance']);
						  $('[name="cf_3271"]').val(result['standard_fuel']);
						  $('[name="cf_3275"]').val(result['average_consumption']);
						  $('[name="cf_4553"]').val(result['cash_required']);
						  
						
					});
			}
			
		})

		jQuery('[name="cf_3257"], [name="cf_3259"]').blur(function() {
				
			var due_leave_km = $('[name="cf_3257"]').val();
			var due_arrival_km = $('[name="cf_3259"]').val();
			if(due_leave_km && due_arrival_km)
			{
				var value = Number(due_arrival_km) - Number(due_leave_km);
				$('[name="cf_3263"]').val(value); //total km traveled during trip
			}
			
		})

		//Basically they already added due leave km before trip, that's why we restricted here.
		//if(jQuery.param('record').length!=0)
		//{
			//$('input[name="cf_3257"]').prop('readonly', true); //Due Leave (km) on Edit
		//}
		
		jQuery('form[name="EditView"]').on('submit', function(event){
					
			if ($('[name="cf_5964"]').next().find('span').html() === 'Temperature') {
			   event.preventDefault();
			   var f_name = "";
			   var f_label = "";
			   
				   if ($("[name='cf_5966']").val().length == 0){
					   f_name = "Temperature Range From";
					   f_label = "cf_5966";
				   } else if ($("[name='cf_5968']").val().length == 0){
					   f_name = "Temperature Range To";
					   f_label = "cf_5968";
				   }
				   
			   if (f_name.length > 0){
				   params = "Please fill " + f_name + " field";
			  
				  // var notify = Vtiger_Helper_Js.showPnotify(params);
				  // $("[name='"+f_label+"']").focus();
				  app.helper.showAlertBox({'message':app.vtranslate(params)});
				  container.find("[name='cf_4559']").focus();
				   //return false;
			   } else $('form[name="EditView"]').off('submit').submit();
			}
		   })
		   
		   

	
	 },
	

		 
	 registerBasicEvents: function(container){
        this._super(container);
		this.getFleetRate(container);
	 },
});