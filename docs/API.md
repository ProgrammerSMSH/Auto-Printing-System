# Print Management System - API Documentation

## Table of Contents
- [Overview](#overview)
- [Authentication](#authentication)
- [Base URL](#base-url)
- [Rate Limiting](#rate-limiting)
- [Endpoints](#endpoints)
- [Status Codes](#status-codes)
- [Error Handling](#error-handling)
- [Examples](#examples)

## Overview

The Print Management System API provides endpoints for managing print jobs, tracking status, and retrieving printer information. All endpoints return JSON responses and require API key authentication.

## Authentication

All API requests require an API key to be included in the request headers.

**Header Format:**
```
X-API-Key: your_api_key_here
```

**Example:**
```bash
curl -H "X-API-Key: abc123xyz789" https://api.example.com/print/status/PJ-20250112-ABC123
```

## Base URL

```
Production: https://api.yourprinting.com
Development: http://localhost/your-app
```

## Rate Limiting

- **Limit:** 100 requests per hour per API key
- **Response Header:** `X-RateLimit-Remaining`
- **Reset Header:** `X-RateLimit-Reset`

When rate limit is exceeded:
```json
{
  "status": "error",
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 3600
}
```

---

## Endpoints

### 1. Upload Print Job

Upload a PDF file for printing.

**Endpoint:** `POST /print/upload`

**Headers:**
- `X-API-Key: string` (required)
- `Content-Type: multipart/form-data`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| file | file | Yes | PDF file to print |
| paper_size | string | No | A4, A3, Letter, Legal (default: A4) |
| color_mode | string | No | color, bw (default: bw) |
| page_range | string | No | Page range (e.g., "1-5", "all") |
| copies | integer | No | Number of copies (default: 1) |
| printer_name | string | No | Specific printer name |

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Print job created successfully",
  "data": {
    "job_id": "PJ-20250112-ABC123",
    "filename": "document.pdf",
    "status": 1,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA...",
    "uploaded_at": "2025-01-12 14:30:00"
  }
}
```

**Example cURL:**
```bash
curl -X POST \
  -H "X-API-Key: your_api_key" \
  -F "file=@document.pdf" \
  -F "paper_size=A4" \
  -F "color_mode=color" \
  -F "copies=2" \
  https://api.yourprinting.com/print/upload
```

---

### 2. Get Job Status

Retrieve the status of a specific print job.

**Endpoint:** `GET /print/status/{job_id}`

**Headers:**
- `X-API-Key: string` (required)

**URL Parameters:**
- `job_id` (required): The unique job identifier

**Success Response (200):**
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
    "completed_at": "2025-01-12 14:31:30",
    "error_message": null
  }
}
```

**Status Values:**
- `1` - Pending
- `2` - Processing
- `3` - Printed
- `4` - Failed

**Example cURL:**
```bash
curl -H "X-API-Key: your_api_key" \
  https://api.yourprinting.com/print/status/PJ-20250112-ABC123
```

---

### 3. Get Print History

Retrieve paginated list of all print jobs.

**Endpoint:** `GET /print/history`

**Headers:**
- `X-API-Key: string` (required)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| limit | integer | No | Items per page (default: 20, max: 100) |
| status | integer | No | Filter by status (1-4) |
| from_date | string | No | Filter from date (YYYY-MM-DD) |
| to_date | string | No | Filter to date (YYYY-MM-DD) |

**Success Response (200):**
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

**Example cURL:**
```bash
curl -H "X-API-Key: your_api_key" \
  "https://api.yourprinting.com/print/history?page=1&limit=20&status=3"
```

---

### 4. Get Pending Jobs

Retrieve all pending print jobs (for admin/printer service).

**Endpoint:** `GET /print/pending`

**Headers:**
- `X-API-Key: string` (required)

**Success Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "job_id": "PJ-20250112-XYZ789",
      "filename": "report.pdf",
      "filepath": "uploads/2025/01/12/report_abc123.pdf",
      "file_size": 2456789,
      "paper_size": "A4",
      "color_mode": "color",
      "page_range": "1-5",
      "copies": 2,
      "printer_name": "HP_LaserJet",
      "status": 1,
      "uploaded_at": "2025-01-12 14:25:00"
    }
  ]
}
```

**Example cURL:**
```bash
curl -H "X-API-Key: your_api_key" \
  https://api.yourprinting.com/print/pending
```

---

### 5. Get Printers List

Retrieve all available printers and their capabilities.

**Endpoint:** `GET /printers/list`

**Headers:**
- `X-API-Key: string` (required)

**Success Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "name": "HP_LaserJet",
      "description": "HP LaserJet Pro M404n",
      "paper_sizes": ["A4", "Letter", "Legal"],
      "color": false,
      "status": "online"
    },
    {
      "name": "EPSON_WF",
      "description": "Epson WorkForce Pro WF-4830",
      "paper_sizes": ["A4", "A3", "Letter"],
      "color": true,
      "status": "online"
    }
  ]
}
```

**Example cURL:**
```bash
curl -H "X-API-Key: your_api_key" \
  https://api.yourprinting.com/printers/list
```

---

### 6. Update Job Status

Update the status of a print job (admin only).

**Endpoint:** `PUT /print/update/{job_id}`

**Headers:**
- `X-API-Key: string` (required)
- `Content-Type: application/json`

**URL Parameters:**
- `job_id` (required): The unique job identifier

**Body Parameters:**
```json
{
  "status": 2,
  "error_message": "Optional error message if status is 4"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Job status updated successfully"
}
```

**Example cURL:**
```bash
curl -X PUT \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"status": 3}' \
  https://api.yourprinting.com/print/update/PJ-20250112-ABC123
```

---

### 7. Delete Job

Delete a print job.

**Endpoint:** `DELETE /print/delete/{job_id}`

**Headers:**
- `X-API-Key: string` (required)

**URL Parameters:**
- `job_id` (required): The unique job identifier

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Print job deleted successfully"
}
```

**Example cURL:**
```bash
curl -X DELETE \
  -H "X-API-Key: your_api_key" \
  https://api.yourprinting.com/print/delete/PJ-20250112-ABC123
```

---

## Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid or missing API key |
| 404 | Not Found - Resource doesn't exist |
| 413 | Payload Too Large - File exceeds size limit |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |

---

## Error Handling

All error responses follow this format:

```json
{
  "status": "error",
  "message": "Descriptive error message"
}
```

**Common Errors:**

### Missing API Key
```json
{
  "status": "error",
  "message": "API key required"
}
```

### Invalid Job ID
```json
{
  "status": "error",
  "message": "Job not found"
}
```

### File Upload Error
```json
{
  "status": "error",
  "message": "No file uploaded or invalid file type"
}
```

### Rate Limit Exceeded
```json
{
  "status": "error",
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 3600
}
```

---

## Examples

### JavaScript (Fetch API)

**Upload File:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('paper_size', 'A4');
formData.append('color_mode', 'color');
formData.append('copies', '2');

fetch('https://api.yourprinting.com/print/upload', {
  method: 'POST',
  headers: {
    'X-API-Key': 'your_api_key'
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

**Check Status:**
```javascript
fetch('https://api.yourprinting.com/print/status/PJ-20250112-ABC123', {
  headers: {
    'X-API-Key': 'your_api_key'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Python (requests)

**Upload File:**
```python
import requests

url = 'https://api.yourprinting.com/print/upload'
headers = {'X-API-Key': 'your_api_key'}
files = {'file': open('document.pdf', 'rb')}
data = {
    'paper_size': 'A4',
    'color_mode': 'color',
    'copies': 2
}

response = requests.post(url, headers=headers, files=files, data=data)
print(response.json())
```

**Check Status:**
```python
import requests

url = 'https://api.yourprinting.com/print/status/PJ-20250112-ABC123'
headers = {'X-API-Key': 'your_api_key'}

response = requests.get(url, headers=headers)
print(response.json())
```

### PHP (cURL)

**Upload File:**
```php
<?php
$ch = curl_init();
$file = new CURLFile('document.pdf', 'application/pdf', 'document.pdf');

$data = [
    'file' => $file,
    'paper_size' => 'A4',
    'color_mode' => 'color',
    'copies' => 2
];

curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.yourprinting.com/print/upload',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: your_api_key'
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
```

---

## Webhooks (Optional)

If webhooks are configured, the system will send POST requests to your webhook URL when job status changes.

**Webhook Payload:**
```json
{
  "event": "job.status_changed",
  "job_id": "PJ-20250112-ABC123",
  "old_status": 2,
  "new_status": 3,
  "timestamp": "2025-01-12 14:31:30"
}
```

---

## Support

For API support, contact: api-support@yourprinting.com

**Last Updated:** January 2025  
**API Version:** 1.0
