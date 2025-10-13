<?php
/**
 * My Posts Page
 * Display user's own posts with management options
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

$pageTitle = 'My Posts';
$currentUser = Session::getUser();

// Handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    // Verify user owns the post
    $post = Database::selectOne("SELECT * FROM posts WHERE id = ? AND user_id = ?", [$postId, $currentUser['id']]);
    
    if (!$post) {
        $error = 'Post not found or you do not have permission to modify it.';
    } else {
        try {
            switch ($action) {
                case 'mark_found':
                    if ($post['type'] === 'lost' && $post['status'] === 'approved') {
                        Database::update('posts', ['status' => 'resolved'], 'id = ?', [$postId]);
                        Utils::redirect($_SERVER['PHP_SELF'], 'Post marked as resolved successfully.', 'success');
                    } else {
                        $error = 'Only approved lost items can be marked as found.';
                    }
                    break;

                case 'delete':
                    if (in_array($post['status'], ['pending', 'rejected'])) {
                        Database::delete('posts', 'id = ?', [$postId]);
                        Utils::redirect($_SERVER['PHP_SELF'], 'Post deleted successfully.', 'success');
                    } else {
                        $error = 'Only pending or rejected posts can be deleted.';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = 'Error processing request: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = ['user_id = ?'];
$params = [$currentUser['id']];

if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($type !== 'all') {
    $where[] = 'type = ?';
    $params[] = $type;
}

$whereClause = implode(' AND ', $where);

// Get user's posts
try {
    $posts = Database::select(
        "SELECT * FROM posts 
         WHERE {$whereClause}
         ORDER BY created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    // Get total count for pagination
    $totalPosts = Database::selectOne(
        "SELECT COUNT(*) as count FROM posts WHERE {$whereClause}",
        $params
    )['count'];

    $totalPages = ceil($totalPosts / $limit);

} catch (Exception $e) {
    $error = 'Error loading posts: ' . $e->getMessage();
    $posts = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-list me-2"></i>My Posts</h1>
        <a href="<?php echo Config::get('app.url'); ?>/posts/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Post
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Lost Items</option>
                        <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Found Items</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="my-posts.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Posts -->
    <?php if (empty($posts)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Posts Found</h4>
            <p class="text-muted">You haven't created any posts yet, or none match your current filters.</p>
            <a href="<?php echo Config::get('app.url'); ?>/posts/create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Your First Post
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($post['photo_path'])): ?>
                            <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                <div>
                                    <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                        <?php echo ucfirst($post['type']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <?php
                                $statusClasses = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'resolved' => 'info'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClasses[$post['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </div>
                            
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($post['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($post['location']); ?><br>
                                    <i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($post['date_lost_found'])); ?><br>
                                    <i class="fas fa-clock me-1"></i>Posted <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="mt-3">
                                <div class="btn-group w-100" role="group">
                                    <a href="<?php echo Config::get('app.url'); ?>/posts/browse.php?id=<?php echo $post['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <?php if ($post['status'] === 'approved' && $post['type'] === 'lost'): ?>
                                        <form method="POST" style="display: inline;" class="flex-fill">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="mark_found">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm w-100" 
                                                    onclick="return confirm('Mark this item as found?')">
                                                <i class="fas fa-check"></i> Mark Found
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($post['status'], ['pending', 'rejected'])): ?>
                                        <form method="POST" style="display: inline;" class="flex-fill">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm w-100" 
                                                    onclick="return confirm('Delete this post permanently?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php 
include '../includes/footer.php'; 
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>