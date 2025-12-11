# Print Management System - Troubleshooting Guide

## Table of Contents
- [Common Issues](#common-issues)
- [Database Problems](#database-problems)
- [Upload Issues](#upload-issues)
- [Printer Problems](#printer-problems)
- [API Errors](#api-errors)
- [Performance Issues](#performance-issues)
- [Security Issues](#security-issues)
- [Debugging Tools](#debugging-tools)

---

## Common Issues

### 1. "API key required" Error

**Symptoms:**
```json
{
  "status": "error",
  "message": "API key required"
}
```

**Causes:**
- Missing API key in request header
- Incorrect header name
- API key not generated

**Solutions:**

**Check header format:**
```bash
# Correct
curl -H "X-API-Key: your_key_here" http://yourserver/api/print/status/123

# Incorrect
curl -H "API-Key: your_key_here" http://yourserver/api/print/status/123
```

**Generate API key:**
```sql
-- Check if API keys exist
SELECT COUNT(*) FROM api_keys WHERE is_active = 1;

-- Generate new key if needed
INSERT INTO api_keys (api_key, user_name, email, is_active) VALUES
(SHA2(CONCAT('user', NOW(), RAND()), 256), 'Test User', 'test@example.com', 1);
```

**Verify in PHP:**
```php
// Add to your endpoint
$headers = getallheaders();
echo "Headers received: ";
print_r($headers);
```

---

### 2. File Upload Fails

**Symptoms:**
- "No file uploaded" error
- Upload returns error 413
- Upload times out

**Causes:**
- File too large
- PHP settings restrictive
- Directory permissions wrong
- Disk space full

**Solutions:**

**Check PHP settings:**
```bash
php -i | grep -E 'upload_max_filesize|post_max_size|max_execution_time'
```

**Update php.ini:**
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

**For Apache (.htaccess):**
```apache
php_value upload_max_filesize 10M
php_value post_max_size 12M
php_value max_execution_time 300
```

**For Nginx:**
```nginx
client_max_body_size 10M;
```

**Check directory permissions:**
```bash
# Linux
ls -la uploads/
sudo chmod 777 uploads/
sudo chown www-data:www-data uploads/

# Windows
icacls uploads /grant IIS_IUSRS:(OI)(CI)F
```

**Check disk space:**
```bash
# Linux
df -h

# Windows
wmic logicaldisk get size,freespace,caption
```

---

### 3. Database Connection Failed

**Symptoms:**
```
PDOException: SQLSTATE[HY000] [2002] Connection refused
```

**Causes:**
- MySQL service not running
- Wrong credentials
- Database doesn't exist
- Firewall blocking connection

**Solutions:**

**Check MySQL service:**
```bash
# Linux
sudo systemctl status mysql
sudo systemctl start mysql

# Windows
net start MySQL80
```

**Verify credentials:**
```bash
mysql -u printuser -p
# Enter password and see if you can connect
```

**Test from PHP:**
```php
<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=print_management', 'printuser', 'password');
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

**Check MySQL logs:**
```bash
# Linux
sudo tail -f /var/log/mysql/error.log

# Windows
# Check: C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err
```

**Verify database exists:**
```sql
SHOW DATABASES LIKE 'print_management';
```

---

### 4. Printers Not Detected

**Symptoms:**
- Empty printers list
- "No printers available" error
- Printer status shows "offline"

**Solutions:**

**Windows - Check Printers:**
```powershell
# List all printers
Get-Printer | Format-Table Name, PrinterStatus, DriverName

# Check print spooler
Get-Service -Name Spooler
Start-Service -Name Spooler
```

**Linux - Check CUPS:**
```bash
# Check CUPS status
sudo systemctl status cups

# List printers
lpstat -p -d

# Verify printer is available
lp -d PrinterName test.txt
```

**Verify in Database:**
```sql
SELECT * FROM printers;

-- Update printer status
UPDATE printers SET status = 'online' WHERE name = 'HP_LaserJet';
```

**Add missing printer:**
```sql
INSERT INTO printers (name, description, paper_sizes, color, status) VALUES
('YourPrinter', 'Description', '["A4","Letter"]', 0, 'online');
```

**Test with PHP:**
```php
<?php
// Windows
$printers = shell_exec('wmic printer get name,status');
echo "<pre>$printers</pre>";

// Linux
$printers = shell_exec('lpstat -p');
echo "<pre>$printers</pre>";
?>
```

---

### 5. Print Jobs Stuck in "Pending"

**Symptoms:**
- Jobs remain in status 1 (Pending)
- No error messages
- Printer shows as online

**Causes:**
- Print service not running
- Printer spooler issue
- PHP exec/shell_exec disabled
- File path incorrect

**Solutions:**

**Check PHP functions:**
```php
<?php
// Test if exec is enabled
if (function_exists('exec')) {
    echo "exec() is available\n";
    exec('echo test', $output);
    print_r($output);
} else {
    echo "exec() is disabled\n";
}
?>
```

**Enable exec in php.ini:**
```ini
# Remove exec from disable_functions
disable_functions = 
```

**Check file paths:**
```sql
SELECT job_id, filepath FROM print_jobs WHERE status = 1;
```

**Manually test print:**
```bash
# Windows
print /d:"PrinterName" "C:\path\to\file.pdf"

# Linux
lp -d PrinterName /path/to/file.pdf
```

**Check error logs:**
```bash
tail -f logs/error.log
```

**Force job processing:**
```php
<?php
// Create process_pending.php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/printer.php';

$stmt = $pdo->query("SELECT * FROM print_jobs WHERE status = 1 LIMIT 10");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    echo "Processing job: {$job['job_id']}\n";
    $result = processPrintJob($job);
    echo "Result: " . ($result ? "Success" : "Failed") . "\n";
}
?>
```

---

## Database Problems

### Slow Queries

**Symptoms:**
- API responses take > 2 seconds
- Database CPU high

**Diagnosis:**
```sql
-- Show slow queries
SHOW PROCESSLIST;

-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check indexes
SHOW INDEX FROM print_jobs;
```

**Solutions:**

**Add missing indexes:**
```sql
CREATE INDEX idx_status_uploaded ON print_jobs(status, uploaded_at);
CREATE INDEX idx_api_key_active ON api_keys(api_key, is_active);
```

**Optimize tables:**
```sql
OPTIMIZE TABLE print_jobs;
OPTIMIZE TABLE api_keys;
ANALYZE TABLE print_jobs;
```

**Clean old data:**
```sql
-- Delete completed jobs older than 90 days
DELETE FROM print_jobs 
WHERE status = 3 
AND completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

### Database Locked

**Symptoms:**
```
SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
```

**Solutions:**

**Find blocking queries:**
```sql
-- MySQL 5.7+
SELECT * FROM information_schema.innodb_trx;
SELECT * FROM information_schema.innodb_locks;

-- Kill blocking process
KILL <process_id>;
```

**Increase timeout:**
```sql
SET GLOBAL innodb_lock_wait_timeout = 120;
```

---

## Upload Issues

### File Type Rejected

**Symptoms:**
- "Invalid file type" error
- Only PDFs should work

**Solutions:**

**Check MIME type detection:**
```php
<?php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $uploadedFile);
echo "Detected MIME type: $mimeType\n";
finfo_close($finfo);

// Should return: application/pdf
?>
```

**Verify file extension:**
```php
<?php
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
echo "Extension: $ext\n";

// Allowed extensions
$allowed = ['pdf'];
if (!in_array($ext, $allowed)) {
    die("Only PDF files allowed");
}
?>
```

---

### Corrupt PDF Files

**Symptoms:**
- Upload succeeds but print fails
- "Invalid PDF" error

**Solutions:**

**Validate PDF:**
```php
<?php
function isValidPDF($filepath) {
    $handle = fopen($filepath, 'r');
    $header = fread($handle, 4);
    fclose($handle);
    
    return $header === '%PDF';
}

if (!isValidPDF($uploadedFile)) {
    die("Invalid PDF file");
}
?>
```

**Use Ghostscript to validate:**
```bash
# Linux
gs -dNOPAUSE -dBATCH -sDEVICE=nullpage uploaded.pdf

# If exits with 0, PDF is valid
echo $?
```

---

## Printer Problems

### Printer Offline

**Solutions:**

**Windows:**
```powershell
# Set printer online
Set-Printer -Name "PrinterName" -PrinterStatus Online

# Restart print spooler
Restart-Service -Name Spooler
```

**Linux:**
```bash
# Enable printer
cupsenable PrinterName

# Restart CUPS
sudo systemctl restart cups
```

---

### Print Quality Issues

**Symptoms:**
- Faded prints
- Missing pages
- Incorrect colors

**Solutions:**

1. Check printer settings in code
2. Verify paper size matches
3. Test direct print from computer
4. Clean printer heads
5. Update printer drivers

---

## API Errors

### Rate Limit Exceeded

**Symptoms:**
```json
{
  "status": "error",
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 3600
}
```

**Solutions:**

**Check rate limits:**
```sql
SELECT api_key_id, COUNT(*) as requests, window_start 
FROM rate_limits 
WHERE window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY api_key_id;
```

**Reset rate limit:**
```sql
DELETE FROM rate_limits WHERE api_key_id = <your_key_id>;
```

**Increase limit for specific key:**
```sql
UPDATE api_keys SET rate_limit = 500 WHERE id = <key_id>;
```

---

### CORS Errors

**Symptoms:**
```
Access to fetch at '...' from origin '...' has been blocked by CORS policy
```

**Solutions:**

**Add CORS headers (PHP):**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

**For Apache (.htaccess):**
```apache
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, X-API-Key"
```

**For Nginx:**
```nginx
add_header Access-Control-Allow-Origin *;
add_header Access-Control-Allow-Methods 'GET, POST, PUT, DELETE, OPTIONS';
add_header Access-Control-Allow-Headers 'Content-Type, X-API-Key';
```

---

## Performance Issues

### Slow Response Times

**Causes:**
- Large database
- No caching
- Slow disk I/O
- Heavy queries

**Solutions:**

**Enable query caching:**
```sql
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
```

**Use PHP OPcache:**
```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

**Add caching layer:**
```php
<?php
// Simple file-based cache
function getCached($key, $ttl = 3600) {
    $file = "cache/$key.cache";
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        return unserialize(file_get_contents($file));
    }
    return null;
}

function setCache($key, $data) {
    file_put_contents("cache/$key.cache", serialize($data));
}
?>
```

---

### High Disk Usage

**Causes:**
- Old uploads not deleted
- Large log files
- Failed job files

**Solutions:**

**Clean old uploads:**
```bash
# Delete completed jobs older than 30 days
find uploads/ -mtime +30 -type f -delete
```

**Rotate logs:**
```bash
# Use logrotate (Linux)
cat > /etc/logrotate.d/print-system << EOF
/var/www/print-system/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
}
EOF
```

**Database cleanup:**
```sql
-- Archive old jobs
CREATE TABLE print_jobs_archive LIKE print_jobs;
INSERT INTO print_jobs_archive 
SELECT * FROM print_jobs 
WHERE completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM print_jobs 
WHERE completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## Security Issues

### Unauthorized Access

**Symptoms:**
- API accessed without proper key
- Suspicious activity in logs

**Solutions:**

**Audit API key usage:**
```sql
SELECT ak.user_name, ak.email, COUNT(pj.id) as jobs, 
       MAX(pj.uploaded_at) as last_used
FROM api_keys ak
LEFT JOIN print_jobs pj ON ak.id = pj.api_key_id
GROUP BY ak.id;
```

**Revoke compromised keys:**
```sql
UPDATE api_keys SET is_active = 0 WHERE api_key = '<compromised_key>';
```

**Enable IP whitelisting:**
```php
<?php
$allowed_ips = ['192.168.1.100', '10.0.0.50'];
$client_ip = $_SERVER['REMOTE_ADDR'];

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Access denied']));
}
?>
```

---

### File Upload Vulnerabilities

**Solutions:**

**Validate file content:**
```php
<?php
// Don't trust file extension alone
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpFile);
finfo_close($finfo);

if ($mimeType !== 'application/pdf') {
    die("Only PDF files allowed");
}

// Rename uploaded files
$safeFilename = bin2hex(random_bytes(16)) . '.pdf';
move_uploaded_file($tmpFile, UPLOAD_DIR . $safeFilename);
?>
```

**Prevent directory traversal:**
```php
<?php
// Sanitize job_id
$job_id = preg_replace('/[^a-zA-Z0-9-_]/', '', $_GET['job_id']);
```

---

## Debugging Tools

### Enable Debug Mode

**In config.php:**
```php
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG');

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

### Custom Debug Function

```php
<?php
function debugLog($message, $data = null) {
    if (!DEBUG_MODE) return;
    
    $logEntry = date('Y-m-d H:i:s') . " - $message";
    if ($data !== null) {
        $logEntry .= "\n" . print_r($data, true);
    }
    $logEntry .= "\n" . str_repeat('-', 80) . "\n";
    
    file_put_contents(LOG_DIR . 'debug.log', $logEntry, FILE_APPEND);
}

// Usage
debugLog('Upload attempt', $_FILES);
debugLog('Query executed', $sql);
?>
```

### Test All Endpoints

Create `test_api.php`:
```php
<?php
$baseUrl = 'http://localhost/print-system/api';
$apiKey = 'your_test_key';

$tests = [
    ['GET', '/printers/list'],
    ['GET', '/print/history'],
    ['GET', '/print/pending'],
];

foreach ($tests as $test) {
    [$method, $endpoint] = $test;
    
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "$method $endpoint - HTTP $httpCode\n";
    echo substr($response, 0, 100) . "...\n\n";
}
?>
```

---

## Getting Help

If issues persist after trying these solutions:

1. **Check Logs:**
   - `logs/error.log`
   - `logs/api_access.log`
   - MySQL error log
   - PHP error log
   - Web server error log

2. **Enable Verbose Logging:**
   ```php
   define('LOG_LEVEL', 'DEBUG');
   ```

3. **Gather Information:**
   - PHP version: `php -v`
   - MySQL version: `mysql --version`
   - OS version: `uname -a` or `ver`
   - Disk space: `df -h`
   - Error messages from logs

4. **Contact Support:**
   - Email: support@yourprinting.com
   - Include: error messages, logs, system info
   - Describe: steps to reproduce the issue

---

## Common Error Codes Reference

| Code | Meaning | Common Cause |
|------|---------|--------------|
| 400 | Bad Request | Invalid parameters |
| 401 | Unauthorized | Missing/invalid API key |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Invalid endpoint or job_id |
| 413 | Payload Too Large | File exceeds size limit |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |
| 503 | Service Unavailable | Server overloaded |

---

**Last Updated:** December 2025  
**Version:** 1.0
