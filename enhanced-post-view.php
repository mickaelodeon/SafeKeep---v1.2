<?php
/**
 * Enhanced Post View with Modern UI/UX
 * Beautiful, responsive design with advanced features
 */

session_start();

// Include required files
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    header('Location: /safekeep-v2/');
    exit;
}

// Get current user
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
} else {
    // Auto-login for testing
    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['johnmichaeleborda79@gmail.com']);
    $currentUser = $stmt->fetch();
    if ($currentUser) {
        $_SESSION['user_id'] = $currentUser['id'];
        $_SESSION['user_name'] = $currentUser['full_name'];
    }
}

$contactSuccess = '';
$contactError = '';

// Handle contact form submission
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
            try {
                $contactData = [
                    'post_id' => $postId,
                    'sender_user_id' => $currentUser['id'],
                    'sender_name' => $currentUser['full_name'],
                    'sender_email' => $currentUser['email'],
                    'message' => $message,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'email_sent' => 0
                ];
                
                $contactId = Database::insert('contact_logs', $contactData);
                
                if ($contactId) {
                    require_once '../includes/Email.php';
                    
                    $stmt = Database::getConnection()->prepare("SELECT * FROM posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch();
                    
                    $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$post['user_id']]);
                    $postOwner = $stmt->fetch();
                    
                    $emailSubject = "SafeKeep: Someone is interested in your " . ucfirst($post['type']) . " item";
                    $emailBody = "
                        <h3>Someone contacted you about your post: " . htmlspecialchars($post['title']) . "</h3>
                        <p><strong>From:</strong> " . htmlspecialchars($currentUser['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>
                        <p><strong>Message:</strong></p>
                        <div style='background:#f8f9fa; padding:15px; border-left:4px solid #007bff; margin:10px 0;'>
                            " . nl2br(htmlspecialchars($message)) . "
                        </div>
                    ";
                    
                    $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody, true);
                    
                    if ($emailSent) {
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$contactId]);
                        $contactSuccess = 'Your message has been sent to the item owner. They will receive an email notification and can contact you directly.';
                    } else {
                        $contactSuccess = 'Your message has been logged, but email notification failed. The owner can still see your message when they check their posts.';
                    }
                }
                
            } catch (Exception $e) {
                $contactError = 'Failed to send message: ' . $e->getMessage();
                error_log('Contact form error: ' . $e->getMessage());
            }
        }
    }
}

// Get post details with enhanced info
try {
    $stmt = Database::getConnection()->prepare("
        SELECT p.*, u.full_name as owner_name, u.email as owner_email,
               TIMESTAMPDIFF(HOUR, p.created_at, NOW()) as hours_ago,
               TIMESTAMPDIFF(DAY, p.date_lost_found, NOW()) as days_since_incident
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: /safekeep-v2/posts/browse.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: /safekeep-v2/posts/browse.php');
    exit;
}

// Check if user has already contacted owner
$hasContacted = false;
if ($currentUser) {
    $stmt = Database::getConnection()->prepare("
        SELECT COUNT(*) as count FROM contact_logs 
        WHERE post_id = ? AND sender_user_id = ?
    ");
    $stmt->execute([$postId, $currentUser['id']]);
    $result = $stmt->fetch();
    $hasContacted = $result['count'] > 0;
}

// Get similar items
$similarItems = [];
try {
    $stmt = Database::getConnection()->prepare("
        SELECT p.*, u.full_name as owner_name 
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id != ? AND p.status = 'approved' AND p.is_resolved = 0
        AND (p.category = ? OR p.type = ?)
        ORDER BY p.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$postId, $post['category'], $post['type']]);
    $similarItems = $stmt->fetchAll();
} catch (Exception $e) {
    // Continue without similar items
}

$isOwner = $currentUser && $post['user_id'] == $currentUser['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKeep - <?php echo htmlspecialchars($post['title']); ?></title>
    
    <!-- Enhanced CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($post['type'] . ' Item: ' . $post['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($post['description'], 0, 160)); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Thelvetica, Arial, sans-serif;
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="1000,100 1000,0 0,100"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        /* Enhanced Cards */
        .post-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            transition: var(--transition);
            overflow: hidden;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .post-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            position: relative;
        }

        .post-header.lost {
            background: var(--danger-gradient);
        }

        .post-header.found {
            background: var(--success-gradient);
        }

        /* Animated Status Badge */
        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Image Gallery */
        .image-gallery {
            position: relative;
            cursor: pointer;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .image-gallery:hover {
            transform: scale(1.02);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }

        .image-gallery:hover .image-overlay {
            opacity: 1;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--bs-primary);
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateX(5px);
            border-left-width: 8px;
        }

        .info-card .icon {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        /* Contact Form Enhancements */
        .contact-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: sticky;
            top: 2rem;
        }

        .contact-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .contact-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 10px 15px 0 15px;
            border-color: #764ba2 transparent transparent transparent;
        }

        .contact-form {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }

        .btn-enhanced {
            background: var(--primary-gradient);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-enhanced:active {
            transform: translateY(0);
        }

        /* Timeline */
        .timeline-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            border-left: 4px solid #28a745;
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Similar Items */
        .similar-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .similar-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            color: inherit;
            text-decoration: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                padding: 1rem 0;
            }
            
            .contact-card {
                position: static;
                margin-top: 2rem;
            }
            
            .info-card {
                padding: 1rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Urgency Indicator */
        .urgency-high {
            border-left-color: #dc3545 !important;
        }

        .urgency-medium {
            border-left-color: #ffc107 !important;
        }

        .urgency-low {
            border-left-color: #28a745 !important;
        }

        /* Social Share Buttons */
        .social-share {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-btn:hover {
            transform: translateY(-3px);
            color: white;
        }

        .social-btn.facebook { background: #3b5998; }
        .social-btn.twitter { background: #1da1f2; }
        .social-btn.whatsapp { background: #25d366; }
        .social-btn.email { background: #ea4335; }
    </style>
</head>
<body>
    <!-- Enhanced Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-gradient);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/safekeep-v2/">
                <i class="fas fa-shield-alt me-2"></i>SafeKeep
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if ($currentUser): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/safekeep-v2/profile/"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="/safekeep-v2/posts/my-posts.php"><i class="fas fa-list me-2"></i>My Posts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/safekeep-v2/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="/safekeep-v2/auth/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container hero-content">
            <nav aria-label="breadcrumb" data-aos="fade-down">
                <ol class="breadcrumb mb-3" style="background: rgba(255, 255, 255, 0.1); border-radius: 25px;">
                    <li class="breadcrumb-item"><a href="/safekeep-v2/" class="text-white">Home</a></li>
                    <li class="breadcrumb-item"><a href="/safekeep-v2/posts/browse.php" class="text-white">Browse Items</a></li>
                    <li class="breadcrumb-item active text-white-50"><?php echo htmlspecialchars($post['title']); ?></li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-md-8" data-aos="fade-right">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-<?php echo $post['type'] == 'lost' ? 'search' : 'hand-holding-heart'; ?> me-3"></i>
                        <?php echo ucfirst($post['type']); ?> Item
                    </h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($post['title']); ?></p>
                </div>
                <div class="col-md-4 text-end" data-aos="fade-left">
                    <div class="d-flex justify-content-end align-items-center">
                        <span class="badge bg-light text-dark me-2 px-3 py-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php 
                            if ($post['hours_ago'] < 24) {
                                echo $post['hours_ago'] . ' hours ago';
                            } else {
                                echo ceil($post['hours_ago'] / 24) . ' days ago';
                            }
                            ?>
                        </span>
                        <span class="badge bg-<?php echo $post['status'] == 'approved' ? 'success' : 'warning'; ?> px-3 py-2">
                            <i class="fas fa-<?php echo $post['status'] == 'approved' ? 'check-circle' : 'clock'; ?> me-1"></i>
                            <?php echo ucfirst($post['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Post Details Card -->
                <div class="post-card mb-4" data-aos="fade-up">
                    <div class="post-header <?php echo $post['type']; ?>">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-1 fw-bold"><?php echo htmlspecialchars($post['title']); ?></h2>
                                <p class="mb-0 opacity-75">
                                    <i class="fas fa-user me-2"></i>Posted by <?php echo htmlspecialchars($post['owner_name']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="status-badge">
                            <?php if ($post['is_resolved']): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check-circle"></i> Resolved
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6">
                                    <i class="fas fa-exclamation-circle"></i> Active
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="mb-3"><i class="fas fa-align-left text-primary me-2"></i>Description</h5>
                                <div class="lead mb-4" style="line-height: 1.8;">
                                    <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                                </div>
                                
                                <!-- Information Cards -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card <?php echo $post['days_since_incident'] > 7 ? 'urgency-high' : ($post['days_since_incident'] > 3 ? 'urgency-medium' : 'urgency-low'); ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="icon">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Location</h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($post['location']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="d-flex align-items-center">
                                                <div class="icon">
                                                    <i class="fas fa-calendar"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Date <?php echo ucfirst($post['type']); ?></h6>
                                                    <p class="mb-0 text-muted">
                                                        <?php echo date('M d, Y', strtotime($post['date_lost_found'])); ?>
                                                        <small class="d-block"><?php echo $post['days_since_incident']; ?> days ago</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="d-flex align-items-center">
                                                <div class="icon">
                                                    <i class="fas fa-tags"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Category</h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($post['category']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="d-flex align-items-center">
                                                <div class="icon">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Posted</h6>
                                                    <p class="mb-0 text-muted">
                                                        <?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?>
                                                        <small class="d-block"><?php echo $post['hours_ago']; ?> hours ago</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($post['photo_path']): ?>
                            <div class="col-lg-4" data-aos="fade-left" data-aos-delay="200">
                                <h6 class="mb-3"><i class="fas fa-image text-primary me-2"></i>Photo</h6>
                                <div class="image-gallery" onclick="openImageModal()">
                                    <img src="/safekeep-v2/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                                         alt="Item photo" class="img-fluid rounded shadow" style="width: 100%; height: 250px; object-fit: cover;">
                                    <div class="image-overlay">
                                        <i class="fas fa-search-plus fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Social Share -->
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-center mb-3"><i class="fas fa-share-alt me-2"></i>Help Spread the Word</h6>
                            <div class="social-share">
                                <a href="#" class="social-btn facebook" onclick="shareOnFacebook()">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn twitter" onclick="shareOnTwitter()">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-btn whatsapp" onclick="shareOnWhatsApp()">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="#" class="social-btn email" onclick="shareViaEmail()">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Similar Items Section -->
                <?php if (!empty($similarItems)): ?>
                <div class="card mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-lightbulb text-warning me-2"></i>Similar Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($similarItems as $item): ?>
                            <div class="col-md-4">
                                <a href="/safekeep-v2/posts/view.php?id=<?php echo $item['id']; ?>" class="similar-item">
                                    <div class="p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-<?php echo $item['type'] == 'lost' ? 'danger' : 'success'; ?> me-2">
                                                <?php echo ucfirst($item['type']); ?>
                                            </span>
                                            <small class="text-muted"><?php echo $item['category']; ?></small>
                                        </div>
                                        <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['owner_name']); ?>
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-clock me-1"></i><?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                        </p>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Contact Form -->
                <?php if (!$isOwner && $post['status'] == 'approved' && !$post['is_resolved']): ?>
                <div class="contact-card mb-4" data-aos="fade-left">
                    <div class="contact-header">
                        <h5 class="mb-2">
                            <i class="fas fa-envelope me-2"></i>Contact the Owner
                        </h5>
                        <?php if ($hasContacted): ?>
                            <small><i class="fas fa-check-circle me-1"></i>You've already contacted this owner</small>
                        <?php else: ?>
                            <small>Send a message to inquire about this item</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="contact-form">
                        <?php if ($contactSuccess): ?>
                            <div class="alert alert-success alert-dismissible fade show" data-aos="bounce">
                                <i class="fas fa-check-circle me-2"></i><?php echo $contactSuccess; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($contactError): ?>
                            <div class="alert alert-danger alert-dismissible fade show" data-aos="shake">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $contactError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($currentUser): ?>
                            <form method="POST" action="" id="contactForm">
                                <div class="mb-3">
                                    <label for="message" class="form-label fw-bold">Your Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" 
                                            placeholder="Hi! I'm interested in this item. Is it still available? Please let me know how we can arrange to meet." required></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>Minimum 10 characters required.
                                    </div>
                                </div>
                                
                                <button type="submit" name="contact_owner" class="btn btn-enhanced w-100" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-sign-in-alt me-2"></i>Login Required</h6>
                                <p class="mb-3">You must be logged in to contact the owner.</p>
                                <div class="d-grid gap-2">
                                    <a href="/safekeep-v2/auth/login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login
                                    </a>
                                    <a href="/safekeep-v2/auth/register.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus me-2"></i>Register
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($isOwner): ?>
                <div class="contact-card mb-4" data-aos="fade-left">
                    <div class="contact-header">
                        <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Your Post</h5>
                    </div>
                    <div class="contact-form text-center">
                        <i class="fas fa-crown fa-3x text-warning mb-3"></i>
                        <h6>This is your post</h6>
                        <p class="text-muted mb-3">You cannot contact yourself about your own item.</p>
                        <div class="d-grid gap-2">
                            <a href="/safekeep-v2/posts/my-posts.php" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Manage My Posts
                            </a>
                        </div>
                    </div>
                </div>

                <?php elseif ($post['is_resolved']): ?>
                <div class="contact-card mb-4" data-aos="fade-left">
                    <div class="contact-header" style="background: var(--success-gradient);">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Item Resolved</h5>
                    </div>
                    <div class="contact-form text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6>Great news!</h6>
                        <p class="text-muted">This item has been successfully resolved.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="card mb-4" data-aos="fade-left" data-aos-delay="200">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/safekeep-v2/posts/browse.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Back to Browse
                            </a>
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print This Page
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="copyLink()">
                                <i class="fas fa-link me-2"></i>Copy Link
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Report Issue -->
                <div class="card" data-aos="fade-left" data-aos-delay="300">
                    <div class="card-body text-center">
                        <small class="text-muted">
                            <i class="fas fa-flag me-1"></i>
                            <a href="#" class="text-decoration-none">Report inappropriate content</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <?php if ($post['photo_path']): ?>
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <img src="/safekeep-v2/uploads/<?php echo htmlspecialchars($post['photo_path']); ?>" 
                         alt="Item photo" class="img-fluid w-100">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    
    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true,
            offset: 100
        });

        // Enhanced Form Handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const messageTextarea = document.getElementById('message');

            if (form && submitBtn && messageTextarea) {
                // Real-time character count
                messageTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    const isValid = length >= 10;
                    
                    // Update visual feedback
                    this.classList.toggle('is-valid', isValid && length > 0);
                    this.classList.toggle('is-invalid', !isValid && length > 0);
                    
                    // Update button state
                    submitBtn.disabled = !isValid;
                });

                // Enhanced form submission
                form.addEventListener('submit', function(e) {
                    const message = messageTextarea.value.trim();
                    
                    if (message.length < 10) {
                        e.preventDefault();
                        messageTextarea.classList.add('is-invalid');
                        return false;
                    }
                    
                    // Show loading state
                    submitBtn.innerHTML = '<span class="spinner"></span> Sending...';
                    submitBtn.disabled = true;
                    form.classList.add('loading');
                });
            }
        });

        // Image Modal
        function openImageModal() {
            <?php if ($post['photo_path']): ?>
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
            <?php endif; ?>
        }

        // Social Sharing Functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent('<?php echo addslashes($post['type'] . ' Item: ' . $post['title']); ?>');
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, 'facebook-share', 'width=580,height=296');
        }

        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('<?php echo addslashes('Help find this ' . $post['type'] . ' item: ' . $post['title']); ?>');
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, 'twitter-share', 'width=550,height=420');
        }

        function shareOnWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('<?php echo addslashes('Check out this ' . $post['type'] . ' item on SafeKeep: ' . $post['title']); ?>');
            window.open(`https://wa.me/?text=${text} ${url}`, '_blank');
        }

        function shareViaEmail() {
            const subject = encodeURIComponent('<?php echo addslashes($post['type'] . ' Item: ' . $post['title']); ?>');
            const body = encodeURIComponent(`I found this ${<?php echo "'" . $post['type'] . "'"; ?>} item post that might interest you:\n\n${window.location.href}`);
            window.location.href = `mailto:?subject=${subject}&body=${body}`;
        }

        // Copy link function
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                // Show success feedback
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-info');
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-info');
                }, 2000);
            });
        }

        // Scroll animations
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.hero-section');
            if (parallax) {
                const speed = scrolled * 0.5;
                parallax.style.transform = `translateY(${speed}px)`;
            }
        });

        // Auto-save draft functionality
        if (typeof(Storage) !== "undefined") {
            const messageField = document.getElementById('message');
            if (messageField) {
                // Load saved draft
                const savedDraft = localStorage.getItem('safekeep_contact_draft_<?php echo $postId; ?>');
                if (savedDraft) {
                    messageField.value = savedDraft;
                }

                // Save draft as user types
                messageField.addEventListener('input', function() {
                    localStorage.setItem('safekeep_contact_draft_<?php echo $postId; ?>', this.value);
                });

                // Clear draft on successful submission
                const form = document.getElementById('contactForm');
                if (form) {
                    form.addEventListener('submit', function() {
                        localStorage.removeItem('safekeep_contact_draft_<?php echo $postId; ?>');
                    });
                }
            }
        }
    </script>
</body>
</html>