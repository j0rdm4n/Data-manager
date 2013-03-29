<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLCheckboxList.php");

class ManyToManyLogicField extends LogicField {
	protected $items = array();
	//private $selected_values = array();

	function loadItems() {
		$key_field = $this->getParam('key_field');
		if($key_field) {
			$row_data = $this->getParam('row_data');
			if(isset($row_data[$key_field])) {
				$key_field_value = $row_data[$key_field];
			}else {
				$key_field_value = $this->getParam('key_field_value');
			}
		}else {
			$key_field_value = $this->getParam('key_field_value');
		}
		$many_to_many_table = $this->getParam('many_to_many_table');
		$many_to_many_table_filters = $this->getParam('many_to_many_table_filters');
		$link_field = $this->getParam('link_field');
		$lookup_field = $this->getParam('lookup_field');
		$lookup_table = $this->getParam('lookup_table');
		$lookup_table_id_field = $this->getParam('lookup_table_id_field');
		$lookup_table_title_field = $this->getParam('lookup_table_title_field');
		$lookup_table_enabled_field = $this->getParam('lookup_table_enabled_field');
		$lookup_table_group_by_field = $this->getParam('lookup_table_group_by_field');
		$lookup_table_group_by_table = $this->getParam('lookup_table_group_by_table');
		$lookup_table_group_by_table_id_field = $this->getParam('lookup_table_group_by_table_id_field');
		$lookup_table_group_by_table_title_field = $this->getParam('lookup_table_group_by_table_title_field');

		$db = DBMysql::getInstance();

		$query = "
			SELECT lt.`{$lookup_table_id_field}` AS lookup_table_id_field
				, lt.`{$lookup_table_title_field}` AS lookup_table_title_field
				".(
					$lookup_table_enabled_field?"
						, lt.`{$lookup_table_enabled_field}` AS lookup_table_enabled_field
					":
						''
				)."
				".(
					$lookup_table_group_by_field?"
						, gb.`{$lookup_table_group_by_table_title_field}` AS lookup_table_group_by_table_title_field
					":
						''
				)."
				, mtmt.`{$lookup_field}` AS lookup_field
			FROM `{$lookup_table}` lt
			".(
				$lookup_table_group_by_field?"
					LEFT JOIN `{$lookup_table_group_by_table}` gb ON lt.`{$lookup_table_group_by_field}`
							= gb.`{$lookup_table_group_by_table_id_field}`
				":
					''
			)."
			LEFT JOIN `{$many_to_many_table}` mtmt
				ON lt.`{$lookup_table_id_field}` = mtmt.`{$lookup_field}`
				AND mtmt.`{$link_field}` = '{$key_field_value}'
		";

		if($many_to_many_table_filters && is_array($many_to_many_table_filters)) {
			$where_conds = array();
			foreach($many_to_many_table_filters as $field => $value) {
				$where_conds[] = " AND ( mtmt.`{$field}` = '{$value}' OR mtmt.`{$field}` IS NULL )";
			}
			$query .= implode(' ', $where_conds);
		}

		$query .= " ORDER BY lookup_table_title_field";

		$db->setQuery( $query );
		$result = $db->getArrays();

		if ($result === false) {
			$this->errors[] = $db->getError();
			return false;
		}

		$this->params['value'] = array();
		foreach($result as $item) {
			if(!isset($item['lookup_table_enabled_field'])) {
				$item['lookup_table_enabled_field'] = 0;
			}
			if(!isset($item['lookup_table_group_by_table_title_field'])) {
				$item['lookup_table_group_by_table_title_field'] = '';
			}
			if ($item['lookup_field']) {
				$this->params['value'][] = $item['lookup_field'];
			}

			if(empty($item['lookup_table_group_by_table_title_field'])) {
				$item['lookup_table_group_by_table_title_field'] = 'Other';
			}
			$this->items[$item['lookup_table_group_by_table_title_field']]
				[$item['lookup_table_id_field']] = $item['lookup_table_title_field'];
		}
		return true;
	}

	function getField() {
		if (!$this->loadItems()) {
			return false;
		}
		$field = '';
		$lookup_table_group_by_field = $this->getParam('lookup_table_group_by_field');

		switch($this->params['mode']) {
			case 'list':
				if($this->params['value'] && $this->items) {
					
					foreach($this->items as $group => $items) {
						$selected_items = array();
						foreach($items as $item_id => $item) {
							if(in_array($item_id, $this->params['value'])) {
								$selected_items[] = $item;
							}
						}
						if($selected_items) {
							if($lookup_table_group_by_field) {
								$field .= '<b>'.$group.'</b>: '.implode(', ', $selected_items).'; ';
							}else {
								$field .= implode(', ', $selected_items).'; ';
							}
						}
					}
				}
				break;
			case 'single':
				if($lookup_table_group_by_field) {
					$this->params['is_use_groups'] = true;
				}
				$field_obj = new HTMLCheckboxList();
				$field_obj->addItems($this->items);
				$field_obj->addParams($this->params);
				$field = $field_obj->getField();
				break;
		}

		return $field;
	}

	function getValue() {
		return false;
	}

	function afterSave() {
		$name = $this->getParam('name');

		$key_field_value = $this->getParam('key_field_value');
		$many_to_many_table = $this->getParam('many_to_many_table');
		$link_field = $this->getParam('link_field');
		$lookup_field = $this->getParam('lookup_field');
		$many_to_many_table_filters = $this->getParam('many_to_many_table_filters');

		$db = DBMysql::getInstance();
		/// deleting
		$query = "
			DELETE FROM `{$many_to_many_table}`
			WHERE `{$link_field}` = '{$key_field_value}'
		";
		if($many_to_many_table_filters && is_array($many_to_many_table_filters)) {
			foreach($many_to_many_table_filters as $field => $value) {
				$query .= " AND `{$field}` = '{$value}' ";
			}
		}
		$db->setQuery( $query );
		if (!$db->query()) {
			$this->errors[] = $db->getError();
			return false;
		}

		// inserting
		if (!isset($_POST[$name])) {
			return true;
		}
		$fields = array(
			$link_field
			, $lookup_field
		);
		if($many_to_many_table_filters && is_array($many_to_many_table_filters)) {
			foreach($many_to_many_table_filters as $field => $value) {
				$fields[] = $field;
			}
		}

		$insert_params = array();
		foreach($_POST[$name] as $lookup_value) {
			$values = array(
				$key_field_value
				, $lookup_value
			);
			if($many_to_many_table_filters && is_array($many_to_many_table_filters)) {
				foreach($many_to_many_table_filters as $filter_value) {
					$values[] = $filter_value;
				}
			}
			$insert_params[] = "('".implode("', '", $values)."')";
		}
		$insert_str = implode(', ', $insert_params);
		$query = "
			INSERT INTO `{$many_to_many_table}`
			( `".implode('`, `', $fields)."` )
			VALUES {$insert_str}
		";

		$db->setQuery( $query );
		if (!$db->query()) {
			$this->errors[] = $db->getError();
			return false;
		}

		return true;
	}
}
