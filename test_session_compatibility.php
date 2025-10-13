<?php
// Test session compatibility
session_start();

echo "<h3>Before Session::init()</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Simulate what profile/index.php does
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// This is what the profile page calls
Session::init();

echo "<h3>After Session::init()</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Session::isLoggedIn() Test</h3>";
echo "Is logged in: " . (Session::isLoggedIn() ? 'YES' : 'NO') . "<br>";

if (Session::isLoggedIn()) {
    $user = Session::getUser();
    echo "<br>Current user: " . ($user ? $user['full_name'] : 'NULL') . "<br>";
}
?>