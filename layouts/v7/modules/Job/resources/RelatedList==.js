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
			  alert('am here');
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
  