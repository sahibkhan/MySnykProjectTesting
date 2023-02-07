/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Inventory_Edit_Js("Quotes_Edit_Js",{},{
    
    accountsReferenceField : false,
    contactsReferenceField : false,
    
    initializeVariables : function() {
      this._super();
      var form = this.getForm();
      this.accountsReferenceField = form.find('[name="account_id"]');
      this.contactsReferenceField = form.find('[name="contact_id"]');
    },
    
    /**
	 * Function to get popup params
	 */
	getPopUpParams : function(container) {
		var params = this._super(container);
        var sourceFieldElement = jQuery('input[class="sourceField"]',container);
		var referenceModule = jQuery('input[name=popupReferenceModule]', container).val();
		if(!sourceFieldElement.length) {
			sourceFieldElement = jQuery('input.sourceField',container);
		}
		
		if((sourceFieldElement.attr('name') == 'contact_id' || sourceFieldElement.attr('name') == 'potential_id') && referenceModule != 'Leads') {
			var form = this.getForm();
			var parentIdElement  = form.find('[name="account_id"]');
			if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && parentIdElement.val() != 0) {
				var closestContainer = parentIdElement.closest('td');
				params['related_parent_id'] = parentIdElement.val();
				params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(sourceFieldElement.attr('name') == 'potential_id') {
				parentIdElement  = form.find('[name="contact_id"]');
				var relatedParentModule = parentIdElement.closest('td').find('input[name="popupReferenceModule"]').val()
				if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && relatedParentModule != 'Leads') {
					closestContainer = parentIdElement.closest('td');
					params['related_parent_id'] = parentIdElement.val();
					params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
        }
        return params;
    },
    
    /**
	 * Function which will register event for Reference Fields Selection
	 */
	registerReferenceSelectionEvent : function(container) {
		this._super(container);
		var self = this;
		
		this.accountsReferenceField.on(Vtiger_Edit_Js.referenceSelectionEvent, function(e, data){
			self.referenceSelectionEventHandler(data, container);
		});
	},
    
    /**
	 * Function to search module names
	 */
	searchModuleNames : function(params) {
		var aDeferred = jQuery.Deferred();

		if(typeof params.module == 'undefined') {
			params.module = app.getModuleName();
		}
		if(typeof params.action == 'undefined') {
			params.action = 'BasicAjax';
		}
		
		if(typeof params.base_record == 'undefined') {
			var record = jQuery('[name="record"]');
			var recordId = app.getRecordId();
			if(record.length) {
				params.base_record = record.val();
			} else if(recordId) {
				params.base_record = recordId;
			} else if(app.view() == 'List') {
				var editRecordId = jQuery('#listview-table').find('tr.listViewEntries.edited').data('id');
				if(editRecordId) {
					params.base_record = editRecordId;
				}
			}
		}

		if (params.search_module == 'Contacts' || params.search_module == 'Potentials') {
			var form = this.getForm();
			if(this.accountsReferenceField.length > 0 && this.accountsReferenceField.val().length > 0) {
				var closestContainer = this.accountsReferenceField.closest('td');
				params.parent_id = this.accountsReferenceField.val();
				params.parent_module = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(params.search_module == 'Potentials') {
				
				if(this.contactsReferenceField.length > 0 && this.contactsReferenceField.val().length > 0) {
					closestContainer = this.contactsReferenceField.closest('td');
					params.parent_id = this.contactsReferenceField.val();
					params.parent_module = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
		}
        
        // Added for overlay edit as the module is different
        if(params.search_module == 'Products' || params.search_module == 'Services') {
            params.module = 'Quotes';
        }

		app.request.get({'data':params}).then(
			function(error, data){
                if(error == null) {
                    aDeferred.resolve(data);
                }
			},
			function(error){
				aDeferred.reject();
			}
		)
		return aDeferred.promise();
	},

	getQuotesRate : function(container){
		var self = this;
		jQuery('[name="cf_1719"]').change(function(){
			var rate_value = $('[name="cf_1719"]').val();
			if(rate_value!='')
			{
				var rates = $('[name="cf_1719"] option:selected').val();
				jQuery('[name="description"]').load('include/Quote/pullout_quote_rates.php?rates='+rates);
				var get_rt = $('[name="description"]').text();
				CKEDITOR.instances['Quotes_editView_fieldName_description'].setData(get_rt);
				/*$.post('include/Quote/pullout_quote_rates.php',{ rates: rate_value},function(data){					
					//var result=JSON.parse(data);					
					$('#Quotes_editView_fieldName_description').attr("value", data); //globalink selling rate					
				});*/
			}else{
				$('#Quotes_editView_fieldName_description').attr("value", '');	
			}
		});

		jQuery('[name="cf_817"]').change(function(){
			var terms_condition = $('[name="cf_817"]').val();
			if(terms_condition!='')
			{
				var tms = $('[name="cf_817"] option:selected').val();
				jQuery('#Quotes_editView_fieldName_terms_conditions').load('include/Quote/pullout_terms_and_cond.php?tms='+tms);
				var get_tms = $('#Quotes_editView_fieldName_terms_conditions').val();	   
				CKEDITOR.instances['Quotes_editView_fieldName_terms_conditions'].setData(get_tms);	
				/*$.post('include/Quote/pullout_terms_and_cond.php',{ tms: terms_condition},function(data){					
					//var result=JSON.parse(data);					
					//$('#Quotes_editView_fieldName_terms_conditions').attr("value", data); //globalink selling rate	
					jQuery('[name="description"]').load('include/Quote/pullout_quote_rates.php?rates='+rates);
					var get_rt = $('[name="description"]').text();
	  				CKEDITOR.instances['Quotes_editView_fieldName_description'].setData(get_rt); 				
				});*/
			}else{
				$('#Quotes_editView_fieldName_terms_conditions').attr("value", '');	
			}
		});


	},

	getRecipientList : function(container){
		var self = this;
		var thisInstance = this;
		var rowCount = jQuery('#tbl_recipients tr').length;
		var j = rowCount;
		jQuery('.cls-recipients').change(function(){
			var crm_field = jQuery('.crmfield_recipients').attr('id');	  
  			rowCount ++;

			var user_loginid = jQuery('.cls-recipients option:selected').val();
			$("#tbl_recipients").append('<tr class="remove_invite_user'+rowCount+'"><td class="hide_invite_login" style="display:none;">'+user_loginid+'</td><td id="invite_user_format'+rowCount+'"></td><td id="removeinviteduser"  data-id="'+rowCount+'"><img src="include/images/delete.png"></td></tr>');
			var crm_field_val = jQuery('#'+crm_field);
  			crm_field_val.val(crm_field_val.val() + user_loginid + '|');
				
			$.ajax({
				url: 'include/Vtiger/user_format_arrange.php?user_login='+user_loginid,
				success: function(data){
					j ++;
					$("#invite_user_format" + j).html(data); 
					
				}
			});			
			thisInstance.remove_invited_user(); 
			
		})	
	 },

	 remove_invited_user : function(){
		var thisInstance = this;
		jQuery('#removeinviteduser').live('click',function(e){
			
			var element = jQuery(e.currentTarget);
			var elementid = $(this).data("id");
			//var elementid = element.data('data-id');
			var p = $(".remove_invite_user"+elementid).find('td').html();
			if (p != "dennis.ruiter"){
				
				//var d = element.find(".remove_invite_user"+elementid);
				//alert(JSON.stringify(d))
				var d = document.getElementsByClassName("remove_invite_user"+elementid);
				for (var i = 0; i < d.length; i++){
					//$(this).parent().find(d[i]).remove();
					d[i].parentElement.removeChild(d[i]);
				}
					
				var crm_field_id = $('.crmfield_recipients').attr('id');	
				var crm_field = $('#'+crm_field_id);
				
				crm_field.val('');   
				var MyIndexValue = "";
				
				var MyRows = $('#tbl_recipients').find('tr');
				
					for (var i = 0; i < MyRows.length; i++){
						MyIndexValue += $(MyRows[i]).find('td:eq(0)').html()+'|';
						crm_field.val(MyIndexValue);
					}


			}
		});
	 },

	 registerEventForCkEditor : function(){
		var form = this.getForm();
		var noteContentElement = form.find('[name="description"]');
		if(noteContentElement.length > 0){
			var ckEditorInstance = new Vtiger_CkEditor_Js();
			ckEditorInstance.loadCkEditor(noteContentElement);
		}
		var noteContentTMElement = form.find('[name="terms_conditions"]');
		if(noteContentTMElement.length > 0){
			var ckEditorInstance = new Vtiger_CkEditor_Js();
			ckEditorInstance.loadCkEditor(noteContentTMElement);
		}
	},

        registerBasicEvents: function(container){
            this._super(container);
            this.registerForTogglingBillingandShippingAddress();
			this.registerEventForCopyAddress();
			this.getQuotesRate(container);
			this.getRecipientList(container);
			this.registerEventForCkEditor();
        },
});