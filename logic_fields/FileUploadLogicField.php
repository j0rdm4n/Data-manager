<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLHidden.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLFileUpload.php");

class FileUploadLogicField extends LogicField {
	function getField() {
		$value = $this->getParam('value');
//		$row_data = $this->getParam('row_data');
//		var_dump($row_data);
		$field = '';
		switch($this->params['mode']) {
			case 'list':
				$field = (string) $value;
				break;
			case 'single':
				if($value) {
					$field = 'Uploaded file: '.$value.'<br>';
					$image_url = $this->getParam('image_url');
					if($image_url) {
						$field .= '<img src="'.$image_url.$value.'" width="500"><br>';
//						$delete_image_url = base_url() . 'bids/delete_image/'.$row_data['id'];
//						$approve_image_url = base_url() . 'bids/approve_image/'.$row_data['id'];
//						$field .= "<input type='button' onclick='document.location.href=\"".$approve_image_url."\"' value='APPROVE'>";
//						$field .= "<input style='margin-left: 10px;' type='button' onclick='document.location.href=\"".$delete_image_url."\"' value='DELETE'>";
						$field .= '<br>';
					}
				}
//				$field_obj = new HTMLHidden();
//				$field_obj->addParams($this->params);
//				$field .= $field_obj->getField();

				$field_obj = new HTMLFileUpload();
				$field_obj->addParams($this->params);
				$field .= $field_obj->getField();
				break;
		}
		
		return $field;
	}
	
	function getValue() {
		$name = $this->getParam('name');
//		var_dump($name);
//		var_dump($_FILES);
//		exit;

//		$value = $this->getParam('value');
		if(empty($_FILES) || empty($_FILES[$name]['name'])) {
			return false;
		}

		$file = $_FILES[$name];
//		var_dump($files_unsorted);
//		exit;
//		$files = array();
//		foreach ($files_unsorted['name'] as $key => $value) {
//			$files[] = array(
//				'name'		=> $files_unsorted['name'][$key],
//				'type'		=> $files_unsorted['type'][$key],
//				'tmp_name'	=> $files_unsorted['tmp_name'][$key],
//				'error'		=> $files_unsorted['error'][$key],
//				'size'		=> $files_unsorted['size'][$key]
//			);
//		}

		$upload_path = $this->getParam('upload_path');
		$max_filesize_mb = $this->getParam('max_filesize_mb');
		$allowed_ext = $this->getParam('allowed_ext');

		if($file['error'] != UPLOAD_ERR_OK) {
			$this->errors[] = 'Error uploading file "'.$file['name'];
			return false;
		}
		$file_name = $file['name'];
		$file_size = $file["size"];

		if($allowed_ext && !preg_match("/\." . $allowed_ext . "$/i", $file_name)) {
			$this->errors[] = 'Wrong file type "'.$file['name'];
			return false;
		}

		if($file_size > $max_filesize_mb * 1048576) {
			$this->errors[] = 'Uploaded file too large "'.$file['name'];
			return false;
		}

		if($file_size == 0) {
			$this->errors[] = 'File empty "'.$file['name'];
			return false;
		}

		$pathinfo = pathinfo($file_name);
		$file_name = str_replace(' ', '_', $file_name);
		$file_name = str_replace('&', '_and_', $file_name);
		$file_name = preg_replace('/[^a-z0-9._-]/i', '', $file_name);
		$ext = $pathinfo['extension'];
		$filename_w_o_ext = basename($file_name, ".".$ext);

		$file_name_to_save = $filename_w_o_ext.($ext?'.'.$ext:'');
		$file_to_save = $upload_path.$file_name_to_save;
		$i = 0;
		while (file_exists($file_to_save)) {
			$i++;
			$file_name_to_save = $filename_w_o_ext.$i.($ext?'.'.$ext:'');
			$file_to_save = $upload_path.$file_name_to_save;
		}
		if (move_uploaded_file($file['tmp_name'], $file_to_save)) {
			// save new filename ($file_name_to_save) to database
			return htmlentities($file_name_to_save, ENT_QUOTES);
		} else {
			$this->errors[] = 'Error moving file "'.$file['name'];
			return false;
		}
	}

}
