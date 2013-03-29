<?php

define('DM_PATH', dirname(__FILE__));
if(!defined('DS')) {
	define( 'DS', DIRECTORY_SEPARATOR );
}

include_once DM_PATH.DS.'params.php';
include_once DM_PATH.DS.'classes'.DS.'DBMysql.php';
include_once DM_PATH.DS.'classes'.DS.'DBTable.php';
include_once DM_PATH.DS.'classes'.DS.'DMRenderer.php';

class DataManager extends DBTable{
	private $mode = '';
	private $page = 1;
	private $items_per_page = 20;
	private $key_value = 0;
	private $titles = array();
	private $rendered_fields = array();
	private $fields = array();
	private $fields_params = array();
	private $fields_prefix = '';
	protected $profile = '';

	function __construct($profile) {
		$this->profile = $profile;
		
		$db = DBMysql::getInstance();
		$db->setQuery( 'SET NAMES "utf8"' );
		$db->query();
	}

	function addFieldParam($param, $value) {
		$this->fields_params[$param] = $value;
	}
	
	function delete($key_value, $join_table_key_field_value = false) {
		if( !$this->loadFields() ) {
			return false;
		}

		$delete_fields_params = array();
		foreach($this->fields as $field) {
			if ($field['type'] != 'key') {
				$field = array_merge($field, $this->fields_params);
				$delete_fields_params[] = $field;
			}
		}

		$renderer = new DMRenderer();
		$renderer->setFields($delete_fields_params);
		$renderer->setFieldsPrefix($this->fields_prefix);
		$renderer->triggerEvent('beforeDelete', $key_value, $this->table);

		if(!empty($this->join_table['name'])) {
//			$renderer->triggerEvent('beforeDelete', $join_table_key_field_value
//					, $this->join_table['name']);

			$this->where_conditions = array();
			$this->addWhereCondition(
				" `{$this->join_table['key_field']}` = '{$join_table_key_field_value}' "
			);
			$result = parent::delete($this->join_table['name']);
			if(!$result) {
				return false;
			}
		}

		if ($this->key_field && $key_value) {
			$this->where_conditions = array();
			$this->addWhereCondition(" `{$this->key_field}` = '$key_value' ");
			return parent::delete();
		}
		return false;
	}
	
	function getMode() {
		return ($this->mode?$this->mode:'list');
	}
	
	function getTableFieldsParams() {
		
	}
	
	function getTitles() {
		if( !$this->loadData() ) {
			return false;
		}
		return $this->titles;
	}
	
	function getFields() {
		if( !$this->loadData() ) {
			return false;
		}
		return $this->rendered_fields;
	}

	function getData() {
		return $this->data;
	}
	
	function loadData() {
		if ($this->rendered_fields) {
			return true;
		}
		
		if( !$this->loadFields() ) {
			return false;
		}

		if ($this->key_value && $this->key_field) {
			$this->addWhereCondition(" `{$this->table}`.`{$this->key_field}` = '$this->key_value' ");
		}

		$this->limit = $this->items_per_page;
		$this->limit_start = ($this->page - 1) * $this->items_per_page;
		
		$mode = $this->getMode();
		if ( !($mode == 'single' && !$this->key_value)
			&& parent::loadData() === false ) {
			return false;
		}
		if ($this->mode == 'list' && !$this->data) {
			return true;
		}

		$renderer = new DMRenderer();
		$renderer->setFields($this->fields);
		$renderer->setData($this->data);
		$renderer->setFieldsPrefix($this->fields_prefix);
		$aditional_params = array (
			'key_field' => $this->key_field
			, 'mode' => $mode
			, 'table' => $this->table
			, 'join_table' => $this->join_table
		);
		$renderer->addParams($aditional_params);
		$renderer->addParams($this->fields_params);
		$this->rendered_fields = $renderer->getFields();
		
		if ($this->rendered_fields === false) {
			$this->errors = $renderer->getErrors();
			return false;
		}
		return true;
		
	}

	function loadProfile() {
		if (!$this->profile) {
			$this->errors[] = 'Profile not set';
			return false;
		}

		if (!$this->mode) {
			$this->mode = 'list';
		}

		$profile_file_path = DM_PATH.DS.'profiles'.DS.$this->profile.'.php';
		if (! file_exists($profile_file_path) ) {
			$this->errors[] = 'File '.$this->profile.'.php not found in profiles folder';
			return false;

		}
		include $profile_file_path;

		$this->table = $this->profile;
		if(isset($table)) {
			$this->table = $table;
		}

		if (!isset($fields)) {
			$this->errors[] = '"fields" array not found in '.$this->profile.'.php';
			return false;
		}
		$this->fields = $fields;

		if(isset($join_table)) {
			$this->join_table = $join_table;
		}

		return true;
	}
	
	function loadFields() {
		if ($this->fields) {
			return true;
		}

		if(!$this->loadProfile()) {
			return false;
		}

		foreach($this->fields as $key => $field) {
			if (isset($field[$this->mode.'_mode']) && !$field[$this->mode.'_mode']) {
				unset($this->fields[$key]);
				continue;
			}

			$field_table = $this->table;
			if(isset($field['db_table'])) {
				$field_table = $field['db_table'];
			}
			$this->fields[$key]['db_table'] = $field_table;
			if (empty($field['non_db'])) {
				$this->table_fields[] = array(
					'table' => $field_table
					, 'name' => $field['name']
				);
			}

			if (!isset($field['title'])) {
				$field['title'] = $field['name'];
			}
			$this->titles[$field['name']] = $field['title'];
			if ($field['type'] == 'key') {
				$this->key_field = $field['name'];
			}
		}

		return true;
	}
	
	function save($data) {
		$this->setMode('save');
		
		if( !$this->loadFields() ) {
			return false;
		}

		$update_fields_params = array();
		foreach($this->fields as $field) {
			if ($field['type'] != 'key') {
				$field = array_merge($field, $this->fields_params);
				$update_fields_params[] = $field;
			}
		}

		$renderer = new DMRenderer();
		$renderer->setFields($update_fields_params);
		$renderer->setData($data);
		$renderer->setFieldsPrefix($this->fields_prefix);
		$mode = $this->getMode();
		$aditional_params = array (
			'key_field' => $this->key_field
			, 'mode' => $mode
		);
		$renderer->addParams($aditional_params);
		$data = $renderer->getFieldsData();
		
		if ($data === false) {
			$this->errors = $renderer->getErrors();
			return false;
		}

		$this->key_value = (isset($data[$this->key_field])?$data[$this->key_field]:0);
		if ($this->key_field && $this->key_value) {
			$this->addWhereCondition(" `{$this->key_field}` = '{$this->key_value}' ");
			$result = parent::update($data, $this->table);
			if(!$result) {
				return $result;
			}

			$renderer->triggerEvent('afterSave', $this->key_value, $this->table);

			if(!empty($this->join_table['name'])) {
				$this->where_conditions = array();
				$join_table_key_field_value = $data[$this->join_table['key_field']];
				$this->addWhereCondition(
					" `{$this->join_table['key_field']}` = '{$join_table_key_field_value}' "
				);
				$result = parent::update($data, $this->join_table['name']);
				$renderer->triggerEvent('afterSave', $this->key_value, $this->table);
			}
		}else {
			$result = parent::insert($data, $this->table);

			$db = DBMysql::getInstance();
			$this->key_value = $db->insertid();
			if(!$result) {
				return $result;
			}

			$renderer->triggerEvent('afterSave', $this->key_value, $this->table);

			if(!empty($this->join_table['name'])) {
				$this->table_fields[] = array(
					'table' => $this->join_table['name']
					, 'name' => $this->join_table['join_field']
				);
				$data[$this->join_table['join_field']] = $this->key_value;
				$this->key_field = $this->join_table['key_field'];
				$result = parent::insert($data, $this->join_table['name']);
				$join_table_key_field_value = $db->insertid();
				$renderer->triggerEvent('afterSave', $join_table_key_field_value
					, $this->join_table['name']);
			}
		}

		if(!$result) {
			return $result;
		}

		return $result;
	}

	function getPagesCount() {
		$this->loadProfile();
		$rows_count = $this->getRowsCount();
		if($rows_count === false) {
			return false;
		}

		return (int) ceil($rows_count / $this->items_per_page);
	}

	function setFieldsPrefix($prefix) {
		$this->fields_prefix = $prefix;
	}

	function setItemsPerPage($items_per_page) {
		$this->items_per_page = $items_per_page;
	}
	
	function setKeyValue($key_value) {
		$this->key_value = $key_value;
	}
	
	function setMode($mode) {
		$this->mode = $mode;
	}
	
	function setPage($page) {
		$this->page = $page;
	}

	function getKeyValue() {
		return $this->key_value;
	}
	
}