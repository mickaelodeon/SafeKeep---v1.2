<?php
/**
 * Railway Database Setup Script
 * Creates tables and initial data for SafeKeep deployment
 */

require_once 'includes/Database.php';

// Database configuration from environment
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? 3306;
$dbname = $_ENV['DATABASE_NAME'] ?? 'railway';
$username = $_ENV['DATABASE_USER'] ?? '';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to Railway MySQL database\n";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'admin') DEFAULT 'student',
            student_id VARCHAR(20),
            phone VARCHAR(15),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reset_token VARCHAR(255) NULL,
            reset_token_expires DATETIME NULL
        )
    ");
    
    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            type ENUM('lost', 'found') NOT NULL,
            location VARCHAR(200),
            date_occurred DATE,
            status ENUM('active', 'resolved', 'inactive') DEFAULT 'active',
            contact_info TEXT,
            reward_amount DECIMAL(10,2) DEFAULT 0,
            image_path VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    
    // Create contact_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            sender_name VARCHAR(100) NOT NULL,
            sender_email VARCHAR(100) NOT NULL,
            sender_phone VARCHAR(15),
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ Created all database tables\n";
    
    // Insert default categories
    $categories = [
        ['Electronics', 'Phones, laptops, tablets, headphones, chargers'],
        ['Personal Items', 'Wallets, bags, keys, jewelry, watches'],
        ['Documents', 'ID cards, licenses, certificates, papers'],
        ['Clothing', 'Jackets, hats, shoes, accessories'],
        ['Books & Stationery', 'Textbooks, notebooks, pens, calculators'],
        ['Sports Equipment', 'Balls, rackets, gym equipment, uniforms'],
        ['Other', 'Items that don\'t fit other categories']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    
    echo "✅ Inserted default categories\n";
    
    // Create admin user (password: admin123)
    $adminEmail = 'admin@safekeep.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (first_name, last_name, email, password, role, student_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['SafeKeep', 'Administrator', $adminEmail, $adminPassword, 'admin', 'ADMIN001']);
    
    echo "✅ Created admin user (admin@safekeep.com / admin123)\n";
    
    // Create demo user
    $demoEmail = 'demo@student.edu';
    $demoPassword = password_hash('demo123', PASSWORD_DEFAULT);
    
    $stmt->execute(['Demo', 'Student', $demoEmail, $demoPassword, 'student', 'STU001']);
    
    echo "✅ Created demo user (demo@student.edu / demo123)\n";
    
    echo "🎉 Database setup completed successfully!\n";
    echo "📧 Admin login: admin@safekeep.com / admin123\n";
    echo "👤 Demo login: demo@student.edu / demo123\n";
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>