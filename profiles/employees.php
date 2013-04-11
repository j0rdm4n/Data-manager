<?php
$table = 'employees';

$fields = array (
	array (
		'name' => 'employeeNumber'
		, 'type' => 'key'
		, 'title' => 'ID'
	)
	, array (
		'name' => 'firstName'
		, 'type' => 'input'
		, 'title' => 'First name'
		, 'is_link' => 1
		, 'link' => '/index.php?page=edit_employee&id=*'
	)
	, array (
		'name' => 'lastName'
		, 'type' => 'input'
		, 'title' => 'Last name'
		, 'is_link' => 1
		, 'link' => '/index.php?page=edit_employee&id=*'
	)
	, array (
		'name' => 'officeCode'
		, 'type' => 'lookup'
		, 'title' => 'Office'
		//lookup field specific parameters
		, 'lookup_table' => 'offices'
		, 'key_field' => 'officeCode'
		, 'values_field' => 'addressLine1'
	)
	, array (
		'name' => 'email'
		, 'type' => 'input'
		, 'title' => 'E-mail'
	)
	, array (
		'name' => 'isContractor'
		, 'type' => 'check'
		, 'title' => 'Is contractor'
	)
	, array (
		'name' => 'jobTitle'
		, 'type' => 'input'
		, 'title' => 'Job title'
		, 'list_mode' => 0
	)
	, array (
		'name' => 'photoImage'
		, 'type' => 'file_upload'
		, 'title' => 'Photo'
		, 'list_mode' => 0
		//file upload field specific parameters
		, 'upload_path' => $_SERVER["DOCUMENT_ROOT"].'/images/photos/'
		, 'image_url' => '/images/photos/'
		, 'download_url' => '/images/photos/'
		, 'max_filesize_mb' => 5
		, 'allowed_ext' => 'jpg|jpeg|gif|png'
		, 'is_overwrite_existing' => 1
		, 'is_delete_option' => 1
		, 'is_delete_from_disk' => 1
		, 'file_size_field' => 'file_size'
		, 'is_size_in_human_readable_format' => 1
	)

	, array (
		'name' => 'managing'
		, 'type' => 'managing'
		, 'title' => 'Actions'
		, 'delete_link' => '/index.php?page=delete_employee&id=*'
		, 'edit_link' => '/index.php?page=edit_employee&id=*'
		, 'non_db' => 1
		, 'single_mode' => 0
	)
);
