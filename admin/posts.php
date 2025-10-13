<?php
/**
 * Admin Posts Management
 * Approve, reject, and manage posts
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Manage Posts';
$showAdminSidebar = true;

// Handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);
    $adminId = Session::getUser()['id'];

    try {
        switch ($action) {
            case 'approve':
                if (Post::approve($postId, $adminId)) {
                    Utils::logAuditAction($adminId, 'APPROVE_POST', 'posts', $postId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Post approved successfully.', 'success');
                } else {
                    $error = 'Failed to approve post.';
                }
                break;

            case 'reject':
                $reason = $_POST['reason'] ?? '';
                if (Post::reject($postId, $adminId, $reason)) {
                    Utils::logAuditAction($adminId, 'REJECT_POST', 'posts', $postId, null, ['reason' => $reason]);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Post rejected successfully.', 'success');
                } else {
                    $error = 'Failed to reject post.';
                }
                break;

            case 'delete':
                if (Database::delete('posts', 'id = ?', [$postId])) {
                    Utils::logAuditAction($adminId, 'DELETE_POST', 'posts', $postId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Post deleted successfully.', 'success');
                } else {
                    $error = 'Failed to delete post.';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Error processing request: ' . $e->getMessage();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[] = 'p.status = ?';
    $params[] = $status;
}

if ($type !== 'all') {
    $where[] = 'p.type = ?';
    $params[] = $type;
}

$whereClause = implode(' AND ', $where);

// Get posts with user information
try {
    $posts = Database::select(
        "SELECT p.*, u.full_name as user_name, u.email as user_email,
                a.full_name as approver_name
         FROM posts p
         JOIN users u ON p.user_id = u.id
         LEFT JOIN users a ON p.approved_by = a.id
         WHERE {$whereClause}
         ORDER BY p.created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    // Get total count for pagination
    $totalPosts = Database::selectOne(
        "SELECT COUNT(*) as count FROM posts p JOIN users u ON p.user_id = u.id WHERE {$whereClause}",
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

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Posts</h1>
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
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                        <a href="posts.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Posts Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($posts)): ?>
                <p class="text-muted text-center py-4">No posts found matching your criteria.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($post['photo_path'])): ?>
                                                <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                                     class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" 
                                                     alt="Item image">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($post['description'], 0, 50)) . '...'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                            <?php echo ucfirst($post['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClasses[$post['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($post['user_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($post['user_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal"
                                                    data-title="<?php echo htmlspecialchars($post['title']); ?>"
                                                    data-description="<?php echo htmlspecialchars($post['description']); ?>"
                                                    data-location="<?php echo htmlspecialchars($post['location']); ?>"
                                                    data-date="<?php echo date('F j, Y', strtotime($post['date_lost_found'])); ?>"
                                                    data-image="<?php echo htmlspecialchars($post['photo_path'] ?? ''); ?>"
                                                    data-type="<?php echo htmlspecialchars($post['type']); ?>"
                                                    data-user="<?php echo htmlspecialchars($post['user_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($post['user_email']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($post['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Approve this post?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal"
                                                        data-post-id="<?php echo $post['id']; ?>"
                                                        data-post-title="<?php echo htmlspecialchars($post['title']); ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Delete this post permanently?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>


                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewModalImage" class="mb-3" style="display: none;">
                    <img id="viewModalImg" src="" class="img-fluid" alt="Item image">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> <span id="viewModalType" class="badge"></span></p>
                        <p><strong>Location:</strong> <span id="viewModalLocation"></span></p>
                        <p><strong>Date:</strong> <span id="viewModalDate"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Posted by:</strong> <span id="viewModalUser"></span></p>
                        <p><strong>Email:</strong> <span id="viewModalEmail"></span></p>
                    </div>
                </div>
                <hr>
                <p><strong>Description:</strong></p>
                <div id="viewModalDescription"></div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="post_id" id="rejectPostId">
                    
                    <p>Are you sure you want to reject "<strong id="rejectPostTitle"></strong>"?</p>
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="rejectReason" name="reason" rows="3" 
                                  placeholder="Explain why this post is being rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle view modal
document.getElementById('viewModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    
    // Extract data from button attributes
    const title = button.getAttribute('data-title');
    const description = button.getAttribute('data-description');
    const location = button.getAttribute('data-location');
    const date = button.getAttribute('data-date');
    const image = button.getAttribute('data-image');
    const type = button.getAttribute('data-type');
    const user = button.getAttribute('data-user');
    const email = button.getAttribute('data-email');
    
    // Update modal content
    document.getElementById('viewModalTitle').textContent = title;
    document.getElementById('viewModalDescription').innerHTML = description.replace(/\n/g, '<br>');
    document.getElementById('viewModalLocation').textContent = location;
    document.getElementById('viewModalDate').textContent = date;
    document.getElementById('viewModalType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('viewModalType').className = 'badge bg-' + (type === 'lost' ? 'warning' : 'success');
    document.getElementById('viewModalUser').textContent = user;
    document.getElementById('viewModalEmail').textContent = email;
    
    // Handle image
    if (image) {
        document.getElementById('viewModalImage').style.display = 'block';
        document.getElementById('viewModalImg').src = '<?php echo Config::get('app.url'); ?>/uploads/' + image;
    } else {
        document.getElementById('viewModalImage').style.display = 'none';
    }
});

// Handle reject modal
document.getElementById('rejectModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    
    const postId = button.getAttribute('data-post-id');
    const postTitle = button.getAttribute('data-post-title');
    
    document.getElementById('rejectPostId').value = postId;
    document.getElementById('rejectPostTitle').textContent = postTitle;
    document.getElementById('rejectReason').value = ''; // Clear previous reason
});
</script>

<?php include '../includes/footer.php'; ?>