<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLHidden.php");

class ReadonlyLogicField extends LogicField {
	function getField() {
		$value = $this->getParam('value');
		$key_field_value = $this->getParam('key_field_value');
		$external_link = $this->getParam('external_link');

		switch($this->params['mode']) {
			case 'list':
				$is_link = $this->getParam('is_link');
				$link = $this->getParam('link');

//					var_dump($key_field_value); exit;
				if ($is_link && $link && $key_field_value) {
					$link = str_replace('*', $key_field_value, $link);
					$link = $this->getURLString($link);
					$field = '<a href="'.$link.'">'.$value.'</a>'."\n";
				}elseif($external_link) {
					$external_link = str_replace('*', $value, $external_link);
					$external_link = $this->getURLString($external_link);
					$field = '<a href="'.$external_link.'">'.$value.'</a>'."\n";
				}else {
					$field = (string) $value;
				}
				break;
			case 'single':
				if($external_link) {
					$external_link = str_replace('*', $value, $external_link);
					$external_link = $this->getURLString($external_link);
					$field = '<a href="'.$external_link.'">'.$value.'</a>'."\n";
				}else {
					$field = $value."\n";
				}
				$this->params['value'] = $value;
				$field_obj = new HTMLHidden();
				$field_obj->addParams($this->params);
				$field .= $field_obj->getField();

				break;
		}

		return $field;
	}

	function getValue() {
		return false;
	}
}
