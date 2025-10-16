<?php
/**
 * Configuration Loader
 * Loads environment variables and application configuration
 */

declare(strict_types=1);

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from .env file
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../.env.example';
            if (!file_exists($envFile)) {
                throw new Exception('No .env or .env.example file found');
            }
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        // Set default configuration with proper environment variable precedence
        self::$config = [
            'database' => [
                'host' => self::env('DB_HOST', self::env('MYSQLHOST', self::env('RAILWAY_PRIVATE_DOMAIN', 'localhost'))),
                'name' => self::env('DB_NAME', self::env('MYSQL_DATABASE', self::env('MYSQLDATABASE', 'railway'))),
                'user' => self::env('DB_USER', self::env('MYSQLUSER', 'root')),
                'pass' => self::env('DB_PASS', self::env('MYSQL_ROOT_PASSWORD', self::env('MYSQLPASSWORD', 'rGqvfLQjfcUqCvlxBTWxlwwNgXMEjVvm'))),
                'charset' => 'utf8mb4'
            ],
            'mail' => [
                'enabled' => self::env('MAIL_ENABLED', 'false'),
                'host' => self::env('MAIL_HOST', 'smtp.gmail.com'),
                'port' => (int)self::env('MAIL_PORT', 587),
                'encryption' => self::env('MAIL_ENCRYPTION', 'tls'),
                'username' => self::env('MAIL_USERNAME', ''),
                'password' => self::env('MAIL_PASSWORD', ''),
                'from_email' => self::env('MAIL_FROM_EMAIL', 'noreply@safekeep.school'),
                'from_name' => self::env('MAIL_FROM_NAME', 'SafeKeep - Lost & Found'),
                'reply_to' => self::env('MAIL_REPLY_TO', 'support@safekeep.school')
            ],
            'app' => [
                'name' => self::env('APP_NAME', 'SafeKeep'),
                'url' => self::env('APP_URL', 'https://safekeep-v12-production.up.railway.app'),
                'env' => self::env('APP_ENV', 'production'),
                'debug' => self::env('APP_DEBUG', 'false') === 'true'
            ],
            'security' => [
                'csrf_secret' => self::env('CSRF_SECRET', 'default-csrf-secret-change-me'),
                'session_name' => self::env('SESSION_NAME', 'safekeep_session'),
                'session_lifetime' => (int)self::env('SESSION_LIFETIME', 3600)
            ],
            'school' => [
                'email_domains' => explode(',', self::env('ALLOWED_EMAIL_DOMAINS', '@school.edu,@gmail.com')),
                'auto_approve_users' => self::env('AUTO_APPROVE_USERS', 'false') === 'true'
            ],
            'uploads' => [
                'max_file_size' => (int)self::env('MAX_FILE_SIZE', 5242880), // 5MB
                'allowed_extensions' => explode(',', self::env('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp')),
                'upload_dir' => __DIR__ . '/../uploads/'
            ],
            'rate_limiting' => [
                'contact_limit' => (int)self::env('CONTACT_RATE_LIMIT', 5),
                'contact_window' => (int)self::env('CONTACT_RATE_WINDOW', 3600)
            ]
        ];

        self::$loaded = true;
    }

    /**
     * Get environment variable with default value
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    /**
     * Get configuration value by dot notation
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        self::load();
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            $config = &$config[$k];
        }
        
        $config = $value;
    }

    /**
     * Get all configuration
     */
    public static function all(): array
    {
        self::load();
        return self::$config;
    }
}