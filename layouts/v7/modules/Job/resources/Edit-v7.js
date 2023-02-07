Vtiger_Index_Js("Job_Edit_Js",{ } , {

	jQuery(document).ready(function() {
	jQuery('form[name="EditView"]').on('submit', function(event){


		 // this prevents browser from actually following form url
    // a reference to the form to get used inside ajax callback
    if (jQuery('[name="cf_2197"]').next().find('span').html() === 'Completed') {
		event.preventDefault();
		//cf_5986 :: time of arrival(TA)
		//cf_4805 :: Completion date
		var f_name_i = "";
		var f_label_i = "";
		if (jQuery("[name='cf_4805']").val().length == 0){
			f_name_i = "Completion Date";
			f_label_i = "cf_4805";
		} else if (jQuery("[name='cf_5986']").val().length == 0){
			f_name_i = "Time of Arrival (TA)";
			f_label_i = "cf_5986";
		}

		if (f_name_i.length > 0){
			params_i = {
				text: app.vtranslate("Please fill " + f_name_i + " field"),
				type: 'error'
			}
			var notify = Vtiger_Helper_Js.showPnotify(params_i);
			jQuery("#Job_editView_fieldName_"+f_label_i+"").focus();
			return false;
		} 


		/*
        if ($("#Job_editView_fieldName_cf_5417").val().trim() === '') {
            if ($(document.body).attr('data-language') == 'en_us') {
                alert(' Please Add Vessel | Flight | Trailer details');
            } else {
                alert('Пожалуиста заполните данные для Судно | Авиабилеты | трейлер');
            }
            
            $("#Job_editView_fieldName_cf_5417").focus();
            return false;
        } else  if ($("#Job_editView_fieldName_cf_4805").val().trim() === '') {
            if ($(document.body).attr('data-language') == 'en_us') {
                alert(' Please Add Job Completion Date');
            } else {
                alert('Пожалуиста заполните дату завершения');
            }
            $("#Job_editView_fieldName_cf_4805").focus();
            return false;
        } else*/ 
		if (jQuery("[name='cf_1190']").val() == 85836){
				

				
			/*
				cf_4935 - FLT date
				cf_4925 - RLJ
				cf_4945 - C.W
				cf_1096 - Waybill
			*/
			
			var f_name = "";
			var f_label = "";
			 
			if (jQuery("[name='cf_4935']").val().length == 0){
				f_name = "FLT Date";
				f_label = "cf_4935";
			} else if (jQuery("[name='cf_4925']").val().length == 0){
				f_name = "RLJ";
				f_label = "cf_4925";
			} else if ((jQuery("[name='cf_4945']").val().length == 0) || ($("[name='cf_4945']").val() == 0)){
				f_name = "C.W.(KGs)";
				f_label = "cf_4945";
			} else if (jQuery("[name='cf_1096']").val().length == 0){
				f_name = "Waybill";		
				f_label = "cf_1096";
			} else if (jQuery("[name='cf_2387']").val().length == 0){
				f_name = "HAWB";		
				f_label = "cf_2387";
			} else if (jQuery("[name='cf_4923']").val().length == 0){
				f_name = "MAWB NO";		
				f_label = "cf_4923";	
			} else if (jQuery("[name='cf_1591']").val().length == 0){
				f_name = "ETA";		
				f_label = "cf_1591";
			} else if (jQuery("[name='cf_5417']").val().length == 0){
				f_name = "Vessel | Flight | Trailer #";		
				f_label = "cf_5417";
			}
			 else if (jQuery("[name='cf_4805']").val().length == 0){
				f_name = "Job Completion Date";		
				f_label = "cf_4805";
			}		
			
			
			if (f_name.length > 0){
				params = {
					text: app.vtranslate("Please fill " + f_name + " field"),
					type: 'error'
				}
				var notify = Vtiger_Helper_Js.showPnotify(params);
				jQuery("[name='"+f_label+"']").focus();
				//return false;
			} else jQuery('form[name="EditView"]').off('submit').submit();
	
				
				
		
		
		} else jQuery('form[name="EditView"]').off('submit').submit();
	}
	
	});

});

	jQuery("input[name='cf_6008']").attr("readonly", true);
	jQuery("[name='cf_1198']").attr("readonly", true);

	jQuery("[data-name='assigned_user_id']").attr('disabled', true);
	jQuery("[data-name='assigned_user_id']").trigger('liszt:updated');


	var cur_record = jQuery('[name="record"]').val();
	if (cur_record.length > 0){	
		jQuery('[name="cf_3527"]').attr('disabled', true);
		jQuery('[name="cf_3527"]').trigger('liszt:updated');	 
	}

	
});

