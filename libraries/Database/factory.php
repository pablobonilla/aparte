<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

/**
 * Database Factory class
 */
abstract class DatabaseFactory {

    /**
     * @var    DatabaseConfig
     */
    public static $config = null;

    /**
     * @var    Database
     */
    public static $database = null;

    /**
     * Uninstantiable constructor
     */
    private function __construct() {
        
    }

    /**
     * Get a database object.
     *
     * @return  Database object
     */
    public static function getObject() {
        if (!self::$database) {
            self::$database = self::createObject();
        }

        return self::$database;
    }

    /**
     * Create an database object
     *
     * @return  Database object
     */
    public static function getInstance() {
        $conf = self::getConfig();

        $options = array(
            'driver' => $conf->driver,
            'database' => $conf->database,
            'host' => $conf->host,
            'user' => $conf->user,
            'password' => $conf->password
        );

        require_once(DB_PATH . '/database.php');

        $db = Database::getInstance($options);

        if ($db->getErrorNum() > 0) {
            die(sprintf('Database connection error (%d): %s', $db->getErrorNum(), $db->getErrorMsg()));
        }

        return $db;
    }

    /**
     * Get a configuration object
     *
     * @param   string  $file  The path to the configuration file
     * @return  DatabaseConfig
     */
    public static function getConfig($file = null) {
        if (!self::$config) {
            if ($file === null) {
                $file = DB_PATH . '/config.php';
            }

            self::$config = self::createConfig($file);
        }

        return self::$config;
    }

    /**
     * Create a configuration object
     *
     * @param   string  $file  The path to the configuration file
     * @return  DatabaseConfig
     */
    public static function createConfig($file = null) {
        if (is_file($file)) {
            require_once($file);
        }

        $class = 'DatabaseConfig';

        if (class_exists($class)) {
            $object = new $class;
        }

        return $object;
    }

}
