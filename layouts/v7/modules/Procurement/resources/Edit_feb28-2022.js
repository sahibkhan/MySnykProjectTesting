var proc_proctypeid = $('#requestedurl').val();

var user_name = $("#username").val();
var department = $("#department").val();
var sending_approval_status_checker = $("#sending_approval_status_checker").val();
if(sending_approval_status_checker!=0){
  $(".saveButton").hide();
}

if(proc_proctypeid=='Add'){

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
var page = 'company';

  $.ajax({

               type: "POST",

               url: newurl+"index.php/?module=Procurement&action=Companydata",
               data: {
                   'page' : page,
                 },

               success: function(res){

                 //console.log(res);
//$("div .fieldBlockContainer[data-block='LBL_PROCUREMENT_INFORMATION'] table tbody td").eq(9).html(res);
//$("#procurement_title").select2();

               }

           });

}


if(proc_proctypeid=='Edit'){
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
  var countervalue=0;   //// we have declared counter variable on the top, because ager hum is ko andar declare karen ga to jo neecha checkvalue ki condition hum na lagai ha vo nahi chala gi
  var checkvalue="";
  $("#counterinput").text("0");
  $("#checkvalue").val("No");

var current_user_ID = $("#current_user").val();
var major_id = $('[name="proc_proctype"]').val();
var pmtitle = $('[name="proc_title"]').val();
 $.ajax({ //// hum ya ajax ka code major id ka base pa child la ka ana ka liya chala raha hain///////

                type: "POST",

                url: newurl+"include/"+module+"/testing.php",
                data:'search='+major_id+','+current_user_ID+','+pmtitle,

                success: function(result){

                     $("table #hiddendiv").html(result);
                     //$("#appendtr2 #childvalues").select2();  ///// ya hum template ka andar selectbox ki copy bana raha hain//////
                }

            });

}

function get_stock(th) {
      //console.log(th.value);
      //alert($(th).val());
      var tr_id = $(th).parents('tr').attr('id');
      //alert(tr_id);
      var pm_type_id = $(th).val();
      var location_id = $('select[name="proc_location"]').val();
      var company_id = $('select[name="proc_title"]').val();
      var user_id = $("#current_user_id").val();
      var type_of_purchase = $('select[name="proc_purchase_type_pm"]').val();
      if($.trim(pm_type_id)!='Select Option') //&& (type_of_purchase=='Own-Stock' || type_of_purchase=='Own Stock')
      {
      $.post('include/Procurement/getStockForItem_procurement.php',{location_id: location_id, 
                                  pm_type_id: pm_type_id, user_id: user_id,company_id:company_id,
                                 },function(data){      
          var result=JSON.parse(data);
          console.log(data);
        // $('#'+tr_id).find('[name^="current_qty"]').val( result['inhand'] );
        // $('#'+tr_id).find('[name^="avg_consumption"]').val( result['avg_consumption'] );        
        // $('#'+tr_id).find('[name^="last_purchase_price"]').val( result['last_purchase_price'] );
        // $('#'+tr_id).find('[name^="last_qty"]').val( result['last_quantity_received'] );
        $('#'+tr_id).find('[name="current_qty[]"]').val(result['inhand']);
        $('#'+tr_id).find('[name="avg_consumption[]"]').val(result['avg_consumption']);        
        $('#'+tr_id).find('[name="last_purchase_price[]"]').val(result['last_purchase_price']);
        $('#'+tr_id).find('[name="last_qty[]"]').val(result['last_quantity_received']);
       });
      }
    }


//$('[name="proc_department"]').val(department);
var request_no = $('#requestnumber').val();
$('[name="proc_request_no"]').val(request_no);

$('#tablemainid').css("color","black");
//	$('#mainid').css("color","white");
  $('#addingbutton').css({"color":"white","background-color":"gray"});
  $("#addingbutton").unbind().click(function() {

  checkvalue = $("#checkvalue").val();

  if(checkvalue=="yes"){
	
	var childvale = $(".remove"+countervalue+" "+"#childvalues").val();
    var qty=$(".remove"+countervalue+" "+"#qty1").val();
    var psc=$(".remove"+countervalue+" "+"#psc1").val();
    var localprice=$(".remove"+countervalue+" "+"#localprice").val();
    var vat=$(".remove"+countervalue+" "+"#VAT").val();
    var pricevat=$(".remove"+countervalue+" "+"#PriceVAT").val();
    var total_local_currency=$(".remove"+countervalue+" "+"#Total_local_currency").val();
    var total_USD=$(".remove"+countervalue+" "+"#Total_USD").val();

    if(childvale=="Select Option" || qty=="" || psc=="" || localprice=="" || total_local_currency=="" || total_USD==""){
  alert("Please enter the detail for Purchasing Item");
  }else{
  var hiddendivdata = $('table #hiddendiv').html(); /// we are getting the template of the table which we have created in the hiddendiv///
  $("#tablemainid tbody").append(hiddendivdata); /// we are appending the template in our main table./////
   countervalue = $("#counterinput").html();
   //alert(countervalue);
  countervalue = ++ countervalue;
  $("#counterinput").text(countervalue);
  $("#tablemainid tbody #appendtr2").addClass("remove"+countervalue);
  $(".remove"+countervalue).append('<td><button type="button" class="btn" onClick="removeRow('+countervalue+')"><span class="add-on"><i class="fa fa-trash"></i></span></button></td>');

  $("#tablemainid tbody #appendtr2").attr("id","appendtr2"+countervalue);
  var pricevatcol = " #appendtr2"+countervalue+" #VAT";
  $("#tablemainid tbody"+pricevatcol).attr("onblur","get_currency_data('#appendtr2"+countervalue+"')");
  var psc = " #appendtr2"+countervalue+" #psc1";
  $("#tablemainid tbody"+psc).attr("onblur","getlocalprice('#appendtr2"+countervalue+"')");
  var currencyfunction = " #appendtr2"+countervalue+" #currency";
  $("#tablemainid tbody"+currencyfunction).attr("onchange","get_currency_data('#appendtr2"+countervalue+"')");
  var variable=countervalue+" #childvalues";
  $("#tablemainid tbody #appendtr2"+variable).select2();
  var variable=countervalue+" #currency";
  $("#tablemainid tbody #appendtr2"+variable).select2();
  }
  }else{

    var hiddendivdata = $('table #hiddendiv').html(); /// we are getting the template of the table which we have created in the hiddendiv////
    $("#tablemainid tbody").append(hiddendivdata); /// we are appending the template in our main table./////
     countervalue = $("#counterinput").html();
     //alert(countervalue);
    countervalue = ++ countervalue;
    $("#counterinput").text(countervalue);
    $("#tablemainid tbody #appendtr2").addClass("remove"+countervalue);
    $(".remove"+countervalue).append('<td><button type="button" class="btn" onClick="removeRow('+countervalue+')"><span class="add-on"><i class="fa fa-trash"></i></span></button></td>');

    $("#tablemainid tbody #appendtr2").attr("id","appendtr2"+countervalue);
    var pricevatcol = " #appendtr2"+countervalue+" #VAT";
    $("#tablemainid tbody"+pricevatcol).attr("onblur","get_currency_data('#appendtr2"+countervalue+"')");
    var psc = " #appendtr2"+countervalue+" #psc1";
    $("#tablemainid tbody"+psc).attr("onblur","getlocalprice('#appendtr2"+countervalue+"')");
    var currencyfunction = " #appendtr2"+countervalue+" #currency";
    $("#tablemainid tbody"+currencyfunction).attr("onchange","get_currency_data('#appendtr2"+countervalue+"')");
    var variable=countervalue+" #childvalues";
    $("#tablemainid tbody #appendtr2"+variable).select2();
    var variable=countervalue+" #currency";
    $("#tablemainid tbody #appendtr2"+variable).select2();
    $("#checkvalue").val("yes");
  }
  });
window. removeRow = function removeEducationRow(removerow)
{
  $(".remove"+removerow).remove();
  // var id = ".remove"+removerow;
  // var delete_total_value = $(id+' #Total_local_currency').val();
  // var total_local_amount = $('[name="proc_loc_currency"]').val();
  // var updated_total_local_amount = Number(total_local_amount) - Number(delete_total_value);
  // $('[name="proc_loc_currency"]').val(updated_total_local_amount);
}

function show_hide_packaging_and_fleet_fields(major_id)
{
	var requested_item_view_mod_value = $.trim($('#Procurement_detailView_fieldValue_proc_proctype span').text());
	
	if(major_id=='4786741' || requested_item_view_mod_value=='Packing Materials' || requested_item_view_mod_value=='Packing Material' || requested_item_view_mod_value=='Packaging Materials'  || requested_item_view_mod_value=='Packaging Material')
	{		
		$('[name="proc_purchase_type_pm"]').parent().show();
		$('[name="proc_purchase_type_pm"]').parent().prev().show();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_pm').show();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_pm').show();
    if($('[name="proc_purchase_type_pm"]').val()=='Direct Pack-Out' || $.trim($('#Procurement_detailView_fieldValue_proc_purchase_type_pm span span').text())=='Direct Pack-Out' || $('[name="proc_purchase_type_pm"]').val()=='Direct Pack Out' || $.trim($('#Procurement_detailView_fieldValue_proc_purchase_type_pm span span').text())=='Direct Pack Out')
    {
      //Job Number//
      $('[name="proc_job_no"]').parent().show();
      $('[name="proc_job_no"]').parent().prev().show();
      $('#Procurement_detailView_fieldValue_proc_job_no').show();
      $('#Procurement_detailView_fieldLabel_proc_job_no').show();
    }
    else
    {
      //Job Number//
      $('[name="proc_job_no"]').parent().hide();
      $('[name="proc_job_no"]').parent().prev().hide();
      $('[name="proc_job_no"]').val('');
      $('#Procurement_detailView_fieldValue_proc_job_no').hide();
      $('#Procurement_detailView_fieldLabel_proc_job_no').hide();
    }
		//Fleet Mode//
		$('[name="proc_purchase_type_fleet"]').parent().hide();
		$('[name="proc_purchase_type_fleet"]').parent().prev().hide();
    $('[name="proc_purchase_type_fleet"]').val($('[name="proc_purchase_type_fleet"] option:first').val());
    $('[name="proc_purchase_type_fleet"]').select2();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_fleet').hide();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_fleet').hide();
		//Vehicle Number//
		$('[name="proc_vehicle_no"]').parent().hide();
		$('[name="proc_vehicle_no"]').parent().prev().hide();
    $('[name="proc_vehicle_no"]').val($('[name="proc_vehicle_no"] option:first').val());
    $('[name="proc_vehicle_no"]').select2();
		$('#Procurement_detailView_fieldValue_proc_vehicle_no').hide();
		$('#Procurement_detailView_fieldLabel_proc_vehicle_no').hide();
		//Vehicle Milage//
		$('[name="proc_vehicle_mileage"]').parent().hide();
		$('[name="proc_vehicle_mileage"]').parent().prev().hide();
    $('[name="proc_vehicle_mileage"]').val('');
		$('#Procurement_detailView_fieldValue_proc_vehicle_mileage').hide();
		$('#Procurement_detailView_fieldLabel_proc_vehicle_mileage').hide();
		//alert(major_id);
		//$('[name="proc_department"]').attr('readonly',true);
	}
	else if(major_id=='4786740' || requested_item_view_mod_value=='Fleet Expenses' || requested_item_view_mod_value=='Fleet Expense')
	{
		//Fleet Mode//
		$('[name="proc_purchase_type_fleet"]').parent().show();
		$('[name="proc_purchase_type_fleet"]').parent().prev().show();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_fleet').show();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_fleet').show();
		
		if($('[name="proc_purchase_type_fleet"]').val()=='Direct To Truck' || $.trim($('#Procurement_detailView_fieldValue_proc_purchase_type_fleet span span').text())=='Direct To Truck')
		{
			//Vehicle Number//
			$('[name="proc_vehicle_no"]').parent().show();
			$('[name="proc_vehicle_no"]').parent().prev().show();
			$('#Procurement_detailView_fieldValue_proc_vehicle_no').show();
			$('#Procurement_detailView_fieldLabel_proc_vehicle_no').show();
			//Vehicle Milage//
			$('[name="proc_vehicle_mileage"]').parent().show();
			$('[name="proc_vehicle_mileage"]').parent().prev().show();
			$('#Procurement_detailView_fieldValue_proc_vehicle_mileage').show();
			$('#Procurement_detailView_fieldLabel_proc_vehicle_mileage').show();
		}
		else
		{
			//Vehicle Number//
			$('[name="proc_vehicle_no"]').parent().hide();
			$('[name="proc_vehicle_no"]').parent().prev().hide();
      $('[name="proc_vehicle_no"]').val($('[name="proc_vehicle_no"] option:first').val());
      $('[name="proc_vehicle_no"]').select2();
			$('#Procurement_detailView_fieldValue_proc_vehicle_no').hide();
			$('#Procurement_detailView_fieldLabel_proc_vehicle_no').hide();
			//Vehicle Milage//
			$('[name="proc_vehicle_mileage"]').parent().hide();
			$('[name="proc_vehicle_mileage"]').parent().prev().hide();
      $('[name="proc_vehicle_mileage"]').val('');
			$('#Procurement_detailView_fieldValue_proc_vehicle_mileage').hide();
			$('#Procurement_detailView_fieldLabel_proc_vehicle_mileage').hide();	

		}
		
		//Packaging Mode//
		$('[name="proc_purchase_type_pm"]').parent().hide();
		$('[name="proc_purchase_type_pm"]').parent().prev().hide();
    $('[name="proc_purchase_type_pm"]').val($('[name="proc_purchase_type_pm"] option:first').val());
    $('[name="proc_purchase_type_pm"]').select2();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_pm').hide();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_pm').hide();
    //Job Number//
    $('[name="proc_job_no"]').parent().hide();
    $('[name="proc_job_no"]').parent().prev().hide();
    $('[name="proc_purchase_type_pm"]').val('');
    $('#Procurement_detailView_fieldValue_proc_job_no').hide();
    $('#Procurement_detailView_fieldLabel_proc_job_no').hide();
		
	}
	else{
		//Packaging Mode//
		$('[name="proc_purchase_type_pm"]').parent().hide();
		$('[name="proc_purchase_type_pm"]').parent().prev().hide();
    $('[name="proc_purchase_type_pm"]').val($('[name="proc_purchase_type_pm"] option:first').val());
    $('[name="proc_purchase_type_pm"]').select2();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_pm').hide();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_pm').hide();
		//Fleet Mode//
		$('[name="proc_purchase_type_fleet"]').parent().hide();
		$('[name="proc_purchase_type_fleet"]').parent().prev().hide();
    $('[name="proc_purchase_type_fleet"]').val($('[name="proc_purchase_type_fleet"] option:first').val());
    $('[name="proc_purchase_type_fleet"]').select2();
		$('#Procurement_detailView_fieldValue_proc_purchase_type_fleet').hide();
		$('#Procurement_detailView_fieldLabel_proc_purchase_type_fleet').hide();
		//Vehicle Number//
		$('[name="proc_vehicle_no"]').parent().hide();
		$('[name="proc_vehicle_no"]').parent().prev().hide();
    $('[name="proc_vehicle_no"]').val($('[name="proc_vehicle_no"] option:first').val());
    $('[name="proc_vehicle_no"]').select2();
		$('#Procurement_detailView_fieldValue_proc_vehicle_no').hide();
		$('#Procurement_detailView_fieldLabel_proc_vehicle_no').hide();
		//Vehicle Milage//
		$('[name="proc_vehicle_mileage"]').parent().hide();
		$('[name="proc_vehicle_mileage"]').parent().prev().hide();
    $('[name="proc_vehicle_mileage"]').val('');
		$('#Procurement_detailView_fieldValue_proc_vehicle_mileage').hide();
		$('#Procurement_detailView_fieldLabel_proc_vehicle_mileage').hide();
		//Job Number//
    $('[name="proc_job_no"]').parent().hide();
    $('[name="proc_job_no"]').parent().prev().hide();
    $('[name="proc_job_no"]').val('');
    $('#Procurement_detailView_fieldValue_proc_job_no').hide();
    $('#Procurement_detailView_fieldLabel_proc_job_no').hide();
		//alert('2 '+major_id); 
	}
}

////// this code is for add page dropdown/////////

$('[name="proc_proctype"],[name="proc_title"]').on('change', function(){
  var countervalue1=0;   //// we have declared counter variable on the top, because ager hum is ko andar declare karen ga to jo neecha checkvalue ki condition hum na lagai ha vo nahi chala gi
  var checkvalue1="";
  $("#counterinput1").text("0");
  $("#checkvalue1").val("No");

var major_id = $('[name="proc_proctype"]').val();

if(major_id){ ////  ager major_id ma kuch ha to if ki condition ma jy ga or add button ko show kar daa ga///

 show_hide_packaging_and_fleet_fields(major_id);//// show/hide Packaging-Mode/Fleet-Mode field 

  $("#tablemainid2 tbody").empty();
  $("table #hiddendiv2").empty();
$("#addingbutton2").css("display","block");
var current_user_ID = $("#current_user").val();
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
var pmtitle = $('[name="proc_title"]').val();
//alert(newurl+"include/"+module+"/testing.php");
  $.ajax({ //// hum ya ajax ka code major id ka base pa child la ka ana ka liya chala raha hain///////

                 type: "POST",

                 url: newurl+"include/"+module+"/testing.php",
                   data:'search='+major_id+','+current_user_ID+','+pmtitle,

                 success: function(result){
                  // alert(result);
                  if(result!='no'){ //// ager result ma no ata ha neecha sa add ka button hide kar dana ha or alert message show karna ha ka ap ka data mojood nahi ha is liya pahla child add karo///
                      $("table #hiddendiv2").html(result);
}else{
alert("Please Add Expense Detail");
$("#addingbutton2").css("display","none");
}
                      //$("#appendtr2 #childvalues").select2();  ///// ya hum template ka andar selectbox ki copy bana raha hain//////
                 }

             });

}else{ /// ager ap na dropdown ma sa dubara select an option ko select kar liya ha to button dubara hide ho jy ga////

$("#addingbutton2").css("display","none");

}

$("#addingbutton2").unbind().click(function() {
checkvalue1 = $("#checkvalue1").val(); 

if(checkvalue1=="yes"){

  var childvale = $(".remove"+countervalue1+" "+"#childvalues").val();
  var qty=$(".remove"+countervalue1+" "+"#qty1").val();
  var psc=$(".remove"+countervalue1+" "+"#psc1").val();
  var localprice=$(".remove"+countervalue1+" "+"#localprice").val();
  var vat=$(".remove"+countervalue1+" "+"#VAT").val();
  var pricevat=$(".remove"+countervalue1+" "+"#PriceVAT").val();
  var total_local_currency=$(".remove"+countervalue1+" "+"#Total_local_currency").val();
  var total_USD=$(".remove"+countervalue1+" "+"#Total_USD").val();

  if(childvale=="Select Option" || qty=="" || psc=="" || localprice=="" || total_local_currency=="" || total_USD=="" || gross_local==""){
alert("Please enter the detail for Purchasing Item");
}else{
var hiddendivdata = $('table #hiddendiv2').html(); /// we are getting the template of the table which we have created in the hiddendiv///
$("#tablemainid2 tbody").append(hiddendivdata); /// we are appending the template in our main table./////
 countervalue1 = $("#counterinput1").html();
countervalue1 = ++ countervalue1;
$("#counterinput1").text(countervalue1);
$("#tablemainid2 tbody #appendtr2").addClass("remove"+countervalue1);
$(".remove"+countervalue1).append('<td><button type="button" class="btn" onClick="removeRow('+countervalue1+')"><span class="add-on"><i class="fa fa-trash"></i></span></button></td>');

$("#tablemainid2 tbody #appendtr2").attr("id","appendtr2"+countervalue1);
var pricevatcol = " #appendtr2"+countervalue1+" #VAT";
$("#tablemainid2 tbody"+pricevatcol).attr("onblur","get_currency_data('#appendtr2"+countervalue1+"')");
var qty = " #appendtr2"+countervalue1+" #qty1";
$("#tablemainid2 tbody"+qty).attr("onblur","getlocalprice('#appendtr2"+countervalue1+"')");
var psc = " #appendtr2"+countervalue1+" #psc1";
$("#tablemainid2 tbody"+psc).attr("onblur","getlocalprice('#appendtr2"+countervalue1+"')");
var currencyfunction = " #appendtr2"+countervalue1+" #currency";
$("#tablemainid2 tbody"+currencyfunction).attr("onchange","get_currency_data('#appendtr2"+countervalue1+"')");
var variable=countervalue1+" #childvalues";
$("#tablemainid2 tbody #appendtr2"+variable).select2();
var variable=countervalue1+" #currency";
$("#tablemainid2 tbody #appendtr2"+variable).select2();

}
}else{
  var hiddendivdata = $('table #hiddendiv2').html(); /// we are getting the template of the table which we have created in the hiddendiv////
  $("#tablemainid2 tbody").append(hiddendivdata); /// we are appending the template in our main table./////
   countervalue1 = $("#counterinput1").html();
  countervalue1 = ++ countervalue1;
  $("#counterinput1").text(countervalue1);
  $("#tablemainid2 tbody #appendtr2").addClass("remove"+countervalue1);
  $(".remove"+countervalue1).append('<td><button type="button" class="btn" onClick="removeRow('+countervalue1+')"><span class="add-on"><i class="fa fa-trash"></i></span></button></td>');

  $("#tablemainid2 tbody #appendtr2").attr("id","appendtr2"+countervalue1);
  var pricevatcol = " #appendtr2"+countervalue1+" #VAT";
  $("#tablemainid2 tbody"+pricevatcol).attr("onblur","get_currency_data('#appendtr2"+countervalue1+"')");
  var qty = " #appendtr2"+countervalue1+" #qty1";
$("#tablemainid2 tbody"+qty).attr("onblur","getlocalprice('#appendtr2"+countervalue1+"')");
  var psc = " #appendtr2"+countervalue1+" #psc1";
  $("#tablemainid2 tbody"+psc).attr("onblur","getlocalprice('#appendtr2"+countervalue1+"')");
  var currencyfunction = " #appendtr2"+countervalue1+" #currency";
  $("#tablemainid2 tbody"+currencyfunction).attr("onchange","get_currency_data('#appendtr2"+countervalue1+"')");
  var variable=countervalue1+" #childvalues";
  $("#tablemainid2 tbody #appendtr2"+variable).select2();
  var variable=countervalue1+" #currency";
  $("#tablemainid2 tbody #appendtr2"+variable).select2();
  $("#checkvalue1").val("yes");
}
});
window. removeRow = function removeEducationRow(removerow)
{
$(".remove"+removerow).remove();
// var id = ".remove"+removerow;
// var delete_total_value = $(id+' #Total_local_currency').val();
// var total_local_amount = $('[name="proc_loc_currency"]').val();
// var updated_total_local_amount = Number(total_local_amount) - Number(delete_total_value);
// $('[name="proc_loc_currency"]').val(updated_total_local_amount);
}


});

$('[name="proc_purchase_type_fleet"],[name="proc_purchase_type_pm"]').on('change', function(){
	
	var major_id = $('[name="proc_proctype"]').val();

	show_hide_packaging_and_fleet_fields(major_id);//// show/hide Fleet-mode/Packaging-Mode field 
	
	});


///// price VAT function////
//	var agentCodeURL = '/devcloud/index.php?module=REDGenerateCode&action=CustomAction';
function getlocalprice(id){

  var qty = $(id+" #qty1").val();
  var psc = $(id+" #psc1").val();
  var totalprice = Number(qty) * Number(psc);
  $(id+" #localprice").val(totalprice);
  if(Number(psc) > 0) // also call get_currency_data function as customer may have changed the qty or price
  {
    get_currency_data(id);
   
  }
}
function getpricevat(id){

  ///alert("getpricevat");
  var localprice = $(id+" #localprice").val();
  var vat = $(id+" #VAT").val();
  var vatpercentage = Number(vat) / Number(100);
  app.helper.showProgress();
  var totalvat = Number(localprice) * Number(vatpercentage);
  var total_local_value = Number(localprice) + Number(totalvat);
  var total_local_currency_net = Number(total_local_value) - Number(totalvat);
  $(id+" #pricevat").val(totalvat);
  var items_currency = $(id+" #currency").val();
  $(id+" #hidden_change_currency").val(items_currency);
  $(id+" #gross").val(total_local_value);
  $(id+" #gross_local").val(total_local_value);
  $(id+" #Total_local_currency").val(total_local_currency_net);
  var local_calculated_price = $('[name="proc_loc_currency"]').val();
  var local_calculated_price_hidden = $(id+' #Total_local_currency_hidden').val();
  var local_calculated_price_org = Number(local_calculated_price) + Number(total_local_currency_net) - Number(local_calculated_price_hidden);

  //$('[name="proc_loc_currency"]').val(local_calculated_price_org);
  $(id+' #Total_local_currency_hidden').val(local_calculated_price_org);
  var datadate = $('[name="proc_issue_date"]').val();
  var fromcurrency = $(id+" #currency").val();
//  alert(fromcurrency);
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
  var page = 'add_currency_conversion';
$.ajax({

             type: "POST",

             url: newurl+"index.php/?module=Procurement&action=Procurementdata",
             data: {
                 'search' : total_local_value,
                 'currency' : fromcurrency,
                 'date' : datadate,
                 'page' : page,
               },

             success: function(res){
               app.helper.hideProgress();
//alert(res);//usman
$(id+" #Total_USD").val(res.result);
var dollar_amount = $('[name="proc_total_amount"]').val();              //for edit page (var = value get krny ka method hai "proc_total_amount ka get kr rhy hain")
var totalamountinusd=Number(dollar_amount) + Number(res.result);
$('[name="proc_total_amount"]').val(totalamountinusd);             //for add page
             }

         });
  
}

function getlocalprice1(id){

  var updatedID = '#tablemainid tbody #apend-'+id;
  // alert(updatedID);
  var qty = $(updatedID+' #qty1').val();
  var psc = $(updatedID+" #psc1").val();
  var totalprice = Number(qty) * Number(psc);
  $(updatedID+" #localprice-apend-"+id).val(totalprice);
}
function getpricevat1(id){
  alert("getpricevat1");


  var updatedID = '#tablemainid tbody #apend-'+id;

  var localprice = $(updatedID+" #localprice-apend-"+id).val();
  var vat_original_value = $(updatedID+" #vat_original_value").val();
  var vat = $(updatedID+" #VAT").val();

  if(vat_original_value!=vat){
  var pmtitle = $('[name="proc_title"]').val();
  var vat = $(updatedID+" #VAT").val();

  var vatpercentage = Number(vat) / Number(100);
  app.helper.showProgress();
  var totalvat = Number(localprice) * Number(vatpercentage);
  var total_local_value = Number(localprice) + Number(totalvat);
  var total_local_currency_net = Number(total_local_value) - Number(totalvat);
  $(updatedID+" #PriceVAT").val(totalvat);
  var items_currency = $(id+" #currency").val();
  $(id+" #hidden_change_currency").val(items_currency);
  $(updatedID+" #gross").val(total_local_value);
  var local_calculated_price = $('[name="proc_loc_currency"]').val();
  var local_calculated_price_hidden = $(id+' #Total_local_currency_hidden').val();
  var local_calculated_price_org = Number(local_calculated_price) + Number(total_local_currency_net) - Number(local_calculated_price_hidden);
  //$('[name="proc_loc_currency"]').val(local_calculated_price_org);
  $(id+' #Total_local_currency_hidden').val(local_calculated_price_org);
  var fromcurrency = $(updatedID+" #currency-apend-"+id).val();
// alert(fromcurrency);
  var datadate = $('[name="proc_issue_date"]').val();
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
  var page = 'edit_currency_conversion';
$.ajax({

             type: "POST",

             url: newurl+"index.php/?module=Procurement&action=Procurementdata",
             data: {
                 'search' : total_local_value,
                 'currency' : fromcurrency,
                 'date' : datadate,
                 'page' : page,
                 'total_local_currency_net' : total_local_currency_net,
                 'pmtitle' : pmtitle,
               },

             success: function(res){
               app.helper.hideProgress();
               // alert(res);
               console.log(res);
               var splitedvalue = res.split(',');
               $(updatedID+" #gross_local-apend-"+id).val(splitedvalue[0]);
               $(updatedID+" #Total_local_currency-apend-"+id).val(splitedvalue[1]);
$(updatedID+" #Total_USD").val(splitedvalue[2]);

             }

         });
       }
}


function deleterecord(removeID){


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

var page = 'delete';
     var del=confirm("Are you sure you want to delete this record?");
     if(del==true){

   $.ajax({

                type: "POST",

                url: newurl+"index.php/?module=Procurement&action=Companydata",
                data: {
                    'page' : page,
                    'deletedID' : removeID,
                  },

                success: function(result){
                  //alert(result);
location.reload();
                }

            });
          }

}

function get_currency_data(id){
  getpricevat(id);
  app.helper.showProgress();
  var pmtitle = $('[name="proc_title"]').val();
  var proc_id = $.trim($('[name="record"]').val());
  var gross_local = $(id+" #gross").val();
  var total_local_currency = $(id+" #localprice").val();
  var currency = $(id+" #currency").val();
  var previous_currency = $(id+" #hidden_change_currency").val();
  var local_currency_code = $("#local_currency").val();
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

  var page = 'currencyfunction';

   $.ajax({

                type: "POST",

                url: newurl+"index.php/?module=Procurement&action=Companydata",
                data: {
                    'page' : page,
                    'gross_local' : gross_local,
                    'total_local_currency' : total_local_currency,
                    'currency' : currency,
                    'local_currency_code' : local_currency_code,
                    'previous_currency' : previous_currency,
                    'pmtitle' : pmtitle,
					'rec_id' : proc_id,
                  },

                success: function(result){

  var splitedvalue = result.split(',');
  $(id+" #gross_local").val(splitedvalue[0]);
  $(id+" #Total_local_currency").val(splitedvalue[1]);
  $(id+" #Total_USD").val(splitedvalue[2]);

  var local_calculated_price = $('[name="proc_loc_currency"]').val();
  var local_calculated_price_hidden = $(id+' #Total_local_currency_hidden').val();

  var local_calculated_price_org = Number(local_calculated_price) - Number(total_local_currency);

  var updated_local_calculated_price_org = Number(local_calculated_price_org) + Number(splitedvalue[1]);

  //$('[name="proc_loc_currency"]').val(updated_local_calculated_price_org.toFixed(2));
  $(id+' #Total_local_currency_hidden').val(splitedvalue[1]);
  $(id+" #hidden_change_currency").val(currency);
                }

            });

app.helper.hideProgress();
}


function get_currency_data1(id){
  app.helper.showProgress();
  var gross_local = $("#gross-"+id).val();
  var total_local_currency = $("#localprice-"+id).val();
  var currency = $("#currency-"+id).val();
  
  alert(currency); app.helper.hideProgress(); return false;
  var previous_currency = $("#hidden_change_currency-"+id).val();
  var local_currency_code = $("#local_currency").val();
  var pmtitle = $('[name="proc_title"]').val();
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

  var page = 'currencyfunction';

   $.ajax({

                type: "POST",

                url: newurl+"index.php/?module=Procurement&action=Companydata",
                data: {
                    'page' : page,
                    'gross_local' : gross_local,
                    'total_local_currency' : total_local_currency,
                    'currency' : currency,
                    'local_currency_code' : local_currency_code,
                    'previous_currency' : previous_currency,
                    'pmtitle' : pmtitle,
                  },

                success: function(result){
//alert(result);
  var splitedvalue = result.split(',');
  $("#gross_local-"+id).val(splitedvalue[0]);
  $("#Total_local_currency-"+id).val(splitedvalue[1]);
    $("#Total_USD-"+id).val(splitedvalue[2]);
  $("#hidden_change_currency-"+id).val(currency);
  var local_calculated_price = $('[name="proc_loc_currency"]').val();
  var local_calculated_price_hidden = $(id+' #Total_local_currency_hidden').val();

  var local_calculated_price_org = Number(local_calculated_price) - Number(total_local_currency);

  var updated_local_calculated_price_org = Number(local_calculated_price_org) + Number(splitedvalue[1]);

  //$('[name="proc_loc_currency"]').val(updated_local_calculated_price_org.toFixed(2));
  $(id+' #Total_local_currency_hidden').val(splitedvalue[1]);
  $(id+" #hidden_change_currency").val(currency);
                }

            });
app.helper.hideProgress();

}

$(document).ready(function(){

  $('[name="proc_department"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_loc_currency"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_total_amount"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_currency_usd_rate"]').attr('readonly',true);
  $('[name="proc_company_status"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_location"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_request_no"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="proc_order_status"]:not(.listSearchContributor)').attr('readonly',true);
  $('[name="assigned_user_id"]:not(.listSearchContributor)').attr('readonly',true);
  show_hide_packaging_and_fleet_fields($('[name="proc_proctype"]').val()); ///function call
  if(proc_proctypeid=='Edit'){
  $('[name="proc_title"]').attr('readonly',true);
  $('[name="proc_proctype"]').attr('readonly',true);
  }

  $("#proc_supplier_display").closest( "td" ).append('<br><i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement" style="cursor:pointer;"></i>');
	$("#Procurement_editView_fieldName_proc_agreement_no").closest( "td" ).html('<select name="proc_agreement_no" class="select2 agreementdropdown" style="width:75%" required><option value="">Select Option</option></select>');
  $(".agreementdropdown").select2();
  load_agreements('auto');
  ///// to select item in the dropdown using jquery //////
$('#searchagreement').on('click',function(){

						
						load_agreements('manual');
					});

}); //// document.ready end here/////

//// refresh button click function ///////


					

///// refresh button click function end here ///////

function load_agreements(load_type)
{
						var customer_id =	$('[name="proc_supplier"]').val();
						var file_title = $('[name="proc_title"]').val();
						//var invoice_date =	$('[name="cf_1355"]').val();

						if(customer_id!=''){
						var record_id =	$('[name="record"]').val();
						//var actions =	$('[name="returnview"]').val();

						// $("div .quickCreateContent table tbody").find( "td" ).eq(13).append('<i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
						// $("div .fieldBlockContainer[data-block='Selling'] table tbody").find( "td" ).eq(3).append('<br><i class="fa fa-refresh" title="Find Agreement" aria-hidden="true" id="searchagreement"></i>');
						var vtigerurl = window.location.href;  //// ya hum na url get karna ka liya leekha ha//////
						vtigerurl=vtigerurl.split("&");
						vtigerurl=vtigerurl[0];
						vtigerurl=vtigerurl.split("?");
						var module = vtigerurl[1];
						module = module.split("=");
						module=module[1];
						var newurl=vtigerurl[0];
						newurl=newurl.split("index.php");
						newurl=newurl[0];  ///// url ka code yahan par khatam hota ha ///////
						if(load_type=='manual'){ 
						$('#searchagreement').addClass('fa-spin');
						}
						$.ajax({
												type: "POST",
												'async': false,
												'global': false,
												 url: newurl+"index.php?module=Procurement&action=FindAgreement",
												 data: {
														 'customer_id' : customer_id,
														 
														 'record_id' : record_id,
														 'file_title' : file_title,
														 
													 },
													success: function(res){

														$(".agreementdropdown").closest( "td" ).html(res);
														//$("div .quickCreateContent table tbody").find( "td" ).eq(25).html(res);
														$(".agreementdropdown").select2();
														$('#searchagreement').removeClass('fa-spin');

												 }
										});
									
									}else if(load_type=='manual'){

app.helper.showAlertNotification({message:app.vtranslate('Please select supplier')},{delay:3000});
									}
}
