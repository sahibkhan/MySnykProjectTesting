{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
<!--
<footer class="app-footer">
	<p>
		&nbsp;&nbsp;© 2013 - {date('Y')}&nbsp;&nbsp;
		<a href="http://globalinklogistics.com/" target="_blank">Globalink Logistics</a>&nbsp;|&nbsp;
		<a href="https://globalinklogistics.com/GDPR" target="_blank">Privacy Policy</a>
	</p>
</footer>-->
</div>
<div id='overlayPage'>
	<!-- arrow is added to point arrow to the clicked element (Ex:- TaskManagement), 
	any one can use this by adding "show" class to it -->
	<div class='arrow'></div>
	<div class='data'>
	</div>
</div>
<div id='helpPageOverlay'></div>
<div id="js_strings" class="hide noprint">{Zend_Json::encode($LANGUAGE_STRINGS)}</div>
<div class="modal myModal fade"></div>
{include file='JSResources.tpl'|@vtemplate_path}

<script>
$(document).ready(function(){

	setTimeout( function(){
		$(".search-links-container .select2-container").show();
		$(".search-links-container .select2-container").css("display","block");
		}  , 1000 );
});
</script>
</body>

</html>