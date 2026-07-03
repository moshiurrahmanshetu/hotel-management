<?php
/**
 * Hotel & Resort Management System
 * Rooms Module - Index Page
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

$page_title = 'Rooms';
$page_description = 'Manage hotel rooms';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Rooms', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $roomId = (int)($_POST['room_id'] ?? 0);
        
        try {
            // Soft delete
            $stmt = $db->prepare("UPDATE rooms SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$roomId]);
            
            logActivity('delete', 'rooms', "Soft deleted room ID: {$roomId}");
            $success = 'Room deleted successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete room error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the room.';
        }
    }
}

// Get master data for dropdowns
$masterData = [];
$masterGroups = ['room_category', 'room_type', 'bed_type', 'view_type'];
foreach ($masterGroups as $groupSlug) {
    $stmt = $db->prepare("
        SELECT mi.id, mi.name, mi.code 
        FROM master_items mi 
        INNER JOIN master_groups mg ON mi.group_id = mg.id 
        WHERE mg.slug = ? AND mi.is_active = 1 AND mi.deleted_at IS NULL 
        ORDER BY mi.display_order ASC, mi.name ASC
    ");
    $stmt->execute([$groupSlug]);
    $masterData[$groupSlug] = $stmt->fetchAll();
}

// Get rooms with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filters
$search = $_GET['search'] ?? '';
$propertyFilter = (int)($_GET['property_id'] ?? 0);
$buildingFilter = (int)($_GET['building_id'] ?? 0);
$floorFilter = (int)($_GET['floor_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$where = ['r.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(r.room_number LIKE ? OR r.room_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($propertyFilter) {
    $where[] = "r.property_id = ?";
    $params[] = $propertyFilter;
}

if ($buildingFilter) {
    $where[] = "r.building_id = ?";
    $params[] = $buildingFilter;
}

if ($floorFilter) {
    $where[] = "r.floor_id = ?";
    $params[] = $floorFilter;
}

if ($statusFilter) {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM rooms r WHERE {$whereClause}");
$stmt->execute($params);
$totalRooms = $stmt->fetchColumn();
$totalPages = ceil($totalRooms / $perPage);

// Get rooms
$stmt = $db->prepare("
    SELECT r.*, 
           p.name as property_name, p.code as property_code,
           b.name as building_name, b.code as building_code,
           f.name as floor_name, f.floor_number,
           rc.name as room_category_name,
           rt.name as room_type_name,
           bt.name as bed_type_name,
           vt.name as view_type_name
    FROM rooms r
    INNER JOIN properties p ON r.property_id = p.id
    INNER JOIN buildings b ON r.building_id = b.id
    INNER JOIN floors f ON r.floor_id = f.id
    LEFT JOIN master_items rc ON r.room_category_id = rc.id
    LEFT JOIN master_items rt ON r.room_type_id = rt.id
    LEFT JOIN master_items bt ON r.bed_type_id = bt.id
    LEFT JOIN master_items vt ON r.view_type_id = vt.id
    WHERE {$whereClause}
    ORDER BY p.name, b.name, f.floor_number, r.room_number
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$rooms = $stmt->fetchAll();

// Get properties for filter
$stmt = $db->query("SELECT id, name, code FROM properties WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
$properties = $stmt->fetchAll();

// Get buildings for filter
$buildingWhere = "deleted_at IS NULL AND is_active = 1";
$buildingParams = [];
if ($propertyFilter) {
    $buildingWhere .= " AND property_id = ?";
    $buildingParams[] = $propertyFilter;
}
$stmt = $db->prepare("SELECT id, name, code FROM buildings WHERE {$buildingWhere} ORDER BY name ASC");
$stmt->execute($buildingParams);
$buildings = $stmt->fetchAll();

// Get floors for filter
$floorWhere = "deleted_at IS NULL AND is_active = 1";
$floorParams = [];
if ($buildingFilter) {
    $floorWhere .= " AND building_id = ?";
    $floorParams[] = $buildingFilter;
}
$stmt = $db->prepare("SELECT id, name, floor_number FROM floors WHERE {$floorWhere} ORDER BY floor_number ASC");
$stmt->execute($floorParams);
$floors = $stmt->fetchAll();

// Status options
$statusOptions = [
    'available' => 'Available',
    'occupied' => 'Occupied',
    'reserved' => 'Reserved',
    'maintenance' => 'Maintenance',
    'cleaning' => 'Cleaning',
    'out_of_service' => 'Out of Service'
];

// Status badge colors
$statusColors = [
    'available' => 'success',
    'occupied' => 'danger',
    'reserved' => 'warning',
    'maintenance' => 'secondary',
    'cleaning' => 'info',
    'out_of_service' => 'dark'
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
                    <h1 class="page-title">Rooms</h1>
                    <p class="page-subtitle">Manage hotel rooms</p>
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
                                <a href="<?php echo APP_URL; ?>/modules/rooms/create.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Room
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2 flex-wrap">
                                    <select name="property_id" class="form-select table-filter" onchange="this.form.submit()">
                                        <option value="">All Properties</option>
                                        <?php foreach ($properties as $prop): ?>
                                            <option value="<?php echo $prop['id']; ?>" <?php echo $propertyFilter === $prop['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prop['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="building_id" class="form-select table-filter" onchange="this.form.submit()">
                                        <option value="">All Buildings</option>
                                        <?php foreach ($buildings as $bld): ?>
                                            <option value="<?php echo $bld['id']; ?>" <?php echo $buildingFilter === $bld['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bld['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="floor_id" class="form-select table-filter" onchange="this.form.submit()">
                                        <option value="">All Floors</option>
                                        <?php foreach ($floors as $flr): ?>
                                            <option value="<?php echo $flr['id']; ?>" <?php echo $floorFilter === $flr['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($flr['name']); ?> (Floor <?php echo $flr['floor_number']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select table-filter" onchange="this.form.submit()">
                                        <option value="">All Status</option>
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search rooms..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="roomsTable">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Room Name</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Capacity</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rooms)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-door-open empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No rooms found</h5>
                                                    <p class="empty-state-description">Get started by adding your first room.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($room['room_number']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($room['room_name'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($room['room_category_name']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($room['room_category_name']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($room['room_type_name']): ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($room['room_type_name']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($room['property_name']); ?></strong><br>
                                                        <?php echo htmlspecialchars($room['building_name']); ?>, Floor <?php echo $room['floor_number']; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <?php if ($room['bed_type_name']): ?>
                                                            <?php echo htmlspecialchars($room['bed_type_name']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($room['view_type_name']): ?>
                                                            <?php echo htmlspecialchars($room['view_type_name']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        Adults: <?php echo $room['max_adults']; ?><br>
                                                        Children: <?php echo $room['max_children']; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo number_format($room['base_price'], 2); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $statusColors[$room['status']] ?? 'secondary'; ?>">
                                                        <?php echo $statusOptions[$room['status']] ?? ucfirst($room['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/rooms/view.php?id=<?php echo $room['id']; ?>" class="table-action-btn" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?php echo APP_URL; ?>/modules/rooms/edit.php?id=<?php echo $room['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Delete">
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&property_id=<?php echo $propertyFilter; ?>&building_id=<?php echo $buildingFilter; ?>&floor_id=<?php echo $floorFilter; ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&property_id=<?php echo $propertyFilter; ?>&building_id=<?php echo $buildingFilter; ?>&floor_id=<?php echo $floorFilter; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&property_id=<?php echo $propertyFilter; ?>&building_id=<?php echo $buildingFilter; ?>&floor_id=<?php echo $floorFilter; ?>&status=<?php echo $statusFilter; ?>">Next</a>
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
            <h5 class="modal-title">Delete Room</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete room <strong id="deleteRoomNumber"></strong>? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="room_id" id="deleteRoomId">
</form>

<script>
function confirmDelete(roomId, roomNumber) {
    document.getElementById('deleteRoomNumber').textContent = roomNumber;
    document.getElementById('deleteRoomId').value = roomId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
