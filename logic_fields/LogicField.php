<?php

include_once DM_PATH.DS.'classes'.DS.'DMObject.php';

class LogicField extends DMObject {
	protected $params = array();
	
	function addParams($params) {
		$this->params = array_merge($this->params, $params); 
	}
	
	function setParam($param, $value) {
		$this->params[$param] = $value;
	}
	
	function getParam($param, $default = false) {
		return (isset($this->params[$param])?$this->params[$param]:$default);
	}

	function getURLString($link) {
		if (!$link) {
			return DM_BASE_URL;
		}
		if (DM_BASE_URL && $link[0] == '/') {
			$link = substr($link, 1);
		}
		return DM_BASE_URL.$link;
	}
	
}
