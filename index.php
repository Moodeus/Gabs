<?php

/**
 * Site entry when nginx document root is the project folder (not public/).
 * Make CodeIgniter think the front controller lives in /public/.
 */
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['PHP_SELF']    = '/public/index.php';

require __DIR__ . '/app/bootstrap_uri.php';
require __DIR__ . '/public/index.php';
