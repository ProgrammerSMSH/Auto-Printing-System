<?php

namespace Config;

class Api extends \CodeIgniter\Config\BaseConfig
{
    public $key = 'your-secure-api-key-here-change-in-production';
    
    public $allowedIPs = [
        '127.0.0.1',
        '192.168.1.100', // Home server IP
        '::1'
    ];
    
    public $rateLimit = 100; // requests per hour
    
    public $upload = [
        'maxSize' => 10485760, // 10MB
        'allowedTypes' => ['pdf'],
        'path' => WRITEPATH . 'uploads/'
    ];
}
