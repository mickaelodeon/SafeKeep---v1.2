<?php
/**
 * User Logout
 * Secure session termination
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

Session::init();

// Log out user
Session::logout();

// Redirect to login page with message
Utils::redirect(Config::get('app.url') . '/auth/login.php', 'You have been logged out successfully.', 'success');