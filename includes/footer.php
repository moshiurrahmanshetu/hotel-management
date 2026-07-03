<?php
/**
 * Hotel & Resort Management System
 * Footer Include File
 * 
 * Reusable footer with copyright and scripts
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
?>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <span>Version <?php echo APP_VERSION; ?></span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS Animation JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>

<?php if (isset($extra_js)): ?>
    <?php echo $extra_js; ?>
<?php endif; ?>

</body>
</html>
