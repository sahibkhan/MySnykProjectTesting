
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
		jQuery('form[name="EditView"]').on('submit',function(e){

		});
	},
	
	/**
	 * Function which will register basic events which will be used in quick create as well
	 *
	 */
	registerBasicEvents : function(container) {
		this._super(container);
		jQuery('#Job_editView_fieldName_cf_6008').prop("readonly", true);
		jQuery("#Job_editView_fieldName_cf_1198").prop("readonly", true);

		//jQuery("[name='assigned_user_id']").prop('disabled', true);
		//jQuery("[name='assigned_user_id']").trigger('select2');
		//var cur_record = jQuery('[name="record"]').val();
		//if (cur_record.length > 0){	
		//	jQuery('[name="cf_3527"]').prop('disabled', true);
		//	jQuery('[name="cf_3527"]').trigger('select2');
		//}
		this.registerRecordPreSaveJob(container);
	}
});