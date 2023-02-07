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

{* TODO: Review the order of parameters - good to eliminate $RECORD->getId, $RECORD should be used *}
{$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'), $RECORD->getId(), $RECORD)}

{if $MODULE_NAME=='Job'}
{$RECORD->getDisplayValue('cf_1522')}
{else if $MODULE_NAME=='Potentials'}
{$RECORD->getDisplayValue('cf_1689')}
{else if $MODULE_NAME=='Quotes'}
{$RECORD->getDisplayValue('cf_1645')}
{else if $MODULE_NAME=='BO'}
{$RECORD->getDisplayValue('cf_1475')}
{else if $MODULE_NAME=='VPO'}
{$RECORD->getDisplayValue('cf_1599')}
{/if}