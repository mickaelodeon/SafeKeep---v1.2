<?php
/**
 * Post View with Working Contact Form
 * Updated for posts directory structure
 */

session_start();

// Include required files
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    header('Location: /safekeep-v2/');
    exit;
}

// Get current user (simulate login if needed)
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
} else {
    // Auto-login as your user for testing
    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['johnmichaeleborda79@gmail.com']);
    $currentUser = $stmt->fetch();
    if ($currentUser) {
        $_SESSION['user_id'] = $currentUser['id'];
        $_SESSION['user_name'] = $currentUser['full_name'];
    }
}

$contactSuccess = '';
$contactError = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    if (!$currentUser) {
        $contactError = 'You must be logged in to contact the owner.';
    } else {
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $contactError = 'Please enter a message.';
        } elseif (strlen($message) < 10) {
            $contactError = 'Message must be at least 10 characters long.';
        } else {
            try {
                // Store the contact attempt in database with correct structure
                $contactData = [
                    'post_id' => $postId,
                    'sender_user_id' => $currentUser['id'],
                    'sender_name' => $currentUser['full_name'],
                    'sender_email' => $currentUser['email'],
                    'message' => $message,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'email_sent' => 0
                ];
                
                $contactId = Database::insert('contact_logs', $contactData);
                
                if ($contactId) {
                    // Try to send email notification
                    require_once '../includes/Email.php';
                    
                    // Get post details
                    $stmt = Database::getConnection()->prepare("SELECT * FROM posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch();
                    
                    // Get post owner
                    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$post['user_id']]);
                    $postOwner = $stmt->fetch();
                    
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
                            <li>Date: " . $post['date_lost_found'] . "</li>
                        </ul>
                        <p>You can reply to this email directly to contact the interested person.</p>
                        <p><a href='http://localhost/safekeep-v2/posts/view.php?id=" . $postId . "'>View your post on SafeKeep</a></p>
                    ";
                    
                    $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody, true);
                    
                    if ($emailSent) {
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$contactId]);
                        $contactSuccess = 'Your message has been sent to the item owner. They will receive an email notification and can contact you directly.';
                    } else {
                        Database::execute("UPDATE contact_logs SET email_error = 'Failed to send email notification' WHERE id = ?", [$contactId]);
                        $contactSuccess = 'Your message has been logged, but email notification failed. The owner can still see your message when they check their posts.';
                    }
                } else {
                    $contactError = 'Failed to send message. Please try again later.';
                }
                
            } catch (Exception $e) {
                $contactError = 'Failed to send message: ' . $e->getMessage();
                error_log('Contact form error: ' . $e->getMessage());
            }
        }
    }
}

// Get post details
try {
    $stmt = Database::getConnection()->prepare("SELECT p.*, u.full_name as owner_name FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: /safekeep-v2/posts/browse.php');
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    header('Location: /safekeep-v2/posts/browse.php');
    exit;
}

$isOwner = $currentUser && $post['user_id'] == $currentUser['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKeep - <?php echo htmlspecialchars($post['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .post-card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .contact-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/safekeep-v2/">
                <i class="fas fa-shield-alt"></i> SafeKeep
            </a>
            <?php if ($currentUser): ?>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>
                </span>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/safekeep-v2/">Home</a></li>
                <li class="breadcrumb-item"><a href="/safekeep-v2/posts/browse.php">Browse Items</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['title']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <!-- Post Details Card -->
                <div class="card post-card mb-4">
                    <div class="card-header bg-<?php echo $post['type'] == 'lost' ? 'danger' : 'success'; ?> text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-<?php echo $post['type'] == 'lost' ? 'search' : 'hand-holding-heart'; ?>"></i>
                            <?php echo ucfirst($post['type']); ?> Item: <?php echo htmlspecialchars($post['title']); ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Description</h5>
                                <p class="lead"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <h6><i class="fas fa-map-marker-alt text-danger"></i> Location</h6>
                                        <p><?php echo htmlspecialchars($post['location']); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <h6><i class="fas fa-calendar text-primary"></i> Date</h6>
                                        <p><?php echo date('M d, Y', strtotime($post['date_lost_found'])); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <h6><i class="fas fa-tags text-info"></i> Category</h6>
                                        <p><?php echo htmlspecialchars($post['category']); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <h6><i class="fas fa-info-circle text-success"></i> Status</h6>
                                        <span class="badge bg-<?php echo $post['status'] == 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($post['photo_path']): ?>
                            <div class="col-md-4">
                                <h6>Photo</h6>
                                <img src="/safekeep-v2/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                     alt="Item photo" class="img-fluid rounded shadow">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        <small class="text-muted">
                            Posted by <strong><?php echo htmlspecialchars($post['owner_name']); ?></strong> 
                            on <?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Contact Form -->
                <?php if (!$isOwner && $post['status'] == 'approved' && !$post['is_resolved']): ?>
                <div class="card">
                    <div class="card-header contact-section">
                        <h5 class="mb-0"><i class="fas fa-envelope"></i> Contact the Owner</h5>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($contactSuccess): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> <?php echo $contactSuccess; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($contactError): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $contactError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($currentUser): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Your Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" 
                                            placeholder="Hi! I'm interested in this item. Is it still available? Please let me know how we can arrange to meet." required></textarea>
                                    <div class="form-text">Minimum 10 characters required.</div>
                                </div>
                                <button type="submit" name="contact_owner" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-sign-in-alt"></i> Login Required</h6>
                                <p class="mb-2">You must be logged in to contact the owner.</p>
                                <a href="/safekeep-v2/auth/login.php" class="btn btn-primary btn-sm">Login</a>
                                <a href="/safekeep-v2/auth/register.php" class="btn btn-outline-primary btn-sm">Register</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($isOwner): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <h5>This is your post</h5>
                        <p class="text-muted">You cannot contact yourself about your own item.</p>
                    </div>
                </div>
                <?php elseif ($post['is_resolved']): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Item Resolved</h5>
                        <p class="text-muted">This item has been marked as resolved.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Back Navigation -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <a href="/safekeep-v2/posts/browse.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Browse
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>