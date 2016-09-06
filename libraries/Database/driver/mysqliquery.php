<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

require_once ('mysqlquery.php');

/**
 * Query Building Class.
 */
class DatabaseQueryMysqli extends DatabaseQueryMysql {
    
}

?>