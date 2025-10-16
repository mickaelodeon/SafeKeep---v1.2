<?php
// SafeKeep Railway Launcher
// This script handles the PORT variable and starts the server properly

echo "SafeKeep Railway Launcher Starting...\n";

// Get PORT from environment with debugging
$port = null;
$portSources = [
    'getenv' => getenv('PORT'),
    '_ENV' => $_ENV['PORT'] ?? null,
    '_SERVER' => $_SERVER['PORT'] ?? null
];

echo "Port detection:\n";
foreach ($portSources as $source => $value) {
    echo "  $source: " . ($value ?: 'not set') . "\n";
    if ($value && is_numeric($value)) {
        $port = $value;
    }
}

// Debug database environment variables
echo "\nDatabase environment variables:\n";
$dbVars = [
    'MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD',
    'MYSQLHOST', 'MYSQLUSER', 'MYSQLDATABASE', 'MYSQLPASSWORD',
    'RAILWAY_PRIVATE_DOMAIN', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'
];
foreach ($dbVars as $var) {
    $value = getenv($var) ?: $_ENV[$var] ?? null;
    echo "  $var: " . ($value ? (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) : 'not set') . "\n";
}

// Fallback to 8080 if no port found
if (!$port) {
    $port = '8080';
    echo "No valid PORT found, using fallback: $port\n";
} else {
    echo "Using PORT: $port\n";
}

// Check if we're already running as a server
if (isset($_SERVER['REQUEST_URI'])) {
    // We're handling a web request, route it properly
    $uri = $_SERVER['REQUEST_URI'];
    echo "Handling web request: $uri\n";
    
    // Simple routing
    if ($uri === '/' || $uri === '/index.php') {
        if (file_exists('index.php')) {
            include 'index.php';
            exit;
        }
    }
    
    // Default response
    echo "<h1>SafeKeep Lost & Found System</h1>";
    echo "<p>Server is running on port $port</p>";
    echo "<p><a href='/'>Go to SafeKeep</a></p>";
    exit;
}

// We're being run from command line, start the server
echo "Starting PHP built-in server...\n";
echo "Command: php -S 0.0.0.0:$port -t .\n";

// Use passthru to show output
passthru("php -S 0.0.0.0:$port -t .");
?>