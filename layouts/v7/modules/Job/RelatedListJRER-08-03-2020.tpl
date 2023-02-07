{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
	{assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}
	{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE LISTVIEW_HEADERS=$RELATED_HEADERS}
	<div class="relatedContainer">
		{assign var=IS_RELATION_FIELD_ACTIVE value="{if $RELATION_FIELD}{$RELATION_FIELD->isActiveField()}{else}false{/if}"}
		<input type="hidden" name="currentPageNum" value="{$PAGING->getCurrentPage()}" />
		<input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE_NAME}" />
		<input type="hidden" value="{$ORDER_BY}" id="orderBy">
		<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
		<input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
		<input type='hidden' value="{$PAGING->getPageLimit()}" id='pageLimit'>
		<input type='hidden' value="{$PAGING->get('page')}" id='pageNumber'>
		<input type="hidden" value="{$PAGING->isNextPageExists()}" id="nextPageExist"/>
		<input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
		<input type='hidden' value="{$TAB_LABEL}" id='tab_label' name='tab_label'>
		<input type='hidden' value="{$IS_RELATION_FIELD_ACTIVE}" id='isRelationFieldActive'>

		{include file="partials/RelatedListHeader.tpl"|vtemplate_path:$RELATED_MODULE_NAME}

			{if $JOB_OWNER}

			<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
		   	 	<div class="bottomscroll-div">
				<table class="table listview-table" id="listview-table">
                <thead >
                <tr class="listViewHeaders">    
                <th colspan="5" class="nowrap" >Assigned User Job Completion Report</th>        
                </tr>
                </thead>
                <thead>
                <tr class="listViewHeaders">    
                <th class="nowrap" >Name</th>
                <th class="nowrap">Branch Department</th>    
                <th class="nowrap" >Designation</th>
                <th class="nowrap" >Email</th>
                <th class="nowrap" >Action</th>    
                </tr>
                </thead>    
                <tbody>
                
				{foreach item=ASSIGNED_USER from=$JOB_ASSIGNED_USER}                  
                <tr class="listViewEntries">    
                    <td class="relatedListEntryValues" >{$ASSIGNED_USER['first_name']} {$ASSIGNED_USER['last_name']} </td>
                    <td class="relatedListEntryValues" >{$ASSIGNED_USER['cf_1559']} {$ASSIGNED_USER['cf_1542']}</td>        
                    <td class="relatedListEntryValues" >{$ASSIGNED_USER['title']}</td>
                    <td class="relatedListEntryValues" ><a href="mailto:{$ASSIGNED_USER['email1']}">{$ASSIGNED_USER['email1']}</a></td>
                    <td class="relatedListEntryValues" >
                    <a href="SubJCR_export_pdf.php?record={$ASSIGNED_USER['job_id']}&userid={$ASSIGNED_USER['user_id']}" target="_blank" title=""><i class="icon-print alignMiddle" title="Job Completion Report from {$ASSIGNED_USER['cf_1559']} {$ASSIGNED_USER['cf_1542']}"></i></a>
                    &nbsp;&nbsp;
                    <a href="SubJCR_local_export_pdf.php?record={$ASSIGNED_USER['job_id']}&userid={$ASSIGNED_USER['user_id']}" target="_blank" title=""><i class="icon-print alignMiddle" title="Local JC Report from {$ASSIGNED_USER['cf_1559']} {$ASSIGNED_USER['cf_1542']}"></i></a>
                    </td>
                </tr>    
                 {/foreach}   
                </tbody>
                </table>
				</div>
			</div>

			{/if}
		
		<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
			<div class="bottomscroll-div" style="min-height:350px">
				<table id="list" class="table listview-table">
					<tr><td ></td></tr>
				</table>
                 <div id="pager"></div>
            
                 <br /><br />
			</div>
		</div>

       
           <br /> <br /> <br />
          <br />


        <div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
		    <div class="bottomscroll-div" style="min-height:415px">
            <table class="table listview-table" id="list_selling"><tr><td  ></td></tr></table>
            <div id="pager_selling"></div>
                                    
            <br />
            <br />            
            
          
          	</div>
          </div>


{if $MODULE == 'Job' }

{literal}
<link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/redmond/jquery-ui.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/css/ui.jqgrid.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/ui.multiselect.css" />
<script type="text/javascript" src="libraries/jqgrid/ui.multiselect.js"></script>
<script type="text/javascript" src="libraries/jqgrid/grid.locale-en.js"></script>
<script type="text/javascript" src="libraries/jqgrid/jquery.jqGrid.js"></script>

<style type="text/css">
        th.ui-th-column div {
            /* see http://stackoverflow.com/a/7256972/315935 for details */
            word-wrap: break-word;      /* IE 5.5+ and CSS3 */
            white-space: -moz-pre-wrap; /* Mozilla, since 1999 */
            white-space: -pre-wrap;     /* Opera 4-6 */
            white-space: -o-pre-wrap;   /* Opera 7 */
            white-space: pre-wrap;      /* CSS3 */
            overflow: hidden;
            height: auto !important;
            vertical-align: middle;
        }
		/*
        .ui-jqgrid tr.jqgrow td {
            white-space: normal !important;
            height: auto;
            vertical-align:text-top;
            padding-top: 2px;
            padding-bottom: 2px;
        }
		*/
        .ui-jqgrid .ui-jqgrid-htable th.ui-th-column {
            padding-top: 2px;
            padding-bottom: 2px;
        }
        .ui-jqgrid .frozen-bdiv, .ui-jqgrid .frozen-div {
            overflow: hidden;
        }
		
		.ui-jqgrid tr.jqgrow td { text-overflow: ellipsis;-o-text-overflow: ellipsis; }
		
		
    </style>

<script type="text/javascript">

$(function () {
'use strict';
var mydata = [],
$grid = $("#list"),
 resizeColumnHeader = function () {
                    var rowHight, resizeSpanHeight,
                        // get the header row which contains
                        headerRow = $(this).closest("div.ui-jqgrid-view")
                            .find("table.ui-jqgrid-htable>thead>tr.ui-jqgrid-labels");
        
                    // reset column height
                    headerRow.find("span.ui-jqgrid-resize").each(function () {
                        this.style.height = '';
                    });
        
                    // increase the height of the resizing span
                    resizeSpanHeight = 'height: ' + headerRow.height() + 'px !important; cursor: col-resize;';
                    headerRow.find("span.ui-jqgrid-resize").each(function () {
                        this.style.cssText = resizeSpanHeight;
                    });
        
                    // set position of the dive with the column header text to the middle
                    rowHight = headerRow.height();
                    headerRow.find("div.ui-jqgrid-sortable").each(function () {
                        var $div = $(this);
                        $div.css('top', (rowHight - $div.outerHeight()) / 2 + 'px');
                    });
                },
fixPositionsOfFrozenDivs = function () {
		var $rows;
		if (this.grid.fbDiv !== undefined) {
			$rows = $('>div>table.ui-jqgrid-btable>tbody>tr', this.grid.bDiv);
			$('>table.ui-jqgrid-btable>tbody>tr', this.grid.fbDiv).each(function (i) {
				var rowHight = $($rows[i]).height(), rowHightFrozen = $(this).height();
				if ($(this).hasClass("jqgrow")) {
					$(this).height(rowHight);
					rowHightFrozen = $(this).height();
					if (rowHight !== rowHightFrozen) {
						$(this).height(rowHight + (rowHight - rowHightFrozen));
					}
				}
			});
			$(this.grid.fbDiv).height(this.grid.bDiv.clientHeight);
			$(this.grid.fbDiv).css($(this.grid.bDiv).position());
		}
		if (this.grid.fhDiv !== undefined) {
			$rows = $('>div>table.ui-jqgrid-htable>thead>tr', this.grid.hDiv);
			$('>table.ui-jqgrid-htable>thead>tr', this.grid.fhDiv).each(function (i) {
				var rowHight = $($rows[i]).height(), rowHightFrozen = $(this).height();
				$(this).height(rowHight);
				rowHightFrozen = $(this).height();
				if (rowHight !== rowHightFrozen) {
					$(this).height(rowHight + (rowHight - rowHightFrozen));
				}
			});
			$(this.grid.fhDiv).height(this.grid.hDiv.clientHeight);
			$(this.grid.fhDiv).css($(this.grid.hDiv).position());
		}
	},
fixGboxHeight = function () {
	var gviewHeight = $("#gview_" + $.jgrid.jqID(this.id)).outerHeight(),
		pagerHeight = $(this.p.pager).outerHeight();

	$("#gbox_" + $.jgrid.jqID(this.id)).height(gviewHeight + pagerHeight);
	gviewHeight = $("#gview_" + $.jgrid.jqID(this.id)).outerHeight();
	pagerHeight = $(this.p.pager).outerHeight();
	$("#gbox_" + $.jgrid.jqID(this.id)).height(gviewHeight + pagerHeight);};




$grid.jqGrid({
    datatype: "json",	
	url:$(location).attr('href')+'&grid=1',
	//datatype: "local",
    colNames: [{/literal}{$EXPENCE_HEADER}{literal}],
    colModel: {/literal}{$EXPENCE_FIELD}{literal},
 
   	rowNum:500,
    width:1250,
	
   	//rowList:[10,20,30],
   	pager: '#pager',
   	sortname: 'invdate',
    viewrecords: true,
    sortorder: "desc",
	
	jsonReader: {
		repeatitems : false
	},
	shrinkToFit: false,
	caption: "Expense",
	height: 225,
	footerrow: false,
	//loadonce:true,
	//multiboxonly: true,
	/*loadComplete: function () {
    $(this).jqGrid('footerData','set',
        {name:'TOTAL', amount:"500", tax:"111", total:'20'},
		{name:'TOTAL 2', amount:"500", tax:"111", total:'20'}
		);
	}
	*/
		
	loadComplete: function () {
		
		fixPositionsOfFrozenDivs.call(this);
		

	}
	
	
});
/*
for(var i=0;i<=mydata.length;i++)
	$grid.jqGrid('addRowData',i+1,mydata[i]);
*/
$grid.jqGrid('setColProp', 'id', {frozen: true});
$grid.jqGrid('destroyFrozenColumns');
$grid.jqGrid('setGridParam', {multiselect: false});
$grid.jqGrid('setFrozenColumns');
$grid.trigger('reloadGrid', [{ current: true}]);
$grid.jqGrid('bindKeys');

//$grid.jqGrid('getGridParam','selarrrow');




$grid.jqGrid('navGrid', '#pager', {refreshstate: 'current', add: false, edit: false, del: false, search:false});
            $.extend(true, $.ui.multiselect, {
                locale: {
                    addAll: 'Make all visible',
                    removeAll: 'Hidde All',
                    itemsCount: 'Avlialble Columns'
                }
            });
            //$.extend(true, $.jgrid.col, {
            //    width: 500,
            //    msel_opts: {dividerLocation: 0.5}
            //});
            $grid.jqGrid('navButtonAdd', '#pager', {
                caption: "",
                buttonicon: "ui-icon-calculator",
                title: "Choose columns",
                onClickButton: function () {
                    $(this).jqGrid('columnChooser',
                        {width: 550, msel_opts: {dividerLocation: 0.5}});
                    //$(this).jqGrid('columnChooser');
                    $("#colchooser_" + $.jgrid.jqID(this.id) + ' div.available>div.actions')
                        .prepend('<label style="float:left;position:relative;margin-left:0.6em;top:0.6em">Search:</label>');
                }
            });
			$grid.jqGrid('gridResize');

			
			$("#PaymentVoucherButton").click( function() {
		var j=0;
		var rowId = '';
		var data = $("#list").getRowData();
		for(i=0;i<data.length;i++){
			
        	if(data[i].send_to_head_of_department_for_approval=='Yes'){
           // str += data[i].id+',';
				var j=i+1;
				rowId += $('#list tr:eq('+j+')').attr('id')+',';
			//newid += $('#list_selling').jqGrid ('getCell', rowId, i);
			//var rowId = data[i];
    		//var rowData = $('#list_selling').jqGrid ('getRowData', data[i]);	
			//alert(rowData);
			}			
	   }
	   
	   if(rowId)
	   {	
	   	   var role='{/literal}{$COORDINATOR_DEPARTMENT_HEAD_ROLE}{literal}';
		   $.ajax({
					url: 'include/Jobexpencereport/send_to_head_of_department.php?job_id='+{/literal}{$JOB_ID}{literal}+'&role='+role+'&expenseids='+rowId,
					success: function(data){
					   //var result=JSON.parse(data);
					//window.location='index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$JOB_ID}{literal}+'&mode=showRelatedList&tab_label=Job Revenue and Expence';
					$("#list").trigger('reloadGrid', [{ current: true}]);
					$("#list").jqGrid('bindKeys');   
				}
		   });
	   }
	   
	});


});

 {/literal}{if $JOB_OWNER}{literal}

$(function () {
'use strict';
var mydata = [],
 getColumnIndexByName = function (grid, columnName) {
                     var cm = grid.jqGrid('getGridParam', 'colModel'), i, l;
                     for (i = 0, l = cm.length; i < l; i += 1) {
                         if (cm[i].name === columnName) {
                             return i; // return the index
                         }
                     }
                     return -1;
                 },
$grid = $("#list_selling"),
resizeColumnHeader = function () {
                    var rowHight, resizeSpanHeight,
                        // get the header row which contains
                        headerRow = $(this).closest("div.ui-jqgrid-view")
                            .find("table.ui-jqgrid-htable>thead>tr.ui-jqgrid-labels");
        
                    // reset column height
                    headerRow.find("span.ui-jqgrid-resize").each(function () {
                        this.style.height = '';
                    });
        
                    // increase the height of the resizing span
                    resizeSpanHeight = 'height: ' + headerRow.height() + 'px !important; cursor: col-resize;';
                    headerRow.find("span.ui-jqgrid-resize").each(function () {
                        this.style.cssText = resizeSpanHeight;
                    });
        
                    // set position of the dive with the column header text to the middle
                    rowHight = headerRow.height();
                    headerRow.find("div.ui-jqgrid-sortable").each(function () {
                        var $div = $(this);
                        $div.css('top', (rowHight - $div.outerHeight()) / 2 + 'px');
                    });
                },
fixPositionsOfFrozenDivs = function () {
		var $rows;
		if (this.grid.fbDiv !== undefined) {
			$rows = $('>div>table.ui-jqgrid-btable>tbody>tr', this.grid.bDiv);
			$('>table.ui-jqgrid-btable>tbody>tr', this.grid.fbDiv).each(function (i) {
				var rowHight = $($rows[i]).height(), rowHightFrozen = $(this).height();
				if ($(this).hasClass("jqgrow")) {
					$(this).height(rowHight);
					rowHightFrozen = $(this).height();
					if (rowHight !== rowHightFrozen) {
						$(this).height(rowHight + (rowHight - rowHightFrozen));
					}
				}
			});
			$(this.grid.fbDiv).height(this.grid.bDiv.clientHeight);
			$(this.grid.fbDiv).css($(this.grid.bDiv).position());
		}
		if (this.grid.fhDiv !== undefined) {
			$rows = $('>div>table.ui-jqgrid-htable>thead>tr', this.grid.hDiv);
			$('>table.ui-jqgrid-htable>thead>tr', this.grid.fhDiv).each(function (i) {
				var rowHight = $($rows[i]).height(), rowHightFrozen = $(this).height();
				$(this).height(rowHight);
				rowHightFrozen = $(this).height();
				if (rowHight !== rowHightFrozen) {
					$(this).height(rowHight + (rowHight - rowHightFrozen));
				}
			});
			$(this.grid.fhDiv).height(this.grid.hDiv.clientHeight);
			$(this.grid.fhDiv).css($(this.grid.hDiv).position());
		}
	},
fixGboxHeight = function () {
	var gviewHeight = $("#gview_" + $.jgrid.jqID(this.id)).outerHeight(),
		pagerHeight = $(this.p.pager).outerHeight();

	$("#gbox_" + $.jgrid.jqID(this.id)).height(gviewHeight + pagerHeight);
	gviewHeight = $("#gview_" + $.jgrid.jqID(this.id)).outerHeight();
	pagerHeight = $(this.p.pager).outerHeight();
	$("#gbox_" + $.jgrid.jqID(this.id)).height(gviewHeight + pagerHeight);};



$grid.jqGrid({
	//datatype: "local",	
	datatype: "json",	
	url:$(location).attr('href')+'&grid=2',
	colNames: [{/literal}{$SELLING_HEADER}{literal}] ,
	colModel: {/literal}{$SELLING_FIELD}{literal},
	
	rowNum:500,
	width:1250,
	//rowList:[10,20,30],
	pager: '#pager_selling',
	sortname: 'invdate',
	viewrecords: true,
	sortorder: "desc",
	
	jsonReader: {
	repeatitems : false
	},
	
	shrinkToFit: false,
	caption: "Selling",
	height: 275,
	footerrow: false,
	//loadonce:true,
	//editurl:$(location).attr('href')+'&grid=2',
	//multiboxonly: true,
	/*loadComplete: function () {
	$(this).jqGrid('footerData','set',
	{name:'TOTAL', amount:"500", tax:"111", total:'20'},
	{name:'TOTAL 2', amount:"500", tax:"111", total:'20'}
	);
	}
	*/
	
	loadComplete: function () {
	
	fixPositionsOfFrozenDivs.call(this);
	
	
	}
	
	
	});
	/*
	for(var i=0;i<=mydata.length;i++)
	$grid.jqGrid('addRowData',i+1,mydata[i]);
	*/
	$grid.jqGrid('setColProp', 'id', {frozen: true});
	$grid.jqGrid('destroyFrozenColumns');
	$grid.jqGrid('setGridParam', {multiselect: false});
	$grid.jqGrid('setFrozenColumns');
	$grid.trigger('reloadGrid', [{ current: true}]);
	$grid.jqGrid('bindKeys');
	
	/*
	$grid.jqGrid('setColProp', 'id', {frozen: true});
	$grid.jqGrid('destroyFrozenColumns');
	$grid.jqGrid('setGridParam', {multiselect: false});
	$grid.jqGrid('setFrozenColumns');
	//$grid.jqGrid('setGridParam', 'cf_1248', {multiselect: true});
	$grid.trigger('reloadGrid', [{ current: true}]);	
	$grid.jqGrid('bindKeys');
	*/
	
	$grid.jqGrid('navGrid', '#pager_selling', {refreshstate: 'current', add: false, edit: false, del: false, search:false});
	$.extend(true, $.ui.multiselect, {
		locale: {
			addAll: 'Make all visible',
			removeAll: 'Hidde All',
			itemsCount: 'Avlialble Columns'
		}
	});
	//$.extend(true, $.jgrid.col, {
	//    width: 500,
	//    msel_opts: {dividerLocation: 0.5}
	//});
	$grid.jqGrid('navButtonAdd', '#pager_selling', {
		caption: "",
		buttonicon: "ui-icon-calculator",
		title: "Choose columns",
		onClickButton: function () {
			$(this).jqGrid('columnChooser',
				{width: 550, msel_opts: {dividerLocation: 0.5}});
			//$(this).jqGrid('columnChooser');
			$("#colchooser_" + $.jgrid.jqID(this.id) + ' div.available>div.actions')
				.prepend('<label style="float:left;position:relative;margin-left:0.6em;top:0.6em">Search:</label>');
		}
	});
	$grid.jqGrid('gridResize');
	
	
	//selected selling for invoice
	
	$("#InvoicePreviewButton").click( function() {
		var j=0;
		var rowId = '';
		var data = $("#list_selling").getRowData();
		for(i=0;i<data.length;i++){			
        	if(data[i].cf_2439=='Yes'){
           // str += data[i].id+',';
				var j=i+1;
				rowId += $('#list_selling tr:eq('+j+')').attr('id')+',';
	   
			//newid += $('#list_selling').jqGrid ('getCell', rowId, i);
			//var rowId = data[i];
    		//var rowData = $('#list_selling').jqGrid ('getRowData', data[i]);	
			//alert(rowData);
			}			
	   }
	   if(rowId)
	   {
		   $.ajax({
					url: 'include/Jobexpencereport/invoice_instruction_preview.php?job_id='+{/literal}{$JOB_ID}{literal}+'&sellingids='+rowId,
					success: function(data){
					   //var result=JSON.parse(data);
					$grid.trigger('reloadGrid', [{ current: true}]);
					$grid.jqGrid('bindKeys');   
					//window.location='index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$JOB_ID}{literal}+'&mode=showRelatedList&tab_label=Job Revenue and Expence';
					
					}
		   });
	   }
	   
	});
	
	$("#InvoiceButton").click( function() {
		var j=0;
		var rowId = '';
		var data = $("#list_selling").getRowData();
		var status_option = $("#status_option").val();
		 
		for(i=0;i<data.length;i++){			
        	if(data[i].cf_1248=='Yes'){
           // str += data[i].id+',';
				var j=i+1;
				rowId += $('#list_selling tr:eq('+j+')').attr('id')+',';
	   
			//newid += $('#list_selling').jqGrid ('getCell', rowId, i);
			//var rowId = data[i];
    		//var rowData = $('#list_selling').jqGrid ('getRowData', data[i]);	
			//alert(rowData);
			}			
	   }
	  
	   if(rowId)
	   {
		 
		  switch(status_option)
		  {
			  case '1':
			   $.ajax({
						url: 'include/Jobexpencereport/invoice_instruction_clear.php?job_id='+{/literal}{$JOB_ID}{literal}+'&sellingids='+rowId,
						success: function(data){
						   //var result=JSON.parse(data);
						$grid.trigger('reloadGrid', [{ current: true}]);
						$grid.jqGrid('bindKeys');   
						//window.location='index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$JOB_ID}{literal}+'&mode=showRelatedList&tab_label=Job Revenue and Expence';
					
						}
					});
			  break;
			  case '2':
			   $.ajax({
					url: 'include/Jobexpencereport/invoice_instruction_preview.php?job_id='+{/literal}{$JOB_ID}{literal}+'&sellingids='+rowId,
					success: function(data){
					   //var result=JSON.parse(data);
					$grid.trigger('reloadGrid', [{ current: true}]);
					$grid.jqGrid('bindKeys');   
					//window.location='index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$JOB_ID}{literal}+'&mode=showRelatedList&tab_label=Job Revenue and Expence';
					
					}
		   		});
			  break;
			  case '3':
				  $.ajax({
						url: 'include/Jobexpencereport/invoice_instruction_new.php?job_id='+{/literal}{$JOB_ID}{literal}+'&sellingids='+rowId,
						success: function(data){
						   //var result=JSON.parse(data);
						$grid.trigger('reloadGrid', [{ current: true}]);
						$grid.jqGrid('bindKeys');   
						//window.location='index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$JOB_ID}{literal}+'&mode=showRelatedList&tab_label=Job Revenue and Expence';
					
						}
					});
			  break;
			  default:			  
			  
		  }
		   
	   }
	   
	});
	
	
	//Selected expense to send head of department for approval	
	
});
{/literal}{/if}{literal}
</script>
{/literal}
{/if}

		<script type="text/javascript">
			var related_uimeta = (function () {
				var fieldInfo = {$RELATED_FIELDS_INFO};
				return {
					field: {
						get: function (name, property) {
							if (name && property === undefined) {
								return fieldInfo[name];
							}
							if (name && property) {
								return fieldInfo[name][property]
							}
						},
						isMandatory: function (name) {
							if (fieldInfo[name]) {
								return fieldInfo[name].mandatory;
							}
							return false;
						},
						getType: function (name) {
							if (fieldInfo[name]) {
								return fieldInfo[name].type
							}
							return false;
						}
					}
				};
			})();
		</script>
	</div>
{/strip}