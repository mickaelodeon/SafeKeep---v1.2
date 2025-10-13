<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Create a new password hash for admin user
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "Updating admin user password...<br>";

try {
    $updated = Database::update(
        'users', 
        ['password_hash' => $hash], 
        'email = ?', 
        ['admin@school.edu']
    );
    
    if ($updated) {
        echo "Admin password updated successfully!<br>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "Email: admin@school.edu<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Failed to update admin password<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Also update the first user
echo "<br>Updating first user password...<br>";

try {
    $updated = Database::update(
        'users', 
        ['password_hash' => $hash], 
        'email = ?', 
        ['johnmichaeleborda79@school.edu']
    );
    
    if ($updated) {
        echo "First user password updated successfully!<br>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "Email: johnmichaeleborda79@school.edu<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Failed to update first user password<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>