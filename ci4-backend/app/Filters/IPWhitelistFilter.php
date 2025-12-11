<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class IPWhitelistFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config('Security');
        $clientIP = $request->getIPAddress();
        
        if (!empty($config->allowedIPs) && !in_array($clientIP, $config->allowedIPs)) {
            return Services::response()
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Access denied from this IP address']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not needed
    }
}
