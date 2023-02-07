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

         <div class="relatedHeader">
            <h5 align="center" style="font-family:Verdana, Geneva, sans-serif;">COSTING BREAKDOWN</h5>
            <table class="table listview-table">
            <thead>
            <tr class="listViewHeaders">
            <th nowrap="" class="medium">Invoice Date</th>
            <th nowrap="" class="medium">Job File</th>
            <th nowrap="" class="medium">Internal Selling</th>
            <th nowrap="" class="medium">Currency</th>
            <th nowrap="" class="medium">Internal Selling USD</th>
            <th nowrap="" class="medium">Internal Selling %</th>
            <th nowrap="" class="medium">Internal Selling KZT</th>
            <th nowrap="" class="medium">Costing</th>
            <th nowrap="" class="medium">Profit</th></tr>
            </thead>
            <tbody>
            {$ALLOCATED_JOB_NO_COSTING_BREAKDOWN}
            </tbody>
            <thead>
            <tr class="listViewHeaders">
            <th ><strong>Total USD</strong></td>
            <th ></th>
            <th ></th>
            <th ></th>
            <th ><strong>{$TOTAL_INTERNAL_SELLING}</strong></th>
            <th ><strong></strong></th>
            <th><strong>{$TOTAL_INTERNAL_SELLING_KZT}</strong></th>
            <th ><strong>{$TOTAL_COSTING} </strong></th>
            <th ><strong>{$TOTAL_PROFIT} </strong></th>
            </tr>
            </thead>
            </table>
            </div>

		{include file="partials/RelatedListHeader.tpl"|vtemplate_path:$RELATED_MODULE_NAME}
		{if $MODULE eq 'Products' && $RELATED_MODULE_NAME eq 'Products' && $TAB_LABEL === 'Product Bundles' && $RELATED_LIST_LINKS}
			<div data-module="{$MODULE}" style = "margin-left:20px">
				{assign var=IS_VIEWABLE value=$PARENT_RECORD->isBundleViewable()}
				<input type="hidden" class="isShowBundles" value="{$IS_VIEWABLE}">
				<label class="showBundlesInInventory checkbox"><input type="checkbox" {if $IS_VIEWABLE}checked{/if} value="{$IS_VIEWABLE}">&nbsp;&nbsp;{vtranslate('LBL_SHOW_BUNDLE_IN_INVENTORY', $MODULE)}</label>
			</div>
		{/if}

		<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container">
			<div class="blockData"  style="min-height:550px">
				<table class="table listview-table  no-border" id="list">
				<tr><td ></td></tr>
				</table>
                 <div id="pager"></div>
            
                 <br /><br />
			
			</div>
		</div>
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

 {literal}
 <link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/css/ui.jqgrid.css" />
<link rel="stylesheet" type="text/css" href="libraries/jqgrid/ui.multiselect.css" />

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
/*$(document).ready(function(){

    //Check if the current URL contains '#'
    if(document.URL.indexOf("#")==-1)
    {
    // Set the URL to whatever it was plus "#".
    url = document.URL+"#";
    location = "#";

    //Reload the page
    location.reload(true);

    }
    });*/
</script>
<script>
//alert('{/literal}{$EXPENCE_HEADER}{literal}');
//window.location.reload(true);
$(function () {
'use strict';
var mydata = {/literal}{$BUYING_ARRAY}{literal},
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
    //datatype: "json",	
	//url:$(location).attr('href')+'&grid=1',
	datatype: "local",
    colNames: [{/literal}{$EXPENCE_HEADER}{literal}],
    colModel: [{/literal}{$EXPENCE_FIELD}{literal}],
   	rowNum:500,
    width:1050,
	
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
	height: 350,
	footerrow: false,
	//multiboxonly: true,
	/*loadComplete: function () {
    $(this).jqGrid('footerData','set',
        {name:'TOTAL', amount:"500", tax:"111", total:'20'},
		{name:'TOTAL 2', amount:"500", tax:"111", total:'20'}
		);
	}
	*/
	
	/*	
	loadComplete: function () {
		
		var $this = $(this),
			sum = $this.jqGrid("getCol", "cf_1349", false, "sum"),
			$footerRow = $(this.grid.sDiv).find("tr.footrow"),
			localData = $this.jqGrid("getGridParam", "data"),
			totalRows = localData.length,
			totalSum = 0,
			$newFooterRow,
			$newFooterRow2,
			i;

		$newFooterRow = $(this.grid.sDiv).find("tr.myfootrow");
		if ($newFooterRow.length === 0) {
			// add second row of the footer if it's not exist
			$newFooterRow = $footerRow.clone();
			$newFooterRow.removeClass("footrow").addClass("myfootrow ui-widget-content footrow");
			$newFooterRow.children("td").each(function () {
				this.style.width = ""; // remove width from inline CSS
			});
			$newFooterRow.insertAfter($footerRow);
		}
		//$this.jqGrid("footerData", "set", {myname: "Total Local Currency", cf_1347: sum, cf_1349: sum, cf_1351: sum, cf_1353: sum});
		$this.jqGrid("footerData", "set", {myname: "Total Local Currency", cf_1347: {/literal}{$BUY_LOCAL_CURRENCY_GROSS}{literal}, cf_1349: {/literal}{$BUY_LOCAL_CURRENCY_NET}{literal},  cf_1351: {/literal}{$EXPECTED_BUY_LOCAL_CURRENCY_NET}{literal}, cf_1353: {/literal}{$VARIATION_EXPECTED_AND_ACTUAL_BUYING}{literal}});
		$newFooterRow.jqGrid("footerData", "set", {myname: "Exchange Rate:"});

		// calculate the value for the second footer row
		
				
		$newFooterRow.find(">td[aria-describedby=" + this.id + "_myname]").text("Exchange Rate:");
		$newFooterRow.find(">td[aria-describedby=" + this.id + "_cf_1347]").text({/literal}{$FINAL_EXCHANGE_RATE}{literal});
		$newFooterRow.find(">td[aria-describedby=" + this.id + "_cf_1349]").text({/literal}{$FINAL_EXCHANGE_RATE}{literal});
		$newFooterRow.find(">td[aria-describedby=" + this.id + "_cf_1351]").text({/literal}{$FINAL_EXCHANGE_RATE}{literal});
		$newFooterRow.find(">td[aria-describedby=" + this.id + "_cf_1353]").text({/literal}{$FINAL_EXCHANGE_RATE}{literal});
		
		$newFooterRow2 = $(this.grid.sDiv).find("tr.myfootrow2");
		if ($newFooterRow2.length === 0) {
			// add second row of the footer if it's not exist
			$newFooterRow2 = $footerRow.clone();
			$newFooterRow2.removeClass("footrow").addClass("myfootrow2 ui-widget-content footrow");
			$newFooterRow2.children("td").each(function () {
				this.style.width = ""; // remove width from inline CSS
			});
			$newFooterRow2.insertAfter($newFooterRow);
		}
		$newFooterRow2.jqGrid("footerData", "set", {myname: "USD:"});
		$newFooterRow2.find(">td[aria-describedby=" + this.id + "_myname]").text("USD:");
		$newFooterRow2.find(">td[aria-describedby=" + this.id + "_cf_1347]").text({/literal}{$TOTAL_COST_IN_USD_NET}{literal});
		$newFooterRow2.find(">td[aria-describedby=" + this.id + "_cf_1349]").text({/literal}{$TOTAL_COST_USD_GROSS}{literal});
		$newFooterRow2.find(">td[aria-describedby=" + this.id + "_cf_1351]").text({/literal}{$TOTAL_EXPECTED_COST_USD_NET}{literal});
		$newFooterRow2.find(">td[aria-describedby=" + this.id + "_cf_1353]").text({/literal}{$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD}{literal});	
		
		fixPositionsOfFrozenDivs.call(this);

	}
	*/
	
});
for(var i=0;i<=mydata.length;i++)
	$grid.jqGrid('addRowData',i+1,mydata[i]);

$grid.jqGrid('setColProp', 'id', {frozen: true});
$grid.jqGrid('destroyFrozenColumns');
$grid.jqGrid('setGridParam', {multiselect: false});
$grid.jqGrid('setFrozenColumns');
$grid.trigger('reloadGrid', [{ current: true}]);
$grid.jqGrid('bindKeys');
//$grid.jqGrid('getGridParam','selarrrow');

//width = $grid.jqGrid('getGridParam', 'width'); // get current width
//$grid.jqGrid('setGridWidth', width, true);



$grid.jqGrid('navGrid', '#pager', {refreshstate: 'current', add: false, edit: false, del: false, search:false});
	/*
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
            });*/
$grid.jqGrid('gridResize');
	
	$("#PaymentVoucherButton").click( function() {
		var j=0;
		var rowId = '';
		var data = $("#list").getRowData();
		for(var i=0;i<data.length;i++){
			
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
					url: 'include/Jobexpencereport/send_to_head_of_department.php?job_id='+{/literal}{$WAGON_TRIP_ID}{literal}+'&role='+role+'&expenseids='+rowId,
					success: function(data){
					   //var result=JSON.parse(data);
					window.location='index.php?module=WagonTrip&relatedModule=Jobexpencereport&view=Detail&record='+{/literal}{$WAGON_TRIP_ID}{literal}+'&mode=showRelatedList&tab_label=Railway%20Expense';
					//$("#list").trigger('reloadGrid', [{ current: true}]);
					//$("#list").jqGrid('bindKeys');   
				}
		   });
	   }
	   
	});
	
});

</script>
{/literal}   
{/strip}