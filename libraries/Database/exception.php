<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

/**
 * Database Exception class
 */
class DatabaseException extends Exception {

    /**
     * Constructor.
     *
     * @param   string  $message  The message generated by the exception
     * @param   int  	$code  The code of the exception
     * @param   Exception  $previous  The previous exception
     */
    public function __construct($message = '', $code = 0, Exception $previous = null) {
        $code = (int) $code;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a the message string
     *
     * @return  string  The message string.
     */
    public function __toString() {
        return parent::__toString();
    }

}

?>