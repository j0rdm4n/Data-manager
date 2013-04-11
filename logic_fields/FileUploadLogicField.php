<?php
include_once(DM_PATH.DS."logic_fields".DS."LogicField.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLHidden.php");
include_once(DM_PATH.DS."html_fields".DS."HTMLFileUpload.php");

class FileUploadLogicField extends LogicField {
	function getField() {
		$name = (string) $this->getParam('name');
		$value = (string) $this->getParam('value');
		$download_url = $this->getParam('download_url');

		$file_text = $value;
		if($download_url) {
			$file_text = '<a href="'.$download_url.$value.'">'.$value.'</a>';
		}

//		$row_data = $this->getParam('row_data');
//		var_dump($row_data);
		$field = '';
		switch($this->params['mode']) {
			case 'list':
				$field = $file_text;
				break;
			case 'single':
				if($value) {
					$field = '<span id="'.$name.'_file_info">Uploaded file: '.$file_text.'<br>';
					$image_url = $this->getParam('image_url');
					if($image_url) {
						$field .= '<img src="'.$image_url.$value.'" width="500"><br>';
						$field .= '<br>';
					}

					$is_delete_option = $this->getParam('is_delete_option');
					if($is_delete_option) {
						$script = "
								document.getElementById('{$name}_file_info').style.display = 'none';
								document.getElementById('{$name}_delete_flag').value = 'deleted';
								return false;
						";
						$field .= '
							<input type="hidden"  id="'.$name.'_delete_flag" name="'.$name.'" value="" />
							<a href="#" onclick="'.$script.'">Delete file</a><br>
						';
					}
					$field .='</span>';
				}

				$field_obj = new HTMLFileUpload();
				$field_obj->addParams($this->params);
				$field .= $field_obj->getField();
				break;
		}
		
		return $field;
	}
	
	function getValue() {
		$name = $this->getParam('name');
		$upload_path = $this->getParam('upload_path');
//		var_dump($name);
//		var_dump($_FILES);
//		exit;

		$value = $this->getParam('value');
		$is_delete_from_disk = $this->getParam('is_delete_from_disk');
		if($value === 'deleted') {
			if($is_delete_from_disk) {
				$table = $this->getParam('db_table');
				$key_field = $this->getParam('key_field');
				$key_field_value = $this->getParam('key_field_value');

				$db = DBMysql::getInstance();
				$query = "
					SELECT `{$name}`
					FROM `{$table}`
					WHERE `{$key_field}` = '{$key_field_value}'
				";
				$db->setQuery( $query );
				$filename = $db->getResult();

				if ($filename === false) {
					$this->errors[] = $db->getError();
					return false;
				}

				unlink($upload_path.$filename);
			}
			return '';
		}

		if(empty($_FILES) || empty($_FILES[$name]['name'])) {
			return false;
		}

		$file = $_FILES[$name];

		$max_filesize_mb = $this->getParam('max_filesize_mb');
		$allowed_ext = $this->getParam('allowed_ext');
		$is_overwrite_existing = $this->getParam('is_overwrite_existing');

		if($file['error'] != UPLOAD_ERR_OK) {
			if($file['error'] == UPLOAD_ERR_INI_SIZE) {
				$this->errors[] = 'Uploaded file too large "'.$file['name'];
			}else {
				$this->errors[] = 'Error uploading file "'.$file['name'];
			}
			return false;
		}
		$file_name = $file['name'];
		$file_size = $file["size"];

		if($allowed_ext && !preg_match("/\." . $allowed_ext . "$/i", $file_name)) {
			$this->errors[] = 'Wrong file type "'.$file['name'];
			return false;
		}

		if($max_filesize_mb && ($file_size > $max_filesize_mb * 1048576)) {
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

		if(!$is_overwrite_existing) {
			$i = 0;
			while (file_exists($file_to_save)) {
				$i++;
				$file_name_to_save = $filename_w_o_ext.$i.($ext?'.'.$ext:'');
				$file_to_save = $upload_path.$file_name_to_save;
			}
		}

		if (move_uploaded_file($file['tmp_name'], $file_to_save)) {
			// save new filename ($file_name_to_save)
			return htmlentities($file_name_to_save, ENT_QUOTES);
		} else {
			$this->errors[] = 'Error moving file "'.$file['name'];
			return false;
		}
	}

	function afterSave() {
//		var_dump($this);exit;
		$value = $this->getParam('value');
		$table = $this->getParam('db_table');
		$file_size_field = $this->getParam('file_size_field');
		$is_size_in_human_readable_format = $this->getParam('is_size_in_human_readable_format');
		$upload_path = $this->getParam('upload_path');
		$key_field_name = $this->getParam('key_field');
		$key_field_value = $this->getParam('key_field_value');

		if(!$file_size_field || $value === false) {
			return true;
		}

		$fileSize = '';
		if($value !== '') {
			$fileSize = filesize($upload_path.$value);

			if($is_size_in_human_readable_format) {
				switch ($fileSize) {
					case ($fileSize < 1024):
						$fileSize = $fileSize.'B';
						break;
					case ($fileSize >= 1024 && $fileSize < 1048576):
						$fileSize = round($fileSize/1024).'KB';
						break;
					case ($fileSize >= 1048576 && $fileSize < 1073741824):
						$fileSize = round($fileSize/1048576).'MB';
				}
			}
		}


		$db = DBMysql::getInstance();
		$query = "
			UPDATE `{$table}`
			SET `{$file_size_field}` = '{$fileSize}'
			WHERE `{$key_field_name}` = '{$key_field_value}'
		";
//		var_dump($query);exit;
		$db->setQuery( $query );
		if (!$db->query()) {
			$this->errors[] = $db->getError();
			return false;
		}

		return true;
	}

}
