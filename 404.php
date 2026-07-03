<?php
/**
 * Hotel & Resort Management System
 * 404 Error Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load configuration
require_once APP_ROOT . '/config/config.php';

$page_title = '404 - Page Not Found';
$page_description = 'The page you are looking for does not exist';
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="error-page">
    <div class="error-container" data-aos="fade-up">
        <div class="error-content">
            <div class="error-code">
                <span>4</span>
                <span class="error-zero">0</span>
                <span>4</span>
            </div>
            <h1>Page Not Found</h1>
            <p class="error-message">
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>
            <div class="error-actions">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-primary">
                    <i class="bi bi-house"></i>
                    Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Go Back
                </a>
            </div>
        </div>
        
        <div class="error-illustration">
            <i class="bi bi-exclamation-triangle"></i>
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
        display: flex;
        max-width: 900px;
        width: 100%;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }
    
    .error-content {
        flex: 1;
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .error-code {
        font-size: 120px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 30px;
        color: #667eea;
    }
    
    .error-zero {
        color: #764ba2;
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    .error-content h1 {
        font-size: 36px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
    }
    
    .error-message {
        font-size: 18px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 40px;
    }
    
    .error-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .error-actions .btn {
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .error-actions .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .error-actions .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .error-illustration {
        flex: 1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .error-illustration i {
        font-size: 200px;
        opacity: 0.3;
    }
    
    @media (max-width: 768px) {
        .error-container {
            flex-direction: column;
        }
        
        .error-illustration {
            display: none;
        }
        
        .error-content {
            padding: 40px 30px;
        }
        
        .error-code {
            font-size: 80px;
        }
        
        .error-content h1 {
            font-size: 28px;
        }
        
        .error-message {
            font-size: 16px;
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
