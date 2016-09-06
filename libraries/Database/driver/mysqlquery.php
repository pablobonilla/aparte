<?php

/* Blocking direct access to the script */
defined('DB_ACCESS_CONTROL') or die;

require_once(DB_PATH . '/query.php');

/**
 * Query Building Class.
 */
class DatabaseQueryMysql extends DatabaseQuery {

    /**
     * Concatenates an array of column names or values.
     *
     * @param   array   $values     An array of values to concatenate.
     * @param   string  $separator  As separator to place between each value.
     * @return  string  The concatenated values.
     */
    public function concatenate($values, $separator = null) {
        if ($separator) {
            $concat_string = 'CONCAT_WS(' . $this->quote($separator);

            foreach ($values as $value) {
                $concat_string .= ', ' . $value;
            }

            return $concat_string . ')';
        } else {
            return 'CONCAT(' . implode(',', $values) . ')';
        }
    }

}

?>