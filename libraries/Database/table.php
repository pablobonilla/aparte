<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

/**
 * Parent class to all tables.
 */
abstract class Table extends CustomObject {

    /**
     * Name of the database table to model.
     *
     * @var    string
     */
    protected $_tbl = '';

    /**
     * Name of the primary key field in the table.
     *
     * @var    string
     */
    protected $_tbl_key = '';

    /**
     * JDatabase connector object.
     *
     * @var    JDatabase
     */
    protected $_db;

    /**
     * Indicator that the tables have been locked.
     *
     * @var    boolean
     */
    protected $_locked = false;

    /**
     * Object constructor to set table and key fields.  In most cases this will
     * be overridden by child classes to explicitly set the table and key fields
     * for a particular database table.
     *
     * @param   string     $table  Name of the table to model.
     * @param   string     $key    Name of the primary key field in the table.
     * @param   Database  &$db    Database connector object.
     */
    public function __construct($table, $key, &$db) {
        // Set internal variables.
        $this->_tbl = $table;
        $this->_tbl_key = $key;
        $this->_db = &$db;

        // Initialise the table properties.
        if ($fields = $this->getFields()) {
            foreach ($fields as $name => $v) {
                // Add the field if it is not already present.
                if (!property_exists($this, $name)) {
                    $this->$name = null;
                }
            }
        }
    }

    /**
     * Get the columns from database table.
     *
     * @return  mixed  An array of the field names, or false if an error occurs.
     */
    public function getFields() {
        static $cache = null;

        if ($cache === null) {
            // Lookup the fields for this table only once.
            $name = $this->_tbl;
            $fields = $this->_db->getTableColumns($name, false);

            if (empty($fields)) {
                throw new DatabaseException('No columns have found');
                return false;
            }
            $cache = $fields;
        }

        return $cache;
    }

    /**
     * Static method to get an instance of a Table class.
     *
     * @param   string  $type    The type (name) of the Table class to get an instance of.
     * @param   string  $prefix  An optional prefix for the table class name.
     * @param   array   $config  An optional array of configuration values for the Table object.
     * @return  mixed    A Table object if found or boolean false if one could not be found.
     */
    public static function getInstance($type, $prefix = 'Table', $config = array()) {
        // Sanitize and prepare the table class name.
        $type = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
        $tableClass = $prefix . ucfirst($type);

        // Only try to load the class if it doesn't already exist.
        if (!class_exists($tableClass)) {
            throw new DatabaseException('Unable to find the class ' . $tableClass);
            return false;
        }

        // If a database object was passed in the configuration array use it, otherwise get the global one from DatabaseFactory.
        $db = isset($config['dbo']) ? $config['dbo'] : DatabaseFactory::getInstance();

        // Instantiate a new table class and return it.
        return new $tableClass($db);
    }

    /**
     * Method to get the database table name for the class.
     *
     * @return  string  The name of the database table being modeled.
     */
    public function getTableName() {
        return $this->_tbl;
    }

    /**
     * Method to get the primary key field name for the table.
     *
     * @return  string  The name of the primary key for the table.
     */
    public function getKeyName() {
        return $this->_tbl_key;
    }

    /**
     * Method to get the Database connector object.
     *
     * @return  Database  The internal database connector object.
     */
    public function getDbo() {
        return $this->_db;
    }

    /**
     * Method to set the Database connector object.
     *
     * @param   object  &$db  A Database connector object to be used by the table object.
     * @return  boolean  True on success.
     */
    public function setDBO(&$db) {
        // Make sure the new database object is a Database.
        if (!($db instanceof Database)) {
            return false;
        }

        $this->_db = &$db;

        return true;
    }

    /**
     * Method to reset class properties to the defaults set in the class
     * definition. It will ignore the primary key as well as any private class
     * properties.
     *
     * @return  void
     */
    public function reset() {
        // Get the default values for the class from the table.
        foreach ($this->getFields() as $k => $v) {
            // If the property is not the primary key or private, reset it.
            if ($k != $this->_tbl_key && (strpos($k, '_') !== 0)) {
                $this->$k = $v->Default;
            }
        }
    }

    /**
     * Method to bind an associative array or object to the Table instance. This
     * method only binds properties that are publicly accessible and optionally
     * takes an array of properties to ignore when binding.
     *
     * @param   mixed  $src     An associative array or object to bind to the Table instance.
     * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
     *
     * @return  boolean  True on success.
     */
    public function bind($src, $ignore = array()) {
        // If the source value is not an array or object return false.
        if (!is_object($src) && !is_array($src)) {
            throw new DatabaseException('Binding failed, invalid arguments.');
            return false;
        }

        // If the source value is an object, get its accessible properties.
        if (is_object($src)) {
            $src = get_object_vars($src);
        }

        // If the ignore value is a string, explode it over spaces.
        if (!is_array($ignore)) {
            $ignore = explode(' ', $ignore);
        }

        // Bind the source value, excluding the ignored fields.
        foreach ($this->getProperties() as $k => $v) {
            // Only process fields not in the ignore array.
            if (!in_array($k, $ignore)) {
                if (isset($src[$k])) {
                    $this->$k = $src[$k];
                }
            }
        }

        return true;
    }

    /**
     * Method to load a row from the database by primary key and bind the fields
     * to the Table instance properties.
     *
     * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
     * set the instance property value is used.
     * @param   boolean  $reset  True to reset the default values before loading the new row.
     * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
     */
    public function load($keys = null, $reset = true) {
        if (empty($keys)) {
            // If empty, use the value of the current key
            $keyName = $this->_tbl_key;
            $keyValue = $this->$keyName;

            // If empty primary key there's is no need to load anything
            if (empty($keyValue)) {
                return true;
            }

            $keys = array($keyName => $keyValue);
        } elseif (!is_array($keys)) {
            // Load by primary key.
            $keys = array($this->_tbl_key => $keys);
        }

        if ($reset) {
            $this->reset();
        }

        // Initialise the query.
        $query = $this->_db->getQuery(true);
        $query->select('*');
        $query->from($this->_tbl);
        $fields = array_keys($this->getProperties());

        foreach ($keys as $field => $value) {
            // Check that $field is in the table.
            if (!in_array($field, $fields)) {
                throw new DatabaseException('Unable to find the field ' . $field . ' in the table ' . $this->_tbl);
                return false;
            }
            // Add the search tuple to the query.
            $query->where($this->_db->quoteName($field) . ' = ' . $this->_db->quote($value));
        }

        $this->_db->setQuery($query);

        try {
            $row = $this->_db->queryAssoc();
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
            return false;
        }

        // Check that we have a result.
        if (empty($row)) {
            throw new DatabaseException('Empty row is returned.');
            return false;
        }

        // Bind the object with the row and return.
        return $this->bind($row);
    }

    /**
     * Method to perform sanity checks on the Table instance properties to ensure
     * they are safe to store in the database.  Child classes should override this
     * method to make sure the data they are storing in the database is safe and
     * as expected before storage.
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database.
     */
    public function check() {
        return true;
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     * If a primary key value is set the row with that primary key value will be
     * updated with the instance property values.  If no primary key value is set
     * a new row will be inserted into the database with the properties from the
     * Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null
     * @return  boolean  True on success.
     */
    public function store($updateNulls = false) {
        // Initialise variables.
        $k = $this->_tbl_key;

        // If a primary key exists update the object, otherwise insert it.
        if ($this->$k) {
            $stored = $this->_db->updateObject($this->_tbl, $this, $this->_tbl_key, $updateNulls);
        } else {
            $stored = $this->_db->insertObject($this->_tbl, $this, $this->_tbl_key);
        }

        // If the store failed return false.
        if (!$stored) {
            throw new DatabaseException(get_class($this) . ' failed to store or update an object.');
            return false;
        }

        if ($this->_locked) {
            $this->_unlock();
        }

        return true;
    }

    /**
     * Method to provide a shortcut to binding, checking and storing a Table
     * instance to the database table.  The method will check a row in once the
     * data has been stored and if an ordering filter is present will attempt to
     * reorder the table rows based on the filter.  The ordering filter is an instance
     * property name.  The rows that will be reordered are those whose value matches
     * the Table instance for the property specified.
     *
     * @param   mixed   $src             An associative array or object to bind to the Table instance.
     * @param   string  $orderingFilter  Filter for the order updating
     * @param   mixed   $ignore          An optional array or space separated list of properties
     * 									 to ignore while binding.
     * @return  boolean  True on success.	 
     */
    public function save($src, $orderingFilter = '', $ignore = '') {
        // Attempt to bind the source to the instance.
        if (!$this->bind($src, $ignore)) {
            return false;
        }

        // Run any sanity checks on the instance and verify that it is ready for storage.
        if (!$this->check()) {
            return false;
        }

        // Attempt to store the properties to the database table.
        if (!$this->store()) {
            return false;
        }

        // If an ordering filter is set, attempt reorder the rows in the table based on the filter and value.
        if ($orderingFilter) {
            $filterValue = $this->$orderingFilter;
            $this->reorder($orderingFilter ? $this->_db->quoteName($orderingFilter) . ' = ' . $this->_db->Quote($filterValue) : '');
        }

        // Set the error to empty and return true.
        $this->setError('');

        return true;
    }

    /**
     * Method to delete a row from the database table by primary key value.
     *
     * @param   mixed  $pk  An optional primary key value to delete.  If not set the instance property value is used.
     * @return  boolean  True on success.
     */
    public function delete($pk = null) {
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            throw new DatabaseException('No primary key is given');
            return false;
        }

        // Delete the row by primary key.
        $query = $this->_db->getQuery(true);
        $query->delete();
        $query->from($this->_tbl);
        $query->where($this->_tbl_key . ' = ' . $this->_db->quote($pk));
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()) {
            throw new DatabaseException(get_class($this) . ' returned error on delete');
            return false;
        }

        return true;
    }

    /**
     * Method to lock the database table for writing.
     *
     * @return  boolean  True on success.
     */
    protected function _lock() {
        $this->_db->lockTable($this->_tbl);
        $this->_locked = true;

        return true;
    }

    /**
     * Method to unlock the database table for writing.
     *
     * @return  boolean  True on success.
     */
    protected function _unlock() {
        $this->_db->unlockTables();
        $this->_locked = false;

        return true;
    }

}

?>