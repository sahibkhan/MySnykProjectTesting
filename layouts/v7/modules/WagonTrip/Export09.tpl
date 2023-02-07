{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/Export.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
{strip}
	<div class="fc-overlay-modal modal-content">
		<form id="exportForm" class="form-horizontal" method="post" action="index.php">
			{*<input type="hidden" name="module" value="{$SOURCE_MODULE}" />*}
			<input type="hidden" name="module" value="{$SOURCE_MODULE}" />
			<input type="hidden" name="filetype" value="excel" />
			<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
			<input type="hidden" name="action" value="ExportData" />
			<input type="hidden" name="viewname" value="{$VIEWID}" />
			<input type="hidden" name="selected_ids" value={ZEND_JSON::encode($SELECTED_IDS)}>
			<input type="hidden" name="excluded_ids" value={ZEND_JSON::encode($EXCLUDED_IDS)}>
			<input type="hidden" id="page" name="page" value="{$PAGE}" />
			<input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
			<input type="hidden" name="operator" value="{$OPERATOR}" />
			<input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
			<input type="hidden" name="search_params" value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($SEARCH_PARAMS))}' />
			<input type="hidden" name="orderby" value="{$ORDER_BY}" />
			<input type="hidden" name="sortorder" value="{$SORT_ORDER}" />
			<input type="hidden" name="tag_params" value='{Zend_JSON::encode($TAG_PARAMS)}' />
			{if $SOURCE_MODULE eq 'Documents'}
				<input type="hidden" name="folder_id" value="{$FOLDER_ID}"/>
				<input type="hidden" name="folder_value" value="{$FOLDER_VALUE}"/>
			{/if}
			<div class="overlayHeader">
				{assign var=TITLE value="{vtranslate('LBL_EXPORT_RECORDS',$MODULE)}"}
				{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
			</div>

			<div class="modal-body" style="margin-bottom:250px">
				<div class="datacontent row">
					<div class="col-lg-3"></div>
					<div class="col-lg-6">
						<div class="well exportContents">
							{*<br><div><b>{vtranslate('LBL_EXPORT_FORMAT',$MODULE)}</b></div><br>*}
									
							{if $SOURCE_MODULE eq 'Calendar'}
								<br><div><b>{vtranslate('LBL_EXPORT_FORMAT',$MODULE)}</b></div><br>
								<div style="margin-left: 50px;">
									<div>
										<input type="radio" name="type" value="csv" id="csv" onchange="Calendar_Edit_Js.handleFileTypeChange();" checked="checked" />
										<label style="font-weight:normal" for="csv">&nbsp;&nbsp;{vtranslate('csv', $MODULE)}</label>
									</div>
									<div>
										<input type="radio" name="type" value="ics" id="ics" onchange="Calendar_Edit_Js.handleFileTypeChange();"/>
										<label style="font-weight:normal" for="ics">&nbsp;&nbsp;{vtranslate('ics', $MODULE)}</label>
									</div>
								</div>
							{/if}

							<br><div><b>{vtranslate('LBL_EXPORT_DATA',$MODULE)}</b></div><br>
							<div style="margin-left: 50px;">
								<div>
									<input type="radio" name="mode" value="ExportSelectedRecords" id="group1" {if !empty($SELECTED_IDS)} checked="checked" {else} disabled="disabled"{/if} style="margin:2px 0 -4px" />
									<label style="font-weight:normal" for="group1">&nbsp;&nbsp;{vtranslate('LBL_EXPORT_SELECTED_RECORDS',$MODULE)}</label>
									{if empty($SELECTED_IDS)}&nbsp; <span style="color:red">{vtranslate('LBL_NO_RECORD_SELECTED',$MODULE)}</span>{/if}
									<input type="hidden" class="isSelectedRecords" value="{if $SELECTED_IDS}1{else}0{/if}" >
								</div>
								<br>
								<div>
									<input type="radio" name="mode" value="ExportCurrentPage" id="group2" style="margin:2px 0 -4px" />
									<label style="font-weight:normal" for="group2">&nbsp;&nbsp;{vtranslate('LBL_EXPORT_DATA_IN_CURRENT_PAGE',$MODULE)}</label>
								</div>
								<br>
								<div>
									<input type="radio" name="mode" value="ExportAllData" id="group3" {if empty($SELECTED_IDS)} checked="checked" {/if} style="margin:2px 0 -4px" />
									<label style="font-weight:normal" for="group3">&nbsp;&nbsp;{vtranslate('LBL_EXPORT_ALL_DATA',$MODULE)}</label>
								</div>

								
								<div>
									<input type="text"  id="agent_invoice_no" name="agent_invoice_no" value="" />
									<label style="font-weight:normal" for="agent_invoice_no">&nbsp;&nbsp;Agent Invoice #</label>
								</div>  
								<div>
									<select class="inputElement select2" name="vendor_id" id="vendor_id" style="width:150px !important;" >
										<option value="">--Select Vendor--</option>
									<option value="48129"> Olzha Logistics LLP </option>	
									<option value="49362"> CF&S Estonia AS </option>
									<option value="1701566"> LogiTrade LLP </option>
									<option value="1732554"> FT Logistic </option>
									<option value="660035"> Eurotransit-KTC LLP </option>
									<option value="48974"> KTZE Khorgos Gateway  </option>
									
									<option value="2023884"> Asia Railways Tracking Service </option>
									<option value="1493399"> Oztemiryolkonteyner JSC </option>
									<option value="1485563"> Globalink Bishkek </option>
									</select>
									<label style="font-weight:normal" for="vendor_id">&nbsp;&nbsp;Report Format</label>
								</div>
							
								{if $MULTI_CURRENCY}
									<br>
									<div class="row"> 
										<div class="col-lg-8 col-md-8 col-lg-pull-0"><strong>{vtranslate('LBL_EXPORT_LINEITEM_CURRENCY',$MODULE)}:&nbsp;</strong>
											<i style="position:relative;top:4px;" class="icon-question-sign" data-toggle="tooltip" title="{vtranslate('LBL_EXPORT_CURRENCY_TOOLTIP_TEXT',$MODULE)}"></i>
										</div>
									</div>
									<br>
									<div class="row">
										<div class="col-lg-1 col-md-1 col-lg-pull-0"><input type="radio" name="selected_currency" value="UserCurrency" checked="checked"/></div>
										<div> {vtranslate('LBL_EXPORT_USER_CURRENCY',$MODULE)}&nbsp;</div>
									</div>
									<br>
									<div class="row">
										<div class="col-lg-1 col-md-1 col-lg-pull-0"><input type="radio" name="selected_currency" value="RecordCurrency"/></div>
										<div>{vtranslate('LBL_EXPORT_RECORD_CURRENCY',$MODULE)}&nbsp;</div>
									</div>
								{/if}
							</div>
							<br>
						</div>
					</div>
					<div class="col-lg-3"></div>
				</div>


				<div class="col-lg-12">
					<div class="bottomscroll-div">
						<table class="table table-bordered listViewEntriesTable" id="list4"></table>
						<div id="pager"></div>                          
					</div>
				</div>	
			</div>





			<div class="modal-overlay-footer clearfix">
				<div class="row clearfix">
					<div class=" textAlignCenter col-lg-6 col-md-6 col-sm-6 ">
						<div>
						 <button class="btn btn-success btn-lg" type="button" id="block_vendor_invoice">List Vendor Invoices</button>
                    	 &nbsp;&nbsp;&nbsp;
						<button type="submit" class="btn btn-success btn-lg">{vtranslate('LBL_EXPORT', 'Vtiger')}&nbsp;{vtranslate($SOURCE_MODULE, $SOURCE_MODULE)}</button>
						&nbsp;&nbsp;&nbsp;<a class="cancelLink" data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a>
						</div>
					</div>

					<div class="col-lg-6 col-md-6 col-sm-6 ">
						<div>
					
                    <button class="btn btn-success" type="button" id="approve_vendor_invoice"><strong>Approve Vendor Invoices</strong></button>&nbsp;&nbsp;&nbsp;&nbsp;
                    <button class="btn btn-success" type="button" id="decline_vendor_invoice"><strong>Open Vendor Invoices Access</strong></button>
                    
					</div>					
				</div>

				</div>				

			</div>
		</form>
	</div>

<link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/css/ui.jqgrid.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/ui.multiselect.css" />
{*
<script type="text/javascript" src="libraries/jqgrid/ui.multiselect.js?v=7.2.0"></script>
<script type="text/javascript" src="libraries/jqgrid/grid.locale-en.js?v=7.2.0"></script>
<script type="text/javascript" src="libraries/jqgrid/jquery.jqGrid.js?v=7.2.0"></script>*}
{literal}
<script>
/*
$(document).ready
        (
            function () {
                $('#block_vendor_invoice').on('click', function () {
					
                      var agent_invoice_no =  $('#agent_invoice_no').val();   
                      //alert(agent_invoice_no);
                       //$("#list4").jqGrid('bindKeys');
                       //s = $("#list4").jqGrid('getGridParam','selarrrow');
                       
                      // bindVendorInvoice();
					   //var agent_invoice_no = $("#agent_invoice_no").val();
						var vendor_id = $("#vendor_id").val();
						//  alert(agent_invoice_no+' '+vendor_id)  
						$grid2 = $('#list4');
						//$grid2.jqGrid('setGridParam',{ url:'',datatype: "local"});
						//$grid2.jqGrid("clearGridData", true).trigger("reloadGrid");
						
						$grid2.jqGrid(
							'setGridParam',{
										url:'index.php?module=WagonTrip&action=ExportData&mode=approveVendorInvoices',
										postData: $("#exportForm").serialize(),
										datatype: "json",
										mtype: 'POST',
										viewrecords: true, 
										}
								).jqGrid("navGrid", "#pager").trigger("reloadGrid");
						

                });
            }
        );
		*/
/*

$(function () {
   
    $grid = $("#list4"),
    $grid.jqGrid({
    url:'index.php?module=WagonTrip&action=ExportData&mode=approveVendorInvoices',
    postData: $("#exportForm").serialize(),
	datatype: "json",
    mtype: 'POST',
	//height: 250,
   	colNames:['Expense ID','Wagon Ref#','Payables Status','Coordinator','Charge','Pay To','Invoice #','Invoice Date','Type','Buy(Vendor Cur Net)','VAT',
              'Buy(Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy(Local Curr Gross)','Buy(Local Cur Net)'],
   	colModel:[
   		{"name":"jobexpencereportid","key":true,"index":"id","align":"center","hidden":true,"frozen":true},
        {"name":"wagon_ref_no","index":"wagon_ref_no","width":"150","frozen":true},
        {"name":"payable_status","index":"payable_status","width":"100","frozen":true},
        {"name":"assigned_user_id","index":"assigned_user_id","width":"100","frozen":true},
        {"name":"charge_name","index":"charge_name","width":"100","frozen":true},
        {"name":"pay_to","index":"pay_to","width":"100","frozen":true},
        {"name":"invoice_no","index":"invoice_no","width":"100","frozen":true},
        {"name":"invoice_date","index":"invoice_date","width":"100","frozen":false},
        {"name":"account_type","index":"account_type","width":"50","frozen":false},
        {"name":"buy_vendor_currency_net","index":"buy_vendor_currency_net","width":"100","frozen":false},
        {"name":"VAT","index":"VAT","width":"50","frozen":false},
        {"name":"buy_Vendor_currency_gross","index":"buy_Vendor_currency_gross","width":"100","frozen":false},
        {"name":"vendor_currency","index":"vendor_currency","width":"100","frozen":false},
        {"name":"exchange_rate","index":"exchange_rate","width":"100","frozen":false},
        {"name":"buy_local_currency_gross","index":"buy_local_currency_gross","width":"100","frozen":false},
        {"name":"buy_local_Currency_net","index":"buy_local_Currency_net","width":"100","frozen":false}
            	
   	],
    rowNum:500,
   	pager: '#pager',
    sortname: 'wagon_ref_no',
    viewrecords: true,
    //jsonReader: {
	//	repeatitems : false
	//},
    shrinkToFit: false,
   	multiselect: true,    
    height: '655',
    width: '1200',
   	caption: "Wagon Trip Approve Vendor Inovices"
});
//$("#list4").jqGrid('navGrid','#pager9',{add:false,del:false,edit:false,search:false,position:'right'});
$grid.jqGrid('navGrid', '#pager', {refreshstate: 'current', add: false, edit: false, del: false, search:false});
$grid.trigger('reloadGrid', [{ current: true}]);
$grid.jqGrid('bindKeys'); 
//var mydata = [];

 }); 
 */
 
 var timeoutHnd;
 /*
 var bindVendorInvoice = function () {

    var agent_invoice_no = $("#agent_invoice_no").val();
	var vendor_id = $("#vendor_id").val();
    //  alert(agent_invoice_no+' '+vendor_id)  
    $grid2 = $('#list4');
    //$grid2.jqGrid('setGridParam',{ url:'',datatype: "local"});
    //$grid2.jqGrid("clearGridData", true).trigger("reloadGrid");
    $grid2.jqGrid(
        'setGridParam',{
                    url:'index.php?module=WagonTrip&action=ExportData&mode=approveVendorInvoices',
                    postData: $("#exportForm").serialize(),
                    datatype: "json",
                    mtype: 'POST',
                    viewrecords: true, 
                    }
            ).jqGrid("navGrid", "#pager").trigger("reloadGrid");
   // var allData = $grid2.getGridParam('data');       
   // $grid2.jqGrid('navGrid', '#pager', {refreshstate: 'current', add: false, edit: false, del: false, search:false});
    //$grid2.trigger('reloadGrid', [{ current: true}]);  
    //$grid2.jqGrid('setFrozenColumns');
    //$grid2.jqGrid('bindKeys');
 } */

 $("#approve_vendor_invoice").click( function() {
	var s;
	s = $("#list4").jqGrid('getGridParam','selarrrow');
	//alert(s);
	 $.ajax({
					url: 'include/Jobexpencereport/approve_wagon_vendor_invoices.php?expenseids='+s,
					success: function(data){
					   //var result=JSON.parse(data);
                       $("#list4").trigger('reloadGrid', [{ current: true}]).jqGrid("navGrid", "#pager");
					   $("#list4").jqGrid('bindKeys'); 
					
					}
		   });
          
});

 $("#decline_vendor_invoice").click( function() {
	var s;
	s = $("#list4").jqGrid('getGridParam','selarrrow');
	//alert(s);
	 $.ajax({
					url: 'include/Jobexpencereport/open_wagon_vendor_invoices.php?expenseids='+s,
					success: function(data){
					   //var result=JSON.parse(data);
                       $("#list4").trigger('reloadGrid', [{ current: true}]).jqGrid("navGrid", "#pager");
					   $("#list4").jqGrid('bindKeys'); 
					
					}
		   });
          
});


  $('#block_vendor_invoice').on('click', function () {
					
			var agent_invoice_no =  $('#agent_invoice_no').val();   
			//alert(agent_invoice_no);
			//$("#list4").jqGrid('bindKeys');
			//s = $("#list4").jqGrid('getGridParam','selarrrow');
			
			// bindVendorInvoice();
			//var agent_invoice_no = $("#agent_invoice_no").val();
			var vendor_id = $("#vendor_id").val();
			//  alert(agent_invoice_no+' '+vendor_id)  
			$grid2 = $('#list4');
			//$grid2.jqGrid('setGridParam',{ url:'',datatype: "local"});
			//$grid2.jqGrid("clearGridData", true).trigger("reloadGrid");
			
			$grid2.jqGrid(
				'setGridParam',{
							url:'index.php?module=WagonTrip&action=ExportData&mode=approveVendorInvoices',
							postData: $("#exportForm").serialize(),
							datatype: "json",
							mtype: 'POST',
							viewrecords: true, 
							}
					).jqGrid("navGrid", "#pager").trigger("reloadGrid");
			

	});

</script>

{/literal}	

{/strip}