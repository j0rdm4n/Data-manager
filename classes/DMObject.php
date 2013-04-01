<?php

class DMObject {
	protected $errors = array();

	function getErrors() {
		foreach($this->errors as &$error) {
			$error = 'Data manager error: '.$error;
		}
		return $this->errors;
	}

	function getErrorsStr() {
		return implode(' ',$this->errors);
	}
	
}