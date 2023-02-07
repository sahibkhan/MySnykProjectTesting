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

		{*{include file="partials/RelatedListHeader.tpl"|vtemplate_path:$RELATED_MODULE_NAME}*}
	

		<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
			    <div class="bottomscroll-div" style="min-height:415px">
                    <table class="table table-bordered listViewEntriesTable" id="list"><tr><td></td></tr></table>
                    <div id="pager"></div>
                
                    <br /><br />
                        
                </div>
		</div>

        <div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
		   	 	<div class="bottomscroll-div">
						
					 <button id="PayablesButton" type="button" class="btn btn-success"><strong>Submit</strong></button>
						
				</div>
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



<script>
//alert('{/literal}{$EXPENCE_HEADER}{literal}');
//window.location.reload(true);
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
	url:$(location).attr('href')+'&grid=5',
    colNames: [{/literal}{$EXPENCE_HEADER_PAYABLES}{literal} ],
    colModel: {/literal}{$EXPENCE_FIELD_PAYABLES}{literal},
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
	height: 275,
	footerrow: false,
	cellEdit: true,
	cellsubmit : 'clientArray',
    editurl: 'clientArray',
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

$grid.jqGrid("setGridParam",{cellEdit : false});

$grid.jqGrid('setColProp', 'id', {frozen: true});
$grid.jqGrid('destroyFrozenColumns');
$grid.jqGrid('setGridParam', {multiselect: false});
$grid.jqGrid('setFrozenColumns');
$grid.jqGrid("setGridParam",{cellEdit : true});
$grid.trigger('reloadGrid', [{ current: true}]);
$grid.jqGrid('bindKeys');
//$grid.jqGrid('getGridParam','selarrrow');

	//width = $grid.jqGrid('getGridParam', 'width'); // get current width
	//$grid.jqGrid('setGridWidth', width, true);



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

		
		$("#PayablesButton").click( function() {
		var j=0;
		var mn = 0;
		var rowId = '';
		var rowIdAccept = '';
		var rowIdReject = '';
		var data = $("#list").getRowData();
		var k = 0;
		for(var i=0;i<data.length;i++){
			var k = i+1;
			
			var payable_status = data[i].payables_action;
			
			if(payable_status=='Accept' || payable_status=='Reject' || payable_status=='Select')
			{
				var selectedText = payable_status;
			}			
			else{
				var dropdown = $('#' + k + '_payables_action')[0];
				var selectedOption = dropdown.options[dropdown.selectedIndex];
				var selectedText = selectedOption.text;
			}
		  	if(selectedText==='Accept'){
           // str += data[i].id+',';
				var j=i+1;
				rowIdAccept += $('#list tr:eq('+j+')').attr('id')+',';
			}
			else if(selectedText==='Reject')
			{
				var mn=i+1;
				rowIdReject += $('#list tr:eq('+mn+')').attr('id')+',';
			}
	   }
	   
	    
	   if(rowIdAccept)
	   {
		  
		   $.ajax({
					url: 'include/Jobexpencereport/payables_new.php?job_id='+{/literal}{$JOB_ID}{literal}+'&expenseids='+rowIdAccept+'&status=accept',
					success: function(data){
					   //var result=JSON.parse(data);
					$grid.trigger('reloadGrid', [{ current: true}]);
					$grid.jqGrid('bindKeys');   
					}
		   });
	   }
	   
	   if(rowIdReject)
	   {
		  $.ajax({
					url: 'include/Jobexpencereport/payables_new.php?job_id='+{/literal}{$JOB_ID}{literal}+'&expenseids='+rowIdReject+'&status=reject',
					success: function(data){
					   //var result=JSON.parse(data);
					$grid.trigger('reloadGrid', [{ current: true}]);
					$grid.jqGrid('bindKeys');   
					}
		   });
	   }
	   
	});

});



</script>
{/literal}

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