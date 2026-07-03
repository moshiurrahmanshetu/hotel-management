<?php
/**
 * Hotel & Resort Management System
 * Rooms Module - Gallery Page
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

$page_title = 'Room Gallery';
$page_description = 'Manage room images';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Rooms', 'url' => APP_URL . '/modules/rooms/index.php'],
    ['label' => 'Gallery', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get room ID
$roomId = (int)($_GET['room_id'] ?? 0);

if (!$roomId) {
    redirect(APP_URL . '/modules/rooms/index.php');
}

// Get room details
$stmt = $db->prepare("SELECT id, room_number, room_name FROM rooms WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    redirect(APP_URL . '/modules/rooms/index.php');
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        
        try {
            // Get file path before deleting
            $stmt = $db->prepare("SELECT file_path FROM room_media WHERE id = ? AND room_id = ?");
            $stmt->execute([$mediaId, $roomId]);
            $media = $stmt->fetch();
            
            if ($media) {
                // Delete from database
                $stmt = $db->prepare("DELETE FROM room_media WHERE id = ? AND room_id = ?");
                $stmt->execute([$mediaId, $roomId]);
                
                // Delete file from disk
                $filePath = APP_ROOT . '/' . $media['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                logActivity('delete', 'room_media', "Deleted room media ID: {$mediaId}");
                $success = 'Image deleted successfully.';
            }
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete media error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the image.';
        }
    }
}

// Handle set featured
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_featured') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        
        try {
            $db->beginTransaction();
            
            // Remove featured from all images in this room
            $stmt = $db->prepare("UPDATE room_media SET is_featured = 0 WHERE room_id = ?");
            $stmt->execute([$roomId]);
            
            // Set featured to selected image
            $stmt = $db->prepare("UPDATE room_media SET is_featured = 1 WHERE id = ? AND room_id = ?");
            $stmt->execute([$mediaId, $roomId]);
            
            $db->commit();
            
            logActivity('update', 'room_media', "Set featured image ID: {$mediaId}");
            $success = 'Featured image updated successfully.';
        } catch (PDOException $e) {
            $db->rollBack();
            if (DEBUG_MODE) {
                error_log("Set featured error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the featured image.';
        }
    }
}

// Handle reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $order = $_POST['order'] ?? [];
        
        try {
            foreach ($order as $mediaId => $displayOrder) {
                $stmt = $db->prepare("UPDATE room_media SET display_order = ? WHERE id = ? AND room_id = ?");
                $stmt->execute([(int)$displayOrder, (int)$mediaId, $roomId]);
            }
            
            logActivity('update', 'room_media', "Reordered room images");
            $success = 'Images reordered successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Reorder error: " . $e->getMessage());
            }
            $error = 'An error occurred while reordering the images.';
        }
    }
}

// Get room media
$stmt = $db->prepare("
    SELECT * FROM room_media 
    WHERE room_id = ? 
    ORDER BY is_featured DESC, display_order ASC, created_at ASC
");
$stmt->execute([$roomId]);
$mediaItems = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Room Gallery</h1>
                    <p class="page-subtitle">Manage images for room <?php echo htmlspecialchars($room['room_number']); ?></p>
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
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Upload Images</h5>
                                <small class="text-muted">Drag & drop or click to upload</small>
                            </div>
                            <a href="<?php echo APP_URL; ?>/modules/rooms/upload.php?room_id=<?php echo $roomId; ?>" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Upload Images
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($mediaItems)): ?>
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-images empty-state-icon"></i>
                                    <h5 class="empty-state-title">No images uploaded</h5>
                                    <p class="empty-state-description">Upload images to showcase this room.</p>
                                    <a href="<?php echo APP_URL; ?>/modules/rooms/upload.php?room_id=<?php echo $roomId; ?>" class="btn btn-primary">
                                        <i class="bi bi-upload me-2"></i>Upload First Image
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="row g-3" id="galleryGrid">
                                <?php foreach ($mediaItems as $index => $media): ?>
                                    <div class="col-md-4 col-sm-6" data-media-id="<?php echo $media['id']; ?>" data-aos="fade-up">
                                        <div class="card gallery-item <?php echo $media['is_featured'] ? 'featured' : ''; ?>">
                                            <div class="card-img-top position-relative">
                                                <img src="<?php echo APP_URL . '/' . $media['file_path']; ?>" alt="<?php echo htmlspecialchars($media['file_name']); ?>" class="img-fluid" style="height: 200px; object-fit: cover; width: 100%;">
                                                <?php if ($media['is_featured']): ?>
                                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                                        <i class="bi bi-star-fill"></i> Featured
                                                    </span>
                                                <?php endif; ?>
                                                <div class="gallery-actions">
                                                    <button type="button" class="btn btn-sm btn-light" onclick="setFeatured(<?php echo $media['id']; ?>)" title="Set as Featured">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-light" onclick="confirmDelete(<?php echo $media['id']; ?>)" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-2">
                                                <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($media['file_name']); ?></small>
                                                <small class="text-muted"><?php echo formatFileSize($media['file_size']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/rooms/view.php?id=<?php echo $roomId; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Room
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Delete Image</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this image? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Set Featured Form -->
<form id="featuredForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="set_featured">
    <input type="hidden" name="media_id" id="featuredMediaId">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="media_id" id="deleteMediaId">
</form>

<script>
let deleteMediaId = null;

function confirmDelete(mediaId) {
    deleteMediaId = mediaId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteMediaId = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteMediaId) {
        document.getElementById('deleteMediaId').value = deleteMediaId;
        document.getElementById('deleteForm').submit();
    }
});

function setFeatured(mediaId) {
    document.getElementById('featuredMediaId').value = mediaId;
    document.getElementById('featuredForm').submit();
}

// Make gallery items sortable
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('galleryGrid');
    if (grid && typeof Sortable !== 'undefined') {
        new Sortable(grid, {
            animation: 150,
            handle: '.gallery-item',
            onEnd: function(evt) {
                const items = grid.querySelectorAll('[data-media-id]');
                const order = {};
                items.forEach((item, index) => {
                    order[item.dataset.mediaId] = index;
                });
                
                // Send order to server
                const formData = new FormData();
                formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
                formData.append('action', 'reorder');
                Object.keys(order).forEach(key => {
                    formData.append('order[' + key + ']', order[key]);
                });
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
            }
        });
    }
});
</script>

<style>
.gallery-item {
    transition: transform 0.2s, box-shadow 0.2s;
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.gallery-item.featured {
    border: 2px solid #ffc107;
}

.gallery-actions {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .gallery-actions {
    opacity: 1;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
