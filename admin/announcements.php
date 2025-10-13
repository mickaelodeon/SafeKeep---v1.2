<?php
/**
 * Admin Announcements Management
 * Manage site announcements and notifications
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Manage Announcements';
$showAdminSidebar = true;

// Handle announcement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $adminId = Session::getUser()['id'];
    
    if ($action === 'create') {
        $title = Security::sanitizeInput($_POST['title']);
        $content = Security::sanitizeInput($_POST['content']);
        $type = $_POST['type'] ?? 'info';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($title) && !empty($content)) {
            try {
                Database::insert('announcements', [
                    'title' => $title,
                    'content' => $content,
                    'type' => $type,
                    'is_active' => $isActive,
                    'created_by' => $adminId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                Utils::redirect(Config::get('app.url') . '/admin/announcements.php', 'Announcement created successfully!', 'success');
            } catch (Exception $e) {
                $error = 'Failed to create announcement: ' . $e->getMessage();
                error_log('Announcement creation error: ' . $e->getMessage());
            }
        } else {
            $error = 'Title and content are required.';
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $title = Security::sanitizeInput($_POST['title']);
        $content = Security::sanitizeInput($_POST['content']);
        $type = $_POST['type'] ?? 'info';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($title) && !empty($content)) {
            try {
                Database::update('announcements', [
                    'title' => $title,
                    'content' => $content,
                    'type' => $type,
                    'is_active' => $isActive,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);
                Utils::redirect(Config::get('app.url') . '/admin/announcements.php', 'Announcement updated successfully!', 'success');
            } catch (Exception $e) {
                $error = 'Failed to update announcement: ' . $e->getMessage();
                error_log('Announcement update error: ' . $e->getMessage());
            }
        } else {
            $error = 'Title and content are required.';
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        try {
            $current = Database::selectOne("SELECT is_active FROM announcements WHERE id = ?", [$id]);
            if ($current) {
                $newStatus = $current['is_active'] ? 0 : 1;
                Database::update('announcements', ['is_active' => $newStatus], 'id = ?', [$id]);
                Utils::redirect(Config::get('app.url') . '/admin/announcements.php', 'Announcement status updated!', 'success');
            }
        } catch (Exception $e) {
            $error = 'Failed to update announcement status: ' . $e->getMessage();
            error_log('Announcement toggle error: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            Database::delete('announcements', 'id = ?', [$id]);
            Utils::redirect(Config::get('app.url') . '/admin/announcements.php', 'Announcement deleted successfully!', 'success');
        } catch (Exception $e) {
            $error = 'Failed to delete announcement: ' . $e->getMessage();
            error_log('Announcement delete error: ' . $e->getMessage());
        }
    }
}

// Get all announcements
try {
    $announcements = Database::select(
        "SELECT a.*, u.full_name as creator_name 
         FROM announcements a 
         LEFT JOIN users u ON a.created_by = u.id 
         ORDER BY a.created_at DESC"
    );
} catch (Exception $e) {
    $announcements = [];
    $error = 'Failed to load announcements.';
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-bullhorn me-2"></i>Manage Announcements</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-1"></i>New Announcement
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($announcements)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No announcements found</h5>
                <p class="text-muted">Create your first announcement to get started.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                <?php if (!empty($announcement['content'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $announcement['type'] === 'success' ? 'success' : ($announcement['type'] === 'warning' ? 'warning' : ($announcement['type'] === 'danger' ? 'danger' : 'info')); ?>">
                                    <?php echo ucfirst($announcement['type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $announcement['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($announcement['creator_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?php echo $announcement['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
                                            data-content="<?php echo htmlspecialchars($announcement['content']); ?>"
                                            data-type="<?php echo $announcement['type']; ?>"
                                            data-active="<?php echo $announcement['is_active']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" class="btn btn-outline-<?php echo $announcement['is_active'] ? 'warning' : 'success'; ?>" 
                                                title="<?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $announcement['is_active'] ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger">
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
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="create_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="create_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_content" class="form-label">Content</label>
                        <textarea class="form-control" id="create_content" name="content" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_type" class="form-label">Type</label>
                        <select class="form-select" id="create_type" name="type">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="create_active" name="is_active" checked>
                        <label class="form-check-label" for="create_active">
                            Active (visible to users)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Type</label>
                        <select class="form-select" id="edit_type" name="type">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_active" name="is_active">
                        <label class="form-check-label" for="edit_active">
                            Active (visible to users)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const modal = this;
    
    modal.querySelector('#edit_id').value = button.getAttribute('data-id');
    modal.querySelector('#edit_title').value = button.getAttribute('data-title');
    modal.querySelector('#edit_content').value = button.getAttribute('data-content');
    modal.querySelector('#edit_type').value = button.getAttribute('data-type');
    modal.querySelector('#edit_active').checked = button.getAttribute('data-active') === '1';
});
</script>

<?php require_once '../includes/footer.php'; ?>