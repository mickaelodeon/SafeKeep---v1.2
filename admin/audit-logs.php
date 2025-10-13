<?php
/**
 * Admin Audit Logs
 * View system audit logs and user activities
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Audit Logs';
$showAdminSidebar = true;

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Filters
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build query conditions
$conditions = [];
$params = [];

if (!empty($filterUser)) {
    $conditions[] = "u.full_name LIKE ?";
    $params[] = "%$filterUser%";
}

if (!empty($filterAction)) {
    $conditions[] = "al.action LIKE ?";
    $params[] = "%$filterAction%";
}

if (!empty($filterDateFrom)) {
    $conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

try {
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $whereClause";
    $totalRecords = Database::selectOne($countQuery, $params)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get audit logs
    $query = "SELECT al.*, u.full_name as user_name, u.email as user_email
              FROM audit_logs al 
              LEFT JOIN users u ON al.user_id = u.id 
              $whereClause
              ORDER BY al.created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $auditLogs = Database::select($query, $params);
    
    // Get unique actions for filter dropdown
    $actions = Database::select("SELECT DISTINCT action FROM audit_logs ORDER BY action");
    
} catch (Exception $e) {
    $auditLogs = [];
    $actions = [];
    $totalRecords = 0;
    $totalPages = 0;
    $error = 'Failed to load audit logs.';
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Audit Logs</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <small class="text-muted"><?php echo number_format($totalRecords); ?> total records</small>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="user" class="form-label">User</label>
                    <input type="text" class="form-control" id="user" name="user" 
                           value="<?php echo htmlspecialchars($filterUser); ?>" 
                           placeholder="Search by user name...">
                </div>
                <div class="col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                <?php echo $action['action'] === $filterAction ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action['action']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filterDateTo); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="<?php echo Config::get('app.url'); ?>/admin/audit-logs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($auditLogs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No audit logs found</h5>
                <p class="text-muted">No activities match your current filters.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>Resource ID</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td>
                                <small><?php echo date('M j, Y g:i:s A', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($log['user_name']): ?>
                                <span title="<?php echo htmlspecialchars($log['user_email']); ?>">
                                    <?php echo htmlspecialchars($log['user_name']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    $actionType = strtolower($log['action']);
                                    if (str_contains($actionType, 'create') || str_contains($actionType, 'approve')) {
                                        echo 'success';
                                    } elseif (str_contains($actionType, 'delete') || str_contains($actionType, 'reject')) {
                                        echo 'danger';
                                    } elseif (str_contains($actionType, 'update') || str_contains($actionType, 'edit')) {
                                        echo 'warning';
                                    } else {
                                        echo 'info';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['resource_type'] ?? '-'); ?></td>
                            <td><?php echo $log['resource_id'] ? '#' . $log['resource_id'] : '-'; ?></td>
                            <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                            <td>
                                <?php if (!empty($log['details'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailsModal"
                                        data-details="<?php echo htmlspecialchars($log['details']); ?>"
                                        data-action="<?php echo htmlspecialchars($log['action']); ?>"
                                        data-timestamp="<?php echo htmlspecialchars($log['created_at']); ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            Previous
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Action:</h6>
                <p id="modalAction" class="mb-3"></p>
                
                <h6>Timestamp:</h6>
                <p id="modalTimestamp" class="mb-3"></p>
                
                <h6>Details:</h6>
                <pre id="modalDetails" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('detailsModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const modal = this;
    
    modal.querySelector('#modalAction').textContent = button.getAttribute('data-action');
    modal.querySelector('#modalTimestamp').textContent = new Date(button.getAttribute('data-timestamp')).toLocaleString();
    modal.querySelector('#modalDetails').textContent = button.getAttribute('data-details') || 'No additional details available.';
});
</script>

<?php require_once '../includes/footer.php'; ?>