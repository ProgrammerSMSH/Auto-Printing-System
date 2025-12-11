# Auto Printing System

A fully automated remote PDF printing system built with CodeIgniter 4, PHP, Python, and CUPS. This system enables users to upload PDF files remotely, configure print settings, and have documents automatically printed on local networked printers with real-time status tracking and QR code job identification.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![Python Version](https://img.shields.io/badge/Python-3.8%2B-green)](https://www.python.org/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-red)](https://codeigniter.com/)

---

## 📋 Table of Contents

- [Description](#description)
- [Key Features](#key-features)
- [Architecture Overview](#architecture-overview)
- [Tech Stack](#tech-stack)
- [Folder Structure](#folder-structure)
- [Setup Instructions](#setup-instructions)
- [API Endpoints](#api-endpoints)
- [Configuration](#configuration)
- [Cron Job Setup](#cron-job-setup)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Author](#author)

---

## 📖 Description

The Remote PDF Printing System is a distributed application that bridges the gap between cloud-based document management and local printer infrastructure. Users can upload PDF files through a web interface, configure print settings, and have documents automatically printed on designated printers connected to a home server.

The system uses a three-tier status management approach to track print jobs from upload to completion, with automatic cleanup mechanisms to efficiently manage storage.

**Status System:**
- **1 - Pending**: Job uploaded and queued for processing
- **2 - Processing**: Home server actively printing the document
- **3 - Printed**: Job completed successfully and cleaned up

---

## ✨ Key Features

### Core Functionality
- **PDF Upload with Live Preview**: Upload PDF files and preview them before printing
- **Flexible Print Options**: Configure paper size, color mode, page range, and copies
- **Intelligent Status Tracking**: Real-time job status updates across the entire workflow
- **Automated Processing**: Cron-based job execution checks every minute for new print jobs

### Advanced Features
- **QR Code Job Tracking**: Each print job generates a unique QR code for easy identification
- **Multi-Printer Support**: Route jobs to specific printers based on requirements
- **Automatic File Cleanup**: Removes processed files to optimize storage usage
- **Comprehensive Logging**: Detailed logs for debugging and audit trails
- **Print History Dashboard**: View and search through historical print jobs
- **Secure File Handling**: Validation and sanitization of uploaded files

---

## 🏗️ Architecture Overview

```
                                          ┌─────────────────┐
                                          │   User Device   │
                                          │  (Web Browser)  │
                                          └────────┬────────┘
                                                   │ HTTPS
                                                   ▼
                                ┌─────────────────────────────────────────┐
                                │          CI4 Backend (VPS)              │
                                │  ┌───────────┐      ┌────────────────┐ │
                                │  │   API     │◄────►│    Database    │ │
                                │  │ Endpoints │      │ (Print Jobs)   │ │
                                │  └───────────┘      └────────────────┘ │
                                └──────────────────┬───────────────────────┘
                                                   │ File Storage
                                                   │ Job Queue
                                                   ▼
                                ┌─────────────────────────────────────────┐
                                │       Home Server (Local Network)       │
                                │  ┌─────────────┐    ┌────────────────┐ │
                                │  │ Cron Job    │───►│  PHP Script    │ │
                                │  │ (Every 1m)  │    │ (cron.php)     │ │
                                │  └─────────────┘    └────────┬───────┘ │
                                │                              │          │
                                │                              ▼          │
                                │                     ┌────────────────┐ │
                                │                     │ Python Script  │ │
                                │                     │ (print_job.py) │ │
                                │                     └────────┬───────┘ │
                                │                              │          │
                                │                              ▼          │
                                │                     ┌────────────────┐ │
                                │                     │  CUPS Server   │ │
                                │                     └────────┬───────┘ │
                                └──────────────────────────────┼─────────┘
                                                               │
                                                               ▼
                                                      ┌─────────────────┐
                                                      │  Local Printer  │
                                                      └─────────────────┘
```

### Workflow Process

1. **Upload Phase**: User uploads PDF through web interface to CI4 backend
2. **Storage Phase**: Backend validates file, stores it, and creates database record with status=1
3. **Queue Phase**: Home server cron job polls backend every minute for pending jobs
4. **Download Phase**: PHP script downloads PDF file from backend to local temp directory
5. **Processing Phase**: Job status updated to 2, Python script invoked with print parameters
6. **Printing Phase**: CUPS processes the print job and sends to configured printer
7. **Completion Phase**: Status updated to 3, local and remote files cleaned up
8. **Notification Phase**: User can check status via API or view in print history

---

## 🛠️ Tech Stack

### Backend (VPS)
- **PHP 8.0+**: Server-side scripting
- **CodeIgniter 4.x**: MVC framework for API development
- **MySQL 8.0+**: Relational database for job management
- **Apache/Nginx**: Web server

### Home Server
- **PHP 8.0+**: Cron job processing
- **Python 3.8+**: Print script execution
- **CUPS (Common Unix Printing System)**: Print job management
- **Linux OS**: Ubuntu 20.04+ or Debian 11+ recommended

### Frontend
- **HTML5**: Structure and markup
- **JavaScript (ES6+)**: Client-side logic and AJAX
- **CSS3**: Styling and responsive design
- **PDF.js**: PDF preview rendering (optional)

### Additional Tools
- **Cron**: Scheduled task execution
- **cURL**: HTTP requests between servers
- **python-cups**: Python bindings for CUPS
- **qrcode (Python)**: QR code generation

---

## 📁 Folder Structure

```
auto-printing-system/
│
├── ci4-backend/                    # CodeIgniter 4 Backend (VPS Server)
│   ├── app/
│   │   ├── Config/
│   │   │   ├── Api.php
│   │   │   ├── App.php
│   │   │   ├── Cache.php
│   │   │   ├── Constants.php
│   │   │   ├── ContentSecurityPolicy.php
│   │   │   ├── Cookie.php
│   │   │   ├── Database.php
│   │   │   ├── Encryption.php
│   │   │   ├── Events.php
│   │   │   ├── Exceptions.php
│   │   │   ├── Feature.php
│   │   │   ├── Filters.php
│   │   │   ├── Format.php
│   │   │   ├── Generators.php
│   │   │   ├── Honeypot.php
│   │   │   ├── Images.php
│   │   │   ├── Kint.php
│   │   │   ├── Logger.php
│   │   │   ├── Migrations.php
│   │   │   ├── Modules.php
│   │   │   ├── Pager.php
│   │   │   ├── Paths.php
│   │   │   ├── Routes.php
│   │   │   ├── Security.php
│   │   │   ├── Services.php
│   │   │   ├── Session.php
│   │   │   ├── Toolbar.php
│   │   │   ├── Upload.php
│   │   │   ├── UserAgents.php
│   │   │   ├── Validation.php
│   │   │   └── View.php
│   │   │
│   │   ├── Controllers/
│   │   │   ├── PrintController.php
│   │   │   ├── PrinterController.php
│   │   │   ├── HistoryController.php
│   │   │   ├── StatusController.php
│   │   │   └── CleanupController.php
│   │   │
│   │   ├── Database/
│   │   │   ├── Migrations/
│   │   │   │   └── 2025-01-01-000001_CreatePrintJobsTable.php
│   │   │   ├── Seeds/
│   │   │   │   └── PrintJobSeeder.php
│   │   │   └── Factories/
│   │   │
│   │   ├── Filters/
│   │   │   ├── ApiAuthFilter.php
│   │   │   ├── RateLimitFilter.php
│   │   │   └── IPWhitelistFilter.php
│   │   │
│   │   ├── Helpers/
│   │   │   └── file_helper.php
│   │   │
│   │   ├── Language/
│   │   │   └── en/
│   │   │
│   │   ├── Libraries/
│   │   │
│   │   ├── Models/
│   │   │   ├── PrintJobModel.php
│   │   │   └── PrinterModel.php
│   │   │
│   │   ├── ThirdParty/
│   │   │
│   │   ├── Views/
│   │   │   ├── errors/
│   │   │   │   ├── cli/
│   │   │   │   ├── html/
│   │   │   │   └── debug/
│   │   │   ├── upload.php
│   │   │   └── history.php
│   │   │
│   │   └── Helpers/
│   │       └── file_helper.php
│   │
│   ├── public/
│   │   ├── .htaccess
│   │   ├── index.php
│   │   ├── uploads/
│   │   │   ├── .htaccess
│   │   │   ├── 2025/
│   │   │   │   ├── 01/
│   │   │   │   │   └── 12/
│   │   │   │   │       └── (PDF files will be stored here)
│   │   │   │   └── (year folders)
│   │   │   └── (organized by date)
│   │   │
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   │   └── style.css
│   │   │   ├── js/
│   │   │   │   └── app.js
│   │   │   └── images/
│   │   │
│   │   └── .htaccess
│   │
│   ├── system/
│   │   └── (CodeIgniter 4 system files - from framework)
│   │
│   ├── tests/
│   │   ├── _support/
│   │   └── unit/
│   │
│   ├── vendor/
│   │   └── (Composer dependencies - will be created by composer)
│   │
│   ├── writable/
│   │   ├── cache/
│   │   ├── logs/
│   │   │   └── log-YYYY-MM-DD.php
│   │   ├── session/
│   │   ├── temp/
│   │   └── uploads/
│   │       └── (symlink or copy of public/uploads/)
│   │
│   ├── .env
│   ├── .env.example
│   ├── composer.json
│   ├── spark
│   └── .htaccess
│
├── home-server/                    # Home Server Scripts
│   ├── cron.php
│   ├── print_job.py
│   ├── config.php
│   ├── health_check.sh
│   ├── install.sh
│   ├── cleanup.php
│   │
│   ├── logs/
│   │   ├── cron.log
│   │   ├── print.log
│   │   ├── health.log
│   │   └── cleanup.log
│   │
│   ├── temp/
│   │   └── (temporary PDF files will be stored here)
│   │
│   └── .env.example
│
├── docs/                          # Documentation
│   ├── API.md
│   ├── SETUP.md
│   ├── TROUBLESHOOTING.md
│   └── DEPLOYMENT.md
│
├── scripts/                       # Utility scripts
│   ├── backup_database.sh
│   ├── restore_database.sh
│   ├── rotate_logs.sh
│   └── update_system.sh
│
├── README.md
├── LICENSE
├── .gitignore
└── .gitattributes
```

---

## 🚀 Setup Instructions

### Prerequisites

Before beginning installation, ensure you have the following:

**VPS (Backend Server):**
- PHP 8.0 or higher with extensions: `curl`, `mbstring`, `intl`, `json`, `xml`
- MySQL 8.0 or higher
- Composer (PHP dependency manager)
- Apache or Nginx web server
- SSL certificate (recommended for production)

**Home Server:**
- Linux operating system (Ubuntu 20.04+ or Debian 11+)
- PHP 8.0 or higher with `curl` extension
- Python 3.8 or higher
- CUPS installed and configured
- Network printer configured in CUPS
- Cron daemon running

---

### Step 1: CI4 Backend Setup

#### 1.1 Clone Repository and Install Dependencies

```bash
# Navigate to web root
cd /var/www/html

# Clone the repository
git clone https://github.com/yourusername/remote-pdf-printing-system.git
cd remote-pdf-printing-system/ci4-backend

# Install CodeIgniter dependencies
composer install
```

#### 1.2 Configure Environment

```bash
# Copy environment file
cp env .env

# Edit configuration
nano .env
```

Add the following configuration:

```ini
# Environment
CI_ENVIRONMENT = production

# Base URL
app.baseURL = 'https://your-domain.com/'

# Database Configuration
database.default.hostname = localhost
database.default.database = print_system
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# Security
app.key = 'your-secret-key-here'

# File Upload
upload.maxSize = 10485760  # 10MB in bytes
upload.allowedTypes = 'pdf'
```

#### 1.3 Set Directory Permissions

```bash
# Set writable permissions
chmod -R 755 writable/
chmod -R 755 public/uploads/

# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data writable/
chown -R www-data:www-data public/uploads/
```

#### 1.4 Create Database and Run Migration

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE print_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'print_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON print_system.* TO 'print_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php spark migrate
```

#### 1.5 Database Schema

The migration will create the following table:

```sql
CREATE TABLE `print_jobs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` VARCHAR(50) NOT NULL UNIQUE,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(500) NOT NULL,
  `file_size` INT(11) NOT NULL,
  `paper_size` VARCHAR(20) DEFAULT 'A4',
  `color_mode` ENUM('color', 'grayscale') DEFAULT 'grayscale',
  `page_range` VARCHAR(50) DEFAULT 'all',
  `copies` INT(3) DEFAULT 1,
  `printer_name` VARCHAR(100) DEFAULT 'default',
  `status` TINYINT(1) DEFAULT 1 COMMENT '1=Pending, 2=Processing, 3=Printed',
  `qr_code` TEXT,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Step 2: Home Server Configuration

#### 2.1 Install Required Software

```bash
# Update package list
sudo apt update

# Install PHP
sudo apt install php8.1-cli php8.1-curl php8.1-mbstring

# Install Python and pip
sudo apt install python3 python3-pip

# Install CUPS
sudo apt install cups cups-client

# Install Python dependencies
pip3 install pycups qrcode[pil] pillow requests
```

#### 2.2 Configure CUPS

```bash
# Add your user to lpadmin group
sudo usermod -a -G lpadmin $USER

# Start CUPS service
sudo systemctl start cups
sudo systemctl enable cups

# Access CUPS web interface
# Navigate to: http://localhost:631
# Add your printer through the web interface
```

#### 2.3 Setup Home Server Scripts

```bash
# Create directory structure
mkdir -p /home/printserver/remote-printing
cd /home/printserver/remote-printing

# Copy scripts from repository
cp ~/remote-pdf-printing-system/home-server/* .

# Create required directories
mkdir -p logs temp

# Set permissions
chmod +x cron.php print_job.py
chmod 755 logs/ temp/
```

#### 2.4 Configure Home Server Settings

Edit `config.php`:

```php
<?php
// API Configuration
define('API_BASE_URL', 'https://your-domain.com/api');
define('API_KEY', 'your-secure-api-key-here');

// Local Configuration
define('TEMP_DIR', __DIR__ . '/temp/');
define('LOG_FILE', __DIR__ . '/logs/cron.log');
define('PRINT_LOG_FILE', __DIR__ . '/logs/print.log');

// Python Script Path
define('PYTHON_SCRIPT', __DIR__ . '/print_job.py');
define('PYTHON_BIN', '/usr/bin/python3');

// Cleanup Settings
define('DELETE_AFTER_PRINT', true);
define('KEEP_LOGS_DAYS', 30);

// Printer Configuration
define('DEFAULT_PRINTER', 'HP_LaserJet');  // Your CUPS printer name
```

---

### Step 3: Cron Job Setup

#### 3.1 Configure Cron Job

```bash
# Edit crontab
crontab -e

# Add the following line to check for print jobs every minute
* * * * * /usr/bin/php /home/printserver/remote-printing/cron.php >> /home/printserver/remote-printing/logs/cron.log 2>&1
```

#### 3.2 Verify Cron Job

```bash
# Check if cron is running
sudo systemctl status cron

# View cron logs
tail -f /home/printserver/remote-printing/logs/cron.log

# Test manual execution
php /home/printserver/remote-printing/cron.php
```

#### 3.3 Alternative Cron Schedules

```bash
# Every 30 seconds (using two jobs)
* * * * * /usr/bin/php /path/to/cron.php
* * * * * sleep 30 && /usr/bin/php /path/to/cron.php

# Every 2 minutes
*/2 * * * * /usr/bin/php /path/to/cron.php

# Every 5 minutes
*/5 * * * * /usr/bin/php /path/to/cron.php
```

---

### Step 4: Python Print Script Configuration

The `print_job.py` script handles the actual printing via CUPS:

```python
#!/usr/bin/env python3
import cups
import sys
import json
import os
from datetime import datetime

def log_message(message):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    log_file = os.path.join(os.path.dirname(__file__), 'logs', 'print.log')
    with open(log_file, 'a') as f:
        f.write(f"[{timestamp}] {message}\n")

def print_pdf(filepath, printer_name, options):
    try:
        conn = cups.Connection()
        printers = conn.getPrinters()
        
        if printer_name not in printers:
            log_message(f"ERROR: Printer '{printer_name}' not found")
            return False
        
        # Prepare print options
        print_options = {}
        
        if options.get('paper_size'):
            print_options['media'] = options['paper_size']
        
        if options.get('color_mode') == 'grayscale':
            print_options['ColorModel'] = 'Gray'
        
        if options.get('copies'):
            print_options['copies'] = str(options['copies'])
        
        if options.get('page_range') and options['page_range'] != 'all':
            print_options['page-ranges'] = options['page_range']
        
        # Submit print job
        job_id = conn.printFile(
            printer_name,
            filepath,
            "Remote Print Job",
            print_options
        )
        
        log_message(f"SUCCESS: Print job {job_id} submitted to {printer_name}")
        return True
        
    except Exception as e:
        log_message(f"ERROR: {str(e)}")
        return False

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: print_job.py <filepath> <printer_name> <options_json>")
        sys.exit(1)
    
    filepath = sys.argv[1]
    printer_name = sys.argv[2]
    options = json.loads(sys.argv[3])
    
    if print_pdf(filepath, printer_name, options):
        sys.exit(0)
    else:
        sys.exit(1)
```

Make it executable:

```bash
chmod +x print_job.py
```

---

## 📡 API Endpoints

### Base URL
```
https://your-domain.com/api
```

### Authentication
Include API key in header:
```
X-API-Key: your-secure-api-key
```

---

### Endpoints Reference

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/print/upload` | Upload PDF and create print job | Yes |
| GET | `/print/status/{job_id}` | Get job status by job ID | Yes |
| GET | `/print/history` | Retrieve print job history | Yes |
| GET | `/print/pending` | Get all pending print jobs | Yes |
| PUT | `/print/update/{job_id}` | Update job status | Yes |
| DELETE | `/print/delete/{job_id}` | Delete print job | Yes |
| GET | `/printers/list` | List available printers | Yes |

---

### POST /print/upload

Upload a PDF file and create a new print job.

**Request:**
```bash
curl -X POST https://your-domain.com/api/print/upload \
  -H "X-API-Key: your-api-key" \
  -F "file=@document.pdf" \
  -F "paper_size=A4" \
  -F "color_mode=grayscale" \
  -F "page_range=all" \
  -F "copies=1" \
  -F "printer_name=HP_LaserJet"
```

**Response:**
```json
{
  "status": "success",
  "message": "Print job created successfully",
  "data": {
    "job_id": "PJ-20250112-ABC123",
    "filename": "document.pdf",
    "status": 1,
    "qr_code": "data:image/png;base64,iVBORw0KGgo...",
    "uploaded_at": "2025-01-12 14:30:00"
  }
}
```

---

### GET /print/status/{job_id}

Retrieve the current status of a print job.

**Request:**
```bash
curl -X GET https://your-domain.com/api/print/status/PJ-20250112-ABC123 \
  -H "X-API-Key: your-api-key"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "job_id": "PJ-20250112-ABC123",
    "filename": "document.pdf",
    "status": 3,
    "status_text": "Printed",
    "uploaded_at": "2025-01-12 14:30:00",
    "processed_at": "2025-01-12 14:31:00",
    "completed_at": "2025-01-12 14:31:30"
  }
}
```

**Status Codes:**
- `1` - Pending (queued, waiting for processing)
- `2` - Processing (actively being printed)
- `3` - Printed (completed successfully)

---

### GET /print/history

Retrieve paginated print job history.

**Request:**
```bash
curl -X GET "https://your-domain.com/api/print/history?page=1&limit=20&status=3" \
  -H "X-API-Key: your-api-key"
```

**Query Parameters:**
- `page` (optional, default: 1) - Page number
- `limit` (optional, default: 20) - Items per page
- `status` (optional) - Filter by status (1, 2, or 3)
- `from_date` (optional) - Filter from date (YYYY-MM-DD)
- `to_date` (optional) - Filter to date (YYYY-MM-DD)

**Response:**
```json
{
  "status": "success",
  "data": {
    "jobs": [
      {
        "job_id": "PJ-20250112-ABC123",
        "filename": "document.pdf",
        "status": 3,
        "status_text": "Printed",
        "uploaded_at": "2025-01-12 14:30:00",
        "completed_at": "2025-01-12 14:31:30"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 98,
      "items_per_page": 20
    }
  }
}
```

---

### GET /print/pending

Get all pending print jobs (used by cron job).

**Request:**
```bash
curl -X GET https://your-domain.com/api/print/pending \
  -H "X-API-Key: your-api-key"
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "job_id": "PJ-20250112-XYZ789",
      "filename": "report.pdf",
      "filepath": "uploads/2025/01/12/report_abc123.pdf",
      "paper_size": "A4",
      "color_mode": "color",
      "page_range": "1-5",
      "copies": 2,
      "printer_name": "HP_LaserJet"
    }
  ]
}
```

---

### PUT /print/update/{job_id}

Update print job status (used by home server).

**Request:**
```bash
curl -X PUT https://your-domain.com/api/print/update/PJ-20250112-ABC123 \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "status": 2,
    "processed_at": "2025-01-12 14:31:00"
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Job status updated successfully"
}
```

---

## ⚙️ Configuration

### Backend Configuration (.env)

```ini
# File Upload Settings
upload.maxSize = 10485760          # Maximum file size (10MB)
upload.allowedTypes = 'pdf'        # Allowed file types
upload.path = 'public/uploads/'    # Upload directory

# Print Job Settings
print.defaultPrinter = 'default'   # Default printer name
print.allowedSizes = 'A4,A3,Letter,Legal'
print.maxCopies = 10               # Maximum copies per job
print.jobExpiry = 7                # Days to keep completed jobs

# API Security
api.key = 'your-secure-api-key'    # API authentication key
api.rateLimit = 100                # Requests per hour per IP

# Cleanup Settings
cleanup.autoDelete = true          # Auto-delete printed files
cleanup.deleteAfter = 24           # Hours to keep printed files
```

### Home Server Configuration (config.php)

```php
// Network Settings
define('API_TIMEOUT', 30);         // API request timeout (seconds)
define('MAX_RETRIES', 3);          // Max retry attempts for failed requests
define('RETRY_DELAY', 5);          // Delay between retries (seconds)

// Processing Settings
define('MAX_CONCURRENT_JOBS', 5);  // Max simultaneous print jobs
define('PROCESS_DELAY', 2);        // Delay between jobs (seconds)

// Logging Settings
define('LOG_LEVEL', 'INFO');       // DEBUG, INFO, WARNING, ERROR
define('LOG_ROTATION', true);      // Enable log rotation
define('LOG_MAX_SIZE', 10485760);  // Max log file size (10MB)
```

---

## 🖨️ Usage

### Web Interface Upload

1. Navigate to `https://your-domain.com/print/upload`
2. Select PDF file (max 10MB)
3. Configure print options:
   - Paper Size: A4, A3, Letter, Legal
   - Color Mode: Color or Grayscale
   - Page Range: All, specific pages (e.g., 1-5, 1,3,5)
   - Copies: 1-10
   - Printer: Select from available printers
4. Click "Upload and Print"
5. Save or scan the generated QR code for job tracking
6. Monitor status in real-time

### Programmatic Usage

```javascript
// JavaScript example
const formData = new FormData();
formData.append('file', pdfFile);
formData.append('paper_size', 'A4');
formData.append('color_mode', 'grayscale');
formData.append('copies', '2');

fetch('https://your-domain.com/api/print/upload', {
  method: 'POST',
  headers: {
    'X-API-Key': 'your-api-key'
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('Job ID:', data.data.job_id);
  // Poll for status
  checkStatus(data.data.job_id);
});

function checkStatus(jobId) {
  fetch(`https://your-domain.com/api/print/status/${jobId}`, {
    headers: { 'X-API-Key': 'your-api-key' }
  })
  .then(response => response.json())
  .then(data => {
    console.log('Status:', data.data.status_text);
  });
}
```

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Print Jobs Stuck in Pending Status

**Symptoms:** Jobs remain in status 1 for extended periods.

**Solutions:**
```bash
# Check if cron job is running
sudo systemctl status cron

# Verify cron job is in crontab
crontab -l

# Check cron logs
tail -f /home/printserver/remote-printing/logs/cron.log

# Test manual execution
php /home/printserver/remote-printing/cron.php
```

#### 2. CUPS Connection Errors

**Symptoms:** Python script fails with CUPS connection errors.

**Solutions:**
```bash
# Restart CUPS service
sudo systemctl restart cups

# Check CUPS status
sudo systemctl status cups

# Verify printer is available
lpstat -p -d

# Test CUPS connection
python3 -c "import cups; print(cups.Connection().getPrinters())"
```

#### 3. File Permission Errors

**Symptoms:** Cannot write to temp directory or logs.

**Solutions:**
```bash
# Fix directory permissions
chmod 755 /home/printserver/remote-printing/temp/
chmod 755 /home/printserver/remote-printing/logs/

# Fix file ownership
chown -R $USER:$USER /home/printserver/remote-printing/

# Verify permissions
ls -la /home/printserver/remote-printing/
```

#### 4. API Connection Timeout

**Symptoms:** Cron job cannot reach backend API.

**Solutions:**
```bash
# Test API connectivity
curl -v https://your-domain.com/api/print/pending \
  -H "X-API-Key: your-api-key"

# Check DNS resolution
nslookup your-domain.com

# Verify firewall rules
sudo ufw status

# Check network connectivity
ping your-domain.com
```

#### 5. Large File Upload Failures

**Symptoms:** PDF uploads fail or timeout for large files.

**Solutions:**

Edit PHP configuration:
```bash
sudo nano /etc/php/8.1/apache2/php.ini
```

Adjust these values:
```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M
```

Restart web server:
```bash
sudo systemctl restart apache2  # or nginx
```

---

### Logging and Debugging

#### Enable Debug Mode

Backend (CI4):
```ini
# .env file
CI_ENVIRONMENT = development
```

Home Server:
```php
// config.php
define('LOG_LEVEL', 'DEBUG');
```

#### View Logs

```bash
# Backend logs
tail -f /var/www/html/ci4-backend/writable/logs/log-*.php

# Cron logs
tail -f /home/printserver/remote-printing/logs/cron.log

# Print logs
tail -f /home/printserver/remote-printing/logs/print.log

# CUPS error logs
tail -f /var/log/cups/error_log
```

#### Check System Resources

```bash
# Disk space
df -h

# Memory usage
free -m

# CPU usage
top

# Print queue status
lpstat -o
```

---

## 📊 Monitoring and Maintenance

### Automated Cleanup

Add daily cleanup cron job:

```bash
# Edit crontab
crontab -e

# Add cleanup job (runs daily at 2 AM)
0 2 * * * /usr/bin/php /home/printserver/remote-printing/cleanup.php
```

### Log Rotation

Configure logrotate:

```bash
sudo nano /etc/logrotate.d/remote-printing
```

Add configuration:
```
/home/printserver/remote-printing/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 printserver printserver
}
```

### Health Check Script

Create `health_check.sh`:

```bash
#!/bin/bash
# Check if services are running

# Check CUPS
systemctl is-active --quiet cups || echo "CUPS is down!"

# Check cron
systemctl is-active --quiet cron || echo "Cron is down!"

# Check API connectivity
curl -f -s https://your-domain.com/api/print/pending \
  -H "X-API-Key: your-api-key" > /dev/null || echo "API unreachable!"

# Check disk space
DISK_USAGE=$(df -h /home/printserver/remote-printing | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "Warning: Disk usage is at ${DISK_USAGE}%"
fi

# Check if print queue is stuck
QUEUE_SIZE=$(lpstat -o | wc -l)
if [ $QUEUE_SIZE -gt 50 ]; then
    echo "Warning: Print queue has $QUEUE_SIZE jobs"
fi
```

Make it executable and add to cron:
```bash
chmod +x health_check.sh

# Run health check every 5 minutes
crontab -e
*/5 * * * * /home/printserver/remote-printing/health_check.sh >> /home/printserver/remote-printing/logs/health.log 2>&1
```

---

## 🔒 Security Considerations

### API Security

#### Generate Strong API Key
```bash
# Generate secure random API key
openssl rand -base64 32
```

#### Implement Rate Limiting
Add to CI4 backend (`app/Filters/RateLimitFilter.php`):

```php
<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $cache = \Config\Services::cache();
        $ip = $request->getIPAddress();
        $key = "rate_limit_$ip";
        
        $count = $cache->get($key) ?? 0;
        
        if ($count >= 100) { // 100 requests per hour
            return service('response')
                ->setStatusCode(429)
                ->setJSON(['error' => 'Rate limit exceeded']);
        }
        
        $cache->save($key, $count + 1, 3600);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not needed
    }
}
```

### File Upload Security

#### Validate PDF Files
```php
// In PrintController.php
private function validatePDF($file)
{
    // Check file extension
    if ($file->getExtension() !== 'pdf') {
        return false;
    }
    
    // Check MIME type
    $mimeType = $file->getMimeType();
    if ($mimeType !== 'application/pdf') {
        return false;
    }
    
    // Check file signature (magic bytes)
    $handle = fopen($file->getTempName(), 'r');
    $header = fread($handle, 4);
    fclose($handle);
    
    if ($header !== '%PDF') {
        return false;
    }
    
    return true;
}
```

### Network Security

#### Use HTTPS Only
Ensure SSL certificate is installed and force HTTPS:

```apache
# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

```nginx
# Nginx configuration
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name your-domain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # Strong SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
}
```

#### Whitelist Home Server IP
In CI4 backend, restrict API access:

```php
// app/Filters/IPWhitelistFilter.php
public function before(RequestInterface $request, $arguments = null)
{
    $allowedIPs = ['123.45.67.89', '98.76.54.32']; // Home server IPs
    $clientIP = $request->getIPAddress();
    
    if (!in_array($clientIP, $allowedIPs)) {
        return service('response')
            ->setStatusCode(403)
            ->setJSON(['error' => 'Access denied']);
    }
}
```

### File System Security

#### Secure File Storage
```bash
# Prevent direct access to uploads directory
# Add .htaccess in public/uploads/
cat > /var/www/html/ci4-backend/public/uploads/.htaccess << 'EOF'
# Deny all direct access
Order Deny,Allow
Deny from all

# Allow only PHP to read files
<FilesMatch "\.(pdf)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
EOF

# Set proper permissions
chmod 644 /var/www/html/ci4-backend/public/uploads/.htaccess
```

#### Secure Temporary Files
```bash
# Set restrictive permissions on temp directory
chmod 700 /home/printserver/remote-printing/temp/

# Add automatic cleanup of old temp files
# Add to crontab
0 * * * * find /home/printserver/remote-printing/temp/ -type f -mtime +1 -delete
```

---

## 📈 Performance Optimization

### Database Optimization

#### Add Indexes
```sql
-- Optimize frequent queries
CREATE INDEX idx_status_uploaded ON print_jobs(status, uploaded_at);
CREATE INDEX idx_printer_status ON print_jobs(printer_name, status);

-- Analyze and optimize tables regularly
ANALYZE TABLE print_jobs;
OPTIMIZE TABLE print_jobs;
```

#### Archive Old Jobs
```sql
-- Create archive table
CREATE TABLE print_jobs_archive LIKE print_jobs;

-- Move completed jobs older than 90 days
INSERT INTO print_jobs_archive 
SELECT * FROM print_jobs 
WHERE status = 3 AND completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM print_jobs 
WHERE status = 3 AND completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Caching

#### Implement Redis Caching
```bash
# Install Redis
sudo apt install redis-server php-redis

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis
```

Configure CI4 to use Redis:
```php
// app/Config/Cache.php
public $redis = [
    'host'     => '127.0.0.1',
    'password' => null,
    'port'     => 6379,
    'timeout'  => 0,
    'database' => 0,
];

// Cache printer list
$cache = \Config\Services::cache();
$printers = $cache->remember('printer_list', 3600, function() {
    return $this->printerModel->findAll();
});
```

### File Processing

#### Optimize PDF Downloads
Update `cron.php` with parallel processing:

```php
<?php
require_once 'config.php';

function downloadFile($url, $destination) {
    $ch = curl_init($url);
    $fp = fopen($destination, 'wb');
    
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    fclose($fp);
    
    return $httpCode === 200;
}

function processJob($job) {
    $tempFile = TEMP_DIR . $job['job_id'] . '.pdf';
    $downloadUrl = API_BASE_URL . '/download/' . $job['job_id'];
    
    logMessage("Processing job: {$job['job_id']}");
    
    // Update status to processing
    updateJobStatus($job['job_id'], 2);
    
    // Download file
    if (!downloadFile($downloadUrl, $tempFile)) {
        logMessage("ERROR: Failed to download {$job['job_id']}");
        updateJobStatus($job['job_id'], 1, 'Download failed');
        return false;
    }
    
    // Prepare print options
    $options = json_encode([
        'paper_size' => $job['paper_size'],
        'color_mode' => $job['color_mode'],
        'page_range' => $job['page_range'],
        'copies' => $job['copies']
    ]);
    
    // Execute Python print script
    $command = sprintf(
        '%s %s %s %s %s 2>&1',
        escapeshellarg(PYTHON_BIN),
        escapeshellarg(PYTHON_SCRIPT),
        escapeshellarg($tempFile),
        escapeshellarg($job['printer_name']),
        escapeshellarg($options)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        logMessage("SUCCESS: Job {$job['job_id']} printed");
        updateJobStatus($job['job_id'], 3);
        
        // Cleanup
        if (DELETE_AFTER_PRINT && file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        return true;
    } else {
        logMessage("ERROR: Print failed for {$job['job_id']}");
        updateJobStatus($job['job_id'], 1, 'Print failed: ' . implode("\n", $output));
        
        return false;
    }
}

function updateJobStatus($jobId, $status, $error = null) {
    $ch = curl_init(API_BASE_URL . '/print/update/' . $jobId);
    
    $data = ['status' => $status];
    if ($error) {
        $data['error_message'] = $error;
    }
    if ($status == 2) {
        $data['processed_at'] = date('Y-m-d H:i:s');
    }
    if ($status == 3) {
        $data['completed_at'] = date('Y-m-d H:i:s');
    }
    
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . API_KEY
        ],
    ]);
    
    curl_exec($ch);
    curl_close($ch);
}

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Main execution
logMessage("Cron job started");

// Fetch pending jobs
$ch = curl_init(API_BASE_URL . '/print/pending');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . API_KEY],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    logMessage("ERROR: Failed to fetch pending jobs (HTTP $httpCode)");
    exit(1);
}

$result = json_decode($response, true);

if (!isset($result['data']) || empty($result['data'])) {
    logMessage("No pending jobs found");
    exit(0);
}

$jobs = $result['data'];
logMessage("Found " . count($jobs) . " pending job(s)");

// Process each job
foreach ($jobs as $job) {
    processJob($job);
    
    // Small delay between jobs
    if (defined('PROCESS_DELAY')) {
        sleep(PROCESS_DELAY);
    }
}

logMessage("Cron job completed");
?>
```

---

## 🧪 Testing

### Unit Testing

#### Backend Tests
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
./vendor/bin/phpunit tests/
```

Create test file `tests/PrintControllerTest.php`:
```php
<?php
namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class PrintControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    
    public function testUploadEndpoint()
    {
        $result = $this->withHeaders(['X-API-Key' => 'test-key'])
            ->withFile('file', 'test.pdf')
            ->post('/api/print/upload');
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }
    
    public function testStatusEndpoint()
    {
        $result = $this->withHeaders(['X-API-Key' => 'test-key'])
            ->get('/api/print/status/PJ-TEST-123');
        
        $result->assertStatus(200);
    }
}
```

### Integration Testing

#### Test Complete Workflow
```bash
#!/bin/bash
# test_workflow.sh

echo "Starting integration test..."

# 1. Upload test file
RESPONSE=$(curl -s -X POST https://your-domain.com/api/print/upload \
  -H "X-API-Key: your-api-key" \
  -F "file=@test.pdf" \
  -F "paper_size=A4" \
  -F "copies=1")

JOB_ID=$(echo $RESPONSE | jq -r '.data.job_id')
echo "Job created: $JOB_ID"

# 2. Wait and check status
sleep 10
STATUS=$(curl -s https://your-domain.com/api/print/status/$JOB_ID \
  -H "X-API-Key: your-api-key" | jq -r '.data.status')

echo "Job status: $STATUS"

if [ "$STATUS" == "3" ]; then
    echo "✓ Test passed: Job completed successfully"
    exit 0
else
    echo "✗ Test failed: Job status is $STATUS"
    exit 1
fi
```

### Load Testing

#### Stress Test API
```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test upload endpoint (100 requests, 10 concurrent)
ab -n 100 -c 10 \
  -H "X-API-Key: your-api-key" \
  -p test.pdf \
  -T "multipart/form-data" \
  https://your-domain.com/api/print/upload

# Test status endpoint
ab -n 1000 -c 50 \
  -H "X-API-Key: your-api-key" \
  https://your-domain.com/api/print/status/TEST-JOB-ID
```

---

## 🚀 Deployment

### Production Checklist

- [ ] SSL certificate installed and configured
- [ ] Environment set to `production` in `.env`
- [ ] Strong API key generated and configured
- [ ] Database credentials secured
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Firewall configured (allow only necessary ports)
- [ ] Rate limiting enabled
- [ ] IP whitelist configured for home server
- [ ] Backup system configured
- [ ] Monitoring and logging enabled
- [ ] Cron jobs tested and running
- [ ] Health check script configured
- [ ] Error pages customized
- [ ] CORS configured if needed

### Backup Strategy

#### Database Backup
```bash
#!/bin/bash
# backup_database.sh

BACKUP_DIR="/home/backups/print-system"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
mysqldump -u print_user -p'secure_password' print_system | gzip > $BACKUP_FILE

# Keep only last 30 days of backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_FILE"
```

Add to crontab:
```bash
# Daily backup at 3 AM
0 3 * * * /home/backups/backup_database.sh >> /home/backups/backup.log 2>&1
```

#### File Backup
```bash
#!/bin/bash
# backup_files.sh

rsync -avz --delete \
  /var/www/html/ci4-backend/public/uploads/ \
  /home/backups/print-system/uploads/

rsync -avz --delete \
  /home/printserver/remote-printing/logs/ \
  /home/backups/print-system/logs/
```

---

## 📚 Additional Resources

### Documentation Links
- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [CUPS Documentation](https://www.cups.org/documentation.html)
- [Python CUPS Bindings](https://github.com/OpenPrinting/pycups)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Useful Commands Reference

```bash
# Check CUPS printer status
lpstat -p

# View print queue
lpstat -o

# Cancel all print jobs
cancel -a

# Test print
lp -d printer_name test.pdf

# View CUPS jobs
lpstat -W all

# Restart all services
sudo systemctl restart cups apache2 cron

# Monitor system resources
htop

# Check open ports
sudo netstat -tulpn

# View system logs
journalctl -xe
```

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style

- Follow PSR-12 coding standards for PHP
- Use the PEP 8 style guide for Python
- Add comments for complex logic
- Write descriptive commit messages
- Include tests for new features

---

## 📄 License

This project is licensed under the MIT License.

```
MIT License

Copyright (c) 2025 Shakib Hossain

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including, without limitation, the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT, OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

```

---

## 👨‍💻 Author

**SHAKIB HOSSAIN**

- Website: [www.shakib.me](https://www.shakib.me)
- Email: [contact@shakib.me](mailto:contact@shakib.me)
- LinkedIn: [Shakib Hossain](https://www.linkedin.com/in/smshme/)

---

## 🙏 Acknowledgments

- CodeIgniter community for the excellent framework
- CUPS project for the powerful printing system
- All contributors and users of this project

---

## 📞 Support

If you encounter any issues or have questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Search existing [GitHub Issues](https://github.com/ProgrammerSMSH/auto-printing-system/issues)
3. Create a new issue with detailed information
4. Contact via email: [contact@shakib.me](mailto:contact@shakib.me)

---

**⭐ If you find this project useful, please consider giving it a star on GitHub!**
