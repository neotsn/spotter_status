<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/18/2014
 * Time: 3:35 PM
 */

/**
 * Class DbPdo
 * a PDO wrapper for Mysql connections
 */
class DbPdo
{

    public $db = null;
    public $query = null;
    public $results = array();

    /**
     * Construct method
     *
     * @param string|null $db_type [sqlite, mysql] (for now)
     */
    public function __construct($db_type = null)
    {
        // Get the connection info from the .ini file
        global $connection_info;

        // Create the database connection with attributes
        switch ($db_type) {
            case 'sqlite':
                $this->db = new PDO('sqlite:' . $connection_info['db']['database'] . '.sqlite3');
                break;
            case 'mysql':
            default:
            $this->db = new PDO('mysql:host=' . $connection_info['db']['hostname'] . ';dbname=' . $connection_info['db']['database'] . ';charset=utf8',
                $connection_info['db']['username'], $connection_info['db']['password']);
                break;
        }

        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Performs the Query statement, substituting ? for values in $params
     *
     * @param string $statement  SQL DEFINE statement
     * @param array  $params     Array of values to translate into the string
     * @param int    $iterations Keep track of reinstantiation attempts, max 1 recursion
     *
     * @return array Array of 0 or More results
     */
    public function query($statement, $params = array(), $iterations = 0)
    {
        try {
            $this->query = $this->db->prepare($statement);
            $this->query->execute($params);
            $this->results = $this->query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            if (!$iterations) {
                $iterations++;
                $this->__construct();
                $this->results = $this->query($statement, $params, $iterations);
            } else {
                echo $e->getMessage();
                echo "\n" . $statement;
            }
        }

        return $this->results;
    }

    /**
     * Executes REPLACE statement on TABLE with Field-Value pairs
     * Was originally called ->update, but SQL UPDATE is handled differently than INSERT/REPLACE
     * So they were split and abstracted
     *
     * @param string $table    DB TABLE DEFINE
     * @param array  $fv_pairs Array of field => value pairs, only one dimensional
     *                         $fv_pairs = array('userid' => 123, 'name' => 'Billy');
     *
     * @return bool|int Last Inserted ID or false
     */
    public function insert($table, $fv_pairs)
    {
        return $this->_modifySingle(SQL_INSERT_GENERIC, $table, $fv_pairs);
    }

    /**
     * Executes REPLACE statement on TABLE with Field-Value pairs
     * Was originally called ->update, but SQL UPDATE is handled differently than INSERT/REPLACE
     * So they were split and abstracted
     *
     * @param string $table    DB TABLE DEFINE
     * @param array  $fv_pairs Array of field => value pairs, only one dimensional
     *                         $fv_pairs = array('userid' => 123, 'name' => 'Billy');
     *
     * @return bool|int Last Inserted ID or false
     */
    public function replace($table, $fv_pairs)
    {
        return $this->_modifySingle(SQL_REPLACE_GENERIC, $table, $fv_pairs);
    }

    /**
     * Executes an UPDATE statement on TABLE with field-value pairs and criteria-pairs
     *
     * @param string $table          DB TABLE DEFINE
     * @param array  $fv_pairs       Array of field => value pairs, only ONE dimensional
     *                               $updates = array('name' => 'Billy-Bob');
     * @param array  $criteria_pairs Array of [field, op, value] arrays, TWO dimensional
     *                               $criteria_pairs[] = array('field' => 'userid', 'op' => '=', 'value' => 123);
     * @param int    $iterations     Recursion counter
     *
     * @return bool|string
     */
    public function update($table, $fv_pairs, $criteria_pairs, $iterations = 0)
    {
        $sql = SQL_UPDATE_GENERIC;

        /*
        * Basically we need to run two variable variable construction loops
        * 1.) for the UPDATE pairing
        * 2.) for the CRITERIA pairing
        *
        * Iterate over the SET field-value pairs and build arrays for implosion and assignment
        * Then do the same for the CRITERIA field-op-value sets
        *
        * Then implode the update/criteria_str arrays into the statement with appropriate concatenation
        * Then iterate the *_value_str arrays to bindParam with variable variables for each field
        * Then assign the variable variables the value for that field from the original pairing array
        *
        * Execute in a loop, and commit the transaction.
        */
        $u_value_str = $update_str = $criteria_str = array();
        foreach ($fv_pairs as $f => $v) {
            $u_value_str[$f] = ':' . $f;
            $update_str[$f] = $f . '=:' . $f;
        }

        $c_value_str = $criteria_str = array();
        foreach ($criteria_pairs as $criteria) {
            $f = $criteria['field'];
            $o = $criteria['op'];

            $c_value_str[$f . '_c'] = ':' . $f . '_c';
            $criteria_str[$f . '_c'] = $f . $o . ':' . $f . '_c';
        }

        try {
            // Start the transaction
            $this->db->beginTransaction();

            // Prepare the SQL statement
            $sql = strtr($sql, array(
                '%t'   => $table,
                '%ufv' => implode(', ', array_unique($update_str)),
                '%cfv' => implode(' AND ', array_unique($criteria_str))
            ));

            $this->query = $this->db->prepare($sql);

            // loop through to bind the variable variable name for each field
            foreach ($u_value_str as $f => $v) {
                $this->query->bindParam($v, $$f); // Intentional variable variable
            }
            foreach ($c_value_str as $f => $v) {
                $this->query->bindParam($v, $$f); // Intentional variable variable
            }

            // loop through to assign the variable variable's value
            foreach ($fv_pairs as $f => $v) {
                $$f = $v; // set the variable variable
            }
            foreach ($criteria_pairs as $criteria) {
                $f = $criteria['field'] . '_c';
                $$f = $criteria['value']; // set the variable variable
            }
            $this->query->execute(); // execute the replace query

            // attempt to commit the transaction
            $this->db->commit();
            // return # rows affected
            $this->results = $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            if (!$iterations) {
                $iterations++;
                $this->db = null;
                $this->__construct();
                $this->results = $this->update($table, $fv_pairs, $criteria_pairs, $iterations);
            } else {
                $this->db->rollBack();
                echo $e->getMessage();
            }
        }

        return $this->results;
    }

    /**
     * Executes delete statement with field-value pairs criteria
     *
     * @param string $table      DB TABLE DEFINE
     * @param array  $fv_pairs   Array of field => value pairs, only one dimensional
     *                           $fv_pairs = array('userid' => 123, 'name' => 'Billy');
     * @param int    $iterations Recursion counter
     *
     * @return bool Success/Fail
     */
    public function delete($table, $fv_pairs, $iterations = 0)
    {

        $sql = SQL_DELETE_GENERIC;

        // Build the field assignment strings
        $field_str = $value_str = array();
        $criteria = array();
        foreach ($fv_pairs as $f => $v) {

            $criteria[$f] = $f . '=:' . $f;
            $field_str[$f] = $f;
            $value_str[$f] = ':' . $f;
        }

        try {
            // Start the transaction
            $this->db->beginTransaction();

            // Prepare the SQL statement
            $sql = strtr($sql, array(
                '%t' => $table,
                '%c' => implode(' AND ', array_unique($criteria))
            ));
            $this->query = $this->db->prepare($sql);

            // loop through to bind the variable variable name for each field
            foreach ($value_str as $f => $v) {
                $this->query->bindParam($v, $$f); // Intentional variable variable
            }
            // loop through to assign the variable variable's value, and execute
            foreach ($fv_pairs as $f => $v) {
                $$f = $v; // set the variable variable
            }
            $this->query->execute(); // execute the delete query

            // attempt to commit the transaction
            $this->results = $this->db->commit();
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            if (!$iterations) {
                $iterations++;
                $this->db = null;
                $this->__construct();
                $this->results = $this->delete($table, $fv_pairs, $iterations);
            } else {
                $this->db->rollBack();
                echo $e->getMessage();
            }
        }

        return $this->results;
    }

    /**
     * Drops a table from the database
     *
     * @param string $table_name Table name to drop
     *
     * @return array|bool
     */
    public function drop($table_name)
    {

        $sql = strtr(SQL_DROP_TABLE_GENERIC, array('%tn' => $table_name));

        try {
            // Start the transaction
            $this->db->beginTransaction();

            $this->query = $this->db->prepare($sql);
            $this->query->execute(); // execute the drop query
            $this->results = $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo $e->getMessage();
        }
        return $this->results;
    }

    /**
     * Executes a Create Table statement in a transaction, with table_name and an array of column=>type pairs
     *
     * @param string $table_name
     * @param array  $columns_types Array of Column_Name => Type pairs (one dimensional)
     *
     * @return array|bool Success or false
     */
    public function createTable($table_name, $columns_types)
    {
        $sql = SQL_CREATE_TABLE_GENERIC;

        $column_str = array();
        foreach ($columns_types as $column => $type) {
            $column_str[] = $column . ' ' . $type;
        }

        $sql = strtr($sql, array(
            '%tn' => $table_name,
            '%ct' => implode(', ', $column_str)
        ));

        try {
            $this->db->beginTransaction();
            $this->query = $this->db->prepare($sql);
            $this->query->execute();
            $this->results = $this->db->commit();
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            $this->db->rollBack();
            echo $e->getMessage();
        }

        return $this->results;
    }

    /**
     * Executes multiple REPLACE statements in a transaction on TABLE with an Array of Field=>Value pair Arrays
     *
     * @param string $table          DB TABLE DEFINE
     * @param array  $fv_pairs_array Array of Field => Value pair arrays (two dimensional)
     *                               $fv_pairs_array[] = array('userid' => 123, 'name' => 'Billy');
     *                               $fv_pairs_array[] = array('userid' => 456, 'name' => 'Lucy');
     *
     * @return bool|int Last Modified Row ID or false
     */
    public function replaceMultiple($table, $fv_pairs_array)
    {
        return $this->_modifyMultiple(SQL_REPLACE_GENERIC, $table, $fv_pairs_array);
    }

    /**
     * Executes multiple INSERT statements in a transaction on TABLE with an Array of Field=>Value pair Arrays
     *
     * @param string $table          DB TABLE DEFINE
     * @param array  $fv_pairs_array Array of Field => Value pair arrays (two dimensional)
     *                               $fv_pairs_array[] = array('userid' => 123, 'name' => 'Billy');
     *                               $fv_pairs_array[] = array('userid' => 456, 'name' => 'Lucy');
     *
     * @return bool|int Last Modified Row ID or false
     */
    public function insertMultiple($table, $fv_pairs_array)
    {
        return $this->_modifyMultiple(SQL_INSERT_GENERIC, $table, $fv_pairs_array);
    }

    /**
     * Gets the next top-element from the results array of a query
     * @return array|mixed Value of the next top-element in the results array
     */
    public function getNext()
    {
        return (!empty($this->results)) ? array_shift($this->results) : array();
    }

    /**
     * Abstraction of the INSERT/REPLACE methods, since they are
     * functionally the same, except for the SQL statement
     * Performs the SQL statement with field-value pairs in a single-execute transaction
     *
     * @param string $sql        SQL DEFINE statement
     * @param string $table      DB TABLE DEFINE
     * @param array  $fv_pairs   Array of field => value pairs, only one dimensional
     *                           $fv_pairs = array('userid' => 123, 'name' => 'Billy');
     * @param int    $iterations Recursion counter
     *
     * @return bool|int Last Inserted ID or false
     */
    private function _modifySingle($sql, $table, $fv_pairs, $iterations = 0)
    {
        // Build the field assignment strings
        $field_str = $value_str = array();
        foreach ($fv_pairs as $f => $v) {
            $field_str[$f] = $f;
            $value_str[$f] = ':' . $f;
        }

        $this->results = 0;
        try {
            // Start the transaction
            $this->db->beginTransaction();

            // Prepare the SQL statement
            $sql = strtr($sql, array(
                '%t' => $table,
                '%c' => implode(', ', array_unique($field_str)),
                '%v' => implode(', ', array_unique($value_str))
            ));
            $this->query = $this->db->prepare($sql);

            // loop through to bind the variable variable name for each field
            foreach ($value_str as $f => $v) {
                $this->query->bindParam($v, $$f); // Intentional variable variable
            }
            // loop through to assign the variable variable's value, and execute
            foreach ($fv_pairs as $f => $v) {
                $$f = $v; // set the variable variable
            }
            $this->query->execute(); // execute the replace query

            // attempt to commit the transaction
            $this->db->commit();
            $this->results = $this->db->lastInsertId($this->_returnIdColumn($table));
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            if (!$iterations) {
                $iterations++;
                $this->db = null;
                $this->__construct();
                $this->results = $this->_modifySingle($sql, $table, $fv_pairs, $iterations);
            } else {
                $this->db->rollBack();
                echo $e->getMessage();
            }
        }

        return $this->results;
    }

    private function _modifyMultiple($sql, $table, $fv_pairs_array, $iterations = 0)
    {
        // Build the field assignment strings
        $field_str = $value_str = array();
        foreach ($fv_pairs_array as $fv_pairs) {
            foreach ($fv_pairs as $f => $v) {
                $field_str[$f] = $f;
                $value_str[$f] = ':' . $f;
            }
        }
        $this->results = 0;
        try {
            // Start the transaction
            $this->db->beginTransaction();

            // Prepare the SQL statement
            $sql = strtr($sql, array(
                '%t' => $table,
                '%c' => implode(', ', array_unique($field_str)),
                '%v' => implode(', ', array_unique($value_str))
            ));
            $this->query = $this->db->prepare($sql);

            // loop through to bind the variable variable name for each field
            foreach ($value_str as $f => $v) {
                $this->query->bindParam($v, $$f); // Intentional variable variable
            }
            // loop through to assign the variable variable's value, and execute
            foreach ($fv_pairs_array as $fv_pairs) {
                foreach ($fv_pairs as $f => $v) {
                    $$f = $v; // set the variable variable
                }
                $this->query->execute(); // execute the replace query
                $this->results += $this->query->rowCount(); // return # rows affected
            }
            // attempt to commit the transaction
            $this->db->commit();
        } catch (PDOException $e) {
            // Attempt to recreate the PDO
            if (!$iterations) {
                $iterations++;
                $this->db = null;
                $this->__construct();
                $this->results = $this->_modifyMultiple($sql, $table, $fv_pairs_array, $iterations);
            } else {
                $this->db->rollBack();
                echo $e->getMessage();
            }
        }

        return $this->results;
    }

    private function _returnIdColumn($table)
    {
        $name = '';
        switch ($table) {
            case TABLE_USERS:
                $name = USERS_ID;
                break;
        }
        return $name;
    }
}
