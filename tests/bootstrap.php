<?php
/**
 * PHPUnit Bootstrap file for ESI Integration Tests
 */

// Load Composer autoloader
$autoloader = require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // .env file doesn't exist - will rely on environment variables or skip tests
    echo "Warning: .env file not found. Copy .env.example to .env and configure your credentials.\n";
}

// Helper function to get environment variable
function env($key, $default = '') {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

// Define test constants for easy access
define('ESI_CLIENT_ID', env('ESI_CLIENT_ID'));
define('ESI_CLIENT_SECRET', env('ESI_CLIENT_SECRET'));
define('ESI_REFRESH_TOKEN', env('ESI_REFRESH_TOKEN'));
define('ESI_CHARACTER_ID', env('ESI_CHARACTER_ID') ? (int)env('ESI_CHARACTER_ID') : 0);
define('ESI_BASE_URL', env('ESI_BASE_URL', 'https://esi.evetech.net'));
define('SSO_BASE_URL', env('SSO_BASE_URL', 'https://login.eveonline.com'));
define('ESI_DATASOURCE', env('ESI_DATASOURCE', 'tranquility'));

echo "\n";
echo "=================================================================\n";
echo "ESI Integration Test Bootstrap\n";
echo "=================================================================\n";
echo "ESI Base URL: " . ESI_BASE_URL . "\n";
echo "SSO Base URL: " . SSO_BASE_URL . "\n";
echo "Data Source: " . ESI_DATASOURCE . "\n";
echo "Character ID: " . (ESI_CHARACTER_ID ?: 'NOT SET') . "\n";
echo "Client ID: " . (ESI_CLIENT_ID ? 'SET' : 'NOT SET') . "\n";
echo "Client Secret: " . (ESI_CLIENT_SECRET ? 'SET' : 'NOT SET') . "\n";
echo "Refresh Token: " . (ESI_REFRESH_TOKEN ? 'SET' : 'NOT SET') . "\n";
echo "=================================================================\n\n";
