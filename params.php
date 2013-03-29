<?php

$config = CoreConfig::getInstance();

define('DM_DB_HOST', $config->get('dbhost'));
define('DM_DB_NAME', $config->get('dbname'));
define('DM_DB_USER', $config->get('dbuser'));
define('DM_DB_PASS', $config->get('dbpass'));

define('DM_BASE_URL', base_url());
