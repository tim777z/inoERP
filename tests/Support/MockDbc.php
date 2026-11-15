<?php

/**
 * MockDbc - A mock database connection for unit tests that don't require MySQL.
 * 
 * Provides a minimal PDO-like interface that the dbObject_t trait can use
 * without requiring a real database server.
 */
class MockDbc
{
    public $connection;
    public $rollback = false;

    public function __construct()
    {
        // Mock PDO connection - no actual database needed
        $this->connection = new class {
            public function prepare($sql) {
                return new MockStatement();
            }
            public function setAttribute($attr, $value) { return true; }
            public function beginTransaction() { return true; }
            public function commit() { return true; }
            public function rollBack() { $this->rollback = true; return true; }
            public function lastInsertId() { return 0; }
            public function setAttribute_once($attr, $value) { return true; }
        };
    }

    public function confirm() {
        if ($this->rollback) {
            // Already rolled back
        } else {
            // Commit does nothing in mock
        }
        $this->connection->beginTransaction();
    }

    public function close_connection() {
        $this->connection = null;
    }
}

/**
 * MockStatement - A minimal PDO statement mock for unit tests.
 */
class MockStatement
{
    private $boundParams = [];
    private $rowCount = 0;
    private $fetchMode = PDO::FETCH_CLASS;
    private $results = [];

    public function bindValue($param, $value, $type = null) {
        $this->boundParams[$param] = $value;
        return true;
    }

    public function bindParam($param, &$value, $type = null) {
        $this->boundParams[$param] = &$value;
        return true;
    }

    public function execute() {
        // Return results based on what was bound
        return true;
    }

    public function fetchAll($mode = null) {
        if ($mode === PDO::FETCH_CLASS) {
            return [];
        }
        return $this->results;
    }

    public function fetch($mode = null, $cursor_orientation = null, $cursor_offset = null) {
        return null;
    }

    public function fetchColumn($column_number = 0) {
        return null;
    }

    public function rowCount() {
        return $this->rowCount;
    }

    public function setFetchMode($mode) {
        $this->fetchMode = $mode;
        return true;
    }
}