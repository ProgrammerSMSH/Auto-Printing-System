<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PrintJobModel;
use CodeIgniter\Files\File;

class PrintController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = 'App\Models\PrintJobModel';
    protected $format    = 'json';

    private function generateJobId()
    {
        $date = date('Ymd');
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        return 'PJ-' . $date . '-' . $random;
    }

    private function generateQRCode($jobId, $filename)
    {
        $qrData = json_encode([
            'job_id' => $jobId,
            'filename' => $filename,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        try {
            $qrCode = new \Endroid\QrCode\QrCode($qrData);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);
            
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (\Exception $e) {
            log_message('error', 'QR Code generation failed: ' . $e->getMessage());
            return null;
        }
    }

    private function validatePDF($file)
    {
        // Check file extension
        if ($file->getClientExtension() !== 'pdf') {
            return ['success' => false, 'message' => 'Only PDF files are allowed'];
        }

        // Check MIME type
        $mimeType = $file->getClientMimeType();
        if ($mimeType !== 'application/pdf') {
            return ['success' => false, 'message' => 'Invalid file type'];
        }

        // Check file signature (magic bytes)
        $filePath = $file->getTempName();
        $handle = fopen($filePath, 'r');
        $header = fread($handle, 4);
        fclose($handle);

        if (strpos($header, '%PDF') !== 0) {
            return ['success' => false, 'message' => 'Invalid PDF file'];
        }

        // Check file size (10MB limit)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
        }

        return ['success' => true];
    }

    public function upload()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'file' => 'uploaded[file]|max_size[file,10240]|ext_in[file,pdf]',
            'paper_size' => 'required|in_list[A4,A3,Letter,Legal]',
            'color_mode' => 'required|in_list[color,grayscale]',
            'copies' => 'required|integer|greater_than[0]|less_than[11]',
            'printer_name' => 'permit_empty|string|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $file = $this->request->getFile('file');
        
        // Validate PDF file
        $pdfValidation = $this->validatePDF($file);
        if (!$pdfValidation['success']) {
            return $this->fail($pdfValidation['message']);
        }

        // Generate unique job ID
        $jobId = $this->generateJobId();
        
        // Create upload directory structure
        $uploadPath = WRITEPATH . 'uploads/' . date('Y/m/d');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        // Generate unique filename
        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);
        
        // Generate QR code
        $qrCode = $this->generateQRCode($jobId, $file->getClientName());
        
        // Prepare print job data
        $printJobData = [
            'job_id' => $jobId,
            'filename' => $file->getClientName(),
            'filepath' => 'uploads/' . date('Y/m/d') . '/' . $newName,
            'file_size' => $file->getSize(),
            'paper_size' => $this->request->getPost('paper_size'),
            'color_mode' => $this->request->getPost('color_mode'),
            'page_range' => $this->request->getPost('page_range') ?? 'all',
            'copies' => $this->request->getPost('copies'),
            'printer_name' => $this->request->getPost('printer_name') ?? 'default',
            'status' => 1, // Pending
            'qr_code' => $qrCode,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];

        // Save to database
        $model = new PrintJobModel();
        if ($model->insert($printJobData)) {
            $response = [
                'status' => 'success',
                'message' => 'Print job created successfully',
                'data' => [
                    'job_id' => $jobId,
                    'filename' => $printJobData['filename'],
                    'status' => 1,
                    'qr_code' => $qrCode,
                    'uploaded_at' => $printJobData['uploaded_at']
                ]
            ];
            
            return $this->respondCreated($response);
        } else {
            return $this->failServerError('Failed to create print job');
        }
    }

    public function pending()
    {
        $model = new PrintJobModel();
        $jobs = $model->where('status', 1)->findAll();
        
        return $this->respond([
            'status' => 'success',
            'data' => $jobs
        ]);
    }

    public function update($job_id = null)
    {
        $model = new PrintJobModel();
        $job = $model->where('job_id', $job_id)->first();
        
        if (!$job) {
            return $this->failNotFound('Print job not found');
        }

        $data = $this->request->getJSON(true);
        
        $allowedFields = ['status', 'processed_at', 'completed_at', 'error_message'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (isset($updateData['status'])) {
            if ($updateData['status'] == 2) {
                $updateData['processed_at'] = date('Y-m-d H:i:s');
            } elseif ($updateData['status'] == 3) {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
        }
        
        if ($model->update($job['id'], $updateData)) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Job status updated successfully'
            ]);
        } else {
            return $this->failServerError('Failed to update job status');
        }
    }

    public function status($job_id = null)
    {
        $model = new PrintJobModel();
        $job = $model->where('job_id', $job_id)->first();
        
        if (!$job) {
            return $this->failNotFound('Print job not found');
        }
        
        $statusText = match((int)$job['status']) {
            1 => 'Pending',
            2 => 'Processing',
            3 => 'Printed',
            default => 'Unknown'
        };
        
        $response = [
            'status' => 'success',
            'data' => [
                'job_id' => $job['job_id'],
                'filename' => $job['filename'],
                'status' => $job['status'],
                'status_text' => $statusText,
                'uploaded_at' => $job['uploaded_at'],
                'processed_at' => $job['processed_at'],
                'completed_at' => $job['completed_at'],
                'error_message' => $job['error_message']
            ]
        ];
        
        return $this->respond($response);
    }

    public function history()
    {
        $model = new PrintJobModel();
        
        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 20;
        $status = $this->request->getGet('status');
        $fromDate = $this->request->getGet('from_date');
        $toDate = $this->request->getGet('to_date');
        
        $builder = $model->builder();
        
        // Apply filters
        if ($status) {
            $builder->where('status', $status);
        }
        
        if ($fromDate) {
            $builder->where('uploaded_at >=', $fromDate . ' 00:00:00');
        }
        
        if ($toDate) {
            $builder->where('uploaded_at <=', $toDate . ' 23:59:59');
        }
        
        // Get total count
        $total = $builder->countAllResults(false);
        $totalPages = ceil($total / $limit);
        
        // Get paginated results
        $offset = ($page - 1) * $limit;
        $jobs = $builder->orderBy('uploaded_at', 'DESC')
                       ->limit($limit, $offset)
                       ->get()
                       ->getResultArray();
        
        // Format response
        $formattedJobs = array_map(function($job) {
            $statusText = match((int)$job['status']) {
                1 => 'Pending',
                2 => 'Processing',
                3 => 'Printed',
                default => 'Unknown'
            };
            
            return [
                'job_id' => $job['job_id'],
                'filename' => $job['filename'],
                'status' => $job['status'],
                'status_text' => $statusText,
                'uploaded_at' => $job['uploaded_at'],
                'completed_at' => $job['completed_at']
            ];
        }, $jobs);
        
        $response = [
            'status' => 'success',
            'data' => [
                'jobs' => $formattedJobs,
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'items_per_page' => (int)$limit
                ]
            ]
        ];
        
        return $this->respond($response);
    }

    public function delete($job_id = null)
    {
        $model = new PrintJobModel();
        $job = $model->where('job_id', $job_id)->first();
        
        if (!$job) {
            return $this->failNotFound('Print job not found');
        }
        
        // Delete file if exists
        $filePath = WRITEPATH . $job['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        if ($model->delete($job['id'])) {
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Print job deleted successfully'
            ]);
        } else {
            return $this->failServerError('Failed to delete print job');
        }
    }
}
