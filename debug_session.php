<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

Session::init();

echo "<h3>Session Debug Information</h3>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Is Logged In:</strong> " . (Session::isLoggedIn() ? 'YES' : 'NO') . "<br>";

echo "<br><strong>Session Data:</strong><br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<br><strong>Session Configuration:</strong><br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "<br>";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";
echo "session.name: " . session_name() . "<br>";

if (Session::isLoggedIn()) {
    $user = Session::getUser();
    echo "<br><strong>Current User:</strong><br>";
    if ($user) {
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . htmlspecialchars($user['full_name']) . "<br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Role: " . $user['role'] . "<br>";
    } else {
        echo "User data could not be retrieved from database.<br>";
    }
}
?>