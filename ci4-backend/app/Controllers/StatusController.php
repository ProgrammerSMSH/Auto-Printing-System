<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class StatusController extends Controller
{
    public function check($job_id)
    {
        $model = new \App\Models\PrintJobModel();
        $job = $model->where('job_id', $job_id)->first();
        
        if (!$job) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Job not found'
            ]);
        }
        
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $job
        ]);
    }
}
