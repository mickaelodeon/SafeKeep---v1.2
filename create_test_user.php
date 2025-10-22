<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";

// Insert test user
try {
    $result = Database::insert('users', [
        'full_name' => 'Test Admin',
        'email' => 'admin@school.edu',
        'password_hash' => $hash,
        'role' => 'admin',
        'is_active' => 1,
        'email_verified' => 1
    ]);
    
    echo "Test admin created successfully!\n";
    echo "Login credentials:\n";
    echo "Email: admin@school.edu\n";
    echo "Password: password123\n";
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}
?>