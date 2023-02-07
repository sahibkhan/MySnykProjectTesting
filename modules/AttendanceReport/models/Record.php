<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger Entity Record Model Class
 */
class AttendanceReport_Record_Model extends Vtiger_Record_Model {

	protected $module = false;

	/**
	 * Function to get the id of the record
	 * @return <Number> - Record Id
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * Function to set the id of the record
	 * @param <type> $value - id value
	 * @return <Object> - current instance
	 */
	public function setId($value) {
		return $this->set('id',$value);
	}

	/**
	 * Fuction to get the Name of the record
	 * @return <String> - Entity Name of the record
	 */
	public function getName() {
		$displayName = $this->get('label');
		if(empty($displayName)) {
			$displayName = $this->getDisplayName();
		}
		return Vtiger_Util_Helper::toSafeHTML(decode_html($displayName));
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return Vtiger_Module_Model
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Function to set the Module to which the record belongs
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public function setModule($moduleName) {
		$this->module = Vtiger_Module_Model::getInstance($moduleName);
		return $this;
	}

	/**
	 * Function to set the Module to which the record belongs from the Module model instance
	 * @param <Vtiger_Module_Model> $module
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public function setModuleFromInstance($module) {
		$this->module = $module;
		return $this;
	}

	/**
	 * Function to get the entity instance of the recrod
	 * @return CRMEntity object
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * Function to set the entity instance of the record
	 * @param CRMEntity $entity
	 * @return Vtiger_Record_Model instance
	 */
	public function setEntity($entity) {
		$this->entity = $entity;
		return $this;
	}

	/**
	 * Function to get raw data
	 * @return <Array>
	 */
	public function getRawData() {
		return $this->rawData;
	}

	/**
	 * Function to set raw data
	 * @param <Array> $data
	 * @return Vtiger_Record_Model instance
	 */
	public function setRawData($data) {
		$this->rawData = $data;
		return $this;
	}
	
	/**
	 * Function to get the Picar url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getpicarViewUrl() { 
		$module = $this->getModule(); 
		$picardata = $this->getInstanceById($this->getId(),$module);
		if($picardata->get("cf_5952") == 0 || $picardata->get("cf_5962") != 0)
		{
		$var = '[[["cf_5904","e","'.$picardata->get("cf_5962").'"],["cf_5932","e","'.$picardata->get("cf_5960").'"],["cf_5934","e","'.sprintf("%02d",$picardata->get("cf_5958")).'"]]]'; //echo $var;
		}else
		{
			$var = '[[["cf_5904","e","'.$picardata->get("cf_5952").'"],["cf_5954","e","'.$picardata->get("cf_5960").'"],["cf_5934","e","'.sprintf("%02d",$picardata->get("cf_5958")).'"]]]'; //echo $var;
		}
		return 'index.php?module=UserAttendance&view=List&user_params='.urlencode($var);
	}

	/**
	 * Function to get the Detail View url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getDetailViewUrl() { 
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getDetailViewName().'&record='.$this->getId();
	}

	/**
	 * Function to get the complete Detail View url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getFullDetailViewUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getDetailViewName().'&record='.$this->getId().'&mode=showDetailViewByMode&requestMode=full';
	}

	/**
	 * Function to get the Edit View url for the record
	 * @return <String> - Record Edit View Url
	 */
	public function getEditViewUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getEditViewName().'&record='.$this->getId();
	}

	/**
	 * Function to get the Update View url for the record
	 * @return <String> - Record Upadte view Url
	 */
	public function getUpdatesUrl() {
		return $this->getDetailViewUrl()."&mode=showRecentActivities&page=1&tab_label=LBL_UPDATES";
	}

	/**
	 * Function to get the Delete Action url for the record
	 * @return <String> - Record Delete Action Url
	 */
	public function getDeleteUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&action='.$module->getDeleteActionName().'&record='.$this->getId();
	}

	/**
	 * Function to get the name of the module to which the record belongs
	 * @return <String> - Record Module Name
	 */
	public function getModuleName() {
		return $this->getModule()->get('name');
	}

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	public function getDisplayName() {
		return getFullNameFromArray($this->getModuleName(),$this->getData());
	}

	/**
	 * Function to retieve display value for a field
	 * @param <String> $fieldName - field name for which values need to get
	 * @return <String>
	 */
	public function getDisplayValue($fieldName,$recordId = false) {
		if(empty($recordId)) {
			$recordId = $this->getId();
		}
		$fieldModel = $this->getModule()->getField($fieldName);
		if($fieldModel) {
			return $fieldModel->getDisplayValue($this->get($fieldName), $recordId, $this);
		}
		return false;
	}

	/**
	 * Function returns the Vtiger_Field_Model
	 * @param <String> $fieldName - field name
	 * @return <Vtiger_Field_Model>
	 */
	public function getField($fieldName) {
		return $this->getModule()->getField($fieldName);
	}

	/**
	 * Function returns all the field values in user format
	 * @return <Array>
	 */
	public function getDisplayableValues() {
		$displayableValues = array();
		$data = $this->getData();
		foreach($data as $fieldName=>$value) {
			$fieldValue = $this->getDisplayValue($fieldName);
			$displayableValues[$fieldName] = ($fieldValue) ? $fieldValue : $value;
		}
		return $displayableValues;
	}

	/**
	 * Function to save the current Record Model
	 */
	public function save() {
		$this->getModule()->saveRecord($this);
	}

	/**
	 * Function to delete the current Record Model
	 */
	public function delete() {
		$this->getModule()->deleteRecord($this);
	}

	/**
	 * Static Function to get the instance of a clean Vtiger Record Model for the given module name
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public static function getCleanInstance($moduleName) {
		//TODO: Handle permissions
		$focus = CRMEntity::getInstance($moduleName);
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
		$instance = new $modelClassName();
		return $instance->setData($focus->column_fields)->setModule($moduleName)->setEntity($focus);
	}

	/**
	 * Static Function to get the instance of the Vtiger Record Model given the recordid and the module name
	 * @param <Number> $recordId
	 * @param <String> $moduleName
	 * @return Vtiger_Record_Model or Module Specific Record Model instance
	 */
	public static function getInstanceById($recordId, $module=null) {
		//TODO: Handle permissions
		if(is_object($module) && is_a($module, 'Vtiger_Module_Model')) {
			$moduleName = $module->get('name');
		} elseif (is_string($module)) {
			$module = Vtiger_Module_Model::getInstance($module);
			$moduleName = $module->get('name');
		} elseif(empty($module)) {
			$moduleName = getSalesEntityType($recordId);
			$module = Vtiger_Module_Model::getInstance($moduleName);
		}

		$focus = CRMEntity::getInstance($moduleName);
		$focus->id = $recordId;
		$focus->retrieve_entity_info($recordId, $moduleName);
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
		$instance = new $modelClassName();
		return $instance->setData($focus->column_fields)->set('id',$recordId)->setModuleFromInstance($module)->setEntity($focus);
	}

	/**
	 * Static Function to get the list of records matching the search key
	 * @param <String> $searchKey
	 * @return <Array> - List of Vtiger_Record_Model or Module Specific Record Model instances
	 */
	public static function getSearchResult($searchKey, $module=false) {
		$db = PearDatabase::getInstance();
		$searchKey = trim($searchKey);

		$query = 'SELECT label, crmid, setype, createdtime FROM vtiger_crmentity WHERE label LIKE ? AND vtiger_crmentity.deleted = 0';
		$params = array("%$searchKey%");

		if($module !== false) {
			$query .= ' AND setype = ?';
			$params[] = $module;
		}
		
		
		/*TOTHOMweb search Leads*/
		if ($module == false || $module == "Leads" ){
			$query = $query . " UNION ALL ". 'SELECT vtiger_leadscf.cf_833 as label, vtiger_leadscf.leadid as crmid, 
		 "Leads" as setype, vtiger_crmentity.createdtime as createdtime
		   
		   FROM vtiger_leadscf
		   LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leadscf.leadid
		   WHERE vtiger_crmentity.deleted = 0 ';
		   
		   $query .=' AND (vtiger_leadscf.cf_833 LIKE ? ) ';
		   $params = array_merge($params ,array("%$searchKey%"));
		}
		/*End TOTHOMweb search Leads*/
		//For job ref no
		if($module=="Job")
		{
			$query = $query . " UNION ALL ". 'select vtiger_jobcf.cf_1198 as label, vtiger_jobcf.jobid as crmid, "Job" as setype, vtiger_crmentity.createdtime as createdtime from vtiger_jobcf , vtiger_crmentity
											  where vtiger_jobcf.jobid = vtiger_crmentity.crmid AND vtiger_crmentity.deleted = 0 AND 
											  (vtiger_jobcf.cf_1198 like ? ) ';
			//new query for job search .. above taking almost 11 second								  
			$query = ' SELECT vtiger_jobcf.cf_1198 as label, vtiger_jobcf.jobid as crmid, "Job" as setype, vtiger_crmentity.createdtime as createdtime 
					   FROM vtiger_jobcf
					   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_jobcf.jobid
					   WHERE  vtiger_crmentity.deleted = 0  ';	
			
			if(strlen($searchKey)==14)
			{
				$query .=' AND (vtiger_jobcf.cf_1198 = ? ) ';
				$params = array_merge(array("$searchKey"));		
			}
			else{
				$query .=' AND (vtiger_jobcf.cf_1198 like ? ) ';
				$params = array_merge(array("%$searchKey%"));		
			}
			//$params = array_merge($params ,array("%$searchKey%"));	
									  
		}
		elseif($module=="Fleettrip")
		{
			$query = ' SELECT vtiger_fleettripcf.cf_3283 as label, vtiger_fleettripcf.fleettripid as crmid, "Fleettrip" as setype, vtiger_crmentity.createdtime as createdtime 
					   FROM vtiger_fleettripcf
					   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_fleettripcf.fleettripid
					   WHERE  vtiger_crmentity.deleted = 0  ';	
			
			$query .=' AND (vtiger_fleettripcf.cf_3283 like ? ) ';
			$params = array_merge(array("%$searchKey%"));		
			
		}
		//For accounts Legal name field
		elseif($module=="Accounts")
		{
			$query = $query . " UNION ALL ". 'select vtiger_accountscf.cf_2395 as label, vtiger_accountscf.accountid as crmid, 
											 "Accounts" as setype, vtiger_crmentity.createdtime as createdtime 
											 from vtiger_accountscf , vtiger_crmentity
											  where vtiger_accountscf.accountid = vtiger_crmentity.crmid AND 
											  vtiger_crmentity.deleted = 0 AND (vtiger_accountscf.cf_2395 like ? ) ';
			$params = array_merge($params ,array("%$searchKey%"));	
			
			//mehtab code
			$query = 'SELECT CONCAT_WS(vtiger_crmentity.label,vtiger_accountscf.cf_2395) as label , crmid, setype, createdtime FROM vtiger_crmentity
	  				  LEFT JOIN  vtiger_accountscf ON vtiger_accountscf.accountid = vtiger_crmentity.crmid 
      				  WHERE (label LIKE "%'.addslashes($searchKey).'%" || vtiger_accountscf.cf_2395 like "%'.addslashes($searchKey).'%") 
					  		AND vtiger_crmentity.deleted = 0 ';	
			$params = array();
			if($module !== false) {
			$query .= ' AND setype = "'.$module.'" ';
			//$params[] = $module;
			}
			//mehtab code
										  
		}
		elseif($module=="Quotes")
		{
			$query = 'SELECT label, crmid, setype, createdtime FROM vtiger_crmentity WHERE vtiger_crmentity.deleted = 0 AND setype = ? AND ( label LIKE ? || crmid LIKE ? ) ';
			
			$params = array_merge(array($module, "%$searchKey%", "%$searchKey%"));			
			
		} elseif($module=="Documents"){
			$HR_Team = array('H201', 'H202');
			$current_user = Users_Record_Model::getCurrentUserModel();
			if (!in_array($current_user->get('roleid'), $HR_Team)){
				$query = "SELECT vtiger_notes.title as label, vtiger_notes.notesid as crmid, vtiger_crmentity.setype as setype, vtiger_crmentity.createdtime as createdtime
				FROM vtiger_notes			
				INNER JOIN vtiger_crmentity ON vtiger_notes.notesid = vtiger_crmentity.crmid 
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
				LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid 
				INNER JOIN vtiger_attachmentsfolder vtiger_attachmentsfolderfolderid ON vtiger_notes.folderid = vtiger_attachmentsfolderfolderid.folderid
				INNER JOIN vtiger_user2role ON vtiger_user2role.userid = vtiger_crmentity.smcreatorid
				
				
				WHERE vtiger_user2role.roleid NOT IN ('H201', 'H202') 
				AND vtiger_crmentity.setype = ? AND (vtiger_notes.title LIKE ? || vtiger_crmentity.crmid LIKE ?) AND vtiger_crmentity.deleted=0 AND vtiger_notes.notesid > 0";	
				
				$params = array_merge(array($module, "%$searchKey%", "%$searchKey%"));
			} else {
				$query = 'SELECT label, crmid, setype, createdtime FROM vtiger_crmentity WHERE vtiger_crmentity.deleted = 0 AND setype = ? AND ( label LIKE ? || crmid LIKE ? ) ';
				
				$params = array_merge(array($module, "%$searchKey%", "%$searchKey%"));					
			}			
		
		}
		
		
		
		//Remove the ordering for now to improve the speed
		//$query .= ' ORDER BY createdtime DESC';

		$result = $db->pquery($query, $params);
		$noOfRows = $db->num_rows($result);
		
		$moduleModels = $matchingRecords = $leadIdsList = array();
		for($i=0; $i<$noOfRows; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			if ($row['setype'] === 'Leads') {
				$leadIdsList[] = $row['crmid'];
			}
		}
		$convertedInfo = Leads_Module_Model::getConvertedInfo($leadIdsList);

		for($i=0, $recordsCount = 0; $i<$noOfRows && $recordsCount<100; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			if ($row['setype'] === 'Leads' && $convertedInfo[$row['crmid']]) {
				continue;
			}

			$custom_permission_check = custom_access_rules($row['crmid'],$row['setype']);			
			if ((Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid'])) || ($custom_permission_check == true)) {
				$row['id'] = $row['crmid'];
				$moduleName = $row['setype'];
				if(!array_key_exists($moduleName, $moduleModels)) {
					$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
				}
				$moduleModel = $moduleModels[$moduleName];
				$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
				$recordInstance = new $modelClassName();
				$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
				$recordsCount++;
			}
		}
		
		
		
		return $matchingRecords;
	}

	/**
	 * Function to get details for user have the permissions to do actions
	 * @return <Boolean> - true/false
	 */
	public function isEditable() {
		return Users_Privileges_Model::isPermitted($this->getModuleName(), 'EditView', $this->getId());
	}

	/**
	 * Function to get details for user have the permissions to do actions
	 * @return <Boolean> - true/false
	 */
	public function isDeletable() {
		return Users_Privileges_Model::isPermitted($this->getModuleName(), 'Delete', $this->getId());
	}

	/**
	 * Funtion to get Duplicate Record Url
	 * @return <String>
	 */
	public function getDuplicateRecordUrl() {
		$module = $this->getModule();
		return 'index.php?module='.$this->getModuleName().'&view='.$module->getEditViewName().'&record='.$this->getId().'&isDuplicate=true';

	}

	/**
	 * Function to get Display value for RelatedList
	 * @param <String> $value
	 * @return <String>
	 */
	public function getRelatedListDisplayValue($fieldName) {
		$fieldModel = $this->getModule()->getField($fieldName);
		return $fieldModel->getRelatedListDisplayValue($this->get($fieldName));
	}

	/**
	 * Function to delete corresponding image
	 * @param <type> $imageId
	 */
	public function deleteImage($imageId) {
		$db = PearDatabase::getInstance();

		$checkResult = $db->pquery('SELECT crmid FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', array($imageId));
		$crmId = $db->query_result($checkResult, 0, 'crmid');

		if ($this->getId() === $crmId) {
			$db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($imageId));
			$db->pquery('DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', array($imageId));
			return true;
		}
		return false;
	}

	/**
	 * Function to get Descrption value for this record
	 * @return <String> Descrption
	 */
	public function getDescriptionValue() {
		$description = $this->get('description');
		if(empty($description)) {
			$db = PearDatabase::getInstance();
			$result = $db->pquery("SELECT description FROM vtiger_crmentity WHERE crmid = ?", array($this->getId()));
			$description =  $db->query_result($result, 0, "description");
		}
		return $description;
	}

	/**
	 * Function to transfer related records of parent records to this record
	 * @param <Array> $recordIds
	 * @return <Boolean> true/false
	 */
	public function transferRelationInfoOfRecords($recordIds = array()) {
		if ($recordIds) {
			$moduleName = $this->getModuleName();
			$focus = CRMEntity::getInstance($moduleName);
			if (method_exists($focus, 'transferRelatedRecords')) {
				$focus->transferRelatedRecords($moduleName, $recordIds, $this->getId());
			}
		}
		return true;
	}

}
