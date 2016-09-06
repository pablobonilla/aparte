<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

/**
 * This class allows for simple but smart objects with get and set methods
 * and an internal error handler.
 */
class CustomObject {

    /**
     * Class constructor, overridden in descendant classes.
     *
     * @param   mixed  $properties  Either and associative array or another
     * 								object to set the initial properties of the object.
     */
    public function __construct($properties = null) {
        if ($properties !== null) {
            $this->setProperties($properties);
        }
    }

    /**
     * Sets a default value if not alreay assigned
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $default   The default value.
     * @return  mixed
     */
    public function def($property, $default = null) {
        $value = $this->get($property, $default);
        return $this->set($property, $value);
    }

    /**
     * Returns a property of the object or the default value if the property is not set.
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $default   The default value.
     * @return  mixed    The value of the property.
     */
    public function get($property, $default = null) {
        if (isset($this->$property)) {
            return $this->$property;
        }
        return $default;
    }

    /**
     * Returns an associative array of object properties.
     *
     * @param   boolean  $public  If true, returns only the public properties.
     * @return  array
     */
    public function getProperties($public = true) {
        $vars = get_object_vars($this);
        if ($public) {
            foreach ($vars as $key => $value) {
                if ('_' == substr($key, 0, 1)) {
                    unset($vars[$key]);
                }
            }
        }

        return $vars;
    }

    /**
     * Modifies a property of the object, creating it if it does not already exist.
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $value     The value of the property to set.
     * @return  mixed  Previous value of the property.
     */
    public function set($property, $value = null) {
        $previous = isset($this->$property) ? $this->$property : null;
        $this->$property = $value;
        return $previous;
    }

    /**
     * Set the object properties based on a named array/hash.
     *
     * @param   mixed  $properties  Either an associative array or another object.
     * @return  boolean
     */
    public function setProperties($properties) {
        if (is_array($properties) || is_object($properties)) {
            foreach ((array) $properties as $k => $v) {
                // Use the set function which might be overridden.
                $this->set($k, $v);
            }
            return true;
        }

        return false;
    }

}
