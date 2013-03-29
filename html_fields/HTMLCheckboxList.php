<?php

include_once dirname(__FILE__).DS.'HTMLField.php';

class HTMLCheckboxList extends HTMLField {
	protected $items = array();

	function addItem($item, $value = false) {
		if ($value === false) {
			$this->items[] = $item;
		}else {
			$this->items[$value] = $item;
		}
	}

	function addItems($items) {
		$this->items = $this->items + $items;
	}

	function getField() {
		$name = $this->getParam('name');
		$is_use_groups = $this->getParam('is_use_groups');

		//$class = $this->getHTMLParam('class', 'css_class');

		$selected_value = $this->getParam('value');

		$field = '';
		if($is_use_groups) {
			$field = '<div style="overflow-y: scroll; height:200px;">';
		}
		$in_group = false;
		foreach ($this->items as $group => $items) {
			if($is_use_groups && $group) {
				$field .= '<span style="font-weight: bold;">'.$group.'</span><br>';
				$in_group = true;
			}
			foreach ($items as $value => $item) {
				$is_checked = 0;
				if (is_array($selected_value) && in_array($value, $selected_value)) {
					$is_checked = 1;
				}elseif ($selected_value==$value) {
					$is_checked = 1;
				}

				$field .= '
					<input type="checkbox" value="'.$value.'" name="'.$name.'[]" '
							.'id="'.$name.'-'.$value.'" '
							.($is_checked?' checked ':'')
							.($in_group?' style="margin:0 0 0 15px" ':'')
					.'>
					<label for="'.$name.'-'.$value.'">'.$item.'</label>
					<br>
				';
			}
		}
		$field .= "</div>\n";

		return $field;
	}
}