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
<div class="bootbox modal fade" id="DuplicateRemovePopup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel11" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
        <h4 class="modal-title" id="myModalLabel11"> Managing the duplicates </h4>
      </div>
      <div class="modal-body">
	  
		<table>
		  <tr> 
		    <td> Add link or ID of duplicate profile
			
				 <textarea rows="5" cols="80" id="duplicateAccounts"></textarea>
	       <div id='info-response'>   </div>
				 
			</td>
		  </tr>
 	  		  
		  
		</table>
	  
	  <br/>
	  <br/>
	  
      </div>
	  
      <div class="modal-footer">	    
         <button type="button" class="btn btn-primary cls-remove-duplicate" 1data-dis1miss="m1odal"> Remove duplicate and shift data </button>
      </div>
	  
    </div>
  </div>
</div>
{/strip}