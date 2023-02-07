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
	alert('yes am here');
});