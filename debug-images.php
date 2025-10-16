<?php
/**
 * Image Upload Diagnostic Tool
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Image Upload Diagnostic</h2>";

// Check uploads directory
$uploadsDir = __DIR__ . '/uploads';
echo "<h3>Uploads Directory Check:</h3>";

if (is_dir($uploadsDir)) {
    echo "<p>✅ Uploads directory exists: <code>" . htmlspecialchars($uploadsDir) . "</code></p>";
    
    // Check if writable
    if (is_writable($uploadsDir)) {
        echo "<p>✅ Directory is writable</p>";
    } else {
        echo "<p>❌ Directory is NOT writable</p>";
    }
    
    // List files in uploads directory
    $files = scandir($uploadsDir);
    $imageFiles = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
    });
    
    echo "<p><strong>Image files found:</strong> " . count($imageFiles) . "</p>";
    
    if (count($imageFiles) > 0) {
        echo "<h4>Sample Images:</h4>";
        echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
        
        $sampleFiles = array_slice($imageFiles, 0, 5); // Show first 5 images
        foreach ($sampleFiles as $file) {
            $webPath = Config::get('app.url') . '/uploads/' . $file;
            echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
            echo "<img src='" . htmlspecialchars($webPath) . "' style='max-width: 150px; max-height: 150px; object-fit: cover;' alt='Sample'><br>";
            echo "<small>" . htmlspecialchars($file) . "</small>";
            echo "</div>";
        }
        echo "</div>";
    }
    
} else {
    echo "<p>❌ Uploads directory does NOT exist: <code>" . htmlspecialchars($uploadsDir) . "</code></p>";
    
    // Try to create it
    echo "<p>Attempting to create uploads directory...</p>";
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<p>✅ Successfully created uploads directory</p>";
    } else {
        echo "<p>❌ Failed to create uploads directory</p>";
    }
}

// Check configuration
echo "<h3>Upload Configuration:</h3>";
$uploadConfig = Config::get('uploads');
echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Setting</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Max File Size</td><td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($uploadConfig['max_file_size']) . " bytes (" . round($uploadConfig['max_file_size']/1024/1024, 2) . " MB)</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Allowed Extensions</td><td style='border: 1px solid #ddd; padding: 8px;'>" . implode(', ', $uploadConfig['allowed_extensions']) . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Upload Directory</td><td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($uploadConfig['upload_dir']) . "</td></tr>";
echo "</table>";

// Check PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Setting</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>upload_max_filesize</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>post_max_size</td><td style='border: 1px solid #ddd; padding: 8px;'>" . ini_get('post_max_size') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>file_uploads</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</td></tr>";
echo "</table>";

// Test a sample post's image path
echo "<h3>Sample Post Image Test:</h3>";
try {
    $config = Config::get('database');
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $db->prepare("SELECT id, title, photo_path FROM posts WHERE photo_path IS NOT NULL AND photo_path != '' LIMIT 5");
    $stmt->execute();
    $postsWithImages = $stmt->fetchAll();
    
    if (count($postsWithImages) > 0) {
        echo "<p><strong>Posts with images:</strong></p>";
        foreach ($postsWithImages as $post) {
            $imagePath = $uploadsDir . '/' . $post['photo_path'];
            $webPath = Config::get('app.url') . '/uploads/' . $post['photo_path'];
            
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Post:</strong> " . htmlspecialchars($post['title']) . "<br>";
            echo "<strong>Photo Path:</strong> " . htmlspecialchars($post['photo_path']) . "<br>";
            echo "<strong>File Exists:</strong> " . (file_exists($imagePath) ? '✅ Yes' : '❌ No') . "<br>";
            echo "<strong>Web URL:</strong> <a href='" . htmlspecialchars($webPath) . "' target='_blank'>" . htmlspecialchars($webPath) . "</a><br>";
            
            if (file_exists($imagePath)) {
                echo "<img src='" . htmlspecialchars($webPath) . "' style='max-width: 200px; max-height: 150px; object-fit: cover; margin-top: 10px;' alt='Test Image'>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No posts with images found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>📋 Summary & Solutions:</h3>";
echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #b8daff; border-radius: 6px;'>";
echo "<h4>If images aren't showing:</h4>";
echo "<ol>";
echo "<li><strong>Check if uploads directory exists</strong> (should be created above)</li>";
echo "<li><strong>Verify image files are in /uploads/</strong></li>";
echo "<li><strong>Test image URLs directly</strong> in browser</li>";
echo "<li><strong>Check Railway deployment</strong> - uploads folder might not be included</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
table { border-collapse: collapse; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>