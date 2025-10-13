<?php
/**
 * Browse Posts
 * Search and filter lost/found items
 */

declare(strict_types=1);

$pageTitle = 'Browse Items';
require_once __DIR__ . '/../includes/header.php';

// Get filter parameters
$filters = [
    'type' => $_GET['type'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get posts
$posts = Post::search($filters, $perPage, $offset);

// Get total count for pagination
$totalFilters = $filters;
$totalQuery = "SELECT COUNT(*) as total FROM posts p WHERE p.status = 'approved'";
$params = [];

if (!empty($filters['type'])) {
    $totalQuery .= " AND p.type = ?";
    $params[] = $filters['type'];
}

if (!empty($filters['category'])) {
    $totalQuery .= " AND p.category = ?";
    $params[] = $filters['category'];
}

if (!empty($filters['search'])) {
    $totalQuery .= " AND MATCH(p.title, p.description, p.location) AGAINST(? IN NATURAL LANGUAGE MODE)";
    $params[] = $filters['search'];
}

if (!empty($filters['date_from'])) {
    $totalQuery .= " AND p.date_lost_found >= ?";
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $totalQuery .= " AND p.date_lost_found <= ?";
    $params[] = $filters['date_to'];
}

$totalResult = Database::selectOne($totalQuery, $params);
$totalPosts = $totalResult['total'];
$totalPages = ceil($totalPosts / $perPage);

// Get categories for filter
$categories = Database::select("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>
            <?php if (!empty($filters['type'])): ?>
                <i class="fas fa-<?php echo $filters['type'] === 'lost' ? 'search' : 'check'; ?> me-2"></i>
                <?php echo ucfirst($filters['type']); ?> Items
            <?php else: ?>
                <i class="fas fa-list me-2"></i>Browse Items
            <?php endif; ?>
        </h2>
        <p class="text-muted">
            <?php echo number_format($totalPosts); ?> item<?php echo $totalPosts !== 1 ? 's' : ''; ?> found
            <?php if (!empty($filters['search'])): ?>
            for "<?php echo htmlspecialchars($filters['search']); ?>"
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Search & Filter</h5>
    </div>
    <div class="card-body">
        <form method="GET" id="search-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Item Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Items</option>
                        <option value="lost" <?php echo $filters['type'] === 'lost' ? 'selected' : ''; ?>>Lost Items</option>
                        <option value="found" <?php echo $filters['type'] === 'found' ? 'selected' : ''; ?>>Found Items</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                <?php echo $filters['category'] === $category['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Keywords</label>
                    <div class="input-group">
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control" 
                               placeholder="Search title, description, or location..."
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" 
                           name="date_from" 
                           id="date_from" 
                           class="form-control"
                           value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" 
                           name="date_to" 
                           id="date_to" 
                           class="form-control"
                           value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                </div>
                
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" id="clear-filters" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </button>
                    <?php if (Session::isLoggedIn()): ?>
                    <a href="/posts/create.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Post Item
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<?php if (empty($posts)): ?>
<div class="text-center py-5">
    <i class="fas fa-search fa-3x text-muted mb-3"></i>
    <h4>No items found</h4>
    <p class="text-muted">Try adjusting your search criteria or check back later for new posts.</p>
    <?php if (Session::isLoggedIn()): ?>
    <a href="/posts/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Post the First Item
    </a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($posts as $post): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 search-result-item">
            <?php if ($post['photo_path']): ?>
            <img src="<?php echo Config::get('app.url'); ?>/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                 class="card-img-top" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                 style="height: 200px; object-fit: cover;">
            <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <i class="fas fa-image fa-3x text-muted"></i>
            </div>
            <?php endif; ?>
            
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?php echo $post['type'] === 'lost' ? 'warning' : 'success'; ?> mb-2">
                        <?php echo ucfirst($post['type']); ?>
                    </span>
                    <span class="badge bg-secondary">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </span>
                </div>
                
                <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                
                <p class="card-text text-muted flex-grow-1">
                    <?php echo Utils::truncate($post['description'], 120); ?>
                </p>
                
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($post['location']); ?>
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo Utils::formatDate($post['date_lost_found']); ?>
                        </small>
                        <small class="text-muted">
                            <?php echo Utils::timeAgo($post['created_at']); ?>
                        </small>
                    </div>
                    
                    <div class="mt-2">
                        <a href="<?php echo Config::get('app.url'); ?>/posts/view.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                        <?php if ($post['is_resolved']): ?>
                        <span class="badge bg-purple ms-2">Resolved</span>
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
            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $page - 1])); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => 1])); ?>">1</a>
        </li>
        <?php if ($startPage > 2): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a>
        </li>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $page + 1])); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clear filters functionality
    document.getElementById('clear-filters').addEventListener('click', function() {
        const form = document.getElementById('search-form');
        form.reset();
        form.submit();
    });
    
    // Auto-submit on filter changes
    document.querySelectorAll('#type, #category, #date_from, #date_to').forEach(input => {
        input.addEventListener('change', function() {
            document.getElementById('search-form').submit();
        });
    });
    
    // Search on Enter
    document.getElementById('search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('search-form').submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>