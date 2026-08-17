<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

initSession();

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
