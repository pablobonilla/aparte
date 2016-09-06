<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

/**
 * Database connector class.
 */
abstract class Database {

    /**
     * The name of the database driver.
     *
     * @var    string
     */
    private $name;

    /**
     * The name of the database.
     *
     * @var    string
     */
    private $database;

    /**
     * @var    integer  The number of SQL statements executed by the database driver.
     */
    protected $count = 0;

    /**
     * @var    resource  The database connection resource.
     */
    protected $connection;

    /**
     * @var    resource  The database connection cursor from the last query.
     */
    protected $cursor;

    /**
     * @var    integer  The affected row limit for the current SQL statement.
     */
    protected $limit = 0;

    /**
     * The character(s) used to quote SQL statement names such as table names or field names.
     *
     * @var    string
     */
    protected $nameQuoted;

    /**
     * @var    integer  The affected row offset to apply for the current SQL statement.
     */
    protected $offset = 0;

    /**
     * @var    mixed  The current SQL statement to execute.
     */
    protected $sql;

    /**
     * The null or zero representation of a timestamp for the database driver.
     *
     * @var    string
     */
    protected $nullDate;

    /**
     * @var    boolean  True if the database engine supports UTF-8 character encoding.
     */
    protected $utf = true;

    /**
     * @var         integer  The database error number
     */
    protected $errorNum = 0;

    /**
     * @var         string  The database error message
     */
    protected $errorMsg;

    /**
     * @var		Database instance container.
     */
    protected static $instance = null;

    /**
     * @var    string  The minimum supported database version.
     */
    protected $dbMinimum;

    /**
     * Constructor.
     *
     * @param   array  $options  List of options used to configure the connection
     */
    protected function __construct($options) {
        // Initialise object variables.
        $this->database = (isset($options['database'])) ? $options['database'] : '';
        $this->count = 0;
        $this->errorNum = 0;

        // Set charactersets (needed for MySQL 4.1.2+).
        $this->setUTF();
    }

    /**
     * Method to return a Database instance based on the given options.  One global option and then
     * the rest are specific to the database driver.  The 'driver' option defines which DatabaseProvider class is
     * used for the connection
     *
     * Instances are unique to the given options and new objects are only created when a unique options array is
     * passed into the method.  This ensures that we don't end up with unnecessary database connection resources.
     *
     * @param   array  $options  Parameters to be passed to the database driver.
     * @return  Database  A database object.
     */
    public static function getInstance($options = array()) {
        try {
            if (!isset($options['driver']))
                throw new Exception('No driver is specified in the database settings');
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
        }

        $class = ucfirst($options['driver']) . 'Provider';
        $file = DB_PATH . '/driver/' . $options['driver'] . '.php';

        if (file_exists($file)) {
            require_once($file);

            if (class_exists($class)) {
                self::$instance = new $class($options);
            }
        }

        return self::$instance;
    }

    /**
     * Method that provides access to the underlying database connection.
     *
     * @return  resource  The underlying database connection resource.
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Gets the name of the database used by this connection.
     *
     * @return  string
     */
    protected function getDatabase() {
        return $this->database;
    }

    /**
     * Get the total number of SQL statements executed by the database driver.
     *
     * @return  integer
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Get the null or zero representation of a timestamp for the database driver.
     *
     * @return  string  Null or zero representation of a timestamp.
     */
    public function getNullDate() {
        return $this->nullDate;
    }

    /**
     * Returns a PHP date() function compliant date format for the database driver.
     *
     * @return  string  The format string.
     */
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }

    /**
     * Determine whether or not the database engine supports UTF-8 character encoding.
     *
     * @return  boolean  True if the database engine supports UTF-8 character encoding.
     */
    public function getUTFSupport() {
        return $this->utf;
    }

    /**
     * Gets the error number from the database connection.
     *
     * @return      integer  The error number for the most recent query.
     */
    public function getErrorNum() {
        $this->errorNum;
    }

    /**
     * Gets the error message from the database connection.
     *
     * @param   boolean  $escaped  True to escape the message string for use in JavaScript.
     * @return  string  The error message for the most recent query.
     */
    public function getErrorMsg($escaped = false) {
        if ($escaped) {
            return addslashes($this->errorMsg);
        } else {
            return $this->errorMsg;
        }
    }

    /**
     * Sets the SQL statement string for later execution.
     *
     * @param   mixed    $query   The SQL statement to set either as a JDatabaseQuery object or a string.
     * @param   integer  $offset  The affected row offset to set.
     * @param   integer  $limit   The maximum affected rows to set.
     *
     * @return  Database  This object to support method chaining.
     */
    public function setQuery($query, $offset = 0, $limit = 0) {
        $this->sql = $query;
        $this->limit = (int) max(0, $limit);
        $this->offset = (int) max(0, $offset);

        return $this;
    }

    /**
     * Execute the SQL statement.
     *
     * @return  mixed  A database cursor resource on success, boolean false on failure.
     */
    public function query() {
        return $this->execute();
    }

    /**
     * Method to get the first row of the result set from the database query as an associative array
     * of ['field_name' => 'row_value'].
     *
     * @return  mixed  The return value or null if the query failed.
     */
    public function queryAssoc() {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an associative array.
        if ($array = $this->fetchAssoc($cursor)) {
            $ret = $array;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    /**
     * Method to get an array of the result set rows from the database query where each row is an associative array
     * of ['field_name' => 'row_value'].  The array of rows can optionally be keyed by a field name, but defaults to
     * a sequential numeric array.
     *
     * NOTE: Chosing to key the result array by a non-unique field name can result in unwanted
     * behavior and should be avoided.
     *
     * @param   string  $key     The name of a field on which to key the result array.
     * @param   string  $column  An optional column name. Instead of the whole row, only this column value will be in
     * the result array.
     * @return  mixed   The return value or null if the query failed.
     */
    public function queryAssocList($key = null, $column = null) {
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set.
        while ($row = $this->fetchAssoc($cursor)) {
            $value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
            if ($key) {
                $array[$row[$key]] = $value;
            } else {
                $array[] = $value;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    /**
     * Method to get the first row of the result set from the database query as an object.
     *
     * @param   string  $class  The class name to use for the returned row object.
     * @return  mixed   The return value or null if the query failed.
     */
    public function queryObject($class = 'stdClass') {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an object of type $class.
        if ($object = $this->fetchObject($cursor, $class)) {
            $ret = $object;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    /**
     * Method to get an array of the result set rows from the database query where each row is an object.  The array
     * of objects can optionally be keyed by a field name, but defaults to a sequential numeric array.
     *
     * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
     * behavior and should be avoided.
     *
     * @param   string  $key    The name of a field on which to key the result array.
     * @param   string  $class  The class name to use for the returned row objects.
     * @return  mixed   The return value or null if the query failed.
     */
    public function queryObjectList($key = '', $class = 'stdClass') {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as objects of type $class.
        while ($row = $this->fetchObject($cursor, $class)) {
            if ($key) {
                $array[$row->$key] = $row;
            } else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    /**
     * Method to get the first row of the result set from the database query as an array.  Columns are indexed
     * numerically so the first column in the result set would be accessible via <var>$row[0]</var>, etc.
     *
     * @return  mixed  The return value or null if the query failed.
     */
    public function queryRow() {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an array.
        if ($row = $this->fetchArray($cursor)) {
            $ret = $row;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    /**
     * Method to get an array of the result set rows from the database query where each row is an array.  The array
     * of objects can optionally be keyed by a field offset, but defaults to a sequential numeric array.
     *
     * NOTE: Choosing to key the result array by a non-unique field can result in unwanted
     * behavior and should be avoided.
     *
     * @param   string  $key  The name of a field on which to key the result array.
     * @return  mixed   The return value or null if the query failed.
     */
    public function queryRowList($key = null) {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray($cursor)) {
            if ($key !== null) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    /**
     * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
     * the database query.
     *
     * @param   integer  $offset  The row offset to use to build the result array.
     * @return  mixed    The return value or null if the query failed.
     */
    public function queryColumn($offset = 0) {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray($cursor)) {
            $array[] = $row[$offset];
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    /**
     * Inserts a row into a table based on an object's properties.
     *
     * @param   string  $table    The name of the database table to insert into.
     * @param   object  &$object  A reference to an object whose public properties match the table fields.
     * @param   string  $key      The name of the primary key. If provided the object property is updated.
     *
     * @return  boolean    True on success.
     */
    public function insertObject($table, &$object, $key = null) {
        // Initialise variables.
        $fields = array();
        $values = array();

        // Create the base insert statement.
        $statement = 'INSERT INTO ' . $this->quoteName($table) . ' (%s) VALUES (%s)';

        // Iterate over the object variables to build the query fields and values.
        foreach (get_object_vars($object) as $k => $v) {
            // Only process non-null scalars.
            if (is_array($v) or is_object($v) or $v === null) {
                continue;
            }

            // Ignore any internal fields.
            if ($k[0] == '_') {
                continue;
            }

            // Prepare and sanitize the fields and values for the database query.
            $fields[] = $this->quoteName($k);

            if (strtolower($v) == 'now()') {
                $values[] = $v;
            } else {
                $values[] = $this->quote($v);
            }
        }

        // Set the query and execute the insert.
        $this->setQuery(sprintf($statement, implode(',', $fields), implode(',', $values)));

        try {
            if (!$this->execute()) {
                return 0;
            }
        } catch (Exception $e) {
            return 0;
        }

        // Update the primary key if it exists.
        $id = $this->insertid();
        if ($key && $id) {
            $object->$key = $id;
        }

        return $id;
    }

    /**
     * Updates a row in a table based on an object's properties.
     *
     * @param   string   $table    The name of the database table to update.
     * @param   object   &$object  A reference to an object whose public properties match the table fields.
     * @param   string   $key      The name of the primary key.
     * @param   boolean  $nulls    True to update null fields or false to ignore them.
     *
     * @return  boolean  True on success.
     */
    public function updateObject($table, &$object, $key, $nulls = false) {
        // Initialise variables.
        $fields = array();
        $where = '';

        // Create the base update statement.
        $statement = 'UPDATE ' . $this->quoteName($table) . ' SET %s WHERE %s';

        // Iterate over the object variables to build the query fields/value pairs.
        foreach (get_object_vars($object) as $k => $v) {
            // Only process scalars that are not internal fields.
            if (is_array($v) or is_object($v) or $k[0] == '_') {
                continue;
            }

            // Set the primary key to the WHERE clause instead of a field to update.
            if ($k == $key) {
                $where = $this->quoteName($k) . '=' . $this->quote($v);
                continue;
            }

            // Prepare and sanitize the fields and values for the database query.
            if ($v === null) {
                // If the value is null and we want to update nulls then set it.
                if ($nulls) {
                    $val = 'NULL';
                }
                // If the value is null and we do not want to update nulls then ignore this field.
                else {
                    continue;
                }
            }
            // The field is not null so we prep it for update.
            else {
                if (strtolower($v) == 'now()') {
                    $val = $v;
                } else {
                    $val = $this->quote($v);
                }
            }

            // Add the field to be updated.
            $fields[] = $this->quoteName($k) . '=' . $val;
        }

        // We don't have any fields to update.
        if (empty($fields)) {
            return false;
        }

        // Set the query and execute the update.
        $this->setQuery(sprintf($statement, implode(",", $fields), $where));
        $this->execute();

        if ($this->getAffectedRows()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a row in a table based on an object's properties.
     *
     * @param   string   $table    The name of the database table to update.
     * @param   object   &$object  A reference to an object whose public properties match the table fields.
     * @param   string   $key      The name of the primary key.
     * @param   boolean  $nulls    True to update null fields or false to ignore them.
     *
     * @return  boolean  True on success.
     */
    public function deleteObject($table, &$object) {
        // Initialise variables.
        $fields = array();
        $where = '';

        // Create the base delete statement.
        $statement = 'DELETE FROM ' . $this->quoteName($table) . ' WHERE %s';

        // Iterate over the object variables to build the query fields/value pairs.
        foreach (get_object_vars($object) as $k => $v) {
            // Only process scalars that are not internal fields.
            if (is_array($v) or is_object($v) or $k[0] == '_') {
                continue;
            }

            // Prepare and sanitize the fields and values for the database query.
            if ($v !== null) {
                $val = $this->quote($v);
            }

            // Add the field to be deleted.
            $fields[] = $this->quoteName($k) . '=' . $val;
        }

        // We don't have any row to delete.
        if (empty($fields)) {
            return false;
        }

        // Set the query and execute the update.
        $this->setQuery(sprintf($statement, implode(" AND ", $fields), $where));
        $this->execute();

        if ($this->getAffectedRows()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method to quote and optionally escape a string to database requirements for insertion into the database.
     *
     * @param   string   $text    The string to quote.
     * @param   boolean  $escape  True (default) to escape the string, false to leave it unchanged.
     * @return  string  The quoted input string.
     */
    public function quote($text, $escape = true) {
        return '\'' . ($escape ? $this->escape($text) : $text) . '\'';
    }

    /**
     * Wrap an SQL statement identifier name such as column, table or database names in quotes to prevent injection
     * risks and reserved word conflicts.
     *
     * @param   mixed  $name  The identifier name to wrap in quotes, or an array of identifier names to wrap in quotes.
     * 							Each type supports dot-notation name.
     * @param   mixed  $as    The AS query part associated to $name. It can be string or array, in latter case it has to be
     * 							same length of $name; if is null there will not be any AS part for string or array element.
     * @return  mixed  The quote wrapped name, same type of $name.
     */
    public function quoteName($name, $as = null) {
        if (is_string($name)) {
            $quotedName = $this->quoteNameStr(explode('.', $name));

            $quotedAs = '';
            if (!is_null($as)) {
                settype($as, 'array');
                $quotedAs .= ' AS ' . $this->quoteNameStr($as);
            }

            return $quotedName . $quotedAs;
        } else {
            $fin = array();

            if (is_null($as)) {
                foreach ($name as $str) {
                    $fin[] = $this->quoteName($str);
                }
            } elseif (is_array($name) && (count($name) == count($as))) {
                for ($i = 0; $i < count($name); $i++) {
                    $fin[] = $this->quoteName($name[$i], $as[$i]);
                }
            }

            return $fin;
        }
    }

    /**
     * Quote strings coming from quoteName call.
     *
     * @param   array  $strArr  Array of strings coming from quoteName dot-explosion.
     * @return  string  Dot-imploded string of quoted parts.
     */
    protected function quoteNameStr($strArr) {
        $parts = array();
        $q = $this->nameQuote;

        foreach ($strArr as $part) {
            if (is_null($part)) {
                continue;
            }

            if (strlen($q) == 1) {
                $parts[] = $q . $part . $q;
            } else {
                $parts[] = $q{0} . $part . $q{1};
            }
        }

        return implode('.', $parts);
    }

    /**
     * Method to check whether the installed database version is supported by the database driver
     *
     * @return  boolean  True if the database version is supported
     */
    public function isMinimumVersion() {
        return version_compare($this->getVersion(), $this->dbMinimum) >= 0;
    }

    /**
     * Get the minimum supported database version.
     *
     * @return  string  The minimum version number for the database driver.
     */
    public function getMinimum() {
        return $this->dbMinimum;
    }

    /**
     * Method to truncate a table.
     *
     * @param   string  $table  The table to truncate
     * @return  void
     */
    public function truncateTable($table) {
        $this->setQuery('TRUNCATE TABLE ' . $this->quoteName($table));
        $this->execute();
    }

    /**
     * Determines if the connection to the server is active.
     *
     * @return  boolean  True if connected to the database engine.
     */
    public abstract function connected();

    /**
     * Method to escape a string for usage in an SQL statement.
     *
     * @param   string   $text   The string to be escaped.
     * @param   boolean  $extra  Optional parameter to provide extra escaping.
     * @return  string  The escaped string.
     */
    public abstract function escape($text, $extra = false);

    /**
     * Method to fetch a row from the result set cursor as an array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected abstract function fetchArray($cursor = null);

    /**
     * Method to fetch a row from the result set cursor as an associative array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.	 
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected abstract function fetchAssoc($cursor = null);

    /**
     * Method to fetch a row from the result set cursor as an object.
     *
     * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
     * @param   string  $class   The class name to use for the returned row object.
     * @return  mixed   Either the next row from the result set or false if there are no more rows.
     */
    protected abstract function fetchObject($cursor = null, $class = 'stdClass');

    /**
     * Method to free up the memory used for the result set.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  void
     */
    protected abstract function freeResult($cursor = null);

    /**
     * Get the number of returned rows for the previous executed SQL statement.
     *
     * @param   resource  $cursor  An optional database cursor resource to extract the row count from.	
     * @return  integer   The number of returned rows.
     */
    public abstract function getNumRows($cursor = null);

    /**
     * Get the number of affected rows for the previous executed SQL statement.
     *
     * @return  integer  The number of affected rows.
     */
    public abstract function getAffectedRows();

    /**
     * Drops a table from the database.
     *
     * @param   string   $table     The name of the database table to drop.
     * @param   boolean  $ifExists  Optionally specify that the table must exist before it is dropped.	 
     * @return  Database  Returns this object to support chaining.
     */
    public abstract function dropTable($table, $ifExists = true);

    /**
     * Method to get the database collation in use by sampling a text field of a table in the database.
     *
     * @return  mixed  The collation in use by the database or boolean false if not supported.
     */
    public abstract function getCollation();

    /**
     * Retrieves field information about the given tables.
     *
     * @param   string   $table     The name of the database table.
     * @param   boolean  $typeOnly  True (default) to only return field types.
     *
     * @return  array  An array of fields by table.
     */
    public abstract function getTableColumns($table, $typeOnly = true);

    /**
     * Shows the table CREATE statement that creates the given tables.
     *
     * @param   mixed  $tables  A table name or a list of table names.
     * @return  array  A list of the create SQL for the tables.
     */
    public abstract function getTableCreate($tables);

    /**
     * Retrieves field information about the given tables.
     *
     * @param   mixed  $tables  A table name or a list of table names.
     * @return  array  An array of keys for the table(s).
     */
    public abstract function getTableKeys($tables);

    /**
     * Method to get an array of all tables in the database.
     *
     * @return  array  An array of all the tables in the database.
     */
    public abstract function getTableList();

    /**
     * Get the version of the database connector
     *
     * @return  string  The database connector version.
     */
    public abstract function getVersion();

    /**
     * Renames a table in the database.
     *
     * @param   string  $oldTable  The name of the table to be renamed
     * @param   string  $newTable  The new name for the table.
     * @param   string  $backup    Table prefix
     * @param   string  $prefix    For the table - used to rename constraints in non-mysql databases
     * @return  JDatabase  Returns this object to support chaining.
     */
    public abstract function renameTable($oldTable, $newTable, $backup = null, $prefix = null);

    /**
     * Get the current query
     *
     * @param   boolean  $new  False to return the current query object, True to return a new DatabaseQuery object.
     * @return  mixed  The current value of the internal SQL variable or a new JDatabaseQuery object.
     */
    public abstract function getQuery($new = false);

    /**
     * Method to get the auto-incremented value from the last INSERT statement.
     *
     * @return  integer  The value of the auto-increment field from the last inserted row.
     */
    public abstract function insertid();

    /**
     * Locks a table in the database.
     *
     * @param   string  $table  The name of the table to unlock.
     * @return  MysqlProvider  Returns this object to support chaining.
     */
    public abstract function lockTable($tableName);

    /**
     * Execute the SQL statement.
     *
     * @return  mixed  A database cursor resource on success, boolean false on failure.
     * @throws  DatabaseException
     */
    public abstract function execute();

    /**
     * Select a database for use.
     *
     * @param   string  $database  The name of the database to select for use.
     * @return  boolean  True if the database was successfully selected.
     * @throws  DatabaseException
     */
    public abstract function select($database);

    /**
     * Set the connection to use UTF-8 character encoding.
     *
     * @return  boolean  True on success.
     */
    public abstract function setUTF();

    /**
     * Method to commit a transaction.
     *
     * @return  void
     */
    public abstract function transactionCommit();

    /**
     * Method to roll back a transaction.
     *
     * @return  void
     */
    public abstract function transactionRollback();

    /**
     * Method to initialize a transaction.
     *
     * @return  void
     */
    public abstract function transactionStart();

    /**
     * Unlocks tables in the database.
     *
     * @return  MysqlProvider  Returns this object to support chaining.
     */
    public abstract function unlockTables();
}

?>