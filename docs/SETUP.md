# Print Management System - Setup Guide

## Table of Contents
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Printer Setup](#printer-setup)
- [API Key Generation](#api-key-generation)
- [Testing Installation](#testing-installation)
- [Production Deployment](#production-deployment)

---

## System Requirements

### Server Requirements
- **Operating System:** Windows Server 2016+ or Linux (Ubuntu 20.04+)
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **PHP:** 7.4 or higher (PHP 8.0+ recommended)
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Storage:** Minimum 10GB free space for uploads

### PHP Extensions Required
```
php-mysqli
php-pdo
php-gd
php-curl
php-zip
php-mbstring
php-fileinfo
php-json
```

### For Printer Integration
- Windows: Windows Print Spooler service
- Linux: CUPS (Common Unix Printing System)

---

## Installation

### Step 1: Download and Extract

```bash
# Clone or download the repository
git clone https://github.com/yourcompany/print-management-system.git
cd print-management-system

# Or extract from zip
unzip print-management-system.zip
cd print-management-system
```

### Step 2: Set Directory Permissions

**Linux/Ubuntu:**
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/print-system

# Set permissions
sudo chmod -R 755 /var/www/print-system
sudo chmod -R 777 /var/www/print-system/uploads
sudo chmod -R 777 /var/www/print-system/logs
sudo chmod 644 /var/www/print-system/config.php
```

**Windows:**
```powershell
# Give IIS_IUSRS write permissions to uploads and logs folders
icacls "C:\inetpub\wwwroot\print-system\uploads" /grant IIS_IUSRS:(OI)(CI)F
icacls "C:\inetpub\wwwroot\print-system\logs" /grant IIS_IUSRS:(OI)(CI)F
```

### Step 3: Install Dependencies

If using Composer for dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

---

## Database Setup

### Step 1: Create Database

**MySQL/MariaDB:**
```sql
CREATE DATABASE print_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'printuser'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON print_management.* TO 'printuser'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Import Schema

```bash
mysql -u printuser -p print_management < database/schema.sql
```

Or manually run the SQL:

```sql
-- Print Jobs Table
CREATE TABLE print_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(50) UNIQUE NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    paper_size VARCHAR(20) DEFAULT 'A4',
    color_mode VARCHAR(10) DEFAULT 'bw',
    page_range VARCHAR(50) DEFAULT 'all',
    copies INT DEFAULT 1,
    printer_name VARCHAR(100),
    status TINYINT DEFAULT 1,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    user_ip VARCHAR(45),
    api_key_id INT,
    INDEX idx_status (status),
    INDEX idx_job_id (job_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Keys Table
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    is_active TINYINT DEFAULT 1,
    rate_limit INT DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Printers Table
CREATE TABLE printers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    paper_sizes TEXT,
    color TINYINT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'online',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate Limiting Table
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL,
    request_count INT DEFAULT 1,
    window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key_window (api_key_id, window_start),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 3: Verify Tables

```sql
USE print_management;
SHOW TABLES;
```

You should see: `api_keys`, `print_jobs`, `printers`, `rate_limits`

---

## Configuration

### Step 1: Copy Configuration Template

```bash
cp config.example.php config.php
```

### Step 2: Edit Configuration

Edit `config.php` with your settings:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'print_management');
define('DB_USER', 'printuser');
define('DB_PASS', 'secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf']);

// API Settings
define('API_RATE_LIMIT', 100); // requests per hour
define('API_KEY_LENGTH', 64);

// Printer Settings
define('DEFAULT_PAPER_SIZE', 'A4');
define('DEFAULT_COLOR_MODE', 'bw');
define('DEFAULT_COPIES', 1);

// QR Code Settings
define('QR_CODE_SIZE', 300);
define('QR_CODE_MARGIN', 4);

// Logging
define('LOG_DIR', __DIR__ . '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Security
define('API_KEY_HEADER', 'X-API-Key');
define('ENABLE_CORS', true);
define('ALLOWED_ORIGINS', '*'); // Change to specific domains in production

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);
?>
```

### Step 3: Environment Variables (Optional)

For better security, use environment variables:

**.env file:**
```env
DB_HOST=localhost
DB_NAME=print_management
DB_USER=printuser
DB_PASS=secure_password_here

UPLOAD_MAX_SIZE=10485760
API_RATE_LIMIT=100

LOG_LEVEL=INFO
```

Then load with:
```php
// In config.php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
// etc...
```

---

## Printer Setup

### Windows Setup

#### Step 1: Install Printer Drivers
1. Install all printer drivers through Windows
2. Add printers via Control Panel → Devices and Printers
3. Test print to verify functionality

#### Step 2: Grant Permissions
```powershell
# Allow IIS user to access printers
net localgroup "Print Operators" "IIS_IUSRS" /add
```

#### Step 3: Register Printers in Database

```sql
INSERT INTO printers (name, description, paper_sizes, color, status) VALUES
('HP_LaserJet', 'HP LaserJet Pro M404n', '["A4","Letter","Legal"]', 0, 'online'),
('EPSON_WF', 'Epson WorkForce Pro WF-4830', '["A4","A3","Letter"]', 1, 'online');
```

### Linux Setup (CUPS)

#### Step 1: Install CUPS
```bash
sudo apt-get update
sudo apt-get install cups cups-client
sudo systemctl start cups
sudo systemctl enable cups
```

#### Step 2: Configure CUPS
```bash
# Add web user to lpadmin group
sudo usermod -a -G lpadmin www-data

# Edit CUPS configuration
sudo nano /etc/cups/cupsd.conf
```

Add these lines:
```
# Allow web server to access CUPS
<Location />
  Order allow,deny
  Allow localhost
  Allow from 127.0.0.1
</Location>
```

#### Step 3: Add Printers via Web Interface
```bash
# Access CUPS web interface
https://localhost:631

# Or use command line
lpadmin -p HP_LaserJet -E -v socket://192.168.1.100 -m everywhere
```

#### Step 4: Test Printing
```bash
lp -d HP_LaserJet test.pdf
lpstat -p
```

### Verify Printer Setup

Create `test_printer.php`:
```php
<?php
// Windows
$printers = shell_exec('wmic printer get name');
echo "<pre>$printers</pre>";

// Linux
$printers = shell_exec('lpstat -p -d');
echo "<pre>$printers</pre>";
?>
```

---

## API Key Generation

### Method 1: Using Admin Panel

1. Login to admin panel at `/admin`
2. Navigate to "API Keys"
3. Click "Generate New Key"
4. Enter user details
5. Copy and save the generated key

### Method 2: Using SQL

```sql
INSERT INTO api_keys (api_key, user_name, email, is_active, rate_limit) VALUES
(SHA2(CONCAT('user123', NOW(), RAND()), 256), 'John Doe', 'john@example.com', 1, 100);

-- View generated key
SELECT api_key, user_name FROM api_keys ORDER BY id DESC LIMIT 1;
```

### Method 3: Using PHP Script

Create `generate_key.php`:
```php
<?php
require_once 'config.php';
require_once 'includes/db.php';

$userName = 'Test User';
$email = 'test@example.com';
$apiKey = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("INSERT INTO api_keys (api_key, user_name, email) VALUES (?, ?, ?)");
$stmt->execute([$apiKey, $userName, $email]);

echo "Generated API Key: $apiKey\n";
echo "Save this key securely!\n";
?>
```

Run:
```bash
php generate_key.php
```

---

## Testing Installation

### Step 1: Test Database Connection

Create `test_db.php`:
```php
<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    echo "✓ Database connection successful\n";
} catch(PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}
?>
```

### Step 2: Test File Upload

```bash
curl -X POST \
  -H "X-API-Key: your_generated_key" \
  -F "file=@test.pdf" \
  http://localhost/print-system/api/print/upload
```

### Step 3: Test Printer Detection

```bash
curl -H "X-API-Key: your_generated_key" \
  http://localhost/print-system/api/printers/list
```

### Step 4: Check Logs

```bash
# View error log
tail -f logs/error.log

# View access log
tail -f logs/api_access.log
```

---

## Production Deployment

### Security Checklist

- [ ] Change all default passwords
- [ ] Restrict database access to localhost only
- [ ] Enable HTTPS/SSL (Let's Encrypt recommended)
- [ ] Set restrictive file permissions
- [ ] Disable directory listing
- [ ] Configure firewall rules
- [ ] Enable rate limiting
- [ ] Set up regular backups
- [ ] Configure log rotation
- [ ] Remove test files and scripts
- [ ] Update ALLOWED_ORIGINS in config

### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName print.yourdomain.com
    DocumentRoot /var/www/print-system/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your_cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your_key.key
    
    <Directory /var/www/print-system/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/print-system/uploads>
        Options -Indexes -ExecCGI
        AllowOverride None
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/print-system-error.log
    CustomLog ${APACHE_LOG_DIR}/print-system-access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name print.yourdomain.com;
    
    ssl_certificate /etc/ssl/certs/your_cert.crt;
    ssl_certificate_key /etc/ssl/private/your_key.key;
    
    root /var/www/print-system/public;
    index index.php;
    
    client_max_body_size 10M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location /uploads {
        deny all;
        return 403;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Backup Script

Create `backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/backups/print-system"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
mysqldump -u printuser -p print_management > "$BACKUP_DIR/db_$DATE.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" /var/www/print-system/uploads/

# Keep only last 30 days
find $BACKUP_DIR -mtime +30 -delete

echo "Backup completed: $DATE"
```

Add to crontab:
```bash
0 2 * * * /var/www/print-system/backup.sh
```

---

## Post-Installation

### Monitoring

Set up monitoring for:
- Disk space (uploads directory)
- Database size
- API response times
- Print queue length
- Failed jobs

### Maintenance Tasks

**Weekly:**
- Review error logs
- Check disk space
- Verify backup integrity

**Monthly:**
- Clean old completed jobs
- Rotate logs
- Update printer status
- Review API key usage

### Updating

```bash
# Backup first
./backup.sh

# Pull updates
git pull origin main

# Run migrations if any
php migrations/run.php

# Clear cache
rm -rf cache/*

# Test
php test_installation.php
```

---

## Support

If you encounter issues during setup:

1. Check logs in `logs/` directory
2. Verify PHP extensions: `php -m`
3. Test database connection
4. Check file permissions
5. Review [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

**Contact:** developer@shakib.me
