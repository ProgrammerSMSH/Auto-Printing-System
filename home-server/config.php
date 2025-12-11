<?php
/**
 * Remote Printing System - Configuration File
 * 
 * IMPORTANT: Change these values according to your setup
 */

// API Configuration
define('API_BASE_URL', 'https://your-domain.com/api');
define('API_KEY', 'your-secure-api-key-here');
define('API_TIMEOUT', 30); // seconds

// Local Configuration
define('TEMP_DIR', __DIR__ . '/temp/');
define('LOG_FILE', __DIR__ . '/logs/cron.log');
define('PRINT_LOG_FILE', __DIR__ . '/logs/print.log');

// Python Script Configuration
define('PYTHON_SCRIPT', __DIR__ . '/print_job.py');
define('PYTHON_BIN', '/usr/bin/python3');

// Cleanup Settings
define('DELETE_AFTER_PRINT', true);
define('KEEP_LOGS_DAYS', 30);

// Printer Configuration
define('DEFAULT_PRINTER', 'HP_LaserJet'); // Your CUPS printer name

// Processing Settings
define('MAX_CONCURRENT_JOBS', 5);
define('PROCESS_DELAY', 2); // seconds between jobs

// Logging Settings
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_TO_CONSOLE', false);

// Network Settings
define('MAX_RETRIES', 3);
define('RETRY_DELAY', 5); // seconds between retries

// Security Settings
define('ALLOWED_FILE_TYPES', ['pdf']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Create required directories if they don't exist
$requiredDirs = [
    TEMP_DIR,
    dirname(LOG_FILE),
    dirname(PRINT_LOG_FILE)
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Error handling
if (!file_exists(PYTHON_SCRIPT)) {
    die("Error: Python script not found at " . PYTHON_SCRIPT . PHP_EOL);
}

if (!file_exists(PYTHON_BIN)) {
    die("Error: Python binary not found at " . PYTHON_BIN . PHP_EOL);
}

// Validate configuration
if (!filter_var(API_BASE_URL, FILTER_VALIDATE_URL)) {
    die("Error: Invalid API_BASE_URL: " . API_BASE_URL . PHP_EOL);
}

if (empty(API_KEY)) {
    die("Error: API_KEY is not set" . PHP_EOL);
}

if (empty(DEFAULT_PRINTER)) {
    die("Error: DEFAULT_PRINTER is not set" . PHP_EOL);
}
