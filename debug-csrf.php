<?php
/**
 * Debug CSRF Token Issues
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize session
Session::init();

echo "<h2>CSRF Debug Information</h2>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Name: " . session_name() . "<br>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>CSRF Token Test:</h3>";
$token1 = Security::generateCSRFToken();
echo "Generated Token 1: " . $token1 . "<br>";

$token2 = Security::generateCSRFToken();
echo "Generated Token 2: " . $token2 . "<br>";

echo "Tokens Match: " . ($token1 === $token2 ? 'YES' : 'NO') . "<br>";

echo "<h3>Validation Test:</h3>";
echo "Token 1 validates: " . (Security::validateCSRFToken($token1) ? 'YES' : 'NO') . "<br>";
echo "Token 2 validates: " . (Security::validateCSRFToken($token2) ? 'YES' : 'NO') . "<br>";
echo "Empty string validates: " . (Security::validateCSRFToken('') ? 'YES' : 'NO') . "<br>";
echo "Random string validates: " . (Security::validateCSRFToken('random123') ? 'YES' : 'NO') . "<br>";

echo "<h3>Cookie Settings:</h3>";
echo "Cookie Path: " . ini_get('session.cookie_path') . "<br>";
echo "Cookie Domain: " . ini_get('session.cookie_domain') . "<br>";
echo "Cookie Secure: " . ini_get('session.cookie_secure') . "<br>";
echo "Cookie HTTPOnly: " . ini_get('session.cookie_httponly') . "<br>";
echo "Cookie SameSite: " . ini_get('session.cookie_samesite') . "<br>";

echo "<h3>Environment:</h3>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'YES' : 'NO') . "<br>";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "<br>";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data:</h3>";
    echo "CSRF Token from POST: " . ($_POST['csrf_token'] ?? 'NOT SET') . "<br>";
    echo "CSRF Token from SESSION: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>";
    echo "Token Validation: " . (Security::validateCSRFToken($_POST['csrf_token'] ?? '') ? 'VALID' : 'INVALID') . "<br>";
}
?>

<form method="POST" style="margin-top: 20px;">
    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
    <button type="submit">Test CSRF Token</button>
</form>