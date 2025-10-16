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
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'email_sent' => 0
                    // Note: sent_at will be auto-populated by database with current_timestamp()
                ];
                
                Database::insert('contact_logs', $contactData);
                
                // Try to send email notification to post owner
                $postOwner = User::findById($post['author_id']);
                if ($postOwner && $postOwner['email']) {
                    $emailSubject = "SafeKeep: Someone is interested in your " . ucfirst($post['type']) . " item";
                    $emailBody = "
                        <h3>Someone contacted you about your post: " . htmlspecialchars($post['title']) . "</h3>
                        <p><strong>From:</strong> " . htmlspecialchars($currentUser['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>
                        <p><strong>Message:</strong></p>
                        <div style='background:#f8f9fa; padding:15px; border-left:4px solid #007bff; margin:10px 0;'>
                            " . nl2br(htmlspecialchars($message)) . "
                        </div>
                        <p><strong>Post Details:</strong></p>
                        <ul>
                            <li>Title: " . htmlspecialchars($post['title']) . "</li>
                            <li>Type: " . ucfirst($post['type']) . " Item</li>
                            <li>Location: " . htmlspecialchars($post['location']) . "</li>
                            <li>Date: " . Utils::formatDate($post['date_lost_found']) . "</li>
                        </ul>
                        <p>You can reply to this email directly to contact the interested person.</p>
                        <p><a href='" . Config::get('app.url') . "/posts/view.php?id=" . $postId . "'>View your post on SafeKeep</a></p>
                    ";
                    
                    $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody, true);
                    
                    if ($emailSent) {
                        // Update contact log to mark email as sent
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE post_id = ? AND sender_user_id = ? ORDER BY sent_at DESC LIMIT 1", [$postId, $currentUser['id']]);
                    } else {
                        // Log email error if sending failed
                        Database::execute("UPDATE contact_logs SET email_error = 'Failed to send email notification' WHERE post_id = ? AND sender_user_id = ? ORDER BY sent_at DESC LIMIT 1", [$postId, $currentUser['id']]);
                    }
                }
                
                $contactSuccess = 'Your message has been sent to the item owner. They will receive an email notification and can contact you directly.';
                
                // Log the contact attempt for audit
                Utils::logAuditAction($currentUser['id'], 'contact_attempt', 'post', $postId);
                
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

<!-- Enhanced CSS for better UI -->
<style>
    .post-image {
        cursor: pointer;
        transition: transform 0.2s ease;
        border-radius: 8px;
    }
    .post-image:hover {
        transform: scale(1.02);
    }
    .card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        transition: box-shadow 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    }
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.8em;
    }
    .btn {
        border-radius: 6px;
        font-weight: 500;
    }
    .alert {
        border-radius: 8px;
        border: none;
    }
    .contact-form {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        padding: 1.5rem;
    }
    .social-share {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }
    @media (max-width: 768px) {
        .post-image {
            max-height: 250px;
        }
    }
</style>

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

                <!-- Post Image with Zoom -->
                <?php if (!empty($post['photo_path'])): ?>
                <div class="text-center bg-light p-3" style="border-radius: 8px;">
                    <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="img-fluid post-image" 
                         data-bs-toggle="modal" 
                         data-bs-target="#imageModal"
                         style="max-height: 400px; object-fit: contain; cursor: pointer;"
                         loading="lazy">
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-search-plus me-1"></i>Click to enlarge
                        </small>
                    </div>
                </div>

                <!-- Image Zoom Modal -->
                <div class="modal fade" id="imageModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="img-fluid">
                            </div>
                        </div>
                    </div>
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

                    <!-- Social Share & Actions -->
                    <div class="social-share mb-4">
                        <h6 class="mb-3"><i class="fas fa-share-alt me-2"></i>Share This Post</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-primary btn-sm" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook-f me-1"></i>Facebook
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter me-1"></i>Twitter
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="shareOnWhatsApp()">
                                <i class="fab fa-whatsapp me-1"></i>WhatsApp
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                                <i class="fas fa-link me-1"></i>Copy Link
                            </button>
                            <button class="btn btn-outline-dark btn-sm" onclick="printPost()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>

                    <!-- Owner Actions -->
                    <?php if (($isOwner || $isAdmin) && !$post['is_resolved']): ?>
                    <div class="alert alert-info border-0">
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
            <!-- Debug Info (remove this after fixing) -->
            <?php if (false): // Set to true to see debug info ?>
            <div class="alert alert-info">
                <small>
                    Debug: currentUser=<?php echo $currentUser ? 'Yes' : 'No'; ?>, 
                    isOwner=<?php echo $isOwner ? 'Yes' : 'No'; ?>, 
                    is_resolved=<?php echo isset($post['is_resolved']) ? ($post['is_resolved'] ? 'Yes' : 'No') : 'Not set'; ?>
                </small>
            </div>
            <?php endif; ?>
            
            <!-- Contact Owner -->
            <?php if ($currentUser && !$isOwner && (!isset($post['is_resolved']) || !$post['is_resolved'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Owner</h5>
                </div>
                <div class="card-body">
                    <?php if ($contactError): ?>
                    <div class="alert alert-danger alert-sm border-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $contactError; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($contactSuccess): ?>
                    <div class="alert alert-success alert-sm border-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $contactSuccess; ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                The owner has been notified and will contact you via email.
                            </small>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="contact-form">
                        <form method="POST" id="contactForm">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                            <div class="mb-3">
                                <label for="message" class="form-label fw-semibold">Your Message</label>
                                <textarea name="message" id="message" class="form-control" rows="5" 
                                          placeholder="Hi! I believe this is my item. Here are the details that prove it's mine..." 
                                          required maxlength="1000"></textarea>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Be specific: describe unique features, where you lost it, or provide serial numbers.
                                </div>
                                <div class="text-end mt-1">
                                    <small class="text-muted">
                                        <span id="charCount">0</span>/1000 characters
                                    </small>
                                </div>
                            </div>
                            <button type="submit" name="contact_owner" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message to Owner
                            </button>
                        </form>
                    </div>
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
            <?php elseif ($isOwner): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                    <h5>This is your post</h5>
                    <p class="text-muted">You cannot contact yourself about your own item.</p>
                </div>
            </div>
            <?php elseif (isset($post['is_resolved']) && $post['is_resolved']): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>Item Resolved</h5>
                    <p class="text-muted">This item has been marked as resolved.</p>
                </div>
            </div>
            <?php else: ?>
            <!-- Fallback Contact Form - Something went wrong with the main condition -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Owner</h5>
                </div>
                <div class="card-body">
                    <div class="contact-form">
                        <form method="POST" id="contactForm">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                            <div class="mb-3">
                                <label for="message" class="form-label fw-semibold">Your Message</label>
                                <textarea name="message" id="message" class="form-control" rows="5" 
                                          placeholder="Hi! I believe this is my item. Here are the details that prove it's mine..." 
                                          required maxlength="1000"></textarea>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Be specific: describe unique features, where you lost it, or provide serial numbers.
                                </div>
                                <div class="text-end mt-1">
                                    <small class="text-muted">
                                        <span id="charCount">0</span>/1000 characters
                                    </small>
                                </div>
                            </div>
                            <button type="submit" name="contact_owner" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message to Owner
                            </button>
                        </form>
                    </div>
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

<!-- Enhanced JavaScript -->
<script>
// Character counter for contact form
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageTextarea && charCount) {
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // Change color based on character count
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

// Social sharing functions
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?php echo addslashes($post['title']); ?>');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Found this <?php echo $post['type']; ?> item on SafeKeep: <?php echo addslashes($post['title']); ?>');
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Check out this <?php echo $post['type']; ?> item on SafeKeep: <?php echo addslashes($post['title']); ?>');
    window.open(`https://wa.me/?text=${text} ${url}`, '_blank');
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        // Show success message
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = window.location.href;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        alert('Link copied to clipboard!');
    });
}

function printPost() {
    // Create print-friendly version
    const printWindow = window.open('', '_blank');
    const postContent = `
        <html>
        <head>
            <title><?php echo addslashes($post['title']); ?> - SafeKeep</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
                .badge { background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; }
                .details { margin: 20px 0; }
                .details dt { font-weight: bold; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
                img { max-width: 100%; height: auto; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo addslashes($post['title']); ?></h1>
                <p>Posted by <?php echo addslashes($post['author_name']); ?> • <?php echo Utils::formatDate($post['created_at']); ?></p>
                <span class="badge"><?php echo ucfirst($post['type']); ?> Item</span>
            </div>
            
            <?php if (!empty($post['photo_path'])): ?>
            <div style="text-align: center; margin: 20px 0;">
                <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" alt="Item photo">
            </div>
            <?php endif; ?>
            
            <div class="details">
                <dl>
                    <dt>Category:</dt>
                    <dd><?php echo htmlspecialchars($post['category']); ?></dd>
                    
                    <dt>Location:</dt>
                    <dd><?php echo htmlspecialchars($post['location']); ?></dd>
                    
                    <dt>Date <?php echo $post['type'] === 'lost' ? 'Lost' : 'Found'; ?>:</dt>
                    <dd><?php echo Utils::formatDate($post['date_lost_found']); ?></dd>
                    
                    <?php if (!empty($post['reward'])): ?>
                    <dt>Reward:</dt>
                    <dd><?php echo htmlspecialchars($post['reward']); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            
            <div>
                <h3>Description:</h3>
                <p><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
            </div>
            
            <div class="footer">
                <p>Contact: <?php echo htmlspecialchars($post['author_name']); ?></p>
                <p>SafeKeep - Lost & Found System | Printed on <?php echo date('F j, Y g:i A'); ?></p>
                <p>Original post: <?php echo Config::get('app.url'); ?>/posts/view.php?id=<?php echo $postId; ?></p>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(postContent);
    printWindow.document.close();
    printWindow.print();
}

// Enhanced form validation
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    const message = document.getElementById('message').value.trim();
    
    if (message.length < 10) {
        e.preventDefault();
        alert('Please write a more detailed message (at least 10 characters).');
        return false;
    }
    
    if (message.length < 20) {
        if (!confirm('Your message is quite short. Are you sure you want to send it?')) {
            e.preventDefault();
            return false;
        }
    }
});

// Add smooth scrolling for better UX
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href'))?.scrollIntoView({
            behavior: 'smooth'
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>