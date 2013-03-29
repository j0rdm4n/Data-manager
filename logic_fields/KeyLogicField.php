<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLHidden.php");

class KeyLogicField extends LogicField {
		
	function getField() {
		$value = $this->getParam('value');

		$field = $value."\n";

		$field_obj = new HTMLHidden();
		$field_obj->addParams($this->params);
		$field .= $field_obj->getField();
		$field .= "\n";

		$join_table = $this->getParam('join_table');
		if(isset($join_table['key_field'])) {
			$row_data = $this->getParam('row_data');
			$join_table_params = array(
				'name' => $join_table['key_field']
				, 'value' => $row_data[$join_table['key_field']]
			);

			$field_obj = new HTMLHidden();
			$field_obj->addParams($join_table_params);
			$field .= $field_obj->getField();
		}

		return $field;
	}
}
