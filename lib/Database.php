<?php

/**
 * Database Helper Class
 * Provides utility methods for common database operations
 * 
 * @package FARUNOVA
 * @version 1.0
 */

class Database
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $query SQL query with ? placeholders
     * @param array $params Array of parameters to bind
     * @param string $types Type string (i=int, s=string, d=double, b=blob)
     * @return mysqli_result|false Result on success, false on failure
     */
    public function execute($query, $params = [], $types = '')
    {
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            logSecurityEvent('db_error', 'Prepare failed: ' . $this->conn->error, $_SESSION['username'] ?? 'system');
            return false;
        }

        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            logSecurityEvent('db_error', 'Execute failed: ' . $stmt->error, $_SESSION['username'] ?? 'system');
            $stmt->close();
            return false;
        }

        return $stmt->get_result();
    }

    /**
     * Get a single row from database
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @param string $types Type string
     * @return array|null Single row or null
     */
    public function getRow($query, $params = [], $types = '')
    {
        $result = $this->execute($query, $params, $types);

        if (!$result) {
            return null;
        }

        $row = $result->fetch_assoc();
        return $row;
    }

    /**
     * Get multiple rows from database
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @param string $types Type string
     * @return array Array of rows or empty array
     */
    public function getRows($query, $params = [], $types = '')
    {
        $result = $this->execute($query, $params, $types);

        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert a row into database
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Insert ID or false on failure
     */
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');

        $query = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        // Build type string
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            return false;
        }

        return $this->conn->insert_id;
    }

    /**
     * Update rows in database
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param string $where WHERE clause (e.g., 'id = ?')
     * @param array $whereParams Parameters for WHERE clause
     * @return bool Success status
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $set = implode(' = ?, ', $columns) . ' = ?';
        $query = "UPDATE $table SET $set WHERE $where";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        // Build type string
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        // Add where parameter types
        foreach ($whereParams as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $allParams = array_merge($values, $whereParams);
        $stmt->bind_param($types, ...$allParams);

        return $stmt->execute();
    }

    /**
     * Delete rows from database
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return bool Success status
     */
    public function delete($table, $where, $params = [])
    {
        $query = "DELETE FROM $table WHERE $where";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }

            $stmt->bind_param($types, ...$params);
        }

        return $stmt->execute();
    }

    /**
     * Count rows in table
     * 
     * @param string $table Table name
     * @param string $where Optional WHERE clause
     * @param array $params Optional parameters
     * @return int Count of rows
     */
    public function count($table, $where = '', $params = [])
    {
        $query = "SELECT COUNT(*) as count FROM $table";

        if (!empty($where)) {
            $query .= " WHERE $where";
        }

        $result = $this->execute($query, $params);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Check if a row exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool True if exists, false otherwise
     */
    public function exists($table, $where, $params = [])
    {
        return $this->count($table, $where, $params) > 0;
    }

    /**
     * Get last query error
     * 
     * @return string Error message
     */
    public function getError()
    {
        return $this->conn->error;
    }

    /**
     * Start a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction()
    {
        return $this->conn->begin_transaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit()
    {
        return $this->conn->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollback()
    {
        return $this->conn->rollback();
    }

    /**
     * Escape a string (legacy, use prepared statements instead)
     * 
     * @param string $str String to escape
     * @return string Escaped string
     */
    public function escape($str)
    {
        return $this->conn->real_escape_string($str);
    }
}
