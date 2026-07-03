<?php
/**
 * Hotel & Resort Management System
 * 403 Forbidden Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

$page_title = 'Access Denied';
$page_description = 'Access denied - ' . APP_NAME;
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="error-page">
    <div class="error-container" data-aos="fade-up">
        <div class="error-content">
            <div class="error-icon">
                <i class="bi bi-shield-x"></i>
            </div>
            <h1>403</h1>
            <h2>Access Denied</h2>
            <p>You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
            
            <div class="error-actions">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-primary">
                    <i class="bi bi-house me-2"></i>Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Go Back
                </a>
            </div>
            
            <div class="error-info">
                <p class="mb-0">
                    <small class="text-muted">
                        If you need assistance, please contact your system administrator.
                    </small>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .error-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }
    
    .error-container {
        max-width: 600px;
        width: 100%;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 60px 40px;
        text-align: center;
    }
    
    .error-icon {
        font-size: 80px;
        color: #ef4444;
        margin-bottom: 20px;
    }
    
    .error-content h1 {
        font-size: 80px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 10px;
        line-height: 1;
    }
    
    .error-content h2 {
        font-size: 28px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
    }
    
    .error-content p {
        color: #666;
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    
    .error-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .error-actions .btn {
        padding: 12px 24px;
        font-weight: 500;
    }
    
    .error-info {
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    @media (max-width: 576px) {
        .error-container {
            padding: 40px 20px;
        }
        
        .error-icon {
            font-size: 60px;
        }
        
        .error-content h1 {
            font-size: 60px;
        }
        
        .error-content h2 {
            font-size: 24px;
        }
        
        .error-actions {
            flex-direction: column;
        }
        
        .error-actions .btn {
            width: 100%;
        }
    }
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
