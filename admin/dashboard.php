<?php
/**
 * Admin Dashboard
 * Main administrative control panel
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Admin Dashboard';
$showAdminSidebar = true;

// Get dashboard statistics
try {
    $stats = [
        'total_users' => Database::selectOne("SELECT COUNT(*) as count FROM users")['count'],
        'pending_users' => Database::selectOne("SELECT COUNT(*) as count FROM users WHERE is_active = 0")['count'],
        'total_posts' => Database::selectOne("SELECT COUNT(*) as count FROM posts")['count'],
        'pending_posts' => Database::selectOne("SELECT COUNT(*) as count FROM posts WHERE status = 'pending'")['count'],
        'approved_posts' => Database::selectOne("SELECT COUNT(*) as count FROM posts WHERE status = 'approved'")['count'],
        'total_contacts' => Database::selectOne("SELECT COUNT(*) as count FROM contact_logs")['count']
    ];

    // Recent activity
    $recentPosts = Database::select(
        "SELECT p.*, u.full_name as user_name 
         FROM posts p 
         JOIN users u ON p.user_id = u.id 
         WHERE p.status = 'pending' 
         ORDER BY p.created_at DESC 
         LIMIT 5"
    );

    $recentUsers = Database::select(
        "SELECT * FROM users 
         WHERE is_active = 0 
         ORDER BY created_at DESC 
         LIMIT 5"
    );

 } catch (Exception $e) {
    Utils::redirect(Config::get('app.url') . '/index.php', 'Error loading dashboard data.', 'error');
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Posts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_posts']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Pending Posts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_posts']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <!-- Pending Posts -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Pending Posts</h6>
                    <a href="posts.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentPosts)): ?>
                        <p class="text-muted">No pending posts.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>User</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPosts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                                    <?php echo ucfirst($post['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['user_name']); ?></td>
                                            <td><?php echo date('M j', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <a href="posts.php?id=<?php echo $post['id']; ?>" class="btn btn-xs btn-outline-primary">Review</a>
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

        <!-- Pending Users -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Pending User Approvals</h6>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentUsers)): ?>
                        <p class="text-muted">No pending user approvals.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M j', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="users.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-outline-primary">Review</a>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>