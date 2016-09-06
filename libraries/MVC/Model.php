<?php

/**
 * Model class
 */
abstract class Model {

    /**
     * Database driver
     * 
     * @var Database 
     */
    protected $db = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = DatabaseFactory::getInstance();
		
    }

}
