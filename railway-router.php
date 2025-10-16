<?php
// Railway PHP Router - handles requests and PORT variable issues
// This file is used when Railway runs: php -S 0.0.0.0:$PORT -t . railway-router.php

// Get the URI and method
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];

// Log the request for debugging
error_log("SafeKeep Router - $method $uri");

// If it's a static file and exists, serve it
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    // Let PHP serve the static file
    return false;
}

// Handle common routes
switch ($uri) {
    case '/':
    case '/index.php':
        if (file_exists(__DIR__ . '/index.php')) {
            include __DIR__ . '/index.php';
            return true;
        }
        break;
        
    case '/login':
    case '/login.php':
        if (file_exists(__DIR__ . '/login.php')) {
            include __DIR__ . '/login.php';
            return true;
        }
        break;
        
    case '/signup':
    case '/signup.php':
        if (file_exists(__DIR__ . '/signup.php')) {
            include __DIR__ . '/signup.php';
            return true;
        }
        break;
        
    case '/dashboard':
    case '/dashboard.php':
        if (file_exists(__DIR__ . '/dashboard.php')) {
            include __DIR__ . '/dashboard.php';
            return true;
        }
        break;
        
    case '/logout':
    case '/logout.php':
        if (file_exists(__DIR__ . '/logout.php')) {
            include __DIR__ . '/logout.php';
            return true;
        }
        break;
}

// Handle posts routes
if (preg_match('#^/posts/?$#', $uri)) {
    if (file_exists(__DIR__ . '/posts/index.php')) {
        include __DIR__ . '/posts/index.php';
        return true;
    }
}

// Handle posts/view with ID
if (preg_match('#^/posts/view/(\d+)/?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    if (file_exists(__DIR__ . '/posts/view.php')) {
        include __DIR__ . '/posts/view.php';
        return true;
    }
}

// Handle posts directory files
if (preg_match('#^/posts/(.+\.php)$#', $uri, $matches)) {
    $file = __DIR__ . '/posts/' . $matches[1];
    if (file_exists($file)) {
        include $file;
        return true;
    }
}

// Try to find PHP files directly
$phpFile = __DIR__ . $uri;
if (substr($uri, -4) !== '.php') {
    $phpFile .= '.php';
}

if (file_exists($phpFile)) {
    include $phpFile;
    return true;
}

// Default fallback to index
if (file_exists(__DIR__ . '/index.php')) {
    include __DIR__ . '/index.php';
    return true;
}

// 404 if nothing matches
http_response_code(404);
echo "<h1>404 Not Found</h1><p>SafeKeep - Page not found: " . htmlspecialchars($uri) . "</p>";
return true;
?>