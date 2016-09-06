<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die('Direct access not allowed');

/* Define the database system path */
define('DB_PATH', dirname(__FILE__));

/* Include required scripts */
require_once(DB_PATH . '/factory.php');
require_once(DB_PATH . '/exception.php');
//require_once(DB_PATH . '/base/object.php');

/**
 * Database Configuration class
 */
class DatabaseConfig {
    /* Database settings */

    public $driver = 'mysqli';
    public $database = 'mydoctor';
    public $host = 'localhost';
    public $user = 'root';
    public $password = 'bonilla11';

}
