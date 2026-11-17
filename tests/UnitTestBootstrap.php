<?php
/**
 * UnitTestBootstrap - Bootstrap file for PHPUnit unit tests that don't require MySQL.
 * 
 * This bootstrap replaces the global $dbc with a MockDbc instance so that
 * dbObject_t trait methods can be tested without a real database connection.
 * 
 * Usage: phpunit --bootstrap tests/UnitTestBootstrap.php tests/Unit/DbObjectTest.php
 * 
 * Sets TEST_MODE=true to prevent class_dbc.inc from including real MySQL connection.
 * The MockDbc is provided for any code that still references $dbc.
 */

// Enable test mode to prevent real database connection
define('TEST_MODE', true);

// Prevent the real dbc class from being instantiated (which requires MySQL)
if (isset($dbc)) {
    unset($dbc);
}

// Replace with mock database connection for unit testing
require __DIR__ . '/Support/MockDbc.php';
$dbc = new MockDbc();

// Also make the InMemorySqliteConnection available if needed
require __DIR__ . '/Support/InMemorySqliteConnection.php';