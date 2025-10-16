<?php
/**
 * Composer Dependencies Check for Railway
 */

declare(strict_types=1);

echo "<h2>Composer Dependencies Diagnostic</h2>";

// Check if composer.json exists
echo "<h3>1. Composer Files Check:</h3>";
$composerJson = __DIR__ . '/composer.json';
$composerLock = __DIR__ . '/composer.lock';
$vendorDir = __DIR__ . '/vendor';
$autoload = __DIR__ . '/vendor/autoload.php';

echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>File</th><th style='border: 1px solid #ddd; padding: 8px;'>Status</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>composer.json</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (file_exists($composerJson) ? '✅ Exists' : '❌ Missing') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>composer.lock</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (file_exists($composerLock) ? '✅ Exists' : '❌ Missing') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>vendor/</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (is_dir($vendorDir) ? '✅ Exists' : '❌ Missing') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>vendor/autoload.php</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (file_exists($autoload) ? '✅ Exists' : '❌ Missing') . "</td></tr>";
echo "</table>";

// Check vendor directory contents
echo "<h3>2. Vendor Directory Analysis:</h3>";
if (is_dir($vendorDir)) {
    $vendorContents = scandir($vendorDir);
    $packageDirs = array_filter($vendorContents, function($item) use ($vendorDir) {
        return $item !== '.' && $item !== '..' && is_dir($vendorDir . '/' . $item);
    });
    
    echo "<p><strong>Vendor packages found:</strong> " . count($packageDirs) . "</p>";
    
    // Check specifically for PHPMailer
    $phpmailerDir = $vendorDir . '/phpmailer';
    echo "<p><strong>PHPMailer:</strong> " . (is_dir($phpmailerDir) ? '✅ Installed' : '❌ Not found') . "</p>";
    
    if (is_dir($phpmailerDir)) {
        $phpmailerSubDirs = scandir($phpmailerDir);
        echo "<p><strong>PHPMailer subdirs:</strong> " . implode(', ', array_filter($phpmailerSubDirs, function($item) { return $item !== '.' && $item !== '..'; })) . "</p>";
    }
    
    // List first 10 packages
    $samplePackages = array_slice($packageDirs, 0, 10);
    echo "<p><strong>Sample packages:</strong> " . implode(', ', $samplePackages) . "</p>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px;'>";
    echo "<h4>❌ Vendor Directory Missing!</h4>";
    echo "<p>This means Composer dependencies were not installed during Railway deployment.</p>";
    echo "</div>";
}

// Test autoload
echo "<h3>3. Autoloader Test:</h3>";
if (file_exists($autoload)) {
    try {
        require_once $autoload;
        echo "<p>✅ Autoloader loaded successfully</p>";
        
        // Test PHPMailer class
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p>✅ PHPMailer class available</p>";
        } else {
            echo "<p>❌ PHPMailer class not found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Autoloader error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ Autoloader not available</p>";
}

// Check PHP version and extensions
echo "<h3>4. PHP Environment:</h3>";
echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Setting</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>PHP Version</td><td style='border: 1px solid #ddd; padding: 8px;'>" . PHP_VERSION . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Composer Available</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (shell_exec('which composer') ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>cURL Extension</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (extension_loaded('curl') ? '✅ Loaded' : '❌ Missing') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>OpenSSL Extension</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (extension_loaded('openssl') ? '✅ Loaded' : '❌ Missing') . "</td></tr>";
echo "</table>";

// Railway-specific checks
echo "<h3>5. Railway Environment:</h3>";
$railwayEnv = $_ENV['RAILWAY_ENVIRONMENT'] ?? $_SERVER['RAILWAY_ENVIRONMENT'] ?? 'Not detected';
echo "<p><strong>Railway Environment:</strong> " . htmlspecialchars($railwayEnv) . "</p>";

// Show build information if available
$buildCmd = shell_exec('cat /proc/version 2>/dev/null') ?: 'N/A';
echo "<p><strong>System:</strong> " . htmlspecialchars(substr($buildCmd, 0, 100)) . "</p>";

// Solutions
echo "<h3>🔧 Solutions:</h3>";

if (!is_dir($vendorDir)) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 15px 0;'>";
    echo "<h4>🚨 Missing Vendor Directory</h4>";
    echo "<p><strong>Problem:</strong> Composer dependencies not installed during Railway deployment</p>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Check Railway build logs</strong> for Composer errors</li>";
    echo "<li><strong>Verify nixpacks.toml</strong> has correct install command</li>";
    echo "<li><strong>Force rebuild</strong> Railway deployment</li>";
    echo "<li><strong>Alternative:</strong> Install PHPMailer manually without Composer</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b8daff; border-radius: 6px; margin: 15px 0;'>";
    echo "<h4>🎯 Quick Fix: Manual PHPMailer Installation</h4>";
    echo "<p>If Composer continues to fail, we can install PHPMailer manually:</p>";
    echo "<ol>";
    echo "<li>Download PHPMailer directly</li>";
    echo "<li>Include it without Composer</li>";
    echo "<li>Update Email class to use manual inclusion</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px;'>";
    echo "<h4>✅ Dependencies Look Good</h4>";
    echo "<p>Vendor directory exists. Email functionality should work.</p>";
    echo "</div>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
table { border-collapse: collapse; }
</style>