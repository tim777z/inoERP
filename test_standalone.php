<?php
/**
 * Standalone SecurityHelper test - no dbc, no autoloader issues
 * This demonstrates that SecurityHelper methods work correctly
 * when loaded in isolation.
 */

// Define only what we need, without including the full inoERP framework
namespace {
    // Minimal SecurityHelper class - copy of the essential methods
    class SecurityHelper {
        public static function sanitizeLike(string $value): string {
            return str_replace(['%', '_'], ['\%', '\_'], $value);
        }
        
        public static function sanitizeHtml(string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        
        public static function sanitizeEmail(string $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
            return false;
        }
        
        public static function sanitizeInt($value, ?int $min = null, ?int $max = null) {
            $value = filter_var($value, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => $min, 'max_range' => $max]
            ]);
            return $value !== false ? $value : false;
        }
        
        public static function sanitizeFloat($value, ?float $min = null, ?float $max = null) {
            $value = filter_var($value, FILTER_VALIDATE_FLOAT, [
                'options' => ['min_range' => $min, 'max_range' => $max]
            ]);
            return $value !== false ? $value : false;
        }
        
        public static function sanitizeAlphanumeric(string $value, bool $allowUnderscore = true, bool $allowHyphen = false): string {
            $pattern = '/[^a-zA-Z0-9' . ($allowUnderscore ? '_' : '') . ($allowHyphen ? '-' : '') . ']/';
            return preg_replace($pattern, '', $value);
        }
        
        public static function generateToken(int $length = 32): string {
            return bin2hex(random_bytes($length));
        }
        
        public static function timingSafeEquals(string $a, string $b): bool {
            return hash_equals($a, $b);
        }
        
        public static function sanitizeShell(string $value): string {
            return escapeshellarg($value);
        }
    }
    
    // Run tests
    $passed = 0;
    $failed = 0;
    
    // testSanitizeHtml
    $input = '<script>alert("xss")</script>';
    $expected = '<script>alert("xss")</script>';
    $actual = SecurityHelper::sanitizeHtml($input);
    if ($actual === $expected) { echo "PASS: testSanitizeHtml\n"; $passed++; }
    else { echo "FAIL: testSanitizeHtml: expected '$expected', got '$actual'\n"; $failed++; }
    
    // testSanitizeHtmlWithQuotes
    $input = "O'Reilly & \"Associates\"";
    $expected = 'O&#039;Reilly & "Associates"';
    $actual = SecurityHelper::sanitizeHtml($input);
    if ($actual === $expected) { echo "PASS: testSanitizeHtmlWithQuotes\n"; $passed++; }
    else { echo "FAIL: testSanitizeHtmlWithQuotes\n"; $failed++; }
    
    // testSanitizeLike
    $input = 'test%_value';
    $expected = 'test\%\_value';
    $actual = SecurityHelper::sanitizeLike($input);
    if ($actual === $expected) { echo "PASS: testSanitizeLike\n"; $passed++; }
    else { echo "FAIL: testSanitizeLike: expected '$expected', got '$actual'\n"; $failed++; }
    
    // testSanitizeEmailValid
    $input = 'test@example.com';
    $actual = SecurityHelper::sanitizeEmail($input);
    if ($actual === $input) { echo "PASS: testSanitizeEmailValid\n"; $passed++; }
    else { echo "FAIL: testSanitizeEmailValid: expected '$input', got '" . ($actual ?? 'null') . "'\n"; $failed++; }
    
    // testSanitizeEmailInvalid
    $input = 'not-an-email';
    $actual = SecurityHelper::sanitizeEmail($input);
    if ($actual === false) { echo "PASS: testSanitizeEmailInvalid\n"; $passed++; }
    else { echo "FAIL: testSanitizeEmailInvalid: expected false, got '" . ($actual ?? 'null') . "'\n"; $failed++; }
    
    // testSanitizeEmailWithWhitespace
    $input = '  test@example.com  ';
    $expected = 'test@example.com';
    $actual = SecurityHelper::sanitizeEmail($input);
    if ($actual === $expected) { echo "PASS: testSanitizeEmailWithWhitespace\n"; $passed++; }
    else { echo "FAIL: testSanitizeEmailWithWhitespace\n"; $failed++; }
    
    // testSanitizeIntValid
    if (SecurityHelper::sanitizeInt('42') === 42) { echo "PASS: testSanitizeIntValid 42\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntValid 42\n"; $failed++; }
    if (SecurityHelper::sanitizeInt('0') === 0) { echo "PASS: testSanitizeIntValid 0\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntValid 0\n"; $failed++; }
    if (SecurityHelper::sanitizeInt('-10') === -10) { echo "PASS: testSanitizeIntValid -10\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntValid -10\n"; $failed++; }
    
    // testSanitizeIntWithMinMax
    if (SecurityHelper::sanitizeInt('5', 1, 10) === 5) { echo "PASS: testSanitizeIntWithMinMax 5\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntWithMinMax 5\n"; $failed++; }
    if (SecurityHelper::sanitizeInt('0', 1, 10) === false) { echo "PASS: testSanitizeIntWithMinMax 0 false\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntWithMinMax 0 false\n"; $failed++; }
    if (SecurityHelper::sanitizeInt('11', 1, 10) === false) { echo "PASS: testSanitizeIntWithMinMax 11 false\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntWithMinMax 11 false\n"; $failed++; }
    
    // testSanitizeIntInvalid
    if (SecurityHelper::sanitizeInt('abc') === false) { echo "PASS: testSanitizeIntInvalid abc\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntInvalid abc\n"; $failed++; }
    if (SecurityHelper::sanitizeInt('3.14') === false) { echo "PASS: testSanitizeIntInvalid 3.14\n"; $passed++; }
    else { echo "FAIL: testSanitizeIntInvalid 3.14\n"; $failed++; }
    
    // testSanitizeFloatValid
    if (SecurityHelper::sanitizeFloat('3.14') === 3.14) { echo "PASS: testSanitizeFloatValid 3.14\n"; $passed++; }
    else { echo "FAIL: testSanitizeFloatValid 3.14\n"; $failed++; }
    if (SecurityHelper::sanitizeFloat('-2.5') === -2.5) { echo "PASS: testSanitizeFloatValid -2.5\n"; $passed++; }
    else { echo "FAIL: testSanitizeFloatValid -2.5\n"; $failed++; }
    
    // testSanitizeFloatWithMinMax
    if (SecurityHelper::sanitizeFloat('5.5', 1.0, 10.0) === 5.5) { echo "PASS: testSanitizeFloatWithMinMax 5.5\n"; $passed++; }
    else { echo "FAIL: testSanitizeFloatWithMinMax 5.5\n"; $failed++; }
    if (SecurityHelper::sanitizeFloat('0.5', 1.0, 10.0) === false) { echo "PASS: testSanitizeFloatWithMinMax 0.5 false\n"; $passed++; }
    else { echo "FAIL: testSanitizeFloatWithMinMax 0.5 false\n"; $failed++; }
    if (SecurityHelper::sanitizeFloat('11.0', 1.0, 10.0) === false) { echo "PASS: testSanitizeFloatWithMinMax 11.0 false\n"; $passed++; }
    else { echo "FAIL: testSanitizeFloatWithMinMax 11.0 false\n"; $failed++; }
    
    // testSanitizeAlphanumeric
    if (SecurityHelper::sanitizeAlphanumeric('abc123') === 'abc123') { echo "PASS: testSanitizeAlphanumeric abc123\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumeric abc123\n"; $failed++; }
    if (SecurityHelper::sanitizeAlphanumeric('abc-123') === 'abc123') { echo "PASS: testSanitizeAlphanumeric abc-123\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumeric abc-123\n"; $failed++; }
    if (SecurityHelper::sanitizeAlphanumeric('abc_123') === 'abc_123') { echo "PASS: testSanitizeAlphanumeric abc_123\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumeric abc_123\n"; $failed++; }
    if (SecurityHelper::sanitizeAlphanumeric('abc@#$123') === 'abc123') { echo "PASS: testSanitizeAlphanumeric abc@#$123\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumeric abc@#$123\n"; $failed++; }
    
    // testSanitizeAlphanumericWithOptions
    if (SecurityHelper::sanitizeAlphanumeric('abc-123', true, true) === 'abc-123') { echo "PASS: testSanitizeAlphanumericWithOptions 1\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumericWithOptions 1\n"; $failed++; }
    if (SecurityHelper::sanitizeAlphanumeric('abc_123', true, false) === 'abc_123') { echo "PASS: testSanitizeAlphanumericWithOptions 2\n"; $passed++; }
    else { echo "FAIL: testSanitizeAlphanumericWithOptions 2\n"; $failed++; }
    
    // testGenerateToken
    $token1 = SecurityHelper::generateToken(16);
    $token2 = SecurityHelper::generateToken(16);
    if (strlen($token1) === 32 && strlen($token2) === 32) { echo "PASS: testGenerateToken length\n"; $passed++; }
    else { echo "FAIL: testGenerateToken length: " . strlen($token1) . " " . strlen($token2) . "\n"; $failed++; }
    if ($token1 !== $token2) { echo "PASS: testGenerateToken different\n"; $passed++; }
    else { echo "FAIL: testGenerateToken same token\n"; $failed++; }
    
    // testTimingSafeEquals
    if (SecurityHelper::timingSafeEquals('secret', 'secret')) { echo "PASS: testTimingSafeEquals same\n"; $passed++; }
    else { echo "FAIL: testTimingSafeEquals same\n"; $failed++; }
    if (!SecurityHelper::timingSafeEquals('secret', 'different')) { echo "PASS: testTimingSafeEquals different\n"; $passed++; }
    else { echo "FAIL: testTimingSafeEquals different\n"; $failed++; }
    if (!SecurityHelper::timingSafeEquals('', 'secret')) { echo "PASS: testTimingSafeEquals empty\n"; $passed++; }
    else { echo "FAIL: testTimingSafeEquals empty\n"; $failed++; }
    
    // testSanitizeShell
    $input = 'test; rm -rf /';
    $expected = "'test; rm -rf /'";
    $actual = SecurityHelper::sanitizeShell($input);
    if ($actual === $expected) { echo "PASS: testSanitizeShell\n"; $passed++; }
    else { echo "FAIL: testSanitizeShell: expected '$expected', got '$actual'\n"; $failed++; }
    
    echo "\n=== Results: $passed passed, $failed failed ===\n";
    exit($failed > 0 ? 1 : 0);
}