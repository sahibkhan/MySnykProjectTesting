/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("CargoInsurance_Detail_Js", {

		

},
{
	registerPostToWIS : function(){
		var self = this;
		var thisInstance = this;
		
		var user_id = $('#current_user_id').val();
		var declaration_id = $('#declaration_id').val();
		
		var chkref = $("#check_referal_hidden").val();
		var acompany = $("#CargoInsurance_detailView_fieldValue_cf_3599").text().trim();
		//alert(acompany);
		if(chkref=="yes")
		{
			$("#postwis").hide();
		}
		
		var wisref = $("#wis_ref_id").val();
		if(wisref !="")
		{
			$("#postwis").hide();
			$("#CargoInsurance_detailView_basicAction_LBL_EDIT").hide();
			
		}
		var cancancel = $("#can_be_cancelled").val();
		console.log("Cancel ");
		console.log(cancancel);
		/*
		if(cancancel!="yes" || cancancel!="1")
		{
			$("#cancelButton").hide();
		}
		else
		{
			$("#cancelButton").show();
		}
		*/
		
		
		
		//var recordId = thisInstance.getRecordId();
		//var moduleName = app.getModuleName();
		var delayInMilliseconds = 3000; //5 second
		jQuery('#postwis').on('click', function(e){
			app.helper.showProgress();
			var element = jQuery(e.currentTarget);
			//if(element.hasClass('processing')) return;
			//element.addClass('processing');
			var vtrftk = $("input[name=__vtrftk]").val();
			var record = self.getRecordId();
			
			var wisStatus = $("#CargoInsurance_detailView_fieldValue_cf_8364").html();
			wisStatus = wisStatus.replace('<span class="value" data-field-type="string">', "");
			wisStatus = wisStatus.replace('</span>', "");
			wisStatus = wisStatus.trim();
			//alert(acompany);
			if(wisStatus=="Booked")
			{
				$('#display').html('');
                $('#diserror').html(''); //clear previous data
                $('#refstatus').html("WIS Insurance already booked");
				app.helper.hideProgress();
				return false;
			}
			
			console.log(wisStatus);
			//alert(wisStatus);
			//return;
			
			var params = {};
			params.module = app.getModuleName();
			params.action = 'SaveWIS';
			params.record = record;
			params.__vtrftk = vtrftk;
			params.company = acompany;
			val = record;
			var btn = $(this);
			$(btn).html('Start');
			//alert(csrftoken);
			app.request.post({data:params}).then(function(err,data){
				$('#diserror').html(''); //clear previous data
                $('#display').html('');
                localStorage.setItem(val, '0');
                $(btn).html('Stop');
				console.log("Return data :");
				console.log(data);
				app.helper.hideProgress();
				//return false;
				
				var obj = JSON.parse(data);
				//return;
				//var obj =data;
				
				if(obj.status == 1) 
				{
					$('#display').html(data.message);

                    var delayInMilliseconds = 3000; //5 second

                     if ( localStorage.getItem(val) == 0 ) {
                        localStorage.setItem(val, '1');
                     }

                    setTimeout(function() {
                        //your code to be executed after 5 second
                        window.location.reload();

                    }, delayInMilliseconds);
				}
				else if (obj.status == -1) 
				{
                    //send ajax to
                    // alert('hit -1');
                    $("#postwis").hide();
                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                    // $('#referal').append("<input type='button' id='check_referal' value='check referal' >");
                    // document.getElementById("check_referal").style.display = "block";
                    // check_referal
                    $("#check_referal").show();

                    $('#check_referal').on('click', function (e) {
                        e.preventDefault();
                        // alert('check_referal');
                        // console.log('check_referal');
                        $.ajax({
                            type: 'POST',
                            url: 'include/CargoInsurance/webhookstatus.php',
                            dataType: 'json',
                            data: { record: val, company:acompany },
                            success: function (data) {
                                $('#display').html('');
                                $('#diserror').html(''); //clear previous data
                                $('#refstatus').html("Referal status is: " + data.refstatus);
                            }

                        });
                    });
					//<input type='button' value='check referal' >
                }
				else if (obj.status == -2) 
				{
					//alert(obj.message);
                    //$('#quotes').html( data.message );
					$("#postwis").hide();
					$("#display").html(obj.message);
                    $("#quoteContinue").click(function () {
						//var user_id = $('#user_id').val();
                        //var btn = $(this);
                        //$(btn).buttonLoader('start');
						
						$(btn).html('Start');
                        var chosen_quote_id = $("input[id='quoteIds']:checked").val();
						console.log("chosen_quote_id");
						console.log(chosen_quote_id);
						app.helper.showProgress();
						//alert(acompany);
						//return false; 
                        $.ajax({
                            type: 'POST',
                            url: 'include/CargoInsurance/quote.php',
                            dataType: 'json',
                            data: { chosenQuoteId: chosen_quote_id,
                                    declarationId: obj.declarationId,
                                    insuranceId: val,
                                    userId: user_id,
									companyId:acompany},
                            success: function (data) {
								console.log(data);
								//alert(data);
								//return false;
								//var obj = JSON.parse(data);
                                //$(btn).buttonLoader('stop');
								$(btn).html('Post to WIS');
                                $('#display').html('');
                                $('#diserror').html(''); //clear previous data
								app.helper.hideProgress();
								//alert(data.status+" msg "+data.message);
								//alert(data.message);
								
                                if(data.status == 1) {

                                    $('#display').html(data.message);
									//alert(data.message);
                                    var delayInMilliseconds = 3000; //5 second
									
                                    if ( localStorage.getItem(val) == 0 ) {
                                        localStorage.setItem(val, '1');
                                    }

                                    setTimeout(function() {
                                        //your code to be executed after 5 second
                                        window.location.reload();

                                    }, delayInMilliseconds);

                                } 
								else if (obj.status == -1) // declaration is refferd
								{
                                    //send ajax to
                                    $("#postwis").hide();
                                    $('#diserror').html(obj.type + " . " + obj.message + " . " + obj.errors);
                                    $("#check_referal").show();
									$(btn).hide();
									var delayInMilliseconds = 3000; //5 second
									setTimeout(function() {
                                        //your code to be executed after 5 second
                                        window.location.reload();

                                    }, delayInMilliseconds);
									
                                    $('#check_referal').on('click', function (e) {
                                        e.preventDefault();
                                        $.ajax({
                                            type: 'POST',
                                            url: 'include/CargoInsurance/webhookstatus.php',
                                            dataType: 'json',
                                            data: { record: val, declarationId: declaration_id },
                                            success: function (data) {
												var obj = JSON.parse(data);
                                                $('#display').html('');
                                                $('#diserror').html(''); //clear previous data
                                                $('#refstatus').html("Referal status is: " + obj.refstatus);
                                            }

                                        });
                                    });

                                } else {
									
                                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                                }

                            }
                        });
                    });
                }
                else {
					app.helper.showErrorNotification({message:obj.message + " . " + obj.errors});
                    $('#disperror').html(obj.type + " . " + obj.message + " . " + obj.errors);
                }
				$(btn).html('Post to WIS');
				//alert(obj.message);
				//$("#display").html(obj.message);
				
				//element.removeClass('processing');
				//alert('yes am here to post data to wis'+record+' err'+err+' data '+data);
				app.helper.hideProgress();
			})
			//app.helper.hideProgress();
			//element.removeClass('processing');
		});
		// cancel declaration
		$("#cancelButton").click(function() {
			var canBeCancelled = $("#can_be_cancelled").val();
			//alert(canBeCancelled);
			//return;
			// canBeCancelled = true;
			
			if(canBeCancelled == false) {
				//alert('Перед отменой нужно обнулить поля Freight, Value в блоке Cargo Insurance Amount');
				alert('Before cancellation, you need to reset the Freight, Value fields in the Cargo Insurance Amount block');
			} else {
				
				//$("#form1").toggle();
				$("#wisCancelForm").modal();

				
			}

		});
		
		// submit cancel request
		$('#cancelSubmit').on('click', function (e) {
			// e.preventDefault();
			//alert('hit submit');
			var reason = $("#cancellationReason").val();
			var record = self.getRecordId();
			//alert(record);
			// var declaration_id = $("#cancelDeclarationId").val();
			// console.log( reason );
			//alert(reason);
			//return false;
			app.helper.showProgress();
			console.log("Cancel Process Start");
			$.ajax({
				type: 'POST',
				url: 'include/CargoInsurance/wiscanceldeclaration.php',
				dataType: 'json',
				data: { cancellationReason: reason, insuranceId: record },
				success: function (data) {
					$('#display').html('');
					$('#diserror').html(''); //clear previous data
					if(data.refstatus != '<p style="color : red">Cancel reason required</p>')
					{
						$('#refstatus').html("Cancellation status is: " + data.refstatus);
					}
					else
					{
						$('#refstatus').html(data.refstatus);
					}
					$("#cancelButton").hide();
					//$('#form1').hide();
					//$('#wisCancelForm').hide();
					app.helper.hideProgress();
					console.log("Cancel Process End");
				},
				error:function(err){
					app.helper.hideProgress();
					console.log("Process End With Err");
					console.log(err);
				}


			});
			
			

		});

		
		
		
		
		// check referal status button

		$('#check_referal').on('click', function (e) {
			// alert('hit');
			// e.preventDefault();
			// alert('check_referal');
			// console.log('check_referal');
			var record = self.getRecordId();
			$.ajax({
				type: 'POST',
				url: 'include/CargoInsurance/webhookstatus.php',
				dataType: 'json',
				data: { record: record },
				success: function (data) {
					$('#display').html('');
					$('#diserror').html(''); //clear previous data
					$('#refstatus').html( data );
				}

			});
		});

	},
	//Events common for DetailView and OverlayDetailView
	registerBasicEvents: function(){
		var self = this;
		this.registerPostToWIS();
	},	
});