<?php
/**
 * Hotel & Resort Management System
 * Custom Fields Module - Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Custom Field' : 'Add Custom Field';
$page_description = isset($_GET['id']) ? 'Edit custom field' : 'Add a new custom field';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Custom Fields', 'url' => APP_URL . '/modules/custom-fields/index.php'],
    ['label' => isset($_GET['id']) ? 'Edit Field' : 'Add Field', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get field ID for edit
$fieldId = (int)($_GET['id'] ?? 0);
$field = null;

// Supported modules
$supportedModules = ['room', 'guest', 'booking', 'service', 'property', 'building', 'floor'];

// Field types
$fieldTypes = [
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

if ($fieldId) {
    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();
    
    if (!$field) {
        redirect(APP_URL . '/modules/custom-fields/index.php');
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $module = sanitizeString($_POST['module'] ?? '');
        $fieldName = sanitizeString($_POST['field_name'] ?? '');
        $fieldLabel = sanitizeString($_POST['field_label'] ?? '');
        $fieldType = sanitizeString($_POST['field_type'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $placeholder = sanitizeString($_POST['placeholder'] ?? '');
        $defaultValue = $_POST['default_value'] ?? '';
        $options = $_POST['options'] ?? '';
        $validationRules = $_POST['validation_rules'] ?? '';
        $conditionalLogic = $_POST['conditional_logic'] ?? '';
        $isRequired = isset($_POST['is_required']) ? 1 : 0;
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$module) {
            $error = 'Please select a module.';
        } elseif (!in_array($module, $supportedModules)) {
            $error = 'Invalid module selected.';
        } elseif (!$fieldName) {
            $error = 'Please enter field name.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $fieldName)) {
            $error = 'Field name must start with a letter and contain only lowercase letters, numbers, and underscores.';
        } elseif (!$fieldLabel) {
            $error = 'Please enter field label.';
        } elseif (!$fieldType) {
            $error = 'Please select field type.';
        } elseif (!array_key_exists($fieldType, $fieldTypes)) {
            $error = 'Invalid field type selected.';
        } else {
            // Check if field name already exists in the same module (excluding current field for edit)
            if ($fieldId) {
                $stmt = $db->prepare("SELECT id FROM custom_fields WHERE module = ? AND field_name = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$module, $fieldName, $fieldId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM custom_fields WHERE module = ? AND field_name = ? AND deleted_at IS NULL");
                $stmt->execute([$module, $fieldName]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Field name already exists in this module.';
            }
        }
        
        // Validate JSON fields
        if (!$error && $options) {
            json_decode($options);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON format for options.';
            }
        }
        
        if (!$error && $validationRules) {
            json_decode($validationRules);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON format for validation rules.';
            }
        }
        
        if (!$error && $conditionalLogic) {
            json_decode($conditionalLogic);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON format for conditional logic.';
            }
        }
        
        if (!$error) {
            try {
                if ($fieldId) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE custom_fields 
                        SET module = ?, field_name = ?, field_label = ?, field_type = ?, description = ?, 
                            placeholder = ?, default_value = ?, options = ?, validation_rules = ?, 
                            conditional_logic = ?, is_required = ?, display_order = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $module, $fieldName, $fieldLabel, $fieldType, $description ?: null,
                        $placeholder ?: null, $defaultValue ?: null, $options ?: null, $validationRules ?: null,
                        $conditionalLogic ?: null, $isRequired, $displayOrder, $isActive, $fieldId
                    ]);
                    
                    logActivity('update', 'custom_fields', "Updated custom field: {$fieldLabel}", $fieldId);
                    $success = 'Custom field updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    
                    $stmt = $db->prepare("
                        INSERT INTO custom_fields (uuid, module, field_name, field_label, field_type, description, 
                            placeholder, default_value, options, validation_rules, conditional_logic, 
                            is_required, display_order, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $uuid, $module, $fieldName, $fieldLabel, $fieldType, $description ?: null,
                        $placeholder ?: null, $defaultValue ?: null, $options ?: null, $validationRules ?: null,
                        $conditionalLogic ?: null, $isRequired, $displayOrder, $isActive
                    ]);
                    
                    logActivity('create', 'custom_fields', "Created custom field: {$fieldLabel}");
                    $success = 'Custom field created successfully.';
                    
                    // Redirect to edit page
                    $fieldId = $db->lastInsertId();
                    header('refresh:2;url=' . APP_URL . '/modules/custom-fields/form.php?id=' . $fieldId);
                }
                
                // Refresh field data
                if ($fieldId) {
                    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE id = ?");
                    $stmt->execute([$fieldId]);
                    $field = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Field form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the field.';
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
                    <h1 class="page-title"><?php echo $fieldId ? 'Edit Custom Field' : 'Add Custom Field'; ?></h1>
                    <p class="page-subtitle"><?php echo $fieldId ? 'Edit custom field' : 'Add a new custom field for modules'; ?></p>
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
                        <form action="" method="POST" id="fieldForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="module" class="form-label required">Module</label>
                                    <select class="form-select" id="module" name="module" required>
                                        <option value="">Select Module</option>
                                        <?php foreach ($supportedModules as $module): ?>
                                            <option value="<?php echo $module; ?>" <?php echo ($field['module'] ?? '') === $module ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($module); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="field_type" class="form-label required">Field Type</label>
                                    <select class="form-select" id="field_type" name="field_type" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($fieldTypes as $type => $label): ?>
                                            <option value="<?php echo $type; ?>" <?php echo ($field['field_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="field_name" class="form-label required">Field Name</label>
                                    <input type="text" class="form-control" id="field_name" name="field_name" required pattern="[a-z][a-z0-9_]*" value="<?php echo htmlspecialchars($field['field_name'] ?? ''); ?>">
                                    <small class="form-text text-muted">Lowercase, numbers, underscores only (e.g., extra_bed_price)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="field_label" class="form-label required">Field Label</label>
                                    <input type="text" class="form-control" id="field_label" name="field_label" required value="<?php echo htmlspecialchars($field['field_label'] ?? ''); ?>">
                                    <small class="form-text text-muted">Display label (e.g., Extra Bed Price)</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($field['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="placeholder" class="form-label">Placeholder</label>
                                    <input type="text" class="form-control" id="placeholder" name="placeholder" value="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="default_value" class="form-label">Default Value</label>
                                    <input type="text" class="form-control" id="default_value" name="default_value" value="<?php echo htmlspecialchars($field['default_value'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="<?php echo htmlspecialchars($field['display_order'] ?? 0); ?>">
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Required</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_required" name="is_required" <?php echo ($field['is_required'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_required">Field is required</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="options" class="form-label">Options (JSON)</label>
                                <textarea class="form-control" id="options" name="options" rows="4" placeholder='{"Single": "1", "Double": "2", "King": "3"}'><?php echo htmlspecialchars($field['options'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">For select, multi_select, radio, checkbox types. JSON object with key-value pairs.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="validation_rules" class="form-label">Validation Rules (JSON)</label>
                                <textarea class="form-control" id="validation_rules" name="validation_rules" rows="4" placeholder='{"min": 0, "max": 100, "regex": "^[0-9]+$"}'><?php echo htmlspecialchars($field['validation_rules'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">JSON object with validation rules: min, max, regex, pattern, etc.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="conditional_logic" class="form-label">Conditional Logic (JSON)</label>
                                <textarea class="form-control" id="conditional_logic" name="conditional_logic" rows="4" placeholder='{"field": "extra_bed", "operator": "==", "value": "yes"}'><?php echo htmlspecialchars($field['conditional_logic'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Show this field only when condition is met. JSON object with field, operator, value.</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($field) || $field['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $fieldId ? 'Update Field' : 'Create Field'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/custom-fields/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Fields
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Field Types & Examples</h5>
                    </div>
                    <div class="card-body">
                        <h6>Field Types</h6>
                        <ul class="mb-3">
                            <li><strong>Text:</strong> Single line text input</li>
                            <li><strong>Number:</strong> Numeric input</li>
                            <li><strong>Email:</strong> Email address input</li>
                            <li><strong>Phone:</strong> Phone number input</li>
                            <li><strong>Textarea:</strong> Multi-line text input</li>
                            <li><strong>Select:</strong> Dropdown selection (requires options JSON)</li>
                            <li><strong>Multi Select:</strong> Multiple selections (requires options JSON)</li>
                            <li><strong>Checkbox:</strong> Single checkbox (requires options JSON)</li>
                            <li><strong>Radio:</strong> Radio buttons (requires options JSON)</li>
                            <li><strong>Date:</strong> Date picker</li>
                            <li><strong>Time:</strong> Time picker</li>
                            <li><strong>DateTime:</strong> Date and time picker</li>
                            <li><strong>File:</strong> File upload</li>
                            <li><strong>Image:</strong> Image upload</li>
                            <li><strong>URL:</strong> URL input</li>
                        </ul>
                        
                        <h6>Options JSON Example</h6>
                        <pre class="bg-light p-3 rounded"><code>{
    "Single": "1",
    "Double": "2",
    "King": "3",
    "Queen": "4"
}</code></pre>
                        
                        <h6 class="mt-3">Validation Rules JSON Example</h6>
                        <pre class="bg-light p-3 rounded"><code>{
    "min": 0,
    "max": 100,
    "regex": "^[0-9]+$"
}</code></pre>
                        
                        <h6 class="mt-3">Conditional Logic JSON Example</h6>
                        <pre class="bg-light p-3 rounded"><code>{
    "field": "extra_bed",
    "operator": "==",
    "value": "yes"
}</code></pre>
                        <p class="text-muted small">This will show the field only when the "extra_bed" field equals "yes".</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Auto-generate field name from label
document.getElementById('field_label').addEventListener('input', function() {
    const label = this.value;
    const fieldName = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    document.getElementById('field_name').value = fieldName;
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
