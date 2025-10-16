<?php
/**
 * Security and Utility Functions
 * CSRF protection, input validation, file upload handling, rate limiting
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Security
{
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput(mixed $input): mixed
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        if (is_string($input)) {
            return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
        }
        
        return $input;
    }

    /**
     * Validate email format and domain
     */
    public static function validateEmail(string $email): array
    {
        $errors = [];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        $allowedDomains = Config::get('school.email_domains');
        if ($allowedDomains && is_array($allowedDomains)) {
            $domainValid = false;
            foreach ($allowedDomains as $domain) {
                if (str_ends_with($email, trim($domain))) {
                    $domainValid = true;
                    break;
                }
            }
            if (!$domainValid) {
                $domainsStr = implode(' or ', $allowedDomains);
                $errors[] = "Email must end with {$domainsStr}.";
            }
        }
        
        return $errors;
    }

    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return $errors;
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit(string $identifier, string $action, int $maxAttempts, int $windowSeconds): bool
    {
        $now = new DateTime();
        $windowStart = (clone $now)->sub(new DateInterval("PT{$windowSeconds}S"));
        
        // Clean up expired entries
        Database::delete(
            'rate_limits',
            'expires_at < ?',
            [$now->format('Y-m-d H:i:s')]
        );
        
        // Check current attempts
        $record = Database::selectOne(
            'SELECT * FROM rate_limits WHERE identifier = ? AND action = ?',
            [$identifier, $action]
        );
        
        if (!$record) {
            // First attempt
            Database::insert('rate_limits', [
                'identifier' => $identifier,
                'action' => $action,
                'attempts' => 1,
                'window_start' => $now->format('Y-m-d H:i:s'),
                'expires_at' => (clone $now)->add(new DateInterval("PT{$windowSeconds}S"))->format('Y-m-d H:i:s')
            ]);
            return true;
        }
        
        $windowStartTime = new DateTime($record['window_start']);
        if ($windowStartTime < $windowStart) {
            // Window expired, reset
            Database::update(
                'rate_limits',
                [
                    'attempts' => 1,
                    'window_start' => $now->format('Y-m-d H:i:s'),
                    'expires_at' => (clone $now)->add(new DateInterval("PT{$windowSeconds}S"))->format('Y-m-d H:i:s')
                ],
                'id = ?',
                [$record['id']]
            );
            return true;
        }
        
        if ($record['attempts'] >= $maxAttempts) {
            return false; // Rate limited
        }
        
        // Increment attempts
        Database::update(
            'rate_limits',
            ['attempts' => $record['attempts'] + 1],
            'id = ?',
            [$record['id']]
        );
        
        return true;
    }

    /**
     * Generate secure random filename
     */
    public static function generateSecureFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $randomName = bin2hex(random_bytes(16));
        return $randomName . '.' . $extension;
    }

    /**
     * Validate and process file upload
     */
    public static function handleFileUpload(array $file, string $uploadDir): array
    {
        $result = ['success' => false, 'filename' => null, 'error' => null];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = 'File upload failed.';
            return $result;
        }
        
        $maxSize = Config::get('uploads.max_file_size');
        if ($file['size'] > $maxSize) {
            $result['error'] = 'File size exceeds maximum limit (' . self::formatBytes($maxSize) . ').';
            return $result;
        }
        
        $allowedExtensions = Config::get('uploads.allowed_extensions');
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $result['error'] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
            return $result;
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 
            'image/gif', 'image/webp'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $result['error'] = 'Invalid file type detected.';
            return $result;
        }
        
        // Generate secure filename
        $secureFilename = self::generateSecureFilename($file['name']);
        $uploadPath = $uploadDir . $secureFilename;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $result['error'] = 'Failed to create upload directory.';
                return $result;
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $result['success'] = true;
            $result['filename'] = $secureFilename;
        } else {
            $result['error'] = 'Failed to save uploaded file.';
        }
        
        return $result;
    }

    /**
     * Format bytes to human readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }


}

/**
 * Session Management
 */
class Session
{
    /**
     * Initialize secure session
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = Config::get('security');
            
            // Configure session settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.gc_maxlifetime', (string)$config['session_lifetime']);
            ini_set('session.cookie_path', '/');
            
            session_name($config['session_name']);
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }

    /**
     * Get current user
     */
    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return User::findById($_SESSION['user_id']);
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        $user = self::getUser();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Login user
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        session_unset();
        session_destroy();
        
        // Start new session
        self::init();
    }

    /**
     * Require login
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . Config::get('app.url') . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    /**
     * Require admin privileges
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        
        if (!self::isAdmin()) {
            header('Location: ' . Config::get('app.url') . '/index.php?error=access_denied');
            exit;
        }
    }
}

/**
 * Utility Functions
 */
class Utils
{
    /**
     * Redirect with message
     */
    public static function redirect(string $url, ?string $message = null, string $type = 'info'): void
    {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        
        // Ensure session data is written before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Clean any output buffers before redirect
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Location: $url");
        exit;
    }

    /**
     * Get and clear flash message
     */
    public static function getFlashMessage(): ?array
    {
        if (isset($_SESSION['flash_message'])) {
            $message = [
                'text' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return $message;
        }
        
        return null;
    }

    /**
     * Format date for display
     */
    public static function formatDate(string $date, string $format = 'M j, Y'): string
    {
        return date($format, strtotime($date));
    }

    /**
     * Time ago format
     */
    public static function timeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }

    /**
     * Truncate text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Log audit action
     */
    public static function logAuditAction(int $userId, string $action, string $resourceType = null, int $resourceId = null, string $ipAddress = null, array $details = null): void
    {
        try {
            $logData = [
                'user_id' => $userId,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'ip_address' => $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                'details' => $details ? json_encode($details) : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            Database::insert('audit_logs', $logData);
        } catch (Exception $e) {
            // Fail silently - audit logging shouldn't break functionality
            error_log('Audit logging failed: ' . $e->getMessage());
        }
    }
}