#!/usr/bin/env php
<?php
/**
 * Remote Printing System - Cron Job Processor
 * 
 * This script runs every minute via cron to:
 * 1. Fetch pending print jobs from the API
 * 2. Download PDF files
 * 3. Update job status to processing
 * 4. Execute Python print script
 * 5. Update job status on completion
 * 6. Clean up temporary files
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

/**
 * Log a message with timestamp
 */
function logMessage($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    
    if (defined('LOG_TO_CONSOLE') && LOG_TO_CONSOLE) {
        echo $logEntry;
    }
}

/**
 * Make API request with authentication
 */
function makeApiRequest($endpoint, $method = 'GET', $data = null)
{
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init();
    
    $headers = [
        'X-API-Key: ' . API_KEY,
        'Content-Type: application/json',
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL error: {$error}");
    }
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

/**
 * Download file from API
 */
function downloadFile($jobId, $filename)
{
    $tempFile = TEMP_DIR . $jobId . '_' . $filename;
    $url = API_BASE_URL . '/download/' . $jobId;
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-API-Key: ' . API_KEY],
        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    $fileContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Download failed with HTTP code: {$httpCode}");
    }
    
    if ($error) {
        throw new Exception("Download error: {$error}");
    }
    
    // Save file
    if (file_put_contents($tempFile, $fileContent) === false) {
        throw new Exception("Failed to save file: {$tempFile}");
    }
    
    // Verify file exists and has content
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception("Downloaded file is empty or doesn't exist");
    }
    
    return $tempFile;
}

/**
 * Update job status on API
 */
function updateJobStatus($jobId, $status, $error = null)
{
    $data = ['status' => $status];
    
    if ($error) {
        $data['error_message'] = $error;
    }
    
    if ($status == 2) {
        $data['processed_at'] = date('Y-m-d H:i:s');
    } elseif ($status == 3) {
        $data['completed_at'] = date('Y-m-d H:i:s');
    }
    
    try {
        $response = makeApiRequest('/print/update/' . $jobId, 'PUT', $data);
        
        if ($response['code'] !== 200) {
            throw new Exception("API returned code: {$response['code']}");
        }
        
        return true;
    } catch (Exception $e) {
        logMessage("Failed to update job status: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Process a single print job
 */
function processJob($job)
{
    $jobId = $job['job_id'];
    $filename = $job['filename'];
    
    logMessage("Processing job: {$jobId} - {$filename}");
    
    try {
        // Update status to processing
        if (!updateJobStatus($jobId, 2)) {
            throw new Exception("
