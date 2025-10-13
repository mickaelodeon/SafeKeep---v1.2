<?php
/**
 * Common Header Template
 * Bootstrap 5 responsive header with navigation
 */

declare(strict_types=1);

// Start output buffering to prevent "headers already sent" errors
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

Session::init();
$currentUser = Session::getUser();
$flashMessage = Utils::getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo Config::get('app.name'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo Config::get('app.url'); ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta tags -->
    <meta name="description" content="SafeKeep - School Lost & Found System">
    <meta name="author" content="SafeKeep Team">
    <meta name="robots" content="index, follow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo Config::get('app.url'); ?>/assets/images/favicon.ico">
    
    <!-- CSRF Token for JavaScript -->
    <meta name="csrf-token" content="<?php echo Security::generateCSRFToken(); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo Config::get('app.url'); ?>">
                <i class="fas fa-shield-alt me-2"></i>
                <?php echo Config::get('app.name'); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo Config::get('app.url'); ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/posts/browse.php">
                            <i class="fas fa-list me-1"></i>Browse Items
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/announcements.php">
                            <i class="fas fa-bullhorn me-1"></i>Announcements
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($currentUser): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($currentUser['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo Config::get('app.url'); ?>/profile/index.php">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo Config::get('app.url'); ?>/posts/create.php">
                                    <i class="fas fa-plus me-2"></i>Post Item
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo Config::get('app.url'); ?>/posts/my-posts.php">
                                    <i class="fas fa-list me-2"></i>My Posts
                                </a></li>
                                <?php if (Session::isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo Config::get('app.url'); ?>/admin/dashboard.php">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo Config::get('app.url'); ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="container-fluid">
        <div class="row">
            <?php if (isset($showAdminSidebar) && $showAdminSidebar && Session::isAdmin()): ?>
            <!-- Admin Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Administration</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/posts.php">
                                <i class="fas fa-list me-2"></i>Manage Posts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/users.php">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/categories.php">
                                <i class="fas fa-tags me-2"></i>Manage Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo Config::get('app.url'); ?>/admin/audit-logs.php">
                                <i class="fas fa-clipboard-list me-2"></i>Audit Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content with sidebar -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php else: ?>
            <!-- Main content without sidebar -->
            <main class="container my-4">
            <?php endif; ?>