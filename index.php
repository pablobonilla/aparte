<?php

/**
 * Initialize session if it's not yet initialize
 */
if (session_id() == '') {
    session_start();
}

/**
 * Setup error messages
 * THESE SHOULD BE TURN OFF ON PRODUCTION ENVIRONMENT
 */
error_reporting(1);
ini_set('display_errors', 1);

/**
 * Set aplication default timezone
 */
date_default_timezone_set("America/Santo_Domingo");

/**
 * Include required files
 */
 
require_once 'defines.php';
require_once 'libraries/Database/config.inc.php';
require_once 'libraries/Database/factory.php';
require_once 'libraries/MVC/Controller.php';
require_once 'libraries/MVC/Model.php';

require_once 'libraries/MVC/View.php';

require_once 'libraries/Request.php';
require_once 'libraries/FileHelper.php';
require_once 'libraries/Bootstrap.php';
 
require_once 'libraries/Response.php';

require_once 'libraries/JsonResponse.php';

/**
 * Initialize the application
 */
try {
    //Run the application and execute the request
    Bootstrap::run(new Request());
	
} catch (Exception $ex) {
    die($ex->getMessage());
}
