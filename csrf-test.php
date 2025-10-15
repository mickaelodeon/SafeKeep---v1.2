<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

echo "<h2>CSRF Token Test</h2>";

// Generate a token
$token1 = Security::generateCSRFToken();
echo "<p><strong>Generated Token 1:</strong> " . $token1 . "</p>";

// Generate another token (should be the same)
$token2 = Security::generateCSRFToken();
echo "<p><strong>Generated Token 2:</strong> " . $token2 . "</p>";

// Test validation
$isValid = Security::validateCSRFToken($token1);
echo "<p><strong>Token 1 Valid:</strong> " . ($isValid ? 'Yes' : 'No') . "</p>";

$isValid2 = Security::validateCSRFToken($token2);
echo "<p><strong>Token 2 Valid:</strong> " . ($isValid2 ? 'Yes' : 'No') . "</p>";

// Test with fake token
$fakeValid = Security::validateCSRFToken('fake_token');
echo "<p><strong>Fake Token Valid:</strong> " . ($fakeValid ? 'Yes' : 'No') . "</p>";

echo "<h3>Session Data</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>