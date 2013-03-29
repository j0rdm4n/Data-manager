<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");

class ManagingLogicField extends LogicField {
	
	function getField() {
		$key_field_value = $this->getParam('key_field_value');
		$delete_link = $this->getParam('delete_link');
		$edit_link = $this->getParam('edit_link');
		
		$field = '';
		if ($edit_link) {
			$edit_link = str_replace('*', $key_field_value, $edit_link);
			$edit_link = $this->getURLString($edit_link);
			$field .= '<a href="'.$edit_link.'">Edit</a>'."\n";
		}
		
		if (!$delete_link) {
			return $field;
		}

		$check_foreign_key = $this->getParam('check_foreign_key');

		$is_can_delete = 1;
		if($check_foreign_key) {
			foreach($check_foreign_key as $foreign_key) {
				if(!empty($foreign_key['is_allow_delete'])) {
					continue;
				}

				$query = "
					SELECT COUNT(*)
					FROM `{$foreign_key['table']}`
					WHERE `{$foreign_key['field']}` = '{$key_field_value}'
				";
				if(isset($foreign_key['filters']) && $foreign_key['filters']) {
					foreach($foreign_key['filters'] as $field => $value) {
						$query .= " AND `{$field}` = '{$value}' ";
					}
				}

				$db = DBMysql::getInstance();
				$db->setQuery( $query );
				$result = $db->getResult();

				if ($result === false) {
					$this->errors[] = $db->getError();
					return false;
				}

				if($result) {
					$is_can_delete = 0;
					break;
				}
			}
			if(!$is_can_delete) {
				return $field;
			}
		}

		$delete_link = str_replace('*', $key_field_value, $delete_link);
		$delete_link = $this->getURLString($delete_link);
		$field .= ($field?'&nbsp;':'');
		if($edit_link) {
			$field .= '<br>';
		}
		$field .= '<a href="'.$delete_link.'"
			onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</a>'."\n";

		return $field;
	}

	function getValue() {
		return true;
	}

	function beforeDelete() {
		$check_foreign_key = $this->getParam('check_foreign_key');
		$key_field_value = $this->getParam('key_field_value');
//		$many_to_many_table = $this->getParam('many_to_many_table');

		if(!$check_foreign_key) {
			return true;
		}

		$db = DBMysql::getInstance();

		foreach($check_foreign_key as $foreign_key) {
			if(empty($foreign_key['is_allow_delete'])) {
				continue;
			}

			$query = "
				DELETE FROM `{$foreign_key['table']}`
				WHERE `{$foreign_key['field']}` = '{$key_field_value}'
			";
			$db->setQuery( $query );
			if (!$db->query()) {
				$this->errors[] = $db->getError();
				return false;
			}
		}

		return true;
	}

}
