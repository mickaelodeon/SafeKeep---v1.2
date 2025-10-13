<?php
/**
 * User Registration Page
 * Secure user registration with email domain validation
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pageTitle = 'Register';
$errors = [];
$formData = [];

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
        $formData = Security::sanitizeInput($_POST);
        
        // Validate required fields
        if (empty($formData['full_name'])) {
            $errors[] = 'Full name is required.';
        }
        
        if (empty($formData['email'])) {
            $errors[] = 'Email is required.';
        } else {
            $emailErrors = Security::validateEmail($formData['email']);
            $errors = array_merge($errors, $emailErrors);
            
            // Check if email already exists
            if (empty($emailErrors) && User::findByEmail($formData['email'])) {
                $errors[] = 'An account with this email already exists.';
            }
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Password is required.';
        } else {
            $passwordErrors = Security::validatePassword($formData['password']);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        if (empty($formData['confirm_password'])) {
            $errors[] = 'Password confirmation is required.';
        } elseif ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Create user if no errors
        if (empty($errors)) {
            try {
                $userData = [
                    'full_name' => $formData['full_name'],
                    'email' => $formData['email'],
                    'password' => $formData['password'],
                    'is_active' => Config::get('school.auto_approve_users') ? 1 : 0,
                    'email_verified' => 0,
                    'verification_token' => bin2hex(random_bytes(32))
                ];
                
                $userId = User::create($userData);
                
                if (Config::get('school.auto_approve_users')) {
                    $message = 'Account created successfully! You can now log in.';
                } else {
                    $message = 'Account created successfully! Please wait for admin approval before logging in.';
                }
                
                Utils::redirect(Config::get('app.url') . '/auth/login.php', $message, 'success');
                
            } catch (Exception $e) {
                $errors[] = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
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
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control <?php echo isset($errors) && !empty($errors) && empty($formData['full_name']) ? 'is-invalid' : ''; ?>" 
                               id="full_name" 
                               name="full_name" 
                               value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                               required>
                        <div class="invalid-feedback">
                            Please provide your full name.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">School Email <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control <?php echo isset($errors) && !empty($errors) && (empty($formData['email']) || User::findByEmail($formData['email'] ?? '')) ? 'is-invalid' : ''; ?>" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               required>
                        <div class="form-text">Must be a valid email address ending with <?php echo implode(' or ', Config::get('school.email_domains')); ?></div>
                        <div class="invalid-feedback">
                            Please provide a valid school email address.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password"
                               required>
                        <div class="form-text">
                            Must be at least 8 characters with uppercase, lowercase, number, and special character.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password"
                               required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">
                    Already have an account? 
                    <a href="login.php" class="text-decoration-none">Sign in here</a>
                </small>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                <?php if (!Config::get('school.auto_approve_users')): ?>
                <i class="fas fa-info-circle me-1"></i>
                New accounts require admin approval before activation.
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const email = document.getElementById('email');
    
    // Real-time password validation
    password.addEventListener('input', function() {
        const value = this.value;
        const isValid = value.length >= 8 && 
                       /[A-Z]/.test(value) && 
                       /[a-z]/.test(value) && 
                       /[0-9]/.test(value) && 
                       /[^A-Za-z0-9]/.test(value);
        
        this.classList.toggle('is-valid', isValid);
        this.classList.toggle('is-invalid', !isValid && value.length > 0);
    });
    
    // Password confirmation validation
    confirmPassword.addEventListener('input', function() {
        const matches = this.value === password.value;
        this.classList.toggle('is-valid', matches && this.value.length > 0);
        this.classList.toggle('is-invalid', !matches && this.value.length > 0);
    });
    
    // Email domain validation
    email.addEventListener('input', function() {
        const allowedDomains = <?php echo json_encode(Config::get('school.email_domains')); ?>;
        const isValid = allowedDomains.some(domain => this.value.endsWith(domain.trim()));
        this.classList.toggle('is-valid', isValid);
        this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>