<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Set test environment
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');

// Include the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include core files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Load test configuration
Config::load();

// Override database settings for testing
Config::set('database.name', 'safekeep_test');

// Start session for testing
Session::init();