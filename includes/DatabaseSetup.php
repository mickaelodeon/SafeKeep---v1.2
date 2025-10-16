<?php
/**
 * Database Setup and Migration System
 * Automatically creates tables and initial data on first run
 */

declare(strict_types=1);

class DatabaseSetup
{
    private PDO $connection;
    private string $migrationsPath;
    
    public function __construct(PDO $connection, string $migrationsPath = null)
    {
        $this->connection = $connection;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../migrations';
    }
    
    /**
     * Check if database needs setup
     */
    public function needsSetup(): bool
    {
        try {
            // Check if users table exists
            $stmt = $this->connection->query("SHOW TABLES LIKE 'users'");
            return $stmt->rowCount() === 0;
        } catch (PDOException $e) {
            error_log("Database check failed: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Run database setup
     */
    public function setup(): bool
    {
        try {
            echo "Starting SafeKeep database setup...\n";
            
            // Create migrations table to track what's been run
            $this->createMigrationsTable();
            
            // Run migration files
            $this->runMigration('001_create_tables.sql', 'Create database tables');
            $this->runMigration('002_sample_data.sql', 'Insert sample data');
            
            echo "Database setup completed successfully!\n";
            return true;
            
        } catch (Exception $e) {
            error_log("Database setup failed: " . $e->getMessage());
            echo "Database setup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(255) NOT NULL,
                `description` varchar(500) DEFAULT NULL,
                `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `filename` (`filename`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->connection->exec($sql);
        echo "Created migrations table\n";
    }
    
    /**
     * Run a specific migration file
     */
    private function runMigration(string $filename, string $description): void
    {
        // Check if migration already ran
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM migrations WHERE filename = ?");
        $stmt->execute([$filename]);
        
        if ($stmt->fetchColumn() > 0) {
            echo "Migration $filename already executed, skipping\n";
            return;
        }
        
        $filepath = $this->migrationsPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: $filepath");
        }
        
        echo "Running migration: $filename - $description\n";
        
        // Read and execute SQL file
        $sql = file_get_contents($filepath);
        
        // Split by statements and execute each one
        $statements = $this->splitSqlStatements($sql);
        
        $this->connection->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !$this->isComment($statement)) {
                    $this->connection->exec($statement);
                }
            }
            
            // Record migration as completed
            $stmt = $this->connection->prepare("
                INSERT INTO migrations (filename, description) 
                VALUES (?, ?)
            ");
            $stmt->execute([$filename, $description]);
            
            $this->connection->commit();
            echo "Migration $filename completed successfully\n";
            
        } catch (Exception $e) {
            $this->connection->rollback();
            throw new Exception("Migration $filename failed: " . $e->getMessage());
        }
    }
    
    /**
     * Split SQL file into individual statements
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments and normalize line endings
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = str_replace("\r\n", "\n", $sql);
        
        // Split by semicolons, but be careful about semicolons in strings
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && $sql[$i-1] !== '\\') {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return $statements;
    }
    
    /**
     * Check if line is a comment
     */
    private function isComment(string $line): bool
    {
        $line = trim($line);
        return empty($line) || 
               str_starts_with($line, '--') || 
               str_starts_with($line, '/*') ||
               str_starts_with($line, 'SET ') ||
               str_starts_with($line, 'START TRANSACTION') ||
               str_starts_with($line, 'COMMIT') ||
               str_starts_with($line, 'CREATE DATABASE') ||
               str_starts_with($line, 'USE ');
    }
    
    /**
     * Create default admin user if none exists
     */
    public function ensureAdminUser(): void
    {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                echo "Creating default admin user...\n";
                
                $stmt = $this->connection->prepare("
                    INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified) 
                    VALUES (?, ?, ?, 'admin', 1, 1)
                ");
                
                $stmt->execute([
                    'SafeKeep Administrator',
                    'johnmichaeleborda79@gmail.com',
                    password_hash('admin123', PASSWORD_DEFAULT)
                ]);
                
                echo "Default admin user created: johnmichaeleborda79@gmail.com / admin123\n";
            }
        } catch (Exception $e) {
            error_log("Failed to create admin user: " . $e->getMessage());
        }
    }
}
?>