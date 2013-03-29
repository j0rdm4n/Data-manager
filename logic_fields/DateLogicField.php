<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLInput.php");

class DateLogicField extends LogicField {
	function getField() {
		$value = $this->getParam('value');
		$name = $this->getParam('name');
		
		switch($this->params['mode']) {
			case 'list':
				$is_link = $this->getParam('is_link');
				$link = $this->getParam('link');
				$key_field_value = $this->getParam('key_field_value');
				if ($is_link && $link && $key_field_value) {
					$link = str_replace('*', $key_field_value, $link);
					$link = $this->getURLString($link);
					$field = '<a href="'.$link.'">'.$value.'</a>'."\n";
				}else {
					$field = (string) $value;
				}
				break;
			case 'single':
				$field = '
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.2/jquery-ui.js"></script>
					<script type="text/javascript">
						$(document).ready(function(){
							$(".date_field_'.$name.'").datepick();
						});
					</script>
				';
				$this->params['css_class'] = 'date_field_'.$name;
				$field_obj = new HTMLInput();
				$field_obj->addParams($this->params);
				$field .= $field_obj->getField();
				break;
		}
		
		return $field;
	}
	
	function getValue() {
		$value = $this->getParam('value');
		return date('Y-m-d',strtotime($value));
	}
}