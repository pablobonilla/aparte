<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

require_once('mysql.php');
require_once('mysqliquery.php');

/**
 * MySQLi database driver
 */
class MysqliProvider extends MysqlProvider {

    /**
     * The name of the database driver.
     *
     * @var    string
     */
    public $name = 'mysqli';

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
        $options['port'] = null;
        $options['socket'] = null;

        /*
         * Unlike mysql_connect(), mysqli_connect() takes the port and socket as separate arguments. Therefore, we
         * have to extract them from the host string.
         */
        $tmp = substr(strstr($options['host'], ':'), 1);
        if (!empty($tmp)) {
            // Get the port number or socket name
            if (is_numeric($tmp)) {
                $options['port'] = $tmp;
            } else {
                $options['socket'] = $tmp;
            }

            // Extract the host name only
            $options['host'] = substr($options['host'], 0, strlen($options['host']) - (strlen($tmp) + 1));

            // This will take care of the following notation: ":3306"
            if ($options['host'] == '') {
                $options['host'] = 'localhost';
            }
        }

        try {
            // Make sure the MySQLi extension for PHP is installed and enabled.
            if (!function_exists('mysqli_connect')) {
                throw new DatabaseException('The function mysqli_connect() does not exist.');
            }

            $this->connection = @mysqli_connect($options['host'], $options['user'], $options['password'], null, $options['port'], $options['socket']);

            // Attempt to connect to the server.
            if (!$this->connection) {
                throw new DatabaseException('Attempt to connect to the database failed.');
            }
        } catch (Exception $ex) {
            echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
        }

        // Finalize initialisation
        Database::__construct($options);

        // Set sql_mode to non_strict mode
        mysqli_query($this->connection, "SET @@SESSION.sql_mode = '';");

        // If auto-select is enabled select the given database.
        if ($options['select'] && !empty($options['database'])) {
            $this->select($options['database']);
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if (is_callable($this->connection, 'close')) {
            mysqli_close($this->connection);
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
        $result = mysqli_real_escape_string($this->getConnection(), $text);

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
        if (is_object($this->connection)) {
            return mysqli_ping($this->connection);
        }

        return false;
    }

    /**
     * Get the number of affected rows for the previous executed SQL statement.
     *
     * @return  integer  The number of affected rows.
     */
    public function getAffectedRows() {
        return mysqli_affected_rows($this->connection);
    }

    /**
     * Get the number of returned rows for the previous executed SQL statement.
     *
     * @param   resource  $cursor  An optional database cursor resource to extract the row count from.	
     * @return  integer   The number of returned rows.
     */
    public function getNumRows($cursor = null) {
        return mysqli_num_rows($cursor ? $cursor : $this->cursor);
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
            if (!class_exists('DatabaseQueryMysqli')) {
                throw new DatabaseException('Database query building class is missing.');
            }
            return new DatabaseQueryMysqli($this);
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
        return mysqli_insert_id($this->connection);
    }

    /**
     * Execute the SQL statement.
     *
     * @return  mixed  A database cursor resource on success, boolean false on failure.
     * @throws  DatabaseException
     */
    public function execute() {
        try {
            if (!is_object($this->connection)) {
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
            $this->cursor = mysqli_query($this->connection, $sql);

            // If an error occurred handle it.
            if (!$this->cursor) {
                $this->errorNum = (int) mysqli_errno($this->connection);
                $this->errorMsg = (string) mysqli_error($this->connection) . ' SQL=' . $sql;

                throw new DatabaseException('Query to the database failed: ' . $sql);
            }

            return $this->cursor;
        } catch (Exception $ex) {
            throw new DatabaseException('Query to the database failed: ' . $sql);
            //echo $ex->getMessage() . '<br/>' . $ex->getFile() . ': ' . $ex->getLine();
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
            if (!mysqli_select_db($this->connection, $database)) {
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
        mysqli_query($this->connection, "SET NAMES 'utf8'");
    }

    /**
     * Method to fetch a row from the result set cursor as an array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchArray($cursor = null) {
        return mysqli_fetch_row($cursor ? $cursor : $this->cursor);
    }

    /**
     * Method to fetch a row from the result set cursor as an associative array.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.	 
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchAssoc($cursor = null) {
        return mysqli_fetch_assoc($cursor ? $cursor : $this->cursor);
    }

    /**
     * Method to fetch a row from the result set cursor as an object.
     *
     * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
     * @param   string  $class   The class name to use for the returned row object.
     * @return  mixed   Either the next row from the result set or false if there are no more rows.
     */
    protected function fetchObject($cursor = null, $class = 'stdClass') {
        return mysqli_fetch_object($cursor ? $cursor : $this->cursor, $class);
    }

    /**
     * Method to free up the memory used for the result set.
     *
     * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
     * @return  void
     */
    protected function freeResult($cursor = null) {
        mysqli_free_result($cursor ? $cursor : $this->cursor);
    }

    /**
     * Get the version of the database connector.
     *
     * @return  string  The database connector version.
     */
    public function getVersion() {
        return mysqli_get_server_info($this->connection);
    }

}

?>