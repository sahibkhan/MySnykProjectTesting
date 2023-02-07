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
<div class="bootbox modal fade" id="StickerPrintModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel11" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
        <h4 class="modal-title" id="myModalLabel11"> Sticker Print </h4>
      </div>

      <div class="modal-body">
        <form method="POST" action="/index.php?module=Job&view=Print&record={$RECORD->getId()}&type=sticker">
        <table>
          <tr>
            <td> Box Count</td>  <td>  
              <input type="number" name="boxCount" id="boxCount" />
              </td> 
          </tr>
        </table>
        
      </div>
      <div class="modal-footer">
          <button type="submit" class="btn btn-primary"> Generate </button>
      </div>
    </div>
  </div>
  </form>
</div>
{/strip}