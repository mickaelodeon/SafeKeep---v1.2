<?php
/**
 * Fix Email Verification Status
 * Set email_verified = 1 for all active accounts
 */

require_once 'includes/db.php';

$fixed = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_verification'])) {
    try {
        // Update all active accounts to have email_verified = 1
        $stmt = Database::getConnection()->prepare("
            UPDATE users 
            SET email_verified = 1, updated_at = NOW() 
            WHERE is_active = 1 AND email_verified = 0
        ");
        $result = $stmt->execute();
        $affectedRows = $stmt->rowCount();
        
        if ($result) {
            $fixed = true;
            $message = "✅ Successfully fixed email verification for {$affectedRows} account(s)!";
        } else {
            $message = "❌ Failed to update accounts.";
        }
    } catch (Exception $e) {
        $message = "❌ Error: " . htmlspecialchars($e->getMessage());
    }
}

// Get current status
try {
    $stmt = Database::getConnection()->prepare("
        SELECT id, full_name, email, is_active, email_verified 
        FROM users 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $activeUsers = $stmt->fetchAll();
    
    $needsFix = array_filter($activeUsers, function($user) {
        return !$user['email_verified'];
    });
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeKeep - Fix Email Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f0f0f0; }
        .btn { padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 Fix Email Verification Status</h2>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $fixed ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($needsFix) && !$fixed): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Issue Found:</strong> <?php echo count($needsFix); ?> active account(s) have email_verified = 0, which prevents login.
            </div>
            
            <h3>📋 Accounts That Need Fixing:</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Active?</th>
                    <th>Email Verified?</th>
                </tr>
                <?php foreach ($needsFix as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td style="color: green; font-weight: bold;">✅ YES</td>
                    <td style="color: red; font-weight: bold;">❌ NO</td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <form method="POST" style="margin: 20px 0;">
                <button type="submit" name="fix_verification" class="btn btn-danger" 
                        onclick="return confirm('Are you sure you want to fix email verification for all active accounts?')">
                    🔧 Fix Email Verification for All Active Accounts
                </button>
            </form>
            
        <?php elseif ($fixed || empty($needsFix)): ?>
            <div class="alert alert-success">
                <strong>✅ All Good!</strong> All active accounts have proper email verification status.
            </div>
            
            <h3>📋 All Active Users:</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Active?</th>
                    <th>Email Verified?</th>
                </tr>
                <?php foreach ($activeUsers as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td style="color: green; font-weight: bold;">✅ YES</td>
                    <td style="color: green; font-weight: bold;">✅ YES</td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <div style="margin: 20px 0;">
                <a href="/safekeep-v2/auth/login.php" class="btn btn-success">
                    🔓 Try Login Again
                </a>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <p><strong>What this does:</strong></p>
        <ul>
            <li>Sets <code>email_verified = 1</code> for all active accounts</li>
            <li>Updates the <code>updated_at</code> timestamp</li>
            <li>Allows login for accounts that are active but not email verified</li>
        </ul>
        
        <p><a href="/safekeep-v2/">🏠 Back to SafeKeep</a></p>
    </div>
</body>
</html>