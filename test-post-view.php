<?php
// Simple post viewer and contact form tester
require_once './includes/db.php';
require_once './includes/functions.php';
require_once './includes/config.php';

session_start();

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

$postId = $_GET['id'] ?? 5; // Default to post 5
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
                    require_once './includes/Email.php';
                    
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
                    ";
                    
                    $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody, true);
                    
                    if ($emailSent) {
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$contactId]);
                    }
                    
                    $contactSuccess = 'Your message has been sent to the item owner. They will receive an email notification and can contact you directly.';
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
    $stmt = Database::getConnection()->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        $post = null;
    }
} catch (Exception $e) {
    $post = null;
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKeep - Test Contact Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                
                <div class="mb-4">
                    <h2>SafeKeep - Contact Form Test</h2>
                    
                    <?php if ($currentUser): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-user-check"></i> Logged in as: <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Not logged in - contact form won't work
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($post): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><i class="fas fa-<?php echo $post['type'] == 'lost' ? 'search' : 'hand-holding-heart'; ?>"></i> 
                                <?php echo ucfirst($post['type']); ?> Item: <?php echo htmlspecialchars($post['title']); ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($post['description']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($post['location']); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($post['category']); ?></p>
                            <p><strong>Date:</strong> <?php echo $post['date_lost_found']; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $post['status'] == 'approved' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </p>
                            
                            <?php if ($post['photo_path']): ?>
                                <div class="mt-3">
                                    <img src="./uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                         alt="Item photo" class="img-fluid rounded" style="max-height: 300px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-envelope"></i> Contact the Owner</h5>
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

                            <?php if ($currentUser && $post['user_id'] != $currentUser['id']): ?>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Your Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="4" 
                                                placeholder="Hi! I'm interested in this item. Is it still available? Please let me know how we can arrange to meet." required></textarea>
                                    </div>
                                    <button type="submit" name="contact_owner" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </form>
                            <?php elseif ($currentUser && $post['user_id'] == $currentUser['id']): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> This is your own post. You cannot contact yourself.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-sign-in-alt"></i> You must be logged in to contact the owner.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4>Post Not Found</h4>
                        <p>The post you're looking for doesn't exist. Try these available posts:</p>
                        
                        <?php
                        // Show available posts
                        try {
                            $stmt = Database::getConnection()->prepare("SELECT id, title, type FROM posts ORDER BY id DESC LIMIT 5");
                            $stmt->execute();
                            $posts = $stmt->fetchAll();
                            
                            if ($posts) {
                                echo "<ul>";
                                foreach ($posts as $p) {
                                    echo "<li><a href='?id={$p['id']}'>{$p['title']} ({$p['type']})</a></li>";
                                }
                                echo "</ul>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error loading posts: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="./" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to SafeKeep
                    </a>
                    <a href="./debug-deep.php" class="btn btn-info">
                        <i class="fas fa-bug"></i> Debug Tools
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>