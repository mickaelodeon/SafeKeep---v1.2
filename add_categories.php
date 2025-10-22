<?php
// Add default categories to the database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=safekeep_db', 'root', 'r91hyai8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $categories = [
        ['Electronics', 'Devices like phones, laptops, tablets, etc.'],
        ['Books', 'Textbooks, notebooks, and other reading materials'],
        ['Clothing', 'Uniforms, jackets, shoes, hats, etc.'],
        ['Bags', 'Backpacks, purses, wallets, etc.'],
        ['Accessories', 'Watches, jewelry, glasses, etc.'],
        ['Sports', 'Sports equipment and gear'],
        ['Stationery', 'Pens, calculators, ID cards, etc.'],
        ['Others', 'Anything not listed above']
    ];
    
    $pdo->exec('DELETE FROM categories');
    $stmt = $pdo->prepare('INSERT INTO categories (name, description, is_active) VALUES (?, ?, 1)');
    foreach ($categories as $cat) {
        $stmt->execute([$cat[0], $cat[1]]);
    }
    echo "✅ Default categories added!\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
