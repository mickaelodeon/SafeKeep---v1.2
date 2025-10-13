<?php
/**
 * Admin Users Management
 * Manage user accounts and permissions
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Manage Users';
$showAdminSidebar = true;

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $adminId = Session::getUser()['id'];

    try {
        switch ($action) {
            case 'activate':
                if (Database::update('users', ['is_active' => 1], 'id = ?', [$userId])) {
                    Utils::logAuditAction($adminId, 'ACTIVATE_USER', 'users', $userId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'User activated successfully.', 'success');
                } else {
                    $error = 'Failed to activate user.';
                }
                break;

            case 'deactivate':
                if (Database::update('users', ['is_active' => 0], 'id = ?', [$userId])) {
                    Utils::logAuditAction($adminId, 'DEACTIVATE_USER', 'users', $userId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'User deactivated successfully.', 'success');
                } else {
                    $error = 'Failed to deactivate user.';
                }
                break;

            case 'make_admin':
                if (Database::update('users', ['role' => 'admin'], 'id = ?', [$userId])) {
                    Utils::logAuditAction($adminId, 'PROMOTE_ADMIN', 'users', $userId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'User promoted to admin successfully.', 'success');
                } else {
                    $error = 'Failed to promote user.';
                }
                break;

            case 'remove_admin':
                if (Database::update('users', ['role' => 'user'], 'id = ?', [$userId])) {
                    Utils::logAuditAction($adminId, 'DEMOTE_ADMIN', 'users', $userId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Admin privileges removed successfully.', 'success');
                } else {
                    $error = 'Failed to remove admin privileges.';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Error processing request: ' . $e->getMessage();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$role = $_GET['role'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[] = 'is_active = ?';
    $params[] = $status === 'active' ? 1 : 0;
}

if ($role !== 'all') {
    $where[] = 'role = ?';
    $params[] = $role;
}

$whereClause = implode(' AND ', $where);

// Get users
try {
    $users = Database::select(
        "SELECT * FROM users 
         WHERE {$whereClause}
         ORDER BY created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    // Get total count for pagination
    $totalUsers = Database::selectOne(
        "SELECT COUNT(*) as count FROM users WHERE {$whereClause}",
        $params
    )['count'];

    $totalPages = ceil($totalUsers / $limit);

} catch (Exception $e) {
    $error = 'Error loading users: ' . $e->getMessage();
    $users = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Users</h1>
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
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Users</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admins</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <p class="text-muted text-center py-4">No users found matching your criteria.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'warning'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if ($user['email_verified']): ?>
                                            <span class="badge bg-info ms-1">Verified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] != Session::getUser()['id']): // Can't modify own account ?>
                                            <div class="btn-group" role="group">
                                                <?php if ($user['is_active']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" 
                                                                onclick="return confirm('Deactivate this user?')">
                                                            <i class="fas fa-user-slash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" 
                                                                onclick="return confirm('Activate this user?')">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] === 'user'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="make_admin">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary" 
                                                                onclick="return confirm('Promote this user to admin?')">
                                                            <i class="fas fa-user-cog"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($user['role'] === 'admin'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="remove_admin">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Remove admin privileges from this user?')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Current User</small>
                                        <?php endif; ?>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&role=<?php echo $role; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&role=<?php echo $role; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&role=<?php echo $role; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>