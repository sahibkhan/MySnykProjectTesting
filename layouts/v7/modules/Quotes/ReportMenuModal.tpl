{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<div class="bootbox modal fade" id="ResignationReportModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel11" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
        <h4 class="modal-title" id="myModalLabel11"> Generate QT Report </h4>
      </div>
      <div class="modal-body">
        <form method="POST" action="/index.php?module=Quotes&mode=setApproval&action=Approval">
        <table>

          <tr>
            <td> Date </td>
            <td> <input type="text" name="datePeriod" class="dateField form-control" data-fieldtype="date" data-date-format="dd-mm-yyyy" data-rule-date="true" aria-invalid="false"> </td>
          </tr>

        </table>
	  <br/>
	  <br/>
      </div>
      <div class="modal-footer">
         <button type="submit" class="btn btn-primary"> Generate </button>
      </div>
    </div>
    
        </form>
  </div>
</div>
{/strip}