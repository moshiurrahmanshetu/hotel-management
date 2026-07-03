<?php
/**
 * Hotel & Resort Management System
 * Rooms Module - Upload Page
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

$page_title = 'Upload Room Images';
$page_description = 'Upload images for room';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Rooms', 'url' => APP_URL . '/modules/rooms/index.php'],
    ['label' => 'Gallery', 'url' => APP_URL . '/modules/rooms/gallery.php?room_id=' . ($_GET['room_id'] ?? 0)],
    ['label' => 'Upload', 'active' => true]
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

// Allowed image types
$allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 10 * 1024 * 1024; // 10MB

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            $error = 'Please select at least one image to upload.';
        } else {
            $uploadedCount = 0;
            $failedCount = 0;
            
            try {
                $db->beginTransaction();
                
                // Get current max display order
                $stmt = $db->prepare("SELECT MAX(display_order) as max_order FROM room_media WHERE room_id = ?");
                $stmt->execute([$roomId]);
                $result = $stmt->fetch();
                $displayOrder = ($result['max_order'] ?? 0) + 1;
                
                // Get current featured count
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM room_media WHERE room_id = ? AND is_featured = 1");
                $stmt->execute([$roomId]);
                $featuredCount = $stmt->fetch()['count'];
                
                foreach ($_FILES['images']['name'] as $key => $name) {
                    $tmpName = $_FILES['images']['tmp_name'][$key];
                    $size = $_FILES['images']['size'][$key];
                    $type = $_FILES['images']['type'][$key];
                    $error_code = $_FILES['images']['error'][$key];
                    
                    // Skip if upload error
                    if ($error_code !== UPLOAD_ERR_OK) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Validate file size
                    if ($size > $maxFileSize) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Validate MIME type
                    if (!in_array($type, $allowedMimeTypes)) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Validate extension
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowedExtensions)) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Validate actual file content
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detectedType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                    
                    if (!in_array($detectedType, $allowedMimeTypes)) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Generate unique filename
                    $uuid = generateUUID();
                    $fileName = $uuid . '.' . $extension;
                    $relativePath = 'uploads/rooms/' . $roomId . '/' . $fileName;
                    $uploadPath = APP_ROOT . '/' . $relativePath;
                    
                    // Create directory if not exists
                    $dir = dirname($uploadPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        // Check if this should be featured (first image if no featured exists)
                        $isFeatured = ($featuredCount === 0 && $uploadedCount === 0) ? 1 : 0;
                        
                        // Insert into database
                        $stmt = $db->prepare("
                            INSERT INTO room_media (uuid, room_id, file_name, file_path, file_type, file_size, mime_type, is_featured, display_order, is_active, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                        ");
                        $stmt->execute([
                            $uuid,
                            $roomId,
                            $name,
                            $relativePath,
                            $extension,
                            $size,
                            $type,
                            $isFeatured,
                            $displayOrder
                        ]);
                        
                        $uploadedCount++;
                        $displayOrder++;
                        if ($isFeatured) {
                            $featuredCount++;
                        }
                    } else {
                        $failedCount++;
                    }
                }
                
                $db->commit();
                
                if ($uploadedCount > 0) {
                    logActivity('create', 'room_media', "Uploaded {$uploadedCount} images for room ID: {$roomId}");
                    $success = "Successfully uploaded {$uploadedCount} image(s)." . ($failedCount > 0 ? " {$failedCount} image(s) failed to upload." : "");
                } else {
                    $error = 'Failed to upload any images. Please check file formats and sizes.';
                }
            } catch (PDOException $e) {
                $db->rollBack();
                if (DEBUG_MODE) {
                    error_log("Upload error: " . $e->getMessage());
                }
                $error = 'An error occurred while uploading the images.';
            }
        }
    }
}
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Upload Images</h1>
                    <p class="page-subtitle">Upload images for room <?php echo htmlspecialchars($room['room_number']); ?></p>
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
                        <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="upload-area" id="dropZone">
                                <div class="upload-content">
                                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                    <h5 class="upload-title">Drag & Drop Images</h5>
                                    <p class="upload-description">or click to browse</p>
                                    <p class="upload-info">
                                        <small class="text-muted">
                                            Supported formats: JPG, PNG, GIF, WebP<br>
                                            Maximum file size: 10MB per image
                                        </small>
                                    </p>
                                    <input type="file" id="fileInput" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;">
                                    <button type="button" class="btn btn-primary mt-3" onclick="document.getElementById('fileInput').click()">
                                        <i class="bi bi-folder2-open me-2"></i>Browse Files
                                    </button>
                                </div>
                            </div>
                            
                            <div id="previewArea" class="preview-area mt-4" style="display: none;">
                                <h6 class="mb-3">Selected Images</h6>
                                <div class="row g-3" id="previewGrid"></div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                                    <i class="bi bi-upload me-2"></i>Upload Images
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/rooms/gallery.php?room_id=<?php echo $roomId; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Gallery
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const previewArea = document.getElementById('previewArea');
const previewGrid = document.getElementById('previewGrid');
const uploadBtn = document.getElementById('uploadBtn');
const uploadForm = document.getElementById('uploadForm');

let selectedFiles = [];

// Click to browse
dropZone.addEventListener('click', function() {
    fileInput.click();
});

// Drag and drop events
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    handleFiles(files);
});

// File input change
fileInput.addEventListener('change', function(e) {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Validate type
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type: ' + file.name);
            continue;
        }
        
        // Validate size
        if (file.size > maxSize) {
            alert('File too large: ' + file.name);
            continue;
        }
        
        // Check for duplicates
        if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            selectedFiles.push(file);
        }
    }
    
    updatePreview();
}

function updatePreview() {
    if (selectedFiles.length === 0) {
        previewArea.style.display = 'none';
        uploadBtn.disabled = true;
        return;
    }
    
    previewArea.style.display = 'block';
    uploadBtn.disabled = false;
    
    previewGrid.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-4 col-6';
            col.innerHTML = `
                <div class="preview-item">
                    <img src="${e.target.result}" alt="${file.name}" class="img-fluid">
                    <button type="button" class="btn-remove" onclick="removeFile(${index})">
                        <i class="bi bi-x"></i>
                    </button>
                    <small class="preview-name text-truncate">${file.name}</small>
                </div>
            `;
            previewGrid.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updatePreview();
}

// Form submission
uploadForm.addEventListener('submit', function(e) {
    // Create new FormData with selected files
    const formData = new FormData();
    
    // Add CSRF token
    formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
    
    // Add files
    selectedFiles.forEach(file => {
        formData.append('images[]', file);
    });
    
    // Submit via fetch
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload page to show results
        window.location.href = window.location.href;
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('Upload failed. Please try again.');
    });
    
    e.preventDefault();
});
</script>

<style>
.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 60px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-area:hover, .upload-area.dragover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.upload-icon {
    font-size: 4rem;
    color: #0d6efd;
    margin-bottom: 20px;
}

.upload-title {
    font-size: 1.25rem;
    margin-bottom: 10px;
}

.upload-description {
    color: #6c757d;
    margin-bottom: 20px;
}

.upload-info {
    margin-bottom: 20px;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.preview-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.btn-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.btn-remove:hover {
    background: rgba(220, 53, 69, 1);
}

.preview-name {
    display: block;
    padding: 5px;
    background: #f8f9fa;
    font-size: 0.75rem;
    margin-top: 0;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
