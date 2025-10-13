<?php
/**
 * SafeKeep Landing Page
 * Main homepage with recent posts and search functionality
 */

declare(strict_types=1);

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

// Get recent approved posts
$recentLostPosts = Post::search(['type' => 'lost'], 6);
$recentFoundPosts = Post::search(['type' => 'found'], 6);

// Get categories for quick navigation
$categories = Database::select("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");

// Get recent announcements
$announcements = Database::select(
    "SELECT * FROM announcements 
     WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) 
     ORDER BY created_at DESC LIMIT 3"
);

// Statistics
$stats = [
    'total_posts' => Database::count('posts', "status = 'approved'"),
    'lost_items' => Database::count('posts', "type = 'lost' AND status = 'approved'"),
    'found_items' => Database::count('posts', "type = 'found' AND status = 'approved'"),
    'resolved_items' => Database::count('posts', "is_resolved = 1 AND status = 'approved'")
];
?>

<!-- Hero Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="bg-primary text-white rounded-3 p-5 mb-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-shield-alt me-3"></i>
                        Welcome to <?php echo Config::get('app.name'); ?>
                    </h1>
                    <p class="lead mb-4">
                        Your school's trusted lost and found system. Report lost items, post found items, 
                        and help reunite students with their belongings safely and efficiently.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (Session::isLoggedIn()): ?>
                        <a href="posts/create.php" class="btn btn-light btn-lg">
                            <i class="fas fa-plus me-2"></i>Post an Item
                        </a>
                        <a href="posts/browse.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-search me-2"></i>Search Items
                        </a>
                        <?php else: ?>
                        <a href="auth/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-search-location display-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Search -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-search me-2"></i>Quick Search</h5>
                <form action="posts/browse.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="type" class="form-select">
                            <option value="">All Items</option>
                            <option value="lost">Lost Items</option>
                            <option value="found">Found Items</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search keywords...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-5">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="fas fa-list-alt fa-2x mb-2"></i>
                <h4><?php echo number_format($stats['total_posts']); ?></h4>
                <small>Total Posts</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="fas fa-search fa-2x mb-2"></i>
                <h4><?php echo number_format($stats['lost_items']); ?></h4>
                <small>Lost Items</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="fas fa-check fa-2x mb-2"></i>
                <h4><?php echo number_format($stats['found_items']); ?></h4>
                <small>Found Items</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="fas fa-handshake fa-2x mb-2"></i>
                <h4><?php echo number_format($stats['resolved_items']); ?></h4>
                <small>Reunited</small>
            </div>
        </div>
    </div>
</div>

<!-- Recent Announcements -->
<?php if (!empty($announcements)): ?>
<div class="row mb-5">
    <div class="col-12">
        <h3><i class="fas fa-bullhorn me-2"></i>Announcements</h3>
        <?php foreach ($announcements as $announcement): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
            <small class="text-muted">
                <?php echo Utils::formatDate($announcement['created_at']); ?>
            </small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
        <a href="announcements.php" class="btn btn-outline-primary btn-sm">View All Announcements</a>
    </div>
</div>
<?php endif; ?>

<!-- Recent Posts -->
<div class="row">
    <!-- Recent Lost Items -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Recent Lost Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentLostPosts)): ?>
                <p class="text-muted">No lost items posted recently.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentLostPosts as $post): ?>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h6>
                                <p class="mb-1 text-muted small">
                                    <?php echo Utils::truncate($post['description'], 80); ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($post['location']); ?> • 
                                    <?php echo Utils::timeAgo($post['created_at']); ?>
                                </small>
                            </div>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($post['category']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="posts/browse.php?type=lost" class="btn btn-warning btn-sm">
                    View All Lost Items <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Found Items -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check me-2"></i>Recent Found Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentFoundPosts)): ?>
                <p class="text-muted">No found items posted recently.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentFoundPosts as $post): ?>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h6>
                                <p class="mb-1 text-muted small">
                                    <?php echo Utils::truncate($post['description'], 80); ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($post['location']); ?> • 
                                    <?php echo Utils::timeAgo($post['created_at']); ?>
                                </small>
                            </div>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($post['category']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="posts/browse.php?type=found" class="btn btn-success btn-sm">
                    View All Found Items <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<div class="row mb-4">
    <div class="col-12">
        <h3><i class="fas fa-th-large me-2"></i>Browse by Category</h3>
        <div class="row">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="posts/browse.php?category=<?php echo urlencode($category['name']); ?>" 
                   class="card text-decoration-none h-100 hover-shadow">
                    <div class="card-body text-center">
                        <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?> fa-2x text-primary mb-2"></i>
                        <h6 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h6>
                        <?php if ($category['description']): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="row mb-5">
    <div class="col-12">
        <h3><i class="fas fa-question-circle me-2"></i>How It Works</h3>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-center">
                    <div class="card-body">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-plus fa-lg"></i>
                        </div>
                        <h5>1. Register</h5>
                        <p class="text-muted">Create an account with your school email to get started.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-center">
                    <div class="card-body">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-plus fa-lg"></i>
                        </div>
                        <h5>2. Post Items</h5>
                        <p class="text-muted">Report lost items or post items you've found on campus.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-center">
                    <div class="card-body">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-handshake fa-lg"></i>
                        </div>
                        <h5>3. Connect</h5>
                        <p class="text-muted">Use our secure contact form to connect with other users safely.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    transition: all 0.2s ease;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>