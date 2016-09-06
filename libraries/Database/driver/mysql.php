<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

require_once('mysqlquery.php');

/**
 * MySQLi database driver
 */
class MysqlProvider extends Database {

    /**
     * The name of the database driver.
     *
     * @var    string
     */
    public $name = 'mysql';

    /**
     * The character(s) used to quote SQL statement names such as table names or field names.
     *
     * @var    string
     */
    protected $nameQuote = '`';

    /**
     * The null or zero representation of a timestamp for the database driver.
     *
     * @var    string
     */
    protected $nullDate = '0000-00-00 00:00:00';

    /**
     * @var    string  The minimum supported database version.
     */
    protected $dbMinimum = '5.0.4';

    /**
     * Constructor.
     *
     * @param   array  $options  List of options used to configure the connection
     */
    protected function __construct($options) {
        // Get some basic values from the options.
        $options['host'] = (isset($options['host'])) ? $options['host'] : 'localhost';
        $options['user'] = (isset($options['user'])) ? $options['user'] : 'root';
        $options['password'] = (isset($options['password'])) ? $options['password'] : '';
        $options['database'] = (isset($options['database'])) ? $options['database'] : '';
        $options['select'] = (isset($options['select'])) ? (bool) $options['select'] : true;

        try {
            // Make sure the MySQL extension for PHP is installed and enabled.
            if (!function_exists('mysql_connect')) {
                throw new DatabaseException('Function mysql_connect() does not exist.');
            }

            // Attempt to connect to the server.
            if (!($this->connection = @mysql_connect($options['host'], $options['user'], $options['password'], true))) {
                throw new DatabaseException('Attempt to connect to the database failed.');
            }
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
        }

        // Finalize initialisation
        parent::__construct($options);

        // Set sql_mode to non_strict mode
        mysql_query("SET @@SESSION.sql_mode = '';", $this->connection);

        // If auto-select is enabled select the given database.
        if ($options['select'] && !empty($options['database'])) {
            $this->select($options['database']);
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if (is_resource($this->connection)) {
            mysql_close($this->connection);
        }
    }

    /**
     * Method to escape a string for usage in an SQL statement.
     *
     * @param   string   $text   The string to be escaped.
     * @param   boolean  $extra  Optional parameter to provide extra escaping.
     * @return  string  The escaped string.
     */
    public function escape($text, $extra = false) {
        $result = mysql_real_escape_string($text, $this->getConnection());

        if ($extra) {
            $result = addcslashes($result, '%_');
        }

        return $result;
    }

    /**
     * Determines if the connection to the server is active.
     *
     * @return  boolean  True if connected to the database engine.
     */
    public function connected() {
        if (is_resource($this->connection)) {
            return mysql_ping($this->connection);
        }

        return false;
    }

    /**
     * Get the number of affected rows for the previous executed SQL statement.
     *
     * @return  integer  The number of affected rows.
     */
    public function getAffectedRows() {
        return mysql_affected_rows($this->connection);
    }

    /**
     * Get the number of returned rows for the previous executed SQL statement.
     *
     * @param   resource  $cursor  An optional database cursor resource to extract the row count from.	
     * @return  integer   The number of returned rows.
     */
    public function getNumRows($cursor = null) {
        return mysql_num_rows($cursor ? $cursor : $this->cursor);
    }

    /**
     * Get the current query
     *
     * @param   boolean  $new  False to return the current query object, True to return a new DatabaseQuery object.
     * @return  mixed  The current value of the internal SQL variable or a new JDatabaseQuery object.
     */
    public function getQuery($new = false) {
        if ($new) {
            // Make sure we have a query class for this driver.
            if (!class_exists('DatabaseQueryMysql')) {
                throw new DatabaseException('Database query building class is missing.');
            }
            return new DatabaseQueryMysql($this);
        } else {
            return $this->sql;
        }
    }

    /**
     * Method to get the auto-incremented value from the last INSERT statement.
     *
     * @return  integer  The value of the auto-increment field from the last inserted row.
     */
    public function insertid() {
        return mysql_insert_id($this->connection);
    }

    /**
     * Locks a table in the database.
     *
     * @param   string  $table  The name of the table to unlock.
     * @return  MysqlProvider  Returns this object to support chaining.
     */
    public function lockTable($table) {
        $this->setQuery('LOCK TABLES ' . $this->quoteName($table) . ' WRITE')->execute();

        return $this;
    }

    /**
     * Execute the SQL statement.
     *
     * @return  mixed  A database cursor resource on success, boolean false on failure.
     * @throws  DatabaseException
     */
    public function execute() {
        try {
            if (!is_resource($this->connection)) {
                throw new DatabaseException('The actual connection is not a valid resource.');
            }

            // Take a local copy so that we don't modify the original query and cause issues later
            $sql = $this->sql;
            if ($this->limit > 0 || $this->offset > 0) {
                $sql .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
            }

            // Increment the query counter
            $this->count++;

            // Reset the error values.
            $this->errorNum = 0;
            $this->errorMsg = '';

            // Execute the query.
            $this->cursor = mysql_query($sql, $this->connection);

            // If an error occurred handle it.
            if (!$this->cursor) {
                $this->errorNum = (int) mysql_errno($this->connection);
                $this->errorMsg = (string) mysql_error($this->connection) . ' SQL=' . $sql;

                throw new DatabaseException('Query to the database failed: ' . $sql);
            }

            return $this->cursor;
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
        }
    }

    /**
     * Select a database for use.
     *
     * @param   string  $database  The name of the database to select for use.
     * @return  boolean  True if the database was successfully selected.
     * @throws  DatabaseException
     */
    public function select($database) {
        if (!$database) {
            return false;
        }

        try {
            if (!mysql_select_db($database, $this->connection)) {
                throw new DatabaseException('Could not select database: ' . $database);
            }
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
        }

        return true;
    }

    /**
     * Set the connection to use UTF-8 character encoding.
     *
     * @return  boolean  True on success.
     */
    public function setUTF() {
        return mysql_query("SET NAMES 'utf8'", $this->connection);
    }

    /**
     * Method to commit a transaction.
     *
     * @return  void
     */
    public function transactionCommit() {
        $this->setQuery('COMMIT');
        $this->execute();
    }

    /**
     * Method to roll back a transaction.
     *
     * @return  void
     */
    public function transactionRollback() {
        $this->setQuery('ROLLBACK');
        $this->execute();
    }

    /**
     * Method to initialize a transaction.
     *
     * @return  void
     */
    public function transactionStart() {
        $this->setQuery('START TRANSACTION');
        $this->execute();
    }

    /**
     * Method to fetch a row from the result set cursor as an array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchArray($cursor = null) {
        return mysql_fetch_row($cursor ? $cursor : $this->cursor);
    }

    /**
     * Method to fetch a row from the result set cursor as an associative array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.	 
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchAssoc($cursor = null) {
        return mysql_fetch_assoc($cursor ? $cursor : $this->cursor);
    }

    /**
     * Method to fetch a row from the result set cursor as an object.
     *
     * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
     * @param   string  $class   The class name to use for the returned row object.
     * @return  mixed   Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchObject($cursor = null, $class = 'stdClass') {
        return mysql_fetch_object($cursor ? $cursor : $this->cursor, $class);
    }

    /**
     * Method to free up the memory used for the result set.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  void
     */
    protected function freeResult($cursor = null) {
        mysql_free_result($cursor ? $cursor : $this->cursor);
    }

    /**
     * Drops a table from the database.
     *
     * @param   string   $table     The name of the database table to drop.
     * @param   boolean  $ifExists  Optionally specify that the table must exist before it is dropped.	 
     * @return  Database  Returns this object to support chaining.
     */
    public function dropTable($table, $ifExists = true) {
        $query = $this->getQuery(true);
        $this->setQuery('DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $query->quoteName($tableName));
        $this->execute();

        return $this;
    }

    /**
     * Method to get the database collation in use by sampling a text field of a table in the database.
     *
     * @return  mixed  The collation in use by the database or boolean false if not supported.
     */
    public function getCollation() {
        $this->setQuery('SHOW FULL COLUMNS FROM users');
        $array = $this->loadAssocList();

        return $array['2']['Collation'];
    }

    /**
     * Retrieves field information about the given tables.
     *
     * @param   string   $table     The name of the database table.
     * @param   boolean  $typeOnly  True (default) to only return field types.
     *
     * @return  array  An array of fields by table.
     */
    public function getTableColumns($table, $typeOnly = true) {
        $result = array();

        // Set the query to get the table fields statement.
        $this->setQuery('SHOW FULL COLUMNS FROM ' . $this->quoteName($this->escape($table)));
        $fields = $this->queryObjectList();

        // If we only want the type as the value add just that to the list.
        if ($typeOnly) {
            foreach ($fields as $field) {
                $result[$field->Field] = preg_replace("/[(0-9)]/", '', $field->Type);
            }
        }
        // If we want the whole field data object add that to the list.
        else {
            foreach ($fields as $field) {
                $result[$field->Field] = $field;
            }
        }

        return $result;
    }

    /**
     * Shows the table CREATE statement that creates the given tables.
     *
     * @param   mixed  $tables  A table name or a list of table names.
     * @return  array  A list of the create SQL for the tables.
     */
    public function getTableCreate($tables) {
        // Initialise variables.
        $result = array();

        // Sanitize input to an array and iterate over the list.
        settype($tables, 'array');
        foreach ($tables as $table) {
            // Set the query to get the table CREATE statement.
            $this->setQuery('SHOW CREATE table ' . $this->quoteName($this->escape($table)));
            $row = $this->queryRow();

            // Populate the result array based on the create statements.
            $result[$table] = $row[1];
        }

        return $result;
    }

    /**
     * Retrieves field information about the given tables.
     *
     * @param   mixed  $tables  A table name or a list of table names.
     * @return  array  An array of keys for the table(s).
     */
    public function getTableKeys($tables) {
        // Get the details columns information.
        $this->setQuery('SHOW KEYS FROM ' . $this->quoteName($table));
        $keys = $this->loadObjectList();

        return $keys;
    }

    /**
     * Method to get an array of all tables in the database.
     *
     * @return  array  An array of all the tables in the database.
     */
    public function getTableList() {
        // Set the query to get the tables statement.
        $this->setQuery('SHOW TABLES');
        $tables = $this->queryColumn();

        return $tables;
    }

    /**
     * Get the version of the database connector
     *
     * @return  string  The database connector version.
     */
    public function getVersion() {
        return mysql_get_server_info($this->connection);
    }

    /**
     * Renames a table in the database.
     *
     * @param   string  $oldTable  The name of the table to be renamed
     * @param   string  $newTable  The new name for the table.
     * @param   string  $backup    Table prefix
     * @param   string  $prefix    For the table - used to rename constraints in non-mysql databases
     * @return  JDatabase  Returns this object to support chaining.
     */
    public function renameTable($oldTable, $newTable, $backup = null, $prefix = null) {
        $this->setQuery('RENAME TABLE ' . $oldTable . ' TO ' . $newTable)->execute();

        return $this;
    }

    /**
     * Unlocks tables in the database.
     *
     * @return  MysqlProvider  Returns this object to support chaining.
     */
    public function unlockTables() {
        $this->setQuery('UNLOCK TABLES')->execute();

        return $this;
    }

}

?>