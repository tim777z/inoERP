<?php

declare(strict_types=1);

namespace InoERP\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for SecurityHelper class
 */
class SecurityHelperTest extends TestCase
{
    public function testSanitizeHtml(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '<script>alert("xss")</script>';
        $this->assertEquals($expected, \SecurityHelper::sanitizeHtml($input));
    }

    public function testSanitizeHtmlWithQuotes(): void
    {
        $input = "O'Reilly & \"Associates\"";
        $expected = 'O&#039;Reilly & "Associates"';
        $this->assertEquals($expected, \SecurityHelper::sanitizeHtml($input));
    }

    public function testSanitizeLike(): void
    {
        $input = 'test%_value';
        $expected = 'test\%\_value';
        $this->assertEquals($expected, \SecurityHelper::sanitizeLike($input));
    }

    public function testSanitizeEmailValid(): void
    {
        $input = 'test@example.com';
        $this->assertEquals($input, \SecurityHelper::sanitizeEmail($input));
    }

    public function testSanitizeEmailInvalid(): void
    {
        $input = 'not-an-email';
        $this->assertFalse(\SecurityHelper::sanitizeEmail($input));
    }

    public function testSanitizeEmailWithWhitespace(): void
    {
        $input = '  test@example.com  ';
        $this->assertEquals('test@example.com', \SecurityHelper::sanitizeEmail($input));
    }

    public function testSanitizeIntValid(): void
    {
        $this->assertEquals(42, \SecurityHelper::sanitizeInt('42'));
        $this->assertEquals(0, \SecurityHelper::sanitizeInt('0'));
        $this->assertEquals(-10, \SecurityHelper::sanitizeInt('-10'));
    }

    public function testSanitizeIntWithMinMax(): void
    {
        $this->assertEquals(5, \SecurityHelper::sanitizeInt('5', 1, 10));
        $this->assertFalse(\SecurityHelper::sanitizeInt('0', 1, 10));
        $this->assertFalse(\SecurityHelper::sanitizeInt('11', 1, 10));
    }

    public function testSanitizeIntInvalid(): void
    {
        $this->assertFalse(\SecurityHelper::sanitizeInt('abc'));
        $this->assertFalse(\SecurityHelper::sanitizeInt('3.14'));
    }

    public function testSanitizeFloatValid(): void
    {
        $this->assertEquals(3.14, \SecurityHelper::sanitizeFloat('3.14'));
        $this->assertEquals(-2.5, \SecurityHelper::sanitizeFloat('-2.5'));
    }

    public function testSanitizeFloatWithMinMax(): void
    {
        $this->assertEquals(5.5, \SecurityHelper::sanitizeFloat('5.5', 1.0, 10.0));
        $this->assertFalse(\SecurityHelper::sanitizeFloat('0.5', 1.0, 10.0));
        $this->assertFalse(\SecurityHelper::sanitizeFloat('11.0', 1.0, 10.0));
    }

    public function testSanitizeAlphanumeric(): void
    {
        $this->assertEquals('abc123', \SecurityHelper::sanitizeAlphanumeric('abc123'));
        $this->assertEquals('abc123', \SecurityHelper::sanitizeAlphanumeric('abc-123'));
        $this->assertEquals('abc_123', \SecurityHelper::sanitizeAlphanumeric('abc_123'));
        $this->assertEquals('abc123', \SecurityHelper::sanitizeAlphanumeric('abc@#$123'));
    }

    public function testSanitizeAlphanumericWithOptions(): void
    {
        $this->assertEquals('abc-123', \SecurityHelper::sanitizeAlphanumeric('abc-123', true, true));
        $this->assertEquals('abc_123', \SecurityHelper::sanitizeAlphanumeric('abc_123', true, false));
    }

    public function testGenerateToken(): void
    {
        $token1 = \SecurityHelper::generateToken(16);
        $token2 = \SecurityHelper::generateToken(16);
        
        $this->assertEquals(32, strlen($token1)); // 16 bytes = 32 hex chars
        $this->assertEquals(32, strlen($token2));
        $this->assertNotEquals($token1, $token2); // Should be random
    }

    public function testTimingSafeEquals(): void
    {
        $this->assertTrue(\SecurityHelper::timingSafeEquals('secret', 'secret'));
        $this->assertFalse(\SecurityHelper::timingSafeEquals('secret', 'different'));
        $this->assertFalse(\SecurityHelper::timingSafeEquals('', 'secret'));
    }

    public function testSanitizeShell(): void
    {
        $input = 'test; rm -rf /';
        $expected = "'test; rm -rf /'";
        $this->assertEquals($expected, \SecurityHelper::sanitizeShell($input));
    }
}