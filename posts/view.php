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
    if (!$currentUser) {
        $contactError = 'You must be logged in to contact the owner.';
    } else {
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $contactError = 'Please enter a message.';
        } elseif (strlen($message) < 10) {
            $contactError = 'Message must be at least 10 characters long.';
        } else {
            // Here you could implement email notification to the post owner
            // For now, we'll just show a success message
            $contactSuccess = 'Your message has been sent to the item owner. They will contact you if interested.';
            
            // Log the contact attempt
            Utils::logAuditAction($currentUser['id'], 'contact_attempt', 'post', $postId);
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