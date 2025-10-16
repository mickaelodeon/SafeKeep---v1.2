<?php
// Railway startup script for SafeKeep

// Get port from environment variable, default to 8080
$port = getenv('PORT') ?: '8080';

echo "Starting SafeKeep on port $port\n";

// Build the command
$command = "php -S 0.0.0.0:$port -t .";

echo "Executing: $command\n";

// Execute the command
passthru($command);
?>