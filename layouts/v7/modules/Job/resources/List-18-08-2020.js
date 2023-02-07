$('.MISReportPeriodFrom').datepicker({ });
$('.MISReportPeriodTo').datepicker({ });
$("#generate_job_assignement").click(function(event){
	var report_type = $('[name="reporttype"]').val();
	var fromdate = $(".date-from").val();
	var todate = $(".date-to").val();
	var userid = $('[name="userid[]"]').val();
	
	var formData = {
					'report_type' : report_type,
					'financial_Year' : fromdate,
					'file_title_id' : todate,
					'location_id' : userid				
				   };
    //console.log(formData);
	location.href="/index.php?module=Job&action=JobAssignment&mode=JobAssignment&report_type="+report_type+"&fromdate="+fromdate+"&todate="+todate+"&userid="+userid;

	
});

$("#sendto1c").click(function(event){
	var elem = document.getElementById("myBar");   
	var width = 0;
	
    /* declare an checkbox array */
	var chkArray = [];
	
	/* look for all checkboes that have a class 'chk' attached to it and check if it was checked */
	$("#nonUploaded:checked").each(function() {
		chkArray.push($(this).val());
	});

	if(chkArray.length > 0){
	
		document.getElementById("demo1").innerHTML = chkArray.length;
		document.getElementById( 'arraytt' ).style.display = 'block';
		
	}

	var i = 1;
	

	
	$.each(chkArray, function (index, selected) {

		$("#cf_5848_"+selected).html("<img src='layouts/vlayout/skins/images/loading.gif'>");
		$("#cf_5846_"+selected).html("<img src='layouts/vlayout/skins/images/loading.gif'>");

		// Fire off the request to /form.php
		request = $.ajax({
			url: "https://gems.globalink.net/index.php?module=Job&view=List&mode=record_1c_upload&record="+selected,
			type: "post"
		});

		// Callback handler that will be called on success
		request.done(function (response, textStatus, jqXHR){
		
			//alert(response); 
			//$("#arraytt").html(response);
			//$("#cf_5848_"+selected).html(response);
			
			var arr = response.split("_");
	
			$("#cf_5848_"+selected).html(arr[0]);
			$("#cf_5846_"+selected).html(arr[1]);
			
			if (width >= 100) {
			  
			  document.getElementById("myP").className = "w3-text-green w3-animate-opacity";
			  document.getElementById("myP").innerHTML = "Successfully uploaded 10 photos!";
			  
			} else {
			  
			  width++; 
			  
			  elem.style.width = ((width/chkArray.length)*100) + '%'; 
						
			  document.getElementById("demo").innerHTML = i;
			  i++;
			}
			
			
			document.querySelector("input[value='"+selected+"']").id = 'Uploaded' ;
	
			
			// Log a message to the console
			console.log("Hooray, it worked!");
		});

	});

});