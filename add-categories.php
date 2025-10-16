<?php
/**
 * Add Default Categories for SafeKeep Lost & Found
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Adding Default Categories</h2>";

try {
    $config = Config::get('database');
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h3>Checking existing categories...</h3>";
    
    // Check if categories table exists and has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    $existingCount = $result['count'];
    
    echo "<p>Found {$existingCount} existing categories</p>";
    
    // Default categories for a school lost & found system
    $defaultCategories = [
        ['name' => 'Electronics', 'description' => 'Phones, tablets, laptops, chargers, headphones, etc.'],
        ['name' => 'Clothing', 'description' => 'Jackets, shirts, pants, shoes, hats, etc.'],
        ['name' => 'Bags & Backpacks', 'description' => 'Backpacks, purses, lunch bags, sports bags, etc.'],
        ['name' => 'Books & Supplies', 'description' => 'Textbooks, notebooks, pens, calculators, etc.'],
        ['name' => 'Sports Equipment', 'description' => 'Balls, gear, uniforms, water bottles, etc.'],
        ['name' => 'Jewelry & Accessories', 'description' => 'Watches, rings, necklaces, sunglasses, etc.'],
        ['name' => 'Keys & Cards', 'description' => 'House keys, car keys, ID cards, credit cards, etc.'],
        ['name' => 'Personal Items', 'description' => 'Wallets, makeup, personal care items, etc.'],
        ['name' => 'Musical Instruments', 'description' => 'Guitars, flutes, sheet music, etc.'],
        ['name' => 'Other', 'description' => 'Items that don\'t fit other categories']
    ];
    
    echo "<h3>Adding categories...</h3>";
    
    $addedCount = 0;
    $skippedCount = 0;
    
    foreach ($defaultCategories as $category) {
        // Check if category already exists
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$category['name']]);
        
        if ($stmt->fetch()) {
            echo "<p>⚠️ Category '{$category['name']}' already exists - skipped</p>";
            $skippedCount++;
        } else {
            // Insert new category
            $stmt = $db->prepare("
                INSERT INTO categories (name, description, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())
            ");
            $stmt->execute([$category['name'], $category['description']]);
            echo "<p>✅ Added category: <strong>{$category['name']}</strong></p>";
            $addedCount++;
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 15px 0;'>";
    echo "<p><strong>Categories added:</strong> {$addedCount}</p>";
    echo "<p><strong>Categories skipped:</strong> {$skippedCount}</p>";
    echo "<p><strong>Total categories now:</strong> " . ($existingCount + $addedCount) . "</p>";
    echo "</div>";
    
    // Display all current categories
    echo "<h3>All Current Categories</h3>";
    $stmt = $db->query("SELECT id, name, description, created_at FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa; border: 1px solid #ddd;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Name</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Description</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Created</th>";
    echo "</tr>";
    
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$cat['id']}</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>" . htmlspecialchars($cat['name']) . "</strong></td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($cat['description']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$cat['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 15px 0;'>";
    echo "<h4>✅ Categories Setup Complete!</h4>";
    echo "<p>Users can now select from these categories when posting lost or found items.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test posting a new item to see the category dropdown</li>";
    echo "<li>Categories will help users browse and filter items more easily</li>";
    echo "<li>You can add more categories through the admin panel if needed</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 15px 0;'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
table { border-collapse: collapse; }
</style>