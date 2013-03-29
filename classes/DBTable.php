<?php

include_once DM_PATH.DS.'classes'.DS.'DMObject.php';

class DBTable extends DMObject{
	protected $table = '';
	protected $join_table = array();
	protected $ordering_fields = '';
	protected $limit_start = 0;
	protected $limit = 0;
	protected $table_fields = array();
	protected $where_conditions = array();
	protected $key_field = '';
	protected $data = array();
		
	function __construct() {
	}
	
	function addWhereCondition($where_condition) {
		$this->where_conditions[] = $where_condition;
	}
	
	function delete($table = false) {
		$db = DBMysql::getInstance();

		if($table === false) {
			$table = $this->table;
		}
		
		$where_str = $this->getWhereStr();
		
		$query = "DELETE FROM `{$table}` WHERE {$where_str} ";

		$db->setQuery( $query );
		$result = $db->query();

		if(!$result) {
			$this->errors[] = $db->getError();
		}
		return $result;
	}
	
	function loadData() {
		$db = DBMysql::getInstance();
		$select_fields = array();
		foreach($this->table_fields as $table_field) {
			$select_fields[] = "`{$table_field['table']}`.`{$table_field['name']}`";
		}
		if(isset($this->join_table['name'])) {
			$select_fields[] = "`{$this->join_table['name']}`.`{$this->join_table['key_field']}`";
		}

		$query = "
			SELECT ".implode(',', $select_fields)."
			FROM `{$this->table}`
		";
		if(isset($this->join_table['name'])) {
			$query .= "
				INNER JOIN `{$this->join_table['name']}` ON
					`{$this->join_table['name']}`.`{$this->join_table['join_field']}`
						= `{$this->table}`.`{$this->key_field}`
			";
		}
		if ($this->where_conditions) {
			$query .= ' WHERE '.$this->getWhereStr();
		}
		if ($this->ordering_fields) {
			$order_by_params = array();
			foreach($this->ordering_fields as $field_info) {
				$param = "`{$field_info['field']}`";
				if(isset($field_info['is_asc']) && !$field_info['is_asc']) {
					$param .= ' DESC ';
				}
				$order_by_params[] = $param;
			}
			$query .= " ORDER BY  ".implode(', ', $order_by_params);
		}
		if ($this->limit) {
			$query .= " LIMIT {$this->limit_start}, {$this->limit} ";
		}
		$db->setQuery( $query );
		$result = $db->getArrays();

		if ($result === false) {
			$this->errors[] = $db->getError();
			return false;
		}

		$this->data = $result;
		
		return true;
	}

	function getFieldValue($field) {
		return (isset($this->data[0][$field])?$this->data[0][$field]:false);
	}
	
	function getUpdateFields($table) {
		$update_fields = array();
		foreach ($this->table_fields as $field) {
			if (
				$field['name'] == $this->key_field
				|| $field['table'] != $table
			) {
				continue;
			}
			$update_fields[] = $field['name'];
		}
		return $update_fields;
	}
	
	function getWhereStr() {
		if ($this->where_conditions) {
			return implode(' AND ', $this->where_conditions);
		}
		return '';
	}
	
	function insert($data, $table) {
		$db = DBMysql::getInstance();
		
		$update_fields = $this->getUpdateFields($table);
		foreach($update_fields as $key => $field) {
			if (isset($data[$field])) {
				$values[] = $data[$field];
			}else {
				unset($update_fields[$key]);
			}
		}
		$fields_str = '`'.implode('`,`',$update_fields).'`';
		foreach($values as &$value) {
			if(is_null($value)) {
				$value = "NULL";
			}else {
				$value = "'{$value}'";
			}

		}
		$values_str = implode(", ", $values);
		
		$query = "INSERT INTO `{$table}`( {$fields_str} ) VALUES ( {$values_str} )";

		$db->setQuery( $query );
		$result = $db->query();

		if(!$result) {
			$this->errors[] = $db->getError();
		}
		return $result;
	}
	
	function setOrderingField($ordering_field, $is_asc = 1) {
		$this->ordering_fields[] = array(
			'field' => $ordering_field
			, 'is_asc' => $is_asc
		);
	}
	
	function update($data, $table) {
		$db = DBMysql::getInstance();
		
		$update_fields = $this->getUpdateFields($table);

		$params = array();
		foreach($update_fields as $field) {
			if (isset($data[$field])) {
				$params[] = " `{$field}` = '{$data[$field]}' ";
			}
		}

		if(!$params) {
			return true;
		}

		$params_str = implode(', ', $params);
		$where_str = $this->getWhereStr();
		
		$query = "UPDATE `{$table}` SET {$params_str} WHERE {$where_str} ";

		$db->setQuery( $query );
		$result = $db->query();

		if(!$result) {
			$this->errors[] = $db->getError();
		}
		return $result;
	}

	function getRowsCount() {
		$db = DBMysql::getInstance();

		$query = "
			SELECT COUNT(*)
			FROM `{$this->table}`
		";
		if(isset($this->join_table['name'])) {
			$query .= "
				INNER JOIN `{$this->join_table['name']}` ON
					`{$this->join_table['name']}`.`{$this->join_table['join_field']}`
						= `{$this->table}`.`{$this->key_field}`
			";
		}
		if ($this->where_conditions) {
			$query .= ' WHERE '.$this->getWhereStr();
		}

		$db->setQuery( $query );
		$result = $db->getResult();

		if ($result === false) {
			$this->errors[] = $db->getError();
			return false;
		}

		return $result;
	}
	
/*	
	function isTableExists() {
		$db = DBMysql::getInstance();
		$query = "show tables like '{$this->table}'";
		$db->setQuery( $query );
		$is_exist = $db->getResult();
		$is_exist = !empty($is_exist);
		return $is_exist;
	}
	
	function getFields() {
		$db = DBMysql::getInstance();
		$query = 'SHOW FIELDS FROM ' . $this->table;
		$db->setQuery( $query );
		$fields = $db->getObjects();
		foreach($fields as $key => $field) {
			$fields[$key] = $field->Field;
		}
		return $fields;
	}
*/
}