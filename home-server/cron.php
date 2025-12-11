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
            throw new Exception("Failed to update job status to processing");
        }
        
        // Download file
        logMessage("Downloading file for job: {$jobId}");
        $tempFile = downloadFile($jobId, $filename);
        logMessage("File downloaded to: {$tempFile}");
        
        // Prepare print options
        $printOptions = [
            'paper_size' => $job['paper_size'],
            'color_mode' => $job['color_mode'],
            'page_range' => $job['page_range'],
            'copies' => (int)$job['copies']
        ];
        
        $printerName = $job['printer_name'] ?? DEFAULT_PRINTER;
        
        // Execute Python print script
        $command = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellarg(PYTHON_BIN),
            escapeshellarg(PYTHON_SCRIPT),
            escapeshellarg($tempFile),
            escapeshellarg($printerName),
            escapeshellarg(json_encode($printOptions))
        );
        
        logMessage("Executing print command: {$command}");
        
        exec($command, $output, $returnCode);
        
        $outputStr = implode("\n", $output);
        logMessage("Print script output: {$outputStr}");
        
        if ($returnCode === 0) {
            // Success
            logMessage("Print job completed successfully: {$jobId}");
            updateJobStatus($jobId, 3);
            
            // Clean up temporary file
            if (DELETE_AFTER_PRINT && file_exists($tempFile)) {
                unlink($tempFile);
                logMessage("Temporary file deleted: {$tempFile}");
            }
            
            return true;
        } else {
            // Failure
            $errorMsg = "Print script failed with code: {$returnCode}. Output: {$outputStr}";
            logMessage($errorMsg, 'ERROR');
            updateJobStatus($jobId, 1, $errorMsg);
            
            // Don't delete file on failure for debugging
            return false;
        }
        
    } catch (Exception $e) {
        $errorMsg = "Job processing failed: " . $e->getMessage();
        logMessage($errorMsg, 'ERROR');
        updateJobStatus($jobId, 1, $errorMsg);
        return false;
    }
}

/**
 * Main execution
 */
function main()
{
    logMessage("Cron job started");
    
    // Check if temp directory exists
    if (!is_dir(TEMP_DIR)) {
        mkdir(TEMP_DIR, 0755, true);
        logMessage("Created temp directory: " . TEMP_DIR);
    }
    
    // Clean up old temp files (older than 24 hours)
    cleanTempFiles();
    
    try {
        // Fetch pending jobs from API
        logMessage("Fetching pending jobs from API...");
        $response = makeApiRequest('/print/pending');
        
        if ($response['code'] !== 200) {
            throw new Exception("API returned HTTP code: {$response['code']}");
        }
        
        if (!isset($response['body']['status']) || $response['body']['status'] !== 'success') {
            throw new Exception("API response error: " . json_encode($response['body']));
        }
        
        $jobs = $response['body']['data'] ?? [];
        $jobCount = count($jobs);
        
        logMessage("Found {$jobCount} pending job(s)");
        
        if ($jobCount === 0) {
            logMessage("No pending jobs to process");
            return;
        }
        
        // Process each job
        $processed = 0;
        $failed = 0;
        
        foreach ($jobs as $job) {
            if (processJob($job)) {
                $processed++;
            } else {
                $failed++;
            }
            
            // Small delay between jobs to prevent overwhelming the printer
            if (defined('PROCESS_DELAY') && PROCESS_DELAY > 0) {
                sleep(PROCESS_DELAY);
            }
        }
        
        logMessage("Cron job completed. Processed: {$processed}, Failed: {$failed}");
        
    } catch (Exception $e) {
        logMessage("Cron job failed: " . $e->getMessage(), 'ERROR');
        exit(1);
    }
}

/**
 * Clean up old temporary files
 */
function cleanTempFiles()
{
    $files = glob(TEMP_DIR . '*');
    $now = time();
    $cleaned = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = $now - filemtime($file);
            if ($fileAge > 24 * 3600) { // 24 hours
                unlink($file);
                $cleaned++;
            }
        }
    }
    
    if ($cleaned > 0) {
        logMessage("Cleaned up {$cleaned} old temporary files");
    }
}

// Run main function
main();
