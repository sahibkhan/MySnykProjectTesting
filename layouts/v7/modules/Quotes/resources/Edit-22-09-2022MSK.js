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
				params.parent_module = closestContainer.find('[name="popupReferenceModule"]')
				
				
				.val();
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
			// console.log(`loaded`)
			
			if(rate_value!='')
			{
				//var rates = $('[name="cf_1719"] option:selected').val();
				var rates = rate_value;
				jQuery('[name="description"]').load('include/Quote/pullout_quote_rates.php?rates='+rates, function(){
					var get_rt = $('[name="description"]').text();
					CKEDITOR.instances['Quotes_editView_fieldName_description'].setData(get_rt);
				}
				);
			
				/*$.post('include/Quote/pullout_quote_rates.php',{ rates: rate_value},function(data){					
					//var result=JSON.parse(data);					
					$('#Quotes_editView_fieldName_description').attr("value", data); //globalink selling rate					
				});*/
			}else{
				$('#Quotes_editView_fieldName_description').attr("value", '');	
			}
		});

		jQuery('[name="cf_817"], [name="cf_8714"]').change(function(){
			const aDeferred = jQuery.Deferred();
			const termsName = $('[name="cf_817"]').val();
			const shipmentRelatesTo = $('[name="cf_8714"]').val();
			if (!termsName) return;

			var params = {
				module: 'Quotes',
				view: 'Detail',
				mode: 'getQuoteTermsTemplate',
				recordId: null,
				termsName,
				shipmentRelatesTo,
			};

			app.request.post({'data':params}).then(
				function(error, data){
					if (error == null){
						aDeferred.resolve(data);
						const templateData = JSON.parse(data);
						const { termsText } = templateData;

						CKEDITOR.instances['Quotes_editView_fieldName_terms_conditions'].setData(termsText);
					}
				},
				function(error){
					aDeferred.reject();
				}
			)
			return aDeferred.promise();

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
					// hello
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
            puplateOnchange();
			//this.openCalculator();
			// Azhar = 30-05-2022
			$("#openCalculator").click(function (e){
				var sub = $("[name='record']").val();
				 
				alert("Tst "+sub);
				
				if(sub=="")
				{
					var url = "http://tiger.globalink.net/live-gems/index.php?module=RateCalculator&view=List&app=SALES&qtid=New";
				}
				else
				{
					var url = "http://tiger.globalink.net/live-gems/index.php?module=RateCalculator&view=List&app=SALES&qtid="+sub;
				}
				
				
				location.replace(url);
				
			});
			
			var rout = decodeURIComponent(getUrlVars()["rout"]);
			// console.log("rout = ");
		
			//if (typeof rout === "undefined") 
			if(rout === "undefined")
			{
				// console.log("Not define ");
				
			}
			else
			{
				console.log("starting");
				
				
				try{
					//var rv = localStorage.getItem("rout");
					
					const obj = JSON.parse(rout);
					console.log(obj);
					console.log("Loop");
					var l=1;
					var rateHTML = "";
					var tacId = "";
					var fromPort ="";
					var fromCountry ="";
					var toPort ="";
					var toCountry ="";
					$.each(obj, function( index, value ) {
						if(value!="")
						{
							objData = JSON.parse(value);
							console.log( index + ": " + value );
							//console.log(objData);
							//console.log(objData.Error);
							//console.log(decodeURIComponent(objData.Rate));
							//var chk = decodeURIComponent(objData.Rate);
							rateHTML += decodeURIComponent(objData.Rate)+"<br><br>";
							console.log(objData.TaC);
							console.log(rateHTML);
							var handling = objData.Handling;
							//get terms and condtion
							var rslt="";
							app.helper.showProgress();
							$.ajax({
								url: 'include/RateCalculator/gettac.php',
								type: 'POST',
								data:{tcid:objData.TaC,opt:l,handling:handling},
								async: false,
								cache: false,
								timeout: 30000,
								error: function(){
									console.log("Ajax Fail");
									console.log(err);
									app.helper.showErrorNotification({message:"Error in data"});
									app.helper.hideProgress();
									return false;
								},
								success: function(resp){ 
									console.log("resp");
									console.log(resp);
									rslt=resp;
									
								}
							});
							app.helper.hideProgress();
							
							
							tacId += rslt+"<br><br>";
							
							if(l==1)
							{
								fromPort = objData.FromPort;
								fromPort = fromPort.replace(/\+/g, ' ');
								fromCountry = objData.FromCountry;
								fromCountry = fromCountry.replace(/\+/g, ' ');
								
								toPort = objData.ToPort;
								toPort = toPort.replace(/\+/g, ' ');
								toCountry = objData.ToCountry;
								toCountry = toCountry.replace(/\+/g, ' ');
							}
							l++;
						}
					});
					console.log("terms and cond");
					console.log(tacId);
					console.log("Rout names");
					console.log(fromPort);
					console.log(fromCountry);
					console.log(toPort);
					console.log(toCountry);
					
					$("#Quotes_editView_fieldName_cf_1615").val(decodeURIComponent(fromPort)); 
					$("#Quotes_editView_fieldName_cf_1619").val(decodeURIComponent(toPort)); 
					
					$("[name='cf_1613'").val(fromCountry).change();
					$("[name='cf_1617'").val(toCountry).change();
					
					rateHTML = rateHTML.replace(/\+/g, ' ');
					
					CKEDITOR.instances['Quotes_editView_fieldName_description'].setData(rateHTML);
					
					CKEDITOR.instances['Quotes_editView_fieldName_terms_conditions'].setData(tacId);
					
					//$("textarea#Quotes_editView_fieldName_description").val(rateHTML);
					
					console.log("Rate ");
					console.log(rateHTML);
				}
				catch(err){
					alert("Error : "+err.message);
				}
				
				
				
				
				
				
			}
			

			//Mujhaid :: 02.07.2021
			$('#Secured').prop('disabled', !$('#Secured').prop('disabled'));	
			$('[name="quotestage"]').trigger('change.select2');
        },
});

// Read a page's GET URL variables and return them as an associative array.
function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}


//Added onchange function for the  populating the block on the base of mode by Sahib Khan
function puplateOnchange(){
    //populating the block on the base of mode 
      var editId = jQuery('[name="record"]').val();
      var origincountryId    = jQuery('[name="cf_1613"]').val(); 
      var destcountryId      = jQuery('[name="cf_1617"]').val(); 
      var quotes_origin_state = jQuery('[name="quotes_origin_state"]').val();
      var quotes_destination_state = jQuery('[name="quotes_destination_state"]').val();
     	
     
}





//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1613"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1613"]').val(); 
    if(countryId == "US" || countryId == "CA")
    {
        $('select[data-fieldname="quotes_origin_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Quote/getGeneraldata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1615"]').val('');
                  $('select[data-fieldname="cf_1615"]').prop('disabled', true);
                  $('select[data-fieldname="quotes_origin_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
    
        $('select[data-fieldname="cf_1615"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Quote/getGeneraldata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="quotes_origin_state"]').val('');
	            $('select[data-fieldname="quotes_origin_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1615"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});


//Origin Destination cities the base on Dest countries


//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1617"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1617"]').val(); 
    
    if(countryId == "US" || countryId == "CA")
    {
    	$('select[data-fieldname="quotes_destination_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Quote/getGeneraldata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1619"]').val('');
                  $('select[data-fieldname="cf_1619"]').prop('disabled', true);
                  $('select[data-fieldname="quotes_destination_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    {   
       $('select[data-fieldname="cf_1619"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Quote/getGeneraldata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {

	           	$('select[data-fieldname="quotes_destination_state"]').val('');
	            $('select[data-fieldname="quotes_destination_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1619"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});

//Origin City select on the base of Origin States
$('select[data-fieldname="quotes_origin_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1613"]').val(); 
    var origin_state_code =   $('select[data-fieldname="quotes_origin_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Quote/getGeneraldata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1615"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1615"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});


$('select[data-fieldname="quotes_destination_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1617"]').val(); 
    var origin_state_code =   $('select[data-fieldname="quotes_destination_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Quote/getGeneraldata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1619"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1619"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});