<?php
/**
 * User Profile Page
 * Display and edit user profile information
 */

declare(strict_types=1);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Start output buffering to handle potential redirects
ob_start();

// Initialize session
Session::init();

// Require login
Session::requireLogin();

$pageTitle = 'My Profile';
$user = Session::getUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $fullName = trim($_POST['full_name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate full name
    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($fullName) > 100) {
        $errors[] = 'Full name must be less than 100 characters.';
    }

    // Validate password change if requested
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        try {
            $updateData = ['full_name' => $fullName];
            
            if (!empty($newPassword)) {
                $updateData['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            if (Database::update('users', $updateData, 'id = ?', [$user['id']])) {
                // Update session with new name
                $_SESSION['user']['full_name'] = $fullName;
                Utils::redirect($_SERVER['PHP_SELF'], 'Profile updated successfully.', 'success');
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Get user's posts
try {
    $userPosts = Database::select(
        "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
        [$user['id']]
    );
} catch (Exception $e) {
    $userPosts = [];
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <div class="form-text">Email address cannot be changed.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Status</label>
                            <div>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'warning'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Pending Approval'; ?>
                                </span>
                                <span class="badge bg-<?php echo $user['email_verified'] ? 'success' : 'warning'; ?> ms-2">
                                    <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                                </span>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-primary ms-2">Administrator</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <h6>Change Password</h6>
                        <p class="text-muted">Leave blank to keep current password.</p>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Account Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Account Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Total Posts:</span>
                        <strong><?php echo count($userPosts); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Member Since:</span>
                        <strong><?php echo date('M Y', strtotime($user['created_at'])); ?></strong>
                    </div>
                    <?php if (!empty($user['last_login'])): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Last Login:</span>
                            <strong><?php echo date('M j, Y', strtotime($user['last_login'])); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Posts</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($userPosts)): ?>
                        <p class="text-muted">No posts yet.</p>
                        <a href="<?php echo Config::get('app.url'); ?>/posts/create.php" class="btn btn-sm btn-primary">Create First Post</a>
                    <?php else: ?>
                        <?php foreach (array_slice($userPosts, 0, 5) as $post): ?>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'danger' : 'success'; ?> me-2">
                                    <?php echo ucfirst($post['type']); ?>
                                </span>
                                <span class="flex-grow-1"><?php echo htmlspecialchars($post['title']); ?></span>
                                <small class="text-muted"><?php echo date('M j', strtotime($post['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3">
                            <a href="<?php echo Config::get('app.url'); ?>/posts/browse.php?user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">View All Posts</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>