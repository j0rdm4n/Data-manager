<?php

include_once dirname(__FILE__).DS.'HTMLField.php';

class HTMLFileUpload extends HTMLField {
		
	function getField() {
		$field = '<input type="file" ';
		$field .= $this->getHTMLParam('name');
		$field .= ' /> '."\n";
		return $field;
	}
} 