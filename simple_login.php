<?php
/**
 * Simple Login Test
 * Basic login implementation to test authentication
 */

declare(strict_types=1);

// Start session first
session_start();

require_once 'includes/config.php';
require_once 'includes/db.php';

$error = '';
$success = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start();
    $success = 'Logged out successfully!';
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $success = 'You are already logged in! User ID: ' . $_SESSION['user_id'];
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Get user from database
        try {
            $user = Database::selectOne(
                "SELECT * FROM users WHERE email = ? AND is_active = 1", 
                [$email]
            );
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Force session write
                session_write_close();
                
                // Redirect to prevent form resubmission
                header('Location: simple_login.php?success=1');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Check for success redirect
if (isset($_GET['success'])) {
    $success = 'Login successful! Welcome back!';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .form { background: #f5f5f5; padding: 20px; border-radius: 5px; max-width: 400px; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; }
        .info { background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Simple Login Test</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="form">
            <h3>Login</h3>
            <form method="POST">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                
                <label>Password:</label>
                <input type="password" name="password" required>
                
                <button type="submit">Login</button>
            </form>
        </div>
        
        <div class="info">
            <h4>Test Credentials:</h4>
            <p><strong>Email:</strong> test@school.edu<br>
            <strong>Password:</strong> password123</p>
            
            <p><strong>Email:</strong> admin@school.edu<br>
            <strong>Password:</strong> admin123</p>
        </div>
    <?php else: ?>
        <div class="info">
            <h3>You are logged in!</h3>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            <p><strong>Role:</strong> <?php echo $_SESSION['user_role']; ?></p>
            <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
            
            <p><a href="simple_login.php?logout=1">Logout</a></p>
            
            <p><strong>Test Navigation:</strong></p>
            <p><a href="profile/index.php">Go to My Profile</a></p>
            <p><a href="posts/my-posts.php">Go to My Posts</a></p>
        </div>
    <?php endif; ?>
    
    <hr>
    <h3>Session Debug:</h3>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h3>Available Users:</h3>
    <?php
    try {
        $users = Database::select("SELECT id, email, full_name, role, is_active FROM users ORDER BY id");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "Error loading users: " . $e->getMessage();
    }
    ?>
</body>
</html>