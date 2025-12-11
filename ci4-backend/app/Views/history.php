<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print History - Remote PDF Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-processing { background-color: #0dcaf0; color: #fff; }
        .status-printed { background-color: #198754; color: #fff; }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="/print/upload">
                            <i class="fas fa-print text-primary me-2"></i>
                            <strong>Remote Printing System</strong>
                        </a>
                        <div class="navbar-nav">
                            <a class="nav-link" href="/print/upload">
                                <i class="fas fa-upload me-1"></i> Upload
                            </a>
                            <a class="nav-link active" href="/print/history">
                                <i class="fas fa-history me-1"></i> History
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="filter-card">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="filterStatus">
                                <option value="">All Status</option>
                                <option value="1">Pending</option>
                                <option value="2">Processing</option>
                                <option value="3">Printed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" id="filterFromDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" id="filterToDate">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary me-2" onclick="applyFilters()">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Print Job History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Filename</th>
                                        <th>Printer</th>
                                        <th>Size</th>
                                        <th>Pages</th>
                                        <th>Copies</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                        <th>Completed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <?php
                                        $statusClass = match((int)$job['status']) {
                                            1 => 'status-pending',
                                            2 => 'status-processing',
                                            3 => 'status-printed',
                                            default => ''
                                        };
                                        $statusText = match((int)$job['status']) {
                                            1 => 'Pending',
                                            2 => 'Processing',
                                            3 => 'Printed',
                                            default => 'Unknown'
                                        };
                                        ?>
                                        <tr>
                                            <td><code><?= $job['job_id'] ?></code></td>
                                            <td><?= $job['filename'] ?></td>
                                            <td><?= $job['printer_name'] ?></td>
                                            <td><?= formatBytes($job['file_size']) ?></td>
                                            <td><?= $job['page_range'] ?></td>
                                            <td><?= $job['copies'] ?></td>
                                            <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                            <td><?= date('Y-m-d H:i', strtotime($job['uploaded_at'])) ?></td>
                                            <td><?= $job['completed_at'] ? date('Y-m-d H:i', strtotime($job['completed_at'])) : '-' ?></td>
                                            <td>
                                                <?php if ($job['qr_code']): ?>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showQRCode('<?= $job['qr_code'] ?>')" title="Show QR Code">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewJobDetails('<?= $job['job_id'] ?>')" title="View Details">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                <?php if ($job['status'] == 1): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteJob('<?= $job['job_id'] ?>')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center" id="pagination">
                                <!-- Pagination will be loaded dynamically -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for QR Code -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Print Job QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="qrImage" src="" alt="QR Code" class="img-fluid">
                    <p class="mt-3 text-muted">Scan to track this print job</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Job Details -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Print Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentFilters = {};
        
        $(document).ready(function() {
            $('#historyTable').DataTable({
                pageLength: 25,
                order: [[7, 'desc']]
            });
            
            loadPagination();
        });
        
        function showQRCode(qrData) {
            $('#qrImage').attr('src', qrData);
            new bootstrap.Modal($('#qrModal')[0]).show();
        }
        
        async function viewJobDetails(jobId) {
            try {
                const response = await fetch(`/api/print/status/${jobId}`, {
                    headers: {
                        'X-API-Key': '<?= config('Api')->key ?>'
                    }
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    const job = result.data;
                    const details = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Job Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Job ID:</th>
                                        <td><code>${job.job_id}</code></td>
                                    </tr>
                                    <tr>
                                        <th>Filename:</th>
                                        <td>${job.filename}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="badge ${getStatusClass(job.status)}">${job.status_text}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Printer:</th>
                                        <td>${job.printer_name || 'Default'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Print Settings</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Paper Size:</th>
                                        <td>${job.paper_size}</td>
                                    </tr>
                                    <tr>
                                        <th>Color Mode:</th>
                                        <td>${job.color_mode}</td>
                                    </tr>
                                    <tr>
                                        <th>Page Range:</th>
                                        <td>${job.page_range}</td>
                                    </tr>
                                    <tr>
                                        <th>Copies:</th>
                                        <td>${job.copies}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Timestamps</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Uploaded:</th>
                                        <td>${new Date(job.uploaded_at).toLocaleString()}</td>
                                    </tr>
                                    <tr>
                                        <th>Processing Started:</th>
                                        <td>${job.processed_at ? new Date(job.processed_at).toLocaleString() : '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Completed:</th>
                                        <td>${job.completed_at ? new Date(job.completed_at).toLocaleString() : '-'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        ${job.error_message ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-danger">Error Message</h6>
                                <div class="alert alert-danger">${job.error_message}</div>
                            </div>
                        </div>
                        ` : ''}
                    `;
                    
                    $('#jobDetailsContent').html(details);
                    new bootstrap.Modal($('#detailsModal')[0]).show();
                }
            } catch (error) {
                alert('Error loading job details: ' + error.message);
            }
        }
        
        async function deleteJob(jobId) {
            if (!confirm('Are you sure you want to delete this print job?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/print/delete/${jobId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-API-Key': '<?= config('Api')->key ?>'
                    }
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Print job deleted successfully');
                    location.reload();
                } else {
                    alert('Delete failed: ' + result.message);
                }
            } catch (error) {
                alert('Error deleting job: ' + error.message);
            }
        }
        
        function getStatusClass(status) {
            switch(parseInt(status)) {
                case 1: return 'status-pending';
                case 2: return 'status-processing';
                case 3: return 'status-printed';
                default: return '';
            }
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        async function loadHistory(page = 1) {
            const params = new URLSearchParams({
                page: page,
                limit: 25,
                ...currentFilters
            });
            
            try {
                const response = await fetch(`/api/print/history?${params}`, {
                    headers: {
                        'X-API-Key': '<?= config('Api')->key ?>'
                    }
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    renderHistoryTable(result.data.jobs);
                    renderPagination(result.data.pagination);
                }
            } catch (error) {
                console.error('Error loading history:', error);
            }
        }
        
        function applyFilters() {
            currentFilters = {};
            
            const status = $('#filterStatus').val();
            if (status) {
                currentFilters.status = status;
            }
            
            const fromDate = $('#filterFromDate').val();
            if (fromDate) {
                currentFilters.from_date = fromDate;
            }
            
            const toDate = $('#filterToDate').val();
            if (toDate) {
                currentFilters.to_date = toDate;
            }
            
            loadHistory(1);
        }
        
        function resetFilters() {
            $('#filterStatus').val('');
            $('#filterFromDate').val('');
            $('#filterToDate').val('');
            currentFilters = {};
            loadHistory(1);
        }
        
        function loadPagination() {
            // This would be populated from API response
            // For now, we'll use static data
        }
    </script>
</body>
</html>

<?php
function formatBytes($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
