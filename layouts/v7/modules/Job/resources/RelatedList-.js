/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("Job_RelatedList_Js",{},{


	registerEditLink : function(){
	  //	var aDeferred = jQuery.Deferred();
	  //	var thisInstance = this;
		  var relatedContainer =  jQuery('.relatedContainer');
		  relatedContainer.on('click', '#relationEdit', function(e) {
			  var massEditUrl = $('#relationEdit').attr('data-url');
			  var selectedIds = new Array();
			  var selectedIds =  $(this).data("id");
			  //console.log(selectedIds);
			  //var listInstance = Vtiger_List_Js.getInstance();
			  //var selectedIds = [];
  
		  //	var cvId = jQuery('#customFilter').find('option:selected').data('id');
			  //jQuery('#selectedIds').data(cvId+'Selectedids',selectedIds);
			  //selectedIds = 'mehtab';
			  //console.log(selectedIds2);
			  //selectedIds.push(selectedIds);
			  //console.log(selectedIds);
			  //jQuery('#selectedIds').data(cvId+'Selectedids',selectedIds);
			  Vtiger_List_Js.triggerMassActionP(selectedIds, massEditUrl, function(container){
					  var massEditForm = container.find('#massEdit');
					  massEditForm.validationEngine(app.validationEngineOptions);
					  var listInstance = Vtiger_List_Js.getInstance();
					  listInstance.inactiveFieldValidation(massEditForm);
					  listInstance.registerReferenceFieldsForValidation(massEditForm);
					  listInstance.registerFieldsForValidation(massEditForm);
					  listInstance.registerEventForTabClick(massEditForm);
					  listInstance.registerRecordAccessCheckEvent(massEditForm);
					  var editInstance = Vtiger_Edit_Js.getInstance();
					  editInstance.registerBasicEvents(massEditForm);
					  //To remove the change happended for select elements due to picklist dependency
					  container.find('select').trigger('change',{'forceDeSelect':true});
					  listInstance.postMassEditP(container);//to check tomorrow
			
					  listInstance.registerSlimScrollMassEdit();
				  },{'width':'65%'});
  
		   //console.log(massEditUrl);
	  });
	  }
  });
  //On Page Load
  jQuery(document).ready(function() {
	  alert('yes here');
	  var jobMassEditInstance = new Job_RelatedList_Js();
	  jobMassEditInstance.registerEditLink();
  });
  