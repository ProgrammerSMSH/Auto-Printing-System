<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintJobModel extends Model
{
    protected $table = 'print_jobs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'job_id',
        'filename',
        'filepath',
        'file_size',
        'paper_size',
        'color_mode',
        'page_range',
        'copies',
        'printer_name',
        'status',
        'qr_code',
        'uploaded_at',
        'processed_at',
        'completed_at',
        'error_message'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'uploaded_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'job_id' => 'required|max_length[50]|is_unique[print_jobs.job_id]',
        'filename' => 'required|max_length[255]',
        'filepath' => 'required|max_length[500]',
        'file_size' => 'required|integer',
        'paper_size' => 'required|in_list[A4,A3,Letter,Legal]',
        'color_mode' => 'required|in_list[color,grayscale]',
        'page_range' => 'permit_empty|max_length[50]',
        'copies' => 'required|integer|greater_than[0]|less_than[11]',
        'printer_name' => 'permit_empty|max_length[100]',
        'status' => 'required|integer|in_list[1,2,3]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
}
