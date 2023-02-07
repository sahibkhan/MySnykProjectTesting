

Vtiger_List_Js("Job_List_Js", {

		
	

},
{
	JobFilesTo1c: function(container) {
		var self = this;
		//var listViewContentDiv = this.getListViewContainer();
		var module = app.getModuleName();
		jQuery('#sendto1c').on('click', function (e) {
			var listInstance = Vtiger_List_Js.getInstance();
			// Compute selected ids, excluded ids values, along with cvid value and pass as url parameters
			var selectedIds = listInstance.readSelectedIds(true);

			if (selectedIds) {
				var cvId = self.getCurrentCvId();
				var module = self.getModuleName();
				//var parent = app.getParentModuleName();
				var defaultParams = self.getDefaultParams();

				var params = {};
				params['type'] = 'POST';
                params['module'] = module;
                params['view'] = 'List';
                params['mode'] = 'record_1c_upload';
				params['viewname'] = cvId;
				params['__vtrftk'] = csrfMagicToken;
                //params['record'] = selectedIds;
				params = jQuery.extend(params, self.getListSelectAllParams(false));
				//console.log(params);
				
				app.helper.showProgress();
				app.request.post({data:params}).then(
					function (err, data) {
						if (err == null) {
							app.helper.hideProgress();
							app.helper.showSuccessNotification({message: app.vtranslate('LBL_JOBFILE_SUCCESSFULLY_POSTED_TO_1C')});
							self.loadListViewRecords();
							self.clearList();
						} else {
							app.helper.hideProgress();
							app.helper.showErrorNotification({message: app.vtranslate(err.message)})
						}
					});
				
			}
		});
	},

	registerEvents: function(container) {
		this._super(container);
		this.JobFilesTo1c(container);
	}

});



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



