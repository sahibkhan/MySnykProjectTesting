/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class("Job_Detail_Js",{

},{

	registerShowHideAirSeaBlock : function() {
		var self = this;

			$("div[data-block='Air Shipment']").hide();
			$("div[data-block='Sea Shipment']").hide();
			//populating the block on the base of mode 
			var editId =  jQuery('#recordId').val()
			
			var mode = jQuery('#Job_detailView_fieldValue_cf_1711 span span').text();
			mode_arr =  mode.split(" ");
			if (Array.isArray(mode_arr)) {
			
				for(var i = 0; i < mode_arr.length; i++)
				{   
				if(mode_arr[i] == 'Air'){
						//console.log(mode[i]);
						// hide BLOCK 
						$("div[data-block='Air Shipment']").show();    
						
					}if (mode_arr[i] == 'Ocean'){
						
						// hide BLOCK       
						$("div[data-block='Sea Shipment']").show();
					
					}if(mode_arr[i] == 'Air/Sea'){
						
						$("div[data-block='Air Shipment']").show();
						$("div[data-block='Sea Shipment']").show();
					}
				}    
			}

	},
	
	registerEvents: function() {
		this._super();
		this.registerShowHideAirSeaBlock();
	}

});
