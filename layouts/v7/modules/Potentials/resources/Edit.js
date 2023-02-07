
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Potentials_Edit_Js",{},{


		
	init : function(container) {
		this._super(container);
		//this.initializeVariables();
		
		
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


		jQuery('[name="assigned_user_id"]').change(function(){
			// console.log(`Change user `)

			const crm_field = jQuery('.crmfield_recipients').attr('id');  
  				rowCount ++;

			// var user_loginid = jQuery('.cls-recipients option:selected').val();
			const user_loginid = jQuery('.profile-container h5.textOverflowEllipsis').text();

			const existLogin = jQuery('.crmfield_recipients').val()
			if (!existLogin.includes(user_loginid.trim())){
					$("#tbl_recipients").append('<tr class="remove_invite_user'+rowCount+'"><td class="hide_invite_login" style="display:none;">'+user_loginid+'</td><td id="invite_user_format'+rowCount+'"></td><td id="removeinviteduser"  data-id="'+rowCount+'"><img src="include/images/delete.png"></td></tr>');
					let crm_field_val = jQuery('#'+crm_field);
							crm_field_val.val(crm_field_val.val() + user_loginid + '|');
						$.ajax({
							url: 'include/Vtiger/user_format_arrange.php?user_login='+user_loginid,
							success: function(data){
								j ++;
								$("#invite_user_format" + j).html(data); 
							}
						});		
				}
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
		
	},
		 
	 registerBasicEvents: function(container){
        this._super(container);
		this.getRecipientList(container);
		//this.remove_invited_user(container);
		this.registerEventForCkEditor();
		 puplateOnchange();
	 },
});


//Added onchange function for the  populating the block on the base of mode by Sahib Khan
function puplateOnchange(){
    //populating the block on the base of mode 
      var editId = jQuery('[name="record"]').val();
      var potentials_origin_state = jQuery('[name="potentials_origin_state"]').val();
      var potentials_destination_state = jQuery('[name="potentials_destination_state"]').val();
      if(potentials_origin_state=='')
      {
      	 $('select[data-fieldname="potentials_origin_state"]').prop('disabled', true);
           
      }if(potentials_destination_state=='')
      {
      	 $('select[data-fieldname="potentials_destination_state"]').prop('disabled', true);
      }
     
}



//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1657"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1657"]').val(); 
    if(countryId == "US" || countryId == "CA")
    {
        $('select[data-fieldname="potentials_origin_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1659"]').val('');
                  $('select[data-fieldname="cf_1659"]').prop('disabled', true);
                  $('select[data-fieldname="potentials_origin_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
        $('select[data-fieldname="cf_1659"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="potentials_origin_state"]').val('');
	            $('select[data-fieldname="potentials_origin_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1659"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});


//Origin Destination cities the base on Dest countries


//Origin States and origin_cite select on the base General Origin Country
$('select[data-fieldname="cf_1661"]').change(function(e){
    e.preventDefault();
    var countryId    =  $('select[data-fieldname="cf_1661"]').val(); 
    
    if(countryId == "US" || countryId == "CA")
    {
    	$('select[data-fieldname="potentials_destination_state"]').prop('disabled', false);
        $.ajax({
               type: "POST",
               url : "include/Job/getairdata.php",
               data:  {country_states:countryId} ,
               success : function(data) {
               	  $('select[data-fieldname="cf_1663"]').val('');
                  $('select[data-fieldname="cf_1663"]').prop('disabled', true);
                  $('select[data-fieldname="potentials_destination_state"]').html(data);
                  $('select').select2();
                },error:function(e){
                   alert("error");}
        });     

    }else
    { 
       $('select[data-fieldname="cf_1663"]').prop('disabled', false);
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getairdata.php",
	           data:  {general_country_id:countryId} ,
	           success : function(data) {
	           	$('select[data-fieldname="potentials_destination_state"]').val('');
	            $('select[data-fieldname="potentials_destination_state"]').prop('disabled', true);
	            $('select[data-fieldname="cf_1663"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}
	    });
    }

});

//Origin City select on the base of Origin States
$('select[data-fieldname="potentials_origin_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1657"]').val(); 
    var origin_state_code =   $('select[data-fieldname="potentials_origin_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1659"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1659"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});


$('select[data-fieldname="potentials_destination_state"]').change(function(e){
    e.preventDefault();
    var port_country_code =  $('select[data-fieldname="cf_1661"]').val(); 
    var origin_state_code =   $('select[data-fieldname="potentials_destination_state"]').val(); 
  
    if(origin_state_code!='' && port_country_code!='')
    {
        
        $.ajax({
           type: "POST",
           url : "include/Job/getairdata.php",
           data:  {origin_state_code_gernal:origin_state_code,port_country_code:port_country_code} ,
           success : function(data) {
                 $('select[data-fieldname="cf_1663"]').prop('disabled', false);
                 $('select[data-fieldname="cf_1663"]').html(data); 
                    $('select').select2();
                },error:function(e){
                   alert("error");}

        });
    } 
    

});