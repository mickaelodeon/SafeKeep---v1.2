<?php
/**
 * User Login Page
 * Secure user authentication with session management
 */

declare(strict_types=1);

// Start output buffering to handle potential redirects
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize session first
Session::init();

$pageTitle = 'Login';
$errors = [];
$email = '';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Utils::redirect(Config::get('app.url') . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation
        if (empty($email)) {
            $errors[] = 'Email is required.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        if (empty($errors)) {
            // Attempt authentication
            $user = User::authenticate($email, $password);
            
            if ($user) {
                if (!$user['is_active']) {
                    $errors[] = 'Your account is pending approval. Please contact an administrator.';
                } else {
                    // Successful login
                    Session::login($user);
                    
                    // Ensure session is written before redirect
                    session_write_close();
                    
                    // Redirect to intended page or dashboard
                    $redirectUrl = $_GET['redirect'] ?? Config::get('app.url') . '/index.php';
                    Utils::redirect($redirectUrl, 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!', 'success');
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Sign In</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               class="form-control <?php echo !empty($errors) && empty($email) ? 'is-invalid' : ''; ?>" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               required
                               autofocus>
                        <div class="invalid-feedback">
                            Please provide a valid email address.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control <?php echo !empty($errors) && empty($_POST['password'] ?? '') ? 'is-invalid' : ''; ?>" 
                               id="password" 
                               name="password"
                               required>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <small>
                        <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
                    </small>
                </div>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">
                    Don't have an account? 
                    <a href="register.php" class="text-decoration-none">Register here</a>
                </small>
            </div>
        </div>

        <!-- Demo Account Info -->
        <?php if (Config::get('app.env') === 'development'): ?>
        <div class="card mt-3 border-info">
            <div class="card-header bg-info text-white">
                <small><i class="fas fa-info-circle me-1"></i>Demo Accounts (Development Only)</small>
            </div>
            <div class="card-body p-3">
                <small>
                    <strong>Admin:</strong> admin@school.edu<br>
                    <strong>Student:</strong> john.smith@school.edu<br>
                    <strong>Password:</strong> SafeKeep2024!
                </small>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    
    // Email validation
    email.addEventListener('input', function() {
        const isValid = this.checkValidity();
        this.classList.toggle('is-valid', isValid && this.value.length > 0);
        this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
    });
    
    // Password validation
    password.addEventListener('input', function() {
        const isValid = this.value.length > 0;
        this.classList.toggle('is-valid', isValid);
        this.classList.toggle('is-invalid', !isValid);
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php';
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>