<?php
/**
 * Cleanup Script for Remote Printing System
 * Deletes old files and logs
 */

require_once __DIR__ . '/config.php';

function logMessage($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

function cleanupTempFiles($days = 1)
{
    $tempDir = TEMP_DIR;
    $now = time();
    $deleted = 0;
    
    if (!is_dir($tempDir)) {
        return 0;
    }
    
    $files = glob($tempDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = $now - filemtime($file);
            if ($fileAge > $days * 24 * 3600) {
                unlink($file);
                $deleted++;
            }
        }
    }
    
    return $deleted;
}

function cleanupOldLogs($days = 30)
{
    $logDir = dirname(LOG_FILE);
    $now = time();
    $deleted = 0;
    
    $logs = glob($logDir . '/*.log');
    foreach ($logs as $log) {
        if (is_file($log)) {
            $logAge = $now - filemtime($log);
            if ($logAge > $days * 24 * 3600) {
                unlink($log);
                $deleted++;
            }
        }
    }
    
    return $deleted;
}

function checkDiskUsage()
{
    $tempDir = TEMP_DIR;
    $totalSpace = disk_total_space($tempDir);
    $freeSpace = disk_free_space($tempDir);
    $usedPercent = 100 - (($freeSpace / $totalSpace) * 100);
    
    return [
        'total' => $totalSpace,
        'free' => $freeSpace,
        'used_percent' => $usedPercent
    ];
}

// Main execution
logMessage("Starting cleanup process");

$tempDeleted = cleanupTempFiles(1);
logMessage("Deleted {$tempDeleted} old temporary files");

$logDeleted = cleanupOldLogs(KEEP_LOGS_DAYS);
logMessage("Deleted {$logDeleted} old log files");

$diskUsage = checkDiskUsage();
logMessage(sprintf("Disk usage: %.2f%% used (%.2f GB free)", 
    $diskUsage['used_percent'], 
    $diskUsage['free'] / 1024 / 1024 / 1024
));

if ($diskUsage['used_percent'] > 80) {
    logMessage("Warning: Disk usage is above 80%", "WARNING");
}

logMessage("Cleanup process completed");
