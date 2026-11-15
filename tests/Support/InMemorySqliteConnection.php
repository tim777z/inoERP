<?php

/**
 * InMemorySqliteConnection - Provides a PDO SQLite connection for unit tests
 * that don't require a full MySQL database server.
 * 
 * Usage in tests: 
 *   $db = new InMemorySqliteConnection();
 *   $db->connect();
 *   // Use $db->getPdo() for PDO operations
 */

class InMemorySqliteConnection
{
    private $pdo;
    private $dbPath = ':memory:';

    public function connect(): bool
    {
        try {
            $this->pdo = new \PDO("sqlite:$this->dbPath");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return true;
        } catch (\PDOException $e) {
            error_log("InMemorySqliteConnection error: " . $e->getMessage());
            return false;
        }
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function close(): void
    {
        if ($this->pdo) {
            $this->pdo = null;
        }
    }
}