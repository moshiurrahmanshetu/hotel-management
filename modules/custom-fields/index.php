<?php
/**
 * Hotel & Resort Management System
 * Custom Fields Module - Index Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Custom Fields';
$page_description = 'Manage custom fields for modules';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Custom Fields', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $fieldId = (int)($_POST['field_id'] ?? 0);
        
        // Check if field has values
        $stmt = $db->prepare("SELECT COUNT(*) FROM custom_field_values WHERE field_id = ?");
        $stmt->execute([$fieldId]);
        $valueCount = $stmt->fetchColumn();
        
        if ($valueCount > 0) {
            $error = 'Cannot delete field. It has ' . $valueCount . ' value(s) assigned to it.';
        } else {
            try {
                // Soft delete
                $stmt = $db->prepare("UPDATE custom_fields SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$fieldId]);
                
                logActivity('delete', 'custom_fields', "Soft deleted custom field ID: {$fieldId}");
                $success = 'Custom field deleted successfully.';
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Delete field error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the field.';
            }
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $fieldId = (int)($_POST['field_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE custom_fields SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $fieldId]);
            
            logActivity('update', 'custom_fields', "Toggled field status ID: {$fieldId}");
            $success = 'Field status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Supported modules
$supportedModules = ['room', 'guest', 'booking', 'service', 'property', 'building', 'floor'];

// Get fields with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$moduleFilter = $_GET['module'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['deleted_at IS NULL'];
$params = [];

if ($moduleFilter && in_array($moduleFilter, $supportedModules)) {
    $where[] = "module = ?";
    $params[] = $moduleFilter;
}

if ($search) {
    $where[] = "(field_name LIKE ? OR field_label LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM custom_fields WHERE {$whereClause}");
$stmt->execute($params);
$totalFields = $stmt->fetchColumn();
$totalPages = ceil($totalFields / $perPage);

// Get fields
$stmt = $db->prepare("
    SELECT f.*, 
           COUNT(DISTINCT v.id) as value_count
    FROM custom_fields f
    LEFT JOIN custom_field_values v ON f.id = v.field_id
    WHERE {$whereClause}
    GROUP BY f.id
    ORDER BY f.module ASC, f.display_order ASC, f.field_label ASC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$fields = $stmt->fetchAll();

// Field type labels
$fieldTypeLabels = [
    'text' => 'Text',
    'number' => 'Number',
    'email' => 'Email',
    'phone' => 'Phone',
    'textarea' => 'Textarea',
    'select' => 'Select',
    'multi_select' => 'Multi Select',
    'checkbox' => 'Checkbox',
    'radio' => 'Radio',
    'date' => 'Date',
    'time' => 'Time',
    'datetime' => 'Date Time',
    'file' => 'File',
    'image' => 'Image',
    'url' => 'URL'
];
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Custom Fields</h1>
                    <p class="page-subtitle">Manage custom fields for modules (Room, Guest, Booking, etc.)</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-toolbar">
                            <div class="table-toolbar-left">
                                <a href="<?php echo APP_URL; ?>/modules/custom-fields/form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Custom Field
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="module" class="form-select table-filter">
                                        <option value="">All Modules</option>
                                        <?php foreach ($supportedModules as $module): ?>
                                            <option value="<?php echo $module; ?>" <?php echo $moduleFilter === $module ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($module); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search fields..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="fieldsTable">
                                <thead>
                                    <tr>
                                        <th>Field Label</th>
                                        <th>Field Name</th>
                                        <th>Module</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Values</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fields)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-input-cursor-text empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No custom fields found</h5>
                                                    <p class="empty-state-description">Get started by adding your first custom field.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fields as $field): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($field['field_label']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($field['description'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($field['field_name']); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($field['module']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $fieldTypeLabels[$field['field_type']] ?? $field['field_type']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($field['is_required']): ?>
                                                        <i class="bi bi-check-circle-fill text-success"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-circle text-muted"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $field['value_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $field['display_order']; ?>
                                                </td>
                                                <td>
                                                    <?php if ($field['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/custom-fields/form.php?id=<?php echo $field['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $field['id']; ?>, <?php echo $field['is_active'] ? 0 : 1; ?>)" title="<?php echo $field['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $field['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $field['id']; ?>, '<?php echo htmlspecialchars($field['field_label']); ?>')" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&module=<?php echo $moduleFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&module=<?php echo $moduleFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&module=<?php echo $moduleFilter; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Delete Custom Field</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteFieldName"></strong>? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Status Toggle Form -->
<form id="statusForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="field_id" id="statusFieldId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="field_id" id="deleteFieldId">
</form>

<script>
function confirmDelete(fieldId, fieldLabel) {
    document.getElementById('deleteFieldName').textContent = fieldLabel;
    document.getElementById('deleteFieldId').value = fieldId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(fieldId, status) {
    document.getElementById('statusFieldId').value = fieldId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
