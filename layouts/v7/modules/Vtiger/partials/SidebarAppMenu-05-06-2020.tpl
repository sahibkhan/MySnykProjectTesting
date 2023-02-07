{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

<div class="app-menu hide" id="app-menu">
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-2 col-xs-2 cursorPointer app-switcher-container">
				<div class="row app-navigator">
					<span id="menu-toggle-action" class="app-icon fa fa-bars"></span>
				</div>
			</div>
		</div>
		{assign var=USER_PRIVILEGES_MODEL value=Users_Privileges_Model::getCurrentUserPrivilegesModel()}
		{assign var=HOME_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Home')}
		{assign var=DASHBOARD_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Dashboard')}
		<div class="app-list row">
			{if $USER_PRIVILEGES_MODEL->hasModulePermission($DASHBOARD_MODULE_MODEL->getId())}
				<div class="menu-item app-item dropdown-toggle" data-default-url="{$HOME_MODULE_MODEL->getDefaultUrl()}">
					<div class="menu-items-wrapper">
						<span class="app-icon-list fa fa-dashboard"></span>
						<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_DASHBOARD',$MODULE)}</span>
					</div>
				</div>
			{/if}
			{assign var=APP_GROUPED_MENU value=Settings_MenuEditor_Module_Model::getAllVisibleModules()}
			{assign var=APP_LIST value=Vtiger_MenuStructure_Model::getAppMenuList()}
			{foreach item=APP_NAME from=$APP_LIST}
				{if $APP_NAME eq 'ANALYTICS'} {continue}{/if}
				{if count($APP_GROUPED_MENU.$APP_NAME) gt 0}
					<div class="dropdown app-modules-dropdown-container">
						{foreach item=APP_MENU_MODEL from=$APP_GROUPED_MENU.$APP_NAME}
							{assign var=FIRST_MENU_MODEL value=$APP_MENU_MODEL}
							{if $APP_MENU_MODEL}
								{break}
							{/if}
						{/foreach}
						<div class="menu-item app-item dropdown-toggle app-item-color-{$APP_NAME}" data-app-name="{$APP_NAME}" id="{$APP_NAME}_modules_dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" data-default-url="{$FIRST_MENU_MODEL->getDefaultUrl()}&app={$APP_NAME}">
							<div class="menu-items-wrapper app-menu-items-wrapper">
								<span class="app-icon-list fa {$APP_IMAGE_MAP.$APP_NAME}"></span>
								<span class="app-name textOverflowEllipsis"> {vtranslate("LBL_$APP_NAME")}</span>
								<span class="fa fa-chevron-right pull-right"></span>
							</div>
						</div>
						<ul class="dropdown-menu app-modules-dropdown" aria-labelledby="{$APP_NAME}_modules_dropdownMenu">
							{if $APP_NAME=='MARKETING'}
							<li>
								<a href="#" title="Presentation" >
									<span class="module-icon"><i class="fa fa-file-powerpoint-o" title="Presentation"></i></span>
									<span class="module-name textOverflowEllipsis"> Presentation</span>
								</a>
							</li>
							<li>
								<a href="#" title="Removal Advertisement" >
									<span class="module-icon"><i class="fa fa-adn" title="Removal Advertisement"></i></span>
									<span class="module-name textOverflowEllipsis"> Removal Advertisement</span>
								</a>
							</li>
							<li>
								<a href="#" title="News Letter" >
									<span class="module-icon"><i class="fa fa-newspaper-o" title="News Letter"></i></span>
									<span class="module-name textOverflowEllipsis"> News Letter</span>
								</a>
							</li>
							<li>
								<a href="#" title="Company Profile" >
									<span class="module-icon"><i class="fa fa-file-o" title="Company Profile"></i></span>
									<span class="module-name textOverflowEllipsis"> Company Profile</span>
								</a>
							</li>
							<li>
								<a href="#" title="Certification" >
									<span class="module-icon"><i class="fa fa-certificate" title="Certification"></i></span>
									<span class="module-name textOverflowEllipsis"> Certification</span>
								</a>
							</li>
							<li>
								<a href="#" title="Membership" >
									<span class="module-icon"><i class="fa fa-database" title="Membership"></i></span>
									<span class="module-name textOverflowEllipsis"> Membership</span>
								</a>
							</li>
							<li>
								<a href="#" title="Stationary" >
									<span class="module-icon"><i class="fa fa-envelope-o" title="Stationary"></i></span>
									<span class="module-name textOverflowEllipsis"> Stationary</span>
								</a>
							</li>
							{/if}
							{foreach item=moduleModel key=moduleName from=$APP_GROUPED_MENU[$APP_NAME]}
								{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
								<li>
									<a href="{$moduleModel->getDefaultUrl()}&app={$APP_NAME}" title="{$translatedModuleLabel}">
										<span class="module-icon">{$moduleModel->getModuleIcon()}</span>
										<span class="module-name textOverflowEllipsis"> {$translatedModuleLabel}</span>
									</a>
								</li>
							{/foreach}
							{if $APP_NAME=='SUPPORT'}
								<li>
									<a href="manual/index.php" title="ERP User Manual" target="_blank">
										<span class="module-icon"><i class="fa fa-book" title="ERP User Manual"></i></span>
										<span class="module-name textOverflowEllipsis"> ERP User Manual</span>
									</a>
								</li>
								<li>
									<a href="manual/abbr.php" title="System Abbreviations" target="_blank">
										<span class="module-icon"><i class="fa fa-info-circle" title="System Abbreviations"></i></span>
										<span class="module-name textOverflowEllipsis"> System Abbreviations</span>
									</a>
								</li>
							{/if}
						</ul>
					</div>
				{/if}
			{/foreach}
			<div class="app-list-divider"></div>
			{assign var=MAILMANAGER_MODULE_MODEL value=Vtiger_Module_Model::getInstance('MailManager')}
			{if $USER_PRIVILEGES_MODEL->hasModulePermission($MAILMANAGER_MODULE_MODEL->getId())}
				<div class="menu-item app-item app-item-misc" data-default-url="index.php?module=MailManager&view=List">
					<div class="menu-items-wrapper">
						<span class="app-icon-list fa">{$MAILMANAGER_MODULE_MODEL->getModuleIcon()}</span>
						<span class="app-name textOverflowEllipsis"> {vtranslate('MailManager')}</span>
					</div>
				</div>
			{/if}
			{assign var=DOCUMENTS_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Documents')}
			{if $USER_PRIVILEGES_MODEL->hasModulePermission($DOCUMENTS_MODULE_MODEL->getId())}
				<div class="menu-item app-item app-item-misc" data-default-url="index.php?module=Documents&view=List" style="display:none;">
					<div class="menu-items-wrapper">
						<span class="app-icon-list fa">{$DOCUMENTS_MODULE_MODEL->getModuleIcon()}</span>
						<span class="app-name textOverflowEllipsis"> {vtranslate('Documents')}</span>
					</div>
				</div>
			{/if}
			{if $USER_MODEL->isAdminUser()}
				{if vtlib_isModuleActive('ExtensionStore')}
					<div class="menu-item app-item app-item-misc" data-default-url="index.php?module=ExtensionStore&parent=Settings&view=ExtensionStore">
						<div class="menu-items-wrapper">
							<span class="app-icon-list fa fa-shopping-cart"></span>
							<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_EXTENSION_STORE', 'Settings:Vtiger')}</span>
						</div>
					</div>
				{/if}
			{/if}
			{if $USER_MODEL->isAdminUser()}
				<div class="dropdown app-modules-dropdown-container dropdown-compact">
					<div class="menu-item app-item dropdown-toggle app-item-misc" data-app-name="TOOLS" id="TOOLS_modules_dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" data-default-url="{if $USER_MODEL->isAdminUser()}index.php?module=Vtiger&parent=Settings&view=Index{else}index.php?module=Users&view=Settings{/if}">
						<div class="menu-items-wrapper app-menu-items-wrapper">
							<span class="app-icon-list fa fa-cog"></span>
							<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_SETTINGS', 'Settings:Vtiger')}</span>
							{if $USER_MODEL->isAdminUser()}
								<span class="fa fa-chevron-right pull-right"></span>
							{/if}
						</div>
					</div>
					<ul class="dropdown-menu app-modules-dropdown dropdown-modules-compact" aria-labelledby="{$APP_NAME}_modules_dropdownMenu" data-height="0.27">
						<li>
							<a href="?module=Vtiger&parent=Settings&view=Index">
								<span class="fa fa-cog module-icon"></span>
								<span class="module-name textOverflowEllipsis"> {vtranslate('LBL_CRM_SETTINGS','Vtiger')}</span>
							</a>
						</li>
						<li>
							<a href="?module=Users&parent=Settings&view=List">
								<span class="fa fa-user module-icon"></span>
								<span class="module-name textOverflowEllipsis"> {vtranslate('LBL_MANAGE_USERS','Vtiger')}</span>
							</a>
						</li>
					</ul>
				</div>
			{/if}
		</div>
	</div>
</div>
