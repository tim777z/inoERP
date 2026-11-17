<?php
// Minimal test - SecurityHelper only, no dbc needed
// Directly test SecurityHelper static methods

// Load composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// The autoloader includes functions.inc which triggers dbc connection
// Instead, let us directly test by loading only what we need
// We know SecurityHelper is in inoerp/includes/functions/functions.inc

// Since the autoloader triggers the dbc issue, let us use a different approach:
// Create a mock of the autoloader that only loads SecurityHelper

// Actually, let us just test what we can:
// The SecurityHelper class methods are static and don't depend on dbc
// The issue is the autoloader including basics.inc

// Let us disable error reporting for the known warnings
error_reporting(E_ERROR | E_PARSE);

// Now try to test
try {
    // Use reflection to load the class directly without the autoloader
    $classFile = __DIR__ . '/inoerp/includes/functions/functions.inc';
    if (file_exists($classFile)) {
        require_once $classFile;
        echo "functions.inc loaded successfully\n";
        
        // Now test SecurityHelper methods
        $input = '<script>alert("xss")</script>';
        $expected = '<script>alert("xss")</script>';
        $actual = \SecurityHelper::sanitizeHtml($input);
        echo "sanitizeHtml: " . ($actual === $expected ? "PASS" : "FAIL") . "\n";
        
        $input = 'test%_value';
        $expected = 'test\%\_value';
        $actual = \SecurityHelper::sanitizeLike($input);
        echo "sanitizeLike: " . ($actual === $expected ? "PASS" : "FAIL") . "\n";
        
        $input = 'test@example.com';
        $actual = \SecurityHelper::sanitizeEmail($input);
        echo "sanitizeEmail valid: " . ($actual === $input ? "PASS" : "FAIL") . "\n";
        
        $input = 'not-an-email';
        $actual = \SecurityHelper::sanitizeEmail($input);
        echo "sanitizeEmail invalid: " . ($actual === false ? "PASS" : "FAIL") . "\n";
        
        $input = '42';
        $actual = \SecurityHelper::sanitizeInt($input);
        echo "sanitizeInt: " . ($actual === 42 ? "PASS" : "FAIL") . "\n";
        
        $input = '3.14';
        $actual = \SecurityHelper::sanitizeFloat($input);
        echo "sanitizeFloat: " . ($actual === 3.14 ? "PASS" : "FAIL") . "\n";
        
        $input = 'abc-123';
        $actual = \SecurityHelper::sanitizeAlphanumeric($input);
        echo "sanitizeAlphanumeric: " . ($actual === 'abc-123' ? "PASS" : "FAIL") . "\n";
        
        $token1 = \SecurityHelper::generateToken(16);
        $token2 = \SecurityHelper::generateToken(16);
        echo "generateToken: " . (strlen($token1) === 32 && strlen($token2) === 32 ? "PASS" : "FAIL") . "\n";
        echo "generateToken different: " . ($token1 !== $token2 ? "PASS" : "FAIL") . "\n";
        
        $actual = \SecurityHelper::timingSafeEquals('secret', 'secret');
        echo "timingSafeEquals same: " . ($actual ? "PASS" : "FAIL") . "\n";
        
        $actual = \SecurityHelper::timingSafeEquals('secret', 'different');
        echo "timingSafeEquals different: " . (!$actual ? "PASS" : "FAIL") . "\n";
        
        $input = 'test; rm -rf /';
        $expected = "'test; rm -rf /'";
        $actual = \SecurityHelper::sanitizeShell($input);
        echo "sanitizeShell: " . ($actual === $expected ? "PASS" : "FAIL") . "\n";
    } else {
        echo "functions.inc not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}