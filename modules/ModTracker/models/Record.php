<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
require_once('include/diff.php');
require_once('include/finediff.php');

class ModTracker_Record_Model extends Vtiger_Record_Model {

	const UPDATE = 0;
	const DELETE = 1;
	const CREATE = 2;
	const RESTORE = 3;
	const LINK = 4;
	const UNLINK = 5;

	/**
	 * Function to get the history of updates on a record
	 * @param <type> $record - Record model
	 * @param <type> $limit - number of latest changes that need to retrieved
	 * @return <array> - list of  ModTracker_Record_Model
	 */
	public static function getUpdates($parentRecordId, $pagingModel,$moduleName) {
		if($moduleName == 'Calendar') {
			if(getActivityType($parentRecordId) != 'Task') {
				$moduleName = 'Events';
			}
		}
		$db = PearDatabase::getInstance();
		$recordInstances = array();

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$listQuery = "SELECT * FROM vtiger_modtracker_basic WHERE crmid = ? AND module = ? ".
						" ORDER BY changedon DESC LIMIT $startIndex, $pageLimit";

		$result = $db->pquery($listQuery, array($parentRecordId, $moduleName));
		$rows = $db->num_rows($result);

		for ($i=0; $i<$rows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$recordInstance = new self();
			$recordInstance->setData($row)->setParent($row['crmid'], $row['module']);
			$recordInstances[] = $recordInstance;
		}
		return $recordInstances;
	}

	function setParent($id, $moduleName) {
		if(!Vtiger_Util_Helper::checkRecordExistance($id)) {
			$this->parent = Vtiger_Record_Model::getInstanceById($id, $moduleName);
		} else {
			$this->parent = Vtiger_Record_Model::getCleanInstance($moduleName);
			$this->parent->id = $id;
			$this->parent->setId($id);
		}
	}

	function getParent() {
		return $this->parent;
	}

	function checkStatus($callerStatus) {
		$status = $this->get('status');
		if ($status == $callerStatus) {
			return true;
		}
		return false;
	}

	function isCreate() {
		return $this->checkStatus(self::CREATE);
	}

	function isUpdate() {
		return $this->checkStatus(self::UPDATE);
	}

	function isDelete() {
		return $this->checkStatus(self::DELETE);
	}

	function isRestore() {
		return $this->checkStatus(self::RESTORE);
	}

	function isRelationLink() {
		return $this->checkStatus(self::LINK);
	}

	function isRelationUnLink() {
		return $this->checkStatus(self::UNLINK);
	}

	function getModifiedBy() {
		$changeUserId = $this->get('whodid');
		return Users_Record_Model::getInstanceById($changeUserId, 'Users');
	}

	function getActivityTime() {
		return $this->get('changedon');
	}

	function getFieldInstances() {
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		$fieldInstances = array();
		if($this->isCreate() || $this->isUpdate()) {
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_detail WHERE id = ?', array($id));
			$rows = $db->num_rows($result);
			for($i=0; $i<$rows; $i++) {
				$data = $db->query_result_rowdata($result, $i);
				$row = array_map('decode_html', $data);
			
				if($row['fieldname'] == 'record_id' || $row['fieldname'] == 'record_module') continue;

				if ($row['fieldname'] == 'description'){	
					 
					$row['postvalue'] = $this->hightlight_updated_description($row['prevalue'],$row['postvalue']);
				  }

				$fieldModel = Vtiger_Field_Model::getInstance($row['fieldname'], $this->getParent()->getModule());
				if(!$fieldModel) continue;
				
				$fieldInstance = new ModTracker_Field_Model();
				$fieldInstance->setData($row)->setParent($this)->setFieldInstance($fieldModel);
				$fieldInstances[] = $fieldInstance;
			}
		}
		return $fieldInstances;
	}

	function getRelationInstance() {
		$id = $this->get('id');
		$db = PearDatabase::getInstance();

		if($this->isRelationLink() || $this->isRelationUnLink()) {
			$result = $db->pquery('SELECT * FROM vtiger_modtracker_relations WHERE id = ?', array($id));
			$row = $db->query_result_rowdata($result, 0);
			$relationInstance = new ModTracker_Relation_Model();
			$relationInstance->setData($row)->setParent($this);
		}
		return $relationInstance;
	}
        
	public function getTotalRecordCount($recordId) {
    	$db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT COUNT(*) AS count FROM vtiger_modtracker_basic WHERE crmid = ?", array($recordId));
        return $db->query_result($result, 0, 'count');
	}

	public function hightlight_updated_description($before,$after){
		$diff = new diff_class;
		$difference = new stdClass;
		$difference->mode = 'w';
		$difference->patch = true;
		$after_patch = new stdClass;
		if($diff->FormatDiffAsHtml($before, $after, $difference) && $diff->Patch($before, $difference->difference, $after_patch)){
			$text = $difference->html;
			$a = html_entity_decode($text);
			//$text = str_replace("\n","<br />",$a);
		}
		return $a;
	}

	public function hightlight_updated_description_2($from_text,$to_text){
		$cache_lo_water_mark = 900;
		$cache_hi_water_mark = 1100;
		//$compressed_serialized_filename_extension = '.store.gz';
	

		$granularity = 2;
		$diff_opcodes = '';
		$diff_opcodes_len = 0;
		$data_key = '';
	
		$from_text = substr($from_text, 0, 1024*100);
		$to_text = substr($to_text, 0, 1024*100);
	
		// ensure input is suitable for diff
		$from_text = mb_convert_encoding($from_text, 'HTML-ENTITIES', 'UTF-8');		
		$to_text = mb_convert_encoding($to_text, 'HTML-ENTITIES', 'UTF-8');
		
		$granularityStacks = array(
			FineDiff::$paragraphGranularity,
			FineDiff::$sentenceGranularity,
			FineDiff::$wordGranularity,
			FineDiff::$characterGranularity
		);
		$diff_opcodes = FineDiff::getDiffOpcodes($from_text, $to_text, $granularityStacks[$granularity]);
		
		$diff_opcodes_len = strlen($diff_opcodes);
		$exec_time = gettimeofday(true) - $start_time;
		if ( $diff_opcodes_len ) {
			$data_key = sha1(serialize(array('granularity' => $granularity, 'from_text' => $from_text, 'diff_opcodes' => $diff_opcodes)));
			$filename = "{$data_key}{$compressed_serialized_filename_extension}";
			
			if ( !file_exists("./cache/{$filename}") ) {
		
				// purge cache if too many files
				if ( !(time() % 100) ) {
					$files = glob("./cache/*{$compressed_serialized_filename_extension}");
					$num_files = $files ? count($files) : 0;
					if ( $num_files > $cache_hi_water_mark ) {
						$sorted_files = array();
						foreach ( $files as $file ) {
							$sorted_files[strval(@filemtime("./cache/{$file}")).$file] = $file;
						}
						ksort($sorted_files);
						foreach ( $sorted_files as $file ) {
							@unlink("./cache/{$file}");
							$num_files -= 1;
							if ( $num_files < $cache_lo_water_mark ) {
								break;
							}
						}
					}
				}
				// save diff in cache
				$data_to_serialize = array(
					'granularity' => $granularity,
					'from_text' => $from_text,
					'diff_opcodes' => $diff_opcodes,
					'data_key' => $data_key,
				);
				$serialized_data = serialize($data_to_serialize);
				@file_put_contents("./cache/{$filename}", gzcompress($serialized_data));
				@chmod("./cache/{$filename}", 0666);
			}
		}
	
		$rendered_diff = FineDiff::renderDiffToHTMLFromOpcodes($from_text, $diff_opcodes);
		$text = htmlspecialchars_decode($rendered_diff, ENT_QUOTES);
		return str_replace("\n","<br />",$text);
	}
}