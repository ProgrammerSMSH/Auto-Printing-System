<?php

if (!function_exists('validatePDFFile')) {
    function validatePDFFile($filePath)
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'File does not exist'];
        }

        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return ['success' => false, 'message' => 'Invalid file extension'];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return ['success' => false, 'message' => 'Invalid MIME type'];
        }

        // Check PDF magic bytes
        $handle = fopen($filePath, 'r');
        $header = fread($handle, 4);
        fclose($handle);

        if (strpos($header, '%PDF') !== 0) {
            return ['success' => false, 'message' => 'Invalid PDF signature'];
        }

        return ['success' => true, 'message' => 'Valid PDF file'];
    }
}

if (!function_exists('generateQRCodeData')) {
    function generateQRCodeData($data)
    {
        try {
            $qrCode = new \Endroid\QrCode\QrCode(json_encode($data));
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
}

if (!function_exists('formatResponse')) {
    function formatResponse($status, $message, $data = null)
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
}

if (!function_exists('sanitizeFilename')) {
    function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_{2,}/', '_', $filename);
        return trim($filename, '_');
    }
}
