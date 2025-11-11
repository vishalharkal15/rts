<?php
// Railway.app specific configuration

// Get the Railway domain from environment or set default
$railway_domain = getenv('RAILWAY_PUBLIC_DOMAIN') ?: 'rts-production.up.railway.app';

// Allow Railway domains
$allowed_hosts = [
    'localhost',
    '127.0.0.1',
    'rts-production.up.railway.app',
    $railway_domain
];

// Validate the host
$current_host = $_SERVER['HTTP_HOST'] ?? '';

// Trust Railway's proxy headers
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_SERVER['HTTPS'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'on' : 'off';
}

if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

// Set secure session cookies for production
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

// Increase session lifetime for production
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600);
?>
