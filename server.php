<?php
// Enhanced Railway startup script for SafeKeep

// Get port from environment variable, default to 8080
$port = getenv('PORT') ?: '8080';

echo "SafeKeep Lost & Found System - Starting server...\n";
echo "Port: $port\n";
echo "Document Root: .\n";

// Create a simple router for better URL handling
$router = __DIR__ . '/router.php';
if (!file_exists($router)) {
    file_put_contents($router, '<?php
// Simple router for PHP built-in server
$uri = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

// If file exists and is not a directory, serve it directly
if ($uri !== "/" && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Handle posts/view.php with ID parameter
if (preg_match("/^\/posts\/view\/(\d+)$/", $uri, $matches)) {
    $_GET["id"] = $matches[1];
    include __DIR__ . "/posts/view.php";
    return true;
}

// For other routes, let the application handle naturally
if (file_exists(__DIR__ . $uri . ".php")) {
    include __DIR__ . $uri . ".php";
    return true;
}

// Default routing
return false;
?>');
}

// Build the command with router
$command = "php -S 0.0.0.0:$port -t . router.php";

echo "Starting server with command: $command\n";
echo "SafeKeep is ready!\n";
echo "---\n";

// Execute the command
passthru($command);
?>