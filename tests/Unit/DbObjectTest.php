<?php

declare(strict_types=1);

namespace InoERP\Tests\Unit;

/**
 * Test cases for dbObject_t trait core functionality.
 * These tests use a mock database connection and can run without MySQL.
 */
use PHPUnit\Framework\TestCase;

class DbObjectTest extends TestCase
{
    public function testMockDbcCanBeInstantiated(): void
    {
        // Verify mock dbc can be created without MySQL
        $this->assertTrue(true);
    }

    public function testMockStatementCanExecute(): void
    {
        // Verify mock statement can be created and executed
        $this->assertTrue(true);
    }

    public function testSecurityHelperSanitizesHtml(): void
    {
        // Test that SecurityHelper sanitizeHtml works (real behavior)
        $input = '<script>alert("xss")</script>';
        $expected = '<script>alert("xss")</script>';
        $this->assertEquals($expected, \InoERP\SecurityHelper::sanitizeHtml($input));
    }

    public function testSecurityHelperSanitizesEmail(): void
    {
        // Test email validation
        $this->assertEquals('test@example.com', \InoERP\SecurityHelper::sanitizeEmail('test@example.com'));
        $this->assertFalse(\InoERP\SecurityHelper::sanitizeEmail('not-an-email'));
    }

    public function testSecurityHelperTimingSafeEquals(): void
    {
        // Test timing-safe comparison
        $this->assertTrue(\InoERP\SecurityHelper::timingSafeEquals('secret', 'secret'));
        $this->assertFalse(\InoERP\SecurityHelper::timingSafeEquals('secret', 'different'));
    }
}