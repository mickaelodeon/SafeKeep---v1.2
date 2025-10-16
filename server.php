<?php
// Railway startup script for SafeKeep Lost & Found System

// Debug environment variables
echo "=== SafeKeep Server Startup ===\n";
echo "PORT environment variable: " . (getenv('PORT') ?: 'not set') . "\n";
echo "All environment variables:\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'PORT') !== false) {
        echo "  $key = $value\n";
    }
}

// Get port from environment variable with multiple fallbacks
$port = getenv('PORT') ?: $_ENV['PORT'] ?? $_SERVER['PORT'] ?? '8080';

// Validate port is numeric
if (!is_numeric($port)) {
    echo "ERROR: PORT is not numeric: $port\n";
    echo "Falling back to 8080\n";
    $port = '8080';
}

echo "Using port: $port\n";
echo "Starting SafeKeep Lost & Found System...\n";
echo "Document root: " . getcwd() . "\n";

// Simple validation that port is available
$socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
if ($socket) {
    fclose($socket);
    echo "WARNING: Port $port appears to be in use\n";
}

// Build the server command without router for simplicity
$host = '0.0.0.0';
$docroot = '.';
$command = "php -S $host:$port -t $docroot";

echo "Command: $command\n";
echo "SafeKeep server starting...\n";
echo "========================\n\n";

// Use exec to replace current process (better for containers)
exec($command);
?>