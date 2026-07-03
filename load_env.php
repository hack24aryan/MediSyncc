<?php
function loadEnv() {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        die("Configuration error: .env file not found. Please copy `.env.example` to `.env` and configure your environment before running the application.");
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, '"\'');
            if (!defined($key)) define($key, $value);
        }
    }
}
loadEnv();
?>
