<?php
/**
 * Announcements Page
 * Display system announcements and important notices
 */

declare(strict_types=1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$pageTitle = 'Announcements';

// Get announcements
try {
    $announcements = Database::select(
        "SELECT a.*, u.full_name as author_name 
         FROM announcements a
         LEFT JOIN users u ON a.created_by = u.id
         WHERE a.is_active = 1
         ORDER BY a.created_at DESC"
    );
} catch (Exception $e) {
    $announcements = [];
    $error = 'Unable to load announcements at this time.';
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-bullhorn me-2"></i>Announcements</h1>
        <?php if (Session::isAdmin()): ?>
            <a href="<?php echo Config::get('app.url'); ?>/admin/announcements.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Manage Announcements
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($announcements)): ?>
        <div class="text-center py-5">
            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Announcements</h4>
            <p class="text-muted">There are currently no active announcements.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($announcements as $announcement): ?>
                <div class="col-12 mb-4">
                    <div class="card border-<?php 
                        $type = $announcement['type'] ?? 'info';
                        echo $type === 'danger' ? 'danger' : ($type === 'warning' ? 'warning' : ($type === 'success' ? 'success' : 'info'));
                    ?>">

                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h5>
                                    <span class="badge bg-<?php 
                                        $type = $announcement['type'] ?? 'info';
                                        echo $type === 'danger' ? 'danger' : ($type === 'warning' ? 'warning' : ($type === 'success' ? 'success' : 'primary'));
                                    ?>">
                                        <?php echo ucfirst($type); ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($announcement['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="card-text">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                            
                            <?php if (!empty($announcement['author_name'])): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        Posted by <?php echo htmlspecialchars($announcement['author_name']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>