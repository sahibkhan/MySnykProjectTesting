function send_first_approval(user_id,recordID,requestedItemID){
    app.helper.showProgress();
  var vtigerurl      = window.location.href;

  vtigerurl=vtigerurl.split("&");

  vtigerurl=vtigerurl[0];
  vtigerurl=vtigerurl.split("?");
  var module= vtigerurl[1];
  module=module.split("=");
  module=module[1];
  var newurl=vtigerurl[0];
  newurl=newurl.split("index.php");
  newurl=newurl[0];
  var page = 'firstapproval';
  $.ajax({

               type: "POST",

               url: newurl+"index.php/?module=Procurement&action=Sendapprovals",
               data: {
                   'page' : page,
                   'UserID' : user_id,
                   'recordID' : recordID,
                   'ProcurementTypeID' : requestedItemID,
                 },

               success: function(result){
//                  alert(result);
// console.log(result);
				$('.btn-success').prop('disabled', true);
                 app.helper.hideProgress();
				location.reload();

               }

           });
}

function next_approval(user_id,recordID,requestedItemID,usdamount,status_val){
    app.helper.showProgress();
  var vtigerurl      = window.location.href;

  vtigerurl=vtigerurl.split("&");

  vtigerurl=vtigerurl[0];
  vtigerurl=vtigerurl.split("?");
  var module= vtigerurl[1];
  module=module.split("=");
  module=module[1];
  var newurl=vtigerurl[0];
  newurl=newurl.split("index.php");
  newurl=newurl[0];
  //var nexta_approval_value = $("#next_approval_dropdown").val();
  var nexta_approval_value = status_val;
  if(nexta_approval_value == 1){
  var page = 'nextapproval';
  $('.btn-danger').prop('disabled', true);
  $.ajax({

               type: "POST",

               url: newurl+"index.php/?module=Procurement&action=Sendapprovals",
               data: {
                   'page' : page,
                   'UserID' : user_id,
                   'recordID' : recordID,
                   'ProcurementTypeID' : requestedItemID,
                   'usdamount' : usdamount,
                 },

               success: function(result){
 //                alert(result);
 // console.log(result);
				$('.btn-success').prop('disabled', true);
                 app.helper.hideProgress();
				location.reload();

               }

           });

         }else if(nexta_approval_value == 2){
          $('#reject_error').hide();
		  var page = 'rejected';
		  if($.trim($('#reject_reason').val())=='')
		  {
			$('#reject_error').show();
			app.helper.hideProgress();
			return false;
		  }
		  $('.btn-success').prop('disabled', true);
          $.ajax({

                       type: "POST",

                       url: newurl+"index.php/?module=Procurement&action=Sendapprovals",
                       data: {
                           'page' : page,
                           'UserID' : user_id,
                           'recordID' : recordID,
                           'ProcurementTypeID' : requestedItemID,
						   'Reject_Reason' : $.trim($('#reject_reason').val()),
                         },

                       success: function(result){
                           // alert(result);
						
						$('.btn-danger').prop('disabled', true);
						 app.helper.hideProgress();
						location.reload();

                       }

                   });

         }else if(nexta_approval_value == 3){
          $('#reject_error').hide();
		  var page = 'recancel';
		  if($.trim($('#reject_reason').val())=='')
		  {
			$('#reject_error').show();
			app.helper.hideProgress();
			return false;
		  }
		  
          $.ajax({

                       type: "POST",

                       url: newurl+"index.php/?module=Procurement&action=Sendapprovals",
                       data: {
                           'page' : page,
                           'UserID' : user_id,
                           'recordID' : recordID,
                           'ProcurementTypeID' : requestedItemID,
						   'Reject_Reason' : $.trim($('#reject_reason').val()),
                         },

                       success: function(result){
                           // alert(result);
						
						$('.btn-danger').prop('disabled', true);
						 app.helper.hideProgress();
						location.reload();

                       }

                   });

         }
}