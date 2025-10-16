<?php
// Railway direct startup for SafeKeep
// This file can be used as: php -S 0.0.0.0:$PORT index-server.php

// Get the port from environment
$port = getenv('PORT') ?: '8080';

// If we're being called as a server startup script
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'index-server.php') {
    echo "SafeKeep Direct Server Startup\n";
    echo "Port: $port\n";
    
    // Start the actual server
    $command = "php -S 0.0.0.0:$port -t .";
    echo "Executing: $command\n";
    passthru($command);
    exit;
}

// Normal index.php functionality for web requests
// If we get here, we're serving a web request, so load the normal index
if (file_exists(__DIR__ . '/index.php') && basename(__FILE__) !== 'index.php') {
    include __DIR__ . '/index.php';
} else {
    echo "<h1>SafeKeep Lost & Found System</h1>";
    echo "<p>Server is running on port: $port</p>";
    echo "<p>Please navigate to the main application.</p>";
}
?>