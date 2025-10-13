<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$email = 'test@school.edu';
$password = 'password123';

echo "<h3>Login Debug Information</h3>";

// Get user from database
$user = Database::selectOne(
    "SELECT * FROM users WHERE email = ?", 
    [$email]
);

if ($user) {
    echo "<strong>User found:</strong><br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Name: " . $user['full_name'] . "<br>";
    echo "Active: " . ($user['is_active'] ? 'YES' : 'NO') . "<br>";
    echo "Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "<br>";
    
    echo "<strong>Password Hash:</strong><br>";
    echo $user['password_hash'] . "<br><br>";
    
    echo "<strong>Password Verification Test:</strong><br>";
    $verified = password_verify($password, $user['password_hash']);
    echo "Password '$password' verified: " . ($verified ? 'YES' : 'NO') . "<br><br>";
    
    // Test the full authenticate method
    echo "<strong>User::authenticate() Test:</strong><br>";
    $authUser = User::authenticate($email, $password);
    if ($authUser) {
        echo "Authentication successful for: " . $authUser['full_name'] . "<br>";
    } else {
        echo "Authentication failed<br>";
    }
} else {
    echo "User not found in database<br>";
}
?>