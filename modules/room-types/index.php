<?php
/**
 * Hotel & Resort Management System
 * Room Types Module - Index Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Room Types';
$page_description = 'Manage hotel room types';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Types', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        
        try {
            // Soft delete
            $stmt = $db->prepare("UPDATE room_types SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$roomTypeId]);
            
            logActivity('delete', 'room_types', "Soft deleted room type ID: {$roomTypeId}");
            $success = 'Room type deleted successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete room type error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the room type.';
        }
    }
}

// Get room types with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);

$where = ['rt.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(rt.name LIKE ? OR rt.code LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "rt.is_active = ?";
    $params[] = $statusFilter === 'active' ? 1 : 0;
}

if ($minPrice > 0) {
    $where[] = "rt.base_price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "rt.base_price <= ?";
    $params[] = $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) FROM room_types rt WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get room types with room count
$sql = "
    SELECT rt.*, 
           COUNT(DISTINCT r.id) as room_count
    FROM room_types rt
    LEFT JOIN rooms r ON rt.id = r.room_type_id AND r.deleted_at IS NULL
    WHERE {$whereClause}
    GROUP BY rt.id
    ORDER BY rt.name ASC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$roomTypes = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Room Types</h1>
                    <p class="page-subtitle">Manage hotel room types and configurations</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or Code">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="min_price" class="form-label">Min Price</label>
                                <input type="number" class="form-control" id="min_price" name="min_price" 
                                       value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" step="0.01" min="0">
                            </div>
                            <div class="col-md-2">
                                <label for="max_price" class="form-label">Max Price</label>
                                <input type="number" class="form-control" id="max_price" name="max_price" 
                                       value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>" step="0.01" min="0">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/room-types/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Room Types Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Room Types List</h5>
                        <?php if (hasPermission('room_types.create')): ?>
                        <a href="<?php echo APP_URL; ?>/modules/room-types/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Room Type
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($roomTypes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-door-open" style="font-size: 4rem; color: #dee2e6;"></i>
                            <p class="mt-3 text-muted">No room types found.</p>
                            <?php if (hasPermission('room_types.create')): ?>
                            <a href="<?php echo APP_URL; ?>/modules/room-types/create.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Add Room Type
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Base Price</th>
                                        <th>Occupancy</th>
                                        <th>Rooms</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roomTypes as $roomType): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($roomType['name']); ?></strong>
                                            <?php if ($roomType['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($roomType['description'], 0, 50)) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($roomType['code']); ?></code></td>
                                        <td><?php echo formatCurrency($roomType['base_price']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $roomType['max_adults']; ?> Adults
                                                <?php if ($roomType['max_children'] > 0): ?>
                                                + <?php echo $roomType['max_children']; ?> Children
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $roomType['room_count']; ?></td>
                                        <td>
                                            <?php if ($roomType['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($roomType['created_at']); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <?php if (hasPermission('room_types.view')): ?>
                                                <a href="<?php echo APP_URL; ?>/modules/room-types/view.php?id=<?php echo $roomType['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('room_types.edit')): ?>
                                                <a href="<?php echo APP_URL; ?>/modules/room-types/edit.php?id=<?php echo $roomType['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('room_types.delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this room type?');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="room_type_id" value="<?php echo $roomType['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-end">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                                <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
