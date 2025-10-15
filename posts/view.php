<?php
/**
 * View Post Details
 * Display detailed view of a lost or found item
 */

declare(strict_types=1);



// Include necessary dependencies without header
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

Session::init();

Session::requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    Utils::redirect(Config::get('app.url') . '/posts/browse.php');
}

// Get post details
$post = Database::selectOne("
    SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? AND p.status = 'approved'
", [$postId]);

if (!$post) {
    Utils::redirect(Config::get('app.url') . '/posts/browse.php', 'Post not found.');
}

// Get current user
$currentUser = Session::getUser();
$isOwner = $currentUser && $currentUser['id'] == $post['author_id'];
$isAdmin = $currentUser && $currentUser['role'] === 'admin';

// Handle contact form submission
$contactError = '';
$contactSuccess = '';

// Check for success message from redirect
if (isset($_GET['contact']) && $_GET['contact'] === 'success') {
    if (isset($_GET['email']) && $_GET['email'] === 'sent') {
        $contactSuccess = 'Your message has been sent successfully! The owner will receive an email notification.';
    } else {
        $contactSuccess = 'Your message has been logged successfully! However, email notification failed - the owner can still see your message when they check their posts.';
    }
}

// Process contact form submission (check for message field as backup since contact_owner button value sometimes gets lost)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['contact_owner']) || isset($_POST['message']))) {
    
    // Verify CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $contactError = 'Invalid security token. Please try again.';
    } elseif (!$currentUser) {
        $contactError = 'You must be logged in to contact the owner.';
    } elseif ($isOwner) {
        $contactError = 'You cannot contact yourself about your own post.';
    } else {
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $contactError = 'Please enter a message.';
        } elseif (strlen($message) < 10) {
            $contactError = 'Message must be at least 10 characters long.';
        } else {
            try {
                // Store the contact attempt in database
                $contactData = [
                    'post_id' => $postId,
                    'sender_user_id' => $currentUser['id'],
                    'sender_name' => $currentUser['full_name'],
                    'sender_email' => $currentUser['email'],
                    'message' => $message,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'email_sent' => 0
                ];
                
                Database::insert('contact_logs', $contactData);
                $insertId = Database::getConnection()->lastInsertId();
                
                // Try to send email
                $emailSent = false;
                try {
                    $postOwner = User::findById($post['author_id']);
                    
                    if ($postOwner && !empty($postOwner['email'])) {
                        if (!class_exists('Email')) {
                            require_once __DIR__ . '/../includes/Email.php';
                        }
                        
                        $emailSubject = "🔍 SafeKeep Alert: Someone is interested in your " . ucfirst($post['type']) . " item";
                        
                        // Create professional HTML email
                        $emailBody = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKeep Contact Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">🔍 SafeKeep</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">Lost & Found Community</p>
    </div>
    
    <!-- Main Content -->
    <div style="background: #fff; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px; padding: 30px;">
        
        <!-- Alert Banner -->
        <div style="background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin-bottom: 25px; border-radius: 4px;">
            <h2 style="margin: 0 0 10px 0; color: #007bff; font-size: 20px;">
                📧 Someone contacted you about your item!
            </h2>
            <p style="margin: 0; color: #555;">
                You have received a new message regarding your <strong>' . htmlspecialchars($post['type']) . '</strong> post.
            </p>
        </div>
        
        <!-- Post Details -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">
                📋 Your Post Details
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold; width: 100px;">Title:</td>
                    <td style="padding: 8px 0; color: #333;">' . htmlspecialchars($post['title']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Type:</td>
                    <td style="padding: 8px 0;">
                        <span style="background: ' . ($post['type'] === 'lost' ? '#dc3545' : '#28a745') . '; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; text-transform: uppercase;">
                            ' . ($post['type'] === 'lost' ? '❌ Lost' : '✅ Found') . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Location:</td>
                    <td style="padding: 8px 0; color: #333;">📍 ' . htmlspecialchars($post['location']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Date:</td>
                    <td style="padding: 8px 0; color: #333;">📅 ' . date('F j, Y', strtotime($post['date_lost_found'])) . '</td>
                </tr>
            </table>
        </div>
        
        <!-- Contact Information -->
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
            <h3 style="margin: 0 0 15px 0; color: #856404;">
                👤 Message From:
            </h3>
            <p style="margin: 0 0 10px 0; font-size: 16px;">
                <strong>' . htmlspecialchars($currentUser['full_name']) . '</strong>
            </p>
            <p style="margin: 0; color: #666;">
                📧 <a href="mailto:' . htmlspecialchars($currentUser['email']) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($currentUser['email']) . '</a>
            </p>
        </div>
        
        <!-- Message Content -->
        <div style="background: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 15px 0; color: #28a745;">💬 Their Message:</h3>
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0; font-style: italic; line-height: 1.7;">
                "' . nl2br(htmlspecialchars($message)) . '"
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="mailto:' . htmlspecialchars($currentUser['email']) . '?subject=Re: SafeKeep - ' . htmlspecialchars($post['title']) . '" 
               style="display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0;">
                📧 Reply via Email
            </a>
            <a href="' . Config::get('app.url') . '/posts/view.php?id=' . $postId . '" 
               style="display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0;">
                👀 View Your Post
            </a>
        </div>
        
        <!-- Tips -->
        <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #0c5460;">💡 Safety Tips:</h4>
            <ul style="margin: 0; padding-left: 20px; color: #0c5460;">
                <li>Meet in public places when exchanging lost items</li>
                <li>Verify ownership by asking specific questions about the item</li>
                <li>Trust your instincts - if something feels wrong, prioritize your safety</li>
            </ul>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #e0e0e0;">
        <p style="margin: 0 0 10px 0;">
            This email was sent by <strong>SafeKeep Lost & Found System</strong><br>
            Helping reunite people with their lost belongings 💙
        </p>
        <p style="margin: 0; opacity: 0.7;">
            <a href="' . Config::get('app.url') . '" style="color: #007bff; text-decoration: none;">Visit SafeKeep</a> | 
            <a href="' . Config::get('app.url') . '/posts/browse.php" style="color: #007bff; text-decoration: none;">Browse Posts</a>
        </p>
    </div>
    
</body>
</html>';
                        
                        $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody, true);
                        
                        if ($emailSent) {
                            Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$insertId]);
                        } else {
                            Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = 'Email send failed' WHERE id = ?", [$insertId]);
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log('SafeKeep: Email error - ' . $e->getMessage());
                }
                
                if ($emailSent) {
                    $contactSuccess = 'Your message has been sent successfully! The owner will receive an email notification.';
                } else {
                    $contactSuccess = 'Your message has been logged successfully! However, email notification failed - the owner can still see your message when they check their posts.';
                }
                
                // Redirect to prevent form resubmission and show success message
                $redirectUrl = Config::get('app.url') . '/posts/view.php?id=' . $postId . '&contact=success';
                if ($emailSent) {
                    $redirectUrl .= '&email=sent';
                } else {
                    $redirectUrl .= '&email=failed';
                }
                Utils::redirect($redirectUrl);
                
            } catch (Exception $e) {
                $contactError = 'Failed to send message. Please try again later.';
                error_log('Contact form error: ' . $e->getMessage());
            }
        }
    }
}

// Handle mark as resolved (for owners and admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_resolved'])) {
    if ($isOwner || $isAdmin) {
        Database::execute("UPDATE posts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?", [$postId]);
        
        Utils::logAuditAction($currentUser['id'], 'post_resolved', 'post', $postId);
        Utils::redirect(Config::get('app.url') . '/posts/view.php?id=' . $postId, 'Item marked as resolved!');
    }
}

$pageTitle = htmlspecialchars($post['title']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo Config::get('app.url'); ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo Config::get('app.url'); ?>/posts/browse.php">Browse Items</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <!-- Post Header -->
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="mb-1">
                                <i class="fas fa-<?php echo $post['type'] === 'lost' ? 'search' : 'check'; ?> me-2"></i>
                                <?php echo htmlspecialchars($post['title']); ?>
                            </h3>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-user me-1"></i>
                                    Posted by <?php echo htmlspecialchars($post['author_name']); ?>
                                </small>
                                <small class="ms-3">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo Utils::timeAgo($post['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'warning' : 'success'; ?> fs-6">
                                <?php echo ucfirst($post['type']); ?> Item
                            </span>
                            <?php if ($post['is_resolved']): ?>
                            <span class="badge bg-purple fs-6 ms-1">Resolved</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Post Image -->
                <?php if (!empty($post['photo_path'])): ?>
                <div class="text-center bg-light">
                    <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="img-fluid" 
                         style="max-height: 400px; object-fit: contain;">
                </div>
                <?php endif; ?>

                <!-- Post Details -->
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6><i class="fas fa-tags me-2"></i>Category</h6>
                            <p class="mb-3"><?php echo htmlspecialchars($post['category']); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <h6><i class="fas fa-map-marker-alt me-2"></i>Location</h6>
                            <p class="mb-3"><?php echo htmlspecialchars($post['location']); ?></p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6><i class="fas fa-calendar me-2"></i>Date <?php echo $post['type'] === 'lost' ? 'Lost' : 'Found'; ?></h6>
                            <p class="mb-3"><?php echo Utils::formatDate($post['date_lost_found']); ?></p>
                        </div>
                        <?php if (!empty($post['reward'])): ?>
                        <div class="col-sm-6">
                            <h6><i class="fas fa-gift me-2"></i>Reward</h6>
                            <p class="mb-3 text-success fw-bold"><?php echo htmlspecialchars($post['reward']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h6><i class="fas fa-info-circle me-2"></i>Description</h6>
                    <div class="border-start border-primary border-3 ps-3 mb-4">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    </div>

                    <!-- Owner Actions -->
                    <?php if (($isOwner || $isAdmin) && !$post['is_resolved']): ?>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-user-cog me-2"></i>Owner Actions</h6>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="mark_resolved" value="1">
                            <button type="submit" class="btn btn-success btn-sm" 
                                    onclick="return confirm('Mark this item as resolved? This action cannot be undone.')">
                                <i class="fas fa-check me-1"></i>Mark as Resolved
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Contact Owner -->
            <?php if ($currentUser && !$isOwner && !$post['is_resolved']): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Owner</h5>
                </div>
                <div class="card-body">
                    <?php if ($contactError): ?>
                    <div class="alert alert-danger alert-sm">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?php echo $contactError; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($contactSuccess): ?>
                    <div class="alert alert-success alert-sm">
                        <i class="fas fa-check-circle me-1"></i>
                        <?php echo $contactSuccess; ?>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea name="message" id="message" class="form-control" rows="4" 
                                      placeholder="Hi, I think this might be my item..." required></textarea>
                            <div class="form-text">Be specific about why you think this is your item.</div>
                        </div>
                        <button type="submit" name="contact_owner" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Send Message
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif (!$currentUser): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h6><i class="fas fa-sign-in-alt me-2"></i>Want to Contact the Owner?</h6>
                    <p class="text-muted mb-3">You need to be logged in to contact item owners.</p>
                    <a href="<?php echo Config::get('app.url'); ?>/auth/login.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a href="<?php echo Config::get('app.url'); ?>/auth/register.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Item Details Summary -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info me-2"></i>Item Summary</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst($post['type']); ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-4">Category:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($post['category']); ?></dd>
                        
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($post['location']); ?></dd>
                        
                        <dt class="col-sm-4">Date:</dt>
                        <dd class="col-sm-8"><?php echo Utils::formatDate($post['date_lost_found']); ?></dd>
                        
                        <dt class="col-sm-4">Posted:</dt>
                        <dd class="col-sm-8"><?php echo Utils::timeAgo($post['created_at']); ?></dd>
                        
                        <?php if ($post['is_resolved']): ?>
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-purple">Resolved</span>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Safety Tips -->
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Safety Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Meet in public places</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Verify ownership before returning items</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Use school email for communications</li>
                        <li><i class="fas fa-check-circle text-success me-1"></i> Report suspicious behavior</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>