jQuery(function(){
	
					// Get QT record ID					
  var quotation_id = jQuery('input[name="sourceRecord"]');
  //alert(quotation_id.val());
  $.ajax({
    url: 'include/Job/getJsonQuotationDetails.php?record='+quotation_id.val(),
	success: function(data){
	   var result=JSON.parse(data);
	   	  // alert(result['accountid']);
	   $('input[name="cf_1441"]').val(result['accountid']);
	   $('input[name="cf_1441_display"]').val(result['customer_label']);
	   
	   if (result['subject']){
	     $('input[name="name"]').val(result['subject']);
	   }
	   
	   $('[name="cf_1080"]').attr("value", result['transport_mode']);
	   $('[name="cf_1080"]').trigger('liszt:updated');
					// General
	   $('input[name="cf_1072"]').val( result['shipper'] );
	   $('input[name="cf_1074"]').val( result['consignee'] );
	   
	   $('input[name="cf_1504"]').val( result['origin_country'] );
	   $('input[name="cf_1506"]').val( result['destination_country'] );
	   
	   $('input[name="cf_1508"]').val( result['origin_city'] );
	   $('input[name="cf_1510"]').val( result['destination_city'] );
	   
	   $('input[name="cf_1512"]').val( result['pickup_address']);
	   $('input[name="cf_1514"]').val( result['delivery_address']);
				// Date data flow
	  if (result['expected_date_of_loading']){	
	    exp_date_of_loading = $('input[name="cf_1516"]'); 
	    format_date_1 = date_format_arrange(result['expected_date_of_loading'],exp_date_of_loading.attr('data-date-format'));
	    exp_date_of_loading.val(format_date_1);
	  }
	  
	  if (result['expected_date_of_delivery']){	
	    exp_date_of_delivery = $('input[name="cf_1583"]');
	    format_date_2 = date_format_arrange(result['expected_date_of_delivery'],exp_date_of_delivery.attr('data-date-format'));
	    exp_date_of_delivery.val(format_date_2);
	  }
	  
	  if (result['etd']){
	    ETD = $('input[name="cf_1589"]');
	    format_date_1 = date_format_arrange(result['etd'],ETD.attr('data-date-format'));
	    ETD.val(format_date_1);
	  }
	  
	  if (result['eta']){
		ETA = $('input[name="cf_1591"]');
	    format_date_2 = date_format_arrange(result['eta'],ETA.attr('data-date-format'));	    
	    ETA.val(format_date_2);
	  }
	   //$('input[name="cf_1551"]').val( result['vendor']);	   
	   $('input[name="cf_1084"]').val( result['weight'] );
	   $('input[name="cf_1086"]').val( result['volume'] ) ;
	   $('input[name="cf_1429"]').val( result['noofpieces'] );
	   $('input[name="cf_1518"]').val( result['commodity'] );
	   $('textarea[name="cf_1547"]').val( result['cargo_description'] );
	   $('input[name="cf_1524"]').val( result['cargo_value'] );
	   $('textarea[name="cf_1098"]').val(result['terms_of_delivery']);	   
							// Mode
	  if (result['mode']){			
	    mode = result['mode'];	  
	  $.each(mode.split(" |##| "), function(i,e){
	    $('li.select2-search-field').before('<li class="select2-search-choice">    <div>'+ e+'</div>    <a href="#" onclick="return false;" class="select2-search-choice-close" tabindex="-1"></a></li>');	
	    $("#Job_Edit_fieldName_cf_1711 option[value=" + e + "]").attr("selected","selected");
	  });
	  
	  $('.select2-search-choice').on('click',function(){
	    diselect = $(this).find('div').text();
		$("#Job_Edit_fieldName_cf_1711 option[value=" + diselect + "]").attr("selected",false);
		$(this).remove("li");	  
	  });			
	  }
		
							// Make origin and destination country status selected
	  origin_country_code = $('input[name="cf_1504"]').val();
	  $("#origin_countryid option[value='"+origin_country_code+"']").attr("selected","selected");
	  
	  destination_country_code = $('input[name="cf_1506"]').val();
	  $("#destination_countryid option[value='"+destination_country_code+"']").attr("selected","selected");
	   
							// Weight units
	  weight_unit = result['weight_units'];
	  if (weight_unit){	
	    element_code = $('[name="cf_1520"]').attr('ID');	 
	    weight_units = $('[name="cf_1520"] option[value='+weight_unit+']');
	    weight_units.attr('selected','selected');	  
	    $('div #'+element_code+'_chzn a span').text(weight_units.val());
	  }
	  
							// Volume units
	  volume_unit = result['volume_units'];	
      if (volume_unit){ 
	    element_code = $('[name="cf_1522"]').attr('ID');	 
	    volume_units = $('[name="cf_1522"] option[value='+volume_unit+']');
	    volume_units.attr('selected','selected');	  
	    $('div #'+element_code+'_chzn a span').text(volume_units.val());
	  }
	  
							// Cntr or transport_type 
	  cntr_or_transport_type = result['cntr_transport_types'];	
	  if (cntr_or_transport_type){
	    element_code = $('[name="cf_1092"]').attr('ID');	 
	    cntr_transport_types = $('[name="cf_1092"] option[value="'+cntr_or_transport_type+'"]');
	    cntr_transport_types.attr('selected','selected');	  
	    $('div #'+element_code+'_chzn a span').text(cntr_transport_types.val());
	  }

	  
						// Cargo value and currency
	  cargo_unit = result['cargo_unit'];	
	  element_code = $('[name="cf_1721"]').attr('ID');	 
	  cargo_unit = $('[name="cf_1721"] option[value='+cargo_unit+']');
	  cargo_unit.attr('selected','selected');  
	  

	 
	   $('input[name="cf_1082"]').val( result['campaignid'] );
	   $('input[name="cf_1082_display"]').val( result['campaign_label']);
	   
	   
	}
  });
  
  
  
  
  
  /*
  $.ajax({
    url: 'include/Job/getQuotationDetails.php?field_type=quotation_id&record='+quotation_id.val(),
	success: function(data){
	  if ($('input[name="cf_1198"]').val() == ''){
	    //$('input[name="cf_1198"]').val(data);
	  }	
	}
  });*/
		  
		  
});
  $('.assigned_user_id').prop('disabled', true);