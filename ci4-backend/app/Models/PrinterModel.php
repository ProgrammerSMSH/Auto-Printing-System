<?php

namespace App\Models;

use CodeIgniter\Model;

class PrinterModel extends Model
{
    protected $table = 'printers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'description',
        'paper_sizes',
        'color_support',
        'status',
        'last_seen'
    ];

    protected $validationRules = [
        'name' => 'required|max_length[100]|is_unique[printers.name]',
        'description' => 'permit_empty|max_length[255]',
        'status' => 'required|in_list[online,offline,maintenance]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
