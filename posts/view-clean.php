<?php
/**
 * View Post Details
 * Display detailed view of a lost or found item
 */

declare(strict_types=1);

// Include necessary dependencies
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    // Verify CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $contactError = 'Invalid security token. Please try again.';
    } elseif (!$currentUser) {
        $contactError = 'You must be logged in to contact the owner.';
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
                
                // Try to send email notification
                $emailSent = false;
                try {
                    // Get post owner details
                    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$post['author_id']]);
                    $postOwner = $stmt->fetch();
                    
                    if ($postOwner && !empty($postOwner['email'])) {
                        // Include Email class
                        if (!class_exists('Email')) {
                            require_once __DIR__ . '/../includes/Email.php';
                        }
                        
                        $subject = "SafeKeep: Someone contacted you about your " . ucfirst($post['type']) . " item";
                        $body = "Someone is interested in your " . $post['type'] . " item: " . $post['title'] . "\n\n";
                        $body .= "From: " . $currentUser['full_name'] . " (" . $currentUser['email'] . ")\n\n";
                        $body .= "Message:\n" . $message . "\n\n";
                        $body .= "View your post: " . Config::get('app.url') . "/posts/view.php?id=" . $postId;
                        
                        $emailSent = Email::send($postOwner['email'], $subject, $body);
                    }
                } catch (Exception $e) {
                    error_log('Email error: ' . $e->getMessage());
                }
                
                // Update email status
                if ($emailSent) {
                    Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE post_id = ? AND sender_user_id = ? ORDER BY sent_at DESC LIMIT 1", [$postId, $currentUser['id']]);
                    $contactSuccess = 'Your message has been sent successfully! The owner will receive an email notification.';
                } else {
                    $contactSuccess = 'Your message has been logged successfully! The owner can see it when they check their posts.';
                }
                
            } catch (Exception $e) {
                $contactError = 'Failed to send message. Please try again later.';
                error_log('Contact form error: ' . $e->getMessage());
            }
        }
    }
}

// Handle mark as resolved
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_resolved'])) {
    if ($isOwner || $isAdmin) {
        Database::execute("UPDATE posts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?", [$postId]);
        Utils::redirect(Config::get('app.url') . '/posts/view.php?id=' . $postId, 'Item marked as resolved!');
    }
}

// Include header
$pageTitle = $post['title'] . ' - SafeKeep';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo Config::get('app.url'); ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo Config::get('app.url'); ?>/posts/browse.php">Browse</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($post['title']); ?></li>
                </ol>
            </nav>

            <!-- Post Card -->
            <div class="card shadow-sm">
                <!-- Post Header -->
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <small class="opacity-75">
                                Posted by <?php echo htmlspecialchars($post['author_name']); ?> • 
                                <?php echo Utils::formatDate($post['created_at']); ?>
                            </small>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark fs-6">
                                <?php echo ucfirst($post['type']); ?> Item
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Image -->
                    <?php if (!empty($post['photo_path'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                             alt="Item photo" class="img-fluid rounded" style="max-height: 400px;">
                    </div>
                    <?php endif; ?>

                    <!-- Item Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($post['category']); ?></dd>
                                
                                <dt class="col-sm-4">Location:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($post['location']); ?></dd>
                                
                                <dt class="col-sm-4">Date:</dt>
                                <dd class="col-sm-8"><?php echo Utils::formatDate($post['date_lost_found']); ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <?php if ($post['is_resolved']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Resolved!</strong> This item has been returned to its owner.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    </div>

                    <!-- Contact Success/Error Messages -->
                    <?php if ($contactSuccess): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($contactSuccess); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($contactError): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($contactError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="row">
                        <!-- Owner Actions -->
                        <?php if ($isOwner): ?>
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-success">
                                        <i class="fas fa-user-check me-2"></i>You own this item
                                    </h6>
                                    <?php if (!$post['is_resolved']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                        <button type="submit" name="mark_resolved" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Mark as Resolved
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Contact Form -->
                        <?php else: ?>
                        <div class="col-md-8 mx-auto">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-envelope me-2"></i>Contact Owner
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!$post['is_resolved']): ?>
                                    <form method="POST" id="contactForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                        
                                        <div class="mb-3">
                                            <label for="message" class="form-label">Your Message</label>
                                            <textarea name="message" id="message" class="form-control" rows="4" 
                                                      placeholder="Hi! I believe this is my item. Here are the details that prove it's mine..." 
                                                      required minlength="10" maxlength="1000"></textarea>
                                            <div class="form-text">
                                                <i class="fas fa-lightbulb me-1"></i>
                                                Be specific: describe unique features, where you lost it, or provide serial numbers.
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="contact_owner" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message to Owner
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        This item has been marked as resolved and is no longer available.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Browse More Items -->
                    <div class="text-center mt-4">
                        <a href="<?php echo Config::get('app.url'); ?>/posts/browse.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Browse More Items
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter for message
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        // Add character counter
        const counter = document.createElement('div');
        counter.className = 'text-end mt-1';
        counter.innerHTML = '<small class="text-muted"><span id="charCount">0</span>/1000 characters</small>';
        messageTextarea.parentNode.appendChild(counter);
        
        const charCount = document.getElementById('charCount');
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            if (this.value.length > 800) {
                charCount.className = 'text-warning';
            } else if (this.value.length > 950) {
                charCount.className = 'text-danger';
            } else {
                charCount.className = 'text-muted';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>