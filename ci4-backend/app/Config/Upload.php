<?php

namespace Config;

class Upload extends \CodeIgniter\Config\BaseConfig
{
    public $maxSize = 10485760; // 10MB
    public $allowedTypes = 'pdf';
    public $fileExtIn = ['pdf'];
    public $mimeIn = ['application/pdf'];
    
    public $path = WRITEPATH . 'uploads/';
    public $overwrite = false;
    public $maxFilename = 255;
    public $maxDimension = 0;
    public $minDimension = 0;
    public $encryptName = true;
    public $removeSpaces = true;
    public $detectMime = true;
    
    public function __construct()
    {
        parent::__construct();
        
        // Create directory structure for uploads
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        $this->path .= "{$year}/{$month}/{$day}/";
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }
}
