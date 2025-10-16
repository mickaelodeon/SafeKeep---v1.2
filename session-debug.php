<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

echo "<h2>Session Debug</h2>";

echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";

$currentUser = Session::getUser();
if ($currentUser) {
    echo "<p><strong>Logged in as:</strong> " . htmlspecialchars($currentUser['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>";
    echo "<p><strong>User ID:</strong> " . $currentUser['id'] . "</p>";
    echo "<p><strong>Role:</strong> " . $currentUser['role'] . "</p>";
} else {
    echo "<p><strong>Not logged in</strong></p>";
    echo "<p><a href='auth/login.php'>Login here</a></p>";
}

echo "<h3>Test Direct Post Access</h3>";
echo "<p><a href='posts/view.php?id=10' target='_blank'>View Post ID 10</a></p>";
?>