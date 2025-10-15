<?php
/**
 * Test Admin Activation Workflow
 * Create a test user and test the new activation system
 */

require_once 'includes/db.php';

$message = '';
$testUser = null;

// Handle test actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_test_user':
                // Create a test user with unverified status
                $testUserData = [
                    'full_name' => 'Test User ' . date('Hi'),
                    'email' => 'testuser' . time() . '@example.com',
                    'password_hash' => password_hash('TestPassword123!', PASSWORD_DEFAULT),
                    'role' => 'user',
                    'is_active' => 0,
                    'email_verified' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $userId = Database::insert('users', $testUserData);
                if ($userId) {
                    $message = "✅ Test user created with ID: {$userId}";
                } else {
                    $message = "❌ Failed to create test user";
                }
                break;
                
            case 'activate_user':
                $userId = (int)$_POST['user_id'];
                
                // Simulate admin activation (same logic as admin/users.php)
                if (Database::update('users', [
                    'is_active' => 1, 
                    'email_verified' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$userId])) {
                    $message = "✅ User activated successfully with automatic email verification!";
                } else {
                    $message = "❌ Failed to activate user";
                }
                break;
                
            case 'delete_test_users':
                // Clean up test users
                $stmt = Database::getConnection()->prepare("DELETE FROM users WHERE email LIKE 'testuser%@example.com'");
                $result = $stmt->execute();
                $deleted = $stmt->rowCount();
                $message = "🗑️ Deleted {$deleted} test user(s)";
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get current test users
try {
    $stmt = Database::getConnection()->prepare("
        SELECT * FROM users 
        WHERE email LIKE 'testuser%@example.com' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $testUsers = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeKeep - Test Admin Activation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f0f0f0; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 2px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-info { background: #cce7ff; border: 1px solid #b3d9ff; color: #004085; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🧪 Test Admin Activation Workflow</h2>
        
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <strong>🎯 Testing Goal:</strong> Verify that when an admin activates a user, both <code>is_active</code> and <code>email_verified</code> are set to 1 automatically.
        </div>
        
        <h3>📝 Step 1: Create Test User</h3>
        <p>Creates a user with <code>is_active = 0</code> and <code>email_verified = 0</code> (like new registrations)</p>
        <form method="POST" style="margin: 10px 0;">
            <button type="submit" name="action" value="create_test_user" class="btn btn-primary">
                👤 Create Test User
            </button>
        </form>
        
        <?php if (!empty($testUsers)): ?>
        <h3>🧑‍💼 Current Test Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Active?</th>
                <th>Email Verified?</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($testUsers as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td style="color: <?php echo $user['is_active'] ? 'green' : 'red'; ?>; font-weight: bold;">
                    <?php echo $user['is_active'] ? '✅ YES' : '❌ NO'; ?>
                </td>
                <td style="color: <?php echo $user['email_verified'] ? 'green' : 'red'; ?>; font-weight: bold;">
                    <?php echo $user['email_verified'] ? '✅ YES' : '❌ NO'; ?>
                </td>
                <td>
                    <?php if (!$user['is_active']): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="action" value="activate_user" class="btn btn-success">
                            ✅ Activate User
                        </button>
                    </form>
                    <?php else: ?>
                    <span style="color: green;">✅ Activated</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <form method="POST" style="margin: 20px 0;">
            <button type="submit" name="action" value="delete_test_users" class="btn btn-danger" 
                    onclick="return confirm('Delete all test users?')">
                🗑️ Clean Up Test Users
            </button>
        </form>
        <?php endif; ?>
        
        <h3>✅ Expected Behavior (Fixed)</h3>
        <ul>
            <li><strong>Before Fix:</strong> Activate user → only <code>is_active = 1</code>, user still can't login</li>
            <li><strong>After Fix:</strong> Activate user → both <code>is_active = 1</code> AND <code>email_verified = 1</code>, user can login immediately!</li>
        </ul>
        
        <hr style="margin: 30px 0;">
        <p><a href="/safekeep-v2/admin/users.php">👥 Go to Admin Users Page</a> | <a href="/safekeep-v2/">🏠 Back to SafeKeep</a></p>
    </div>
</body>
</html>