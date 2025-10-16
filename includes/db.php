<?php
/**
 * Database Connection and Query Helper
 * Provides secure PDO database connection and common operations
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $connection = null;
    private static array $queryLog = [];

    /**
     * Get PDO connection instance (singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $config = Config::get('database');
                
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['name'],
                    $config['charset']
                );

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ];

                self::$connection = new PDO(
                    $dsn,
                    $config['user'],
                    $config['pass'],
                    $options
                );
                
                // Set timezone
                self::$connection->exec("SET time_zone = '+00:00'");
                
                // Auto-setup database if needed (for first run)
                self::autoSetupDatabase();
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }

        return self::$connection;
    }

    /**
     * Execute a prepared statement with parameters
     */
    public static function execute(string $query, array $params = []): PDOStatement
    {
        $pdo = self::getConnection();
        
        if (Config::get('app.debug')) {
            self::$queryLog[] = [
                'query' => $query,
                'params' => $params,
                'time' => microtime(true)
            ];
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " Query: " . $query);
            throw new Exception("Database operation failed. Please try again.");
        }
    }

    /**
     * Insert a new record and return the last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $query = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $columns),
            $placeholders
        );

        self::execute($query, array_values($data));
        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Update records and return affected row count
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setPairs = array_map(fn($col) => "`$col` = ?", array_keys($data));
        
        $query = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $table,
            implode(', ', $setPairs),
            $where
        );

        $params = array_merge(array_values($data), $whereParams);
        $stmt = self::execute($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records and return affected row count
     */
    public static function delete(string $table, string $where, array $whereParams = []): int
    {
        $query = sprintf("DELETE FROM `%s` WHERE %s", $table, $where);
        $stmt = self::execute($query, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Select records
     */
    public static function select(string $query, array $params = []): array
    {
        $stmt = self::execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Select a single record
     */
    public static function selectOne(string $query, array $params = []): ?array
    {
        $stmt = self::execute($query, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if a record exists
     */
    public static function exists(string $table, string $where, array $params = []): bool
    {
        $query = sprintf("SELECT 1 FROM `%s` WHERE %s LIMIT 1", $table, $where);
        $result = self::selectOne($query, $params);
        return $result !== null;
    }

    /**
     * Get record count
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $query = sprintf("SELECT COUNT(*) as count FROM `%s` WHERE %s", $table, $where);
        $result = self::selectOne($query, $params);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Begin database transaction
     */
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public static function commit(): void
    {
        self::getConnection()->commit();
    }

    /**
     * Rollback database transaction
     */
    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }

    /**
     * Get query log (for debugging)
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * Escape string for LIKE operations
     */
    public static function escapeLike(string $string): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
    }
    
    /**
     * Automatically setup database on first run
     */
    private static function autoSetupDatabase(): void
    {
        static $setupRun = false;
        
        if ($setupRun) {
            return; // Only run once per request
        }
        
        $setupRun = true;
        
        try {
            require_once __DIR__ . '/DatabaseSetup.php';
            
            $setup = new DatabaseSetup(self::$connection);
            
            if ($setup->needsSetup()) {
                error_log("SafeKeep: Database needs setup, running automated setup...");
                
                if ($setup->setup()) {
                    $setup->ensureAdminUser();
                    error_log("SafeKeep: Database setup completed successfully");
                } else {
                    error_log("SafeKeep: Database setup failed");
                }
            }
        } catch (Exception $e) {
            error_log("SafeKeep: Auto-setup error: " . $e->getMessage());
            // Don't throw exception here - let the app continue even if setup fails
        }
    }
}

/**
 * User Model
 */
class User
{
    public static function create(array $data): int
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        $data['created_at'] = date('Y-m-d H:i:s');
        unset($data['password'], $data['confirm_password']);
        
        return Database::insert('users', $data);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::selectOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public static function authenticate(string $email, string $password): ?array
    {
        $user = Database::selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 AND email_verified = 1", 
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            return $user;
        }
        
        return null;
    }

    public static function isAdmin(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && $user['role'] === 'admin';
    }
}

/**
 * Post Model
 */
class Post
{
    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('posts', $data);
    }

    public static function findById(int $id): ?array
    {
        return Database::selectOne(
            "SELECT p.*, u.full_name as user_name, u.email as user_email,
                    a.full_name as approver_name
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id
             LEFT JOIN users a ON p.approved_by = a.id
             WHERE p.id = ?",
            [$id]
        );
    }

    public static function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = ["p.status = 'approved'"];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = "p.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['category'])) {
            $where[] = "p.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[] = "MATCH(p.title, p.description, p.location) AGAINST(? IN NATURAL LANGUAGE MODE)";
            $params[] = $filters['search'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "p.date_lost_found >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "p.date_lost_found <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return Database::select(
            "SELECT p.*, u.full_name as user_name 
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id
             WHERE $whereClause
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function getUserPosts(int $userId): array
    {
        return Database::select(
            "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    public static function getPendingPosts(): array
    {
        return Database::select(
            "SELECT p.*, u.full_name as user_name, u.email as user_email
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id
             WHERE p.status = 'pending'
             ORDER BY p.created_at ASC"
        );
    }

    public static function approve(int $postId, int $adminId): bool
    {
        return Database::update(
            'posts',
            [
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$postId]
        ) > 0;
    }

    public static function reject(int $postId, int $adminId, string $reason = ''): bool
    {
        return Database::update(
            'posts',
            [
                'status' => 'rejected',
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason
            ],
            'id = ?',
            [$postId]
        ) > 0;
    }
    
}