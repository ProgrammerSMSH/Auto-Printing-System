<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config('Api');
        $apiKey = $request->getHeaderLine('X-API-Key');
        
        if (empty($apiKey)) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'API key required']);
        }
        
        if ($apiKey !== $config->key) {
            return Services::response()
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Invalid API key']);
        }
        
        // Check IP whitelist if configured
        $clientIP = $request->getIPAddress();
        if (!empty($config->allowedIPs) && !in_array($clientIP, $config->allowedIPs)) {
            return Services::response()
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'IP address not allowed']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers if needed
        $response->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'X-API-Key, Content-Type, Authorization')
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
}
