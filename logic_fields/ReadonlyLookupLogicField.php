<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");

class ReadonlyLookupLogicField extends LogicField {
	function getField() {
		$value = $this->getParam('value');

		if (!$value) {
			return ' - ';
		}

		$key_field = $this->getParam('key_field');
		$values_field = $this->getParam('values_field');
		$lookup_table = $this->getParam('lookup_table');
		$external_link = $this->getParam('external_link');

		$db = DBMysql::getInstance();
		$query = " SELECT `{$values_field}` FROM `{$lookup_table}` ";
		$query .= " WHERE `{$key_field}` = '{$value}' ";

		$db->setQuery( $query );
		$field = $db->getResult();

		if ($field === false) {
			$this->errors[] = $db->getError();
			return false;
		}

		if($external_link) {
			$external_link = str_replace('*', $value, $external_link);
			$external_link = $this->getURLString($external_link);
			$field = '<a href="'.$external_link.'">'.$field.'</a>'."\n";
		}

		return $field;
	}

	function getValue() {
//		$name = $this->getParam('name');
//		$value = $this->getParam($name.'_value');
//		return (int) $value;
		return false;
	}
}
