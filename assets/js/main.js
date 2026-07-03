/**
 * Hotel & Resort Management System
 * Main JavaScript File
 * 
 * UI interactions and functionality
 */

// Initialize AOS animations
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
});

// ============================================
// Sidebar Toggle & Multi-level Menu
// ============================================
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const topbarToggle = document.getElementById('topbarToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const mainContent = document.querySelector('.main-content');

function toggleSidebar() {
    SidebarState.toggleCollapse();
}

function openSidebar() {
    sidebar.classList.add('active');
    sidebarOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
}

if (topbarToggle) {
    topbarToggle.addEventListener('click', openSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
}

// Close sidebar on window resize if screen is large
window.addEventListener('resize', function() {
    if (window.innerWidth > 992) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Multi-level submenu toggle
document.querySelectorAll('.submenu-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const navItem = this.closest('.nav-item');
        const submenuId = this.getAttribute('href') || navItem.dataset.submenu;
        
        navItem.classList.toggle('open');
        
        // Save state to localStorage
        SidebarState.saveSubmenuState(submenuId, navItem.classList.contains('open'));
    });
});

// Auto-highlight active menu item based on current page
function setActiveNavigation() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link[data-page]');
    
    navLinks.forEach(function(link) {
        const page = link.dataset.page;
        if (currentPath.includes(page)) {
            link.classList.add('active');
            
            // Open parent submenu
            const parentSubmenu = link.closest('.submenu');
            if (parentSubmenu) {
                const parentNavItem = parentSubmenu.closest('.nav-item');
                parentNavItem.classList.add('open');
            }
        } else {
            link.classList.remove('active');
        }
    });
}

setActiveNavigation();

// ============================================
// Dropdowns
// ============================================
document.querySelectorAll('.dropdown-toggle').forEach(function(dropdownToggle) {
    dropdownToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdownMenu = this.nextElementSibling;
        const isOpen = dropdownMenu.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            menu.classList.remove('show');
        });
        
        // Toggle current dropdown
        if (!isOpen) {
            dropdownMenu.classList.add('show');
        }
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
        menu.classList.remove('show');
    });
});

// Prevent dropdown from closing when clicking inside
document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
    menu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

// ============================================
// Search Box
// ============================================
const searchBox = document.querySelector('.search-box input');
const searchToggle = document.getElementById('searchToggle');

if (searchBox) {
    searchBox.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value.trim();
            if (searchTerm) {
                // Implement search functionality
                console.log('Searching for:', searchTerm);
            }
        }
    });
}

if (searchToggle) {
    searchToggle.addEventListener('click', function() {
        const searchContainer = document.querySelector('.search-box');
        if (searchContainer) {
            searchContainer.classList.toggle('active');
            if (!searchContainer.classList.contains('active')) {
                searchBox.value = '';
            }
        }
    });
}

// ============================================
// Fullscreen Toggle
// ============================================
const fullscreenToggle = document.getElementById('fullscreenToggle');
if (fullscreenToggle) {
    fullscreenToggle.addEventListener('click', function() {
        Fullscreen.toggle();
    });
}

// ============================================
// Dark Mode Toggle
// ============================================
const darkModeToggle = document.getElementById('darkModeToggle');
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', function() {
        DarkMode.toggle();
    });
}

// ============================================
// Date & Time Display
// ============================================
DateTime.init();

// ============================================
// Notifications
// ============================================
function markAllAsRead() {
    document.querySelectorAll('.notification-item.unread').forEach(function(item) {
        item.classList.remove('unread');
    });
    
    // Update notification badge
    const badge = document.querySelector('.topbar-btn .badge');
    if (badge) {
        badge.style.display = 'none';
    }
}

const markReadBtn = document.querySelector('.mark-read');
if (markReadBtn) {
    markReadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        markAllAsRead();
    });
}

// ============================================
// Toast Notifications
// ============================================
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Set icon based on type
    let icon = 'bi-check-circle';
    if (type === 'error') icon = 'bi-x-circle';
    if (type === 'warning') icon = 'bi-exclamation-triangle';
    if (type === 'info') icon = 'bi-info-circle';
    
    toast.innerHTML = `
        <div class="toast-header">
            <i class="bi ${icon} me-2"></i>
            <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        delay: 5000
    });
    
    bsToast.show();
    
    // Remove toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// ============================================
// Modal
// ============================================
function showModal(title, content, size = 'md') {
    // Create modal container if it doesn't exist
    let modalContainer = document.querySelector('.modal-container');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.className = 'modal-container';
        document.body.appendChild(modalContainer);
    }
    
    // Create modal element
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-${size}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    `;
    
    modalContainer.appendChild(modal);
    
    // Initialize Bootstrap modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Remove modal element after it's hidden
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

// ============================================
// Loading Spinner
// ============================================
function showSpinner() {
    let spinnerOverlay = document.querySelector('.spinner-overlay');
    if (!spinnerOverlay) {
        spinnerOverlay = document.createElement('div');
        spinnerOverlay.className = 'spinner-overlay';
        spinnerOverlay.innerHTML = `
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(spinnerOverlay);
    }
    spinnerOverlay.style.display = 'flex';
}

function hideSpinner() {
    const spinnerOverlay = document.querySelector('.spinner-overlay');
    if (spinnerOverlay) {
        spinnerOverlay.style.display = 'none';
    }
}

// ============================================
// Form Validation
// ============================================
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            
            // Remove invalid class on input
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            }, { once: true });
        }
    });
    
    return isValid;
}

// ============================================
// Confirm Dialog
// ============================================
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ============================================
// Format Currency
// ============================================
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// ============================================
// Format Date
// ============================================
function formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day);
}

// ============================================
// Debounce Function
// ============================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// Table Search
// ============================================
function searchTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        const match = text.includes(searchTerm.toLowerCase());
        row.style.display = match ? '' : 'none';
    });
}

// ============================================
// Export Table to CSV
// ============================================
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    
    let csv = [];
    
    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        let rowData = [];
        
        cols.forEach(function(col) {
            rowData.push('"' + col.textContent.trim() + '"');
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// ============================================
// Print Page
// ============================================
function printPage() {
    window.print();
}

// ============================================
// Copy to Clipboard
// ============================================
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copied to clipboard!', 'success');
    }).catch(function() {
        showToast('Failed to copy', 'error');
    });
}

// ============================================
// Initialize tooltips
// ============================================
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(tooltip) {
    new bootstrap.Tooltip(tooltip);
});

// ============================================
// Initialize popovers
// ============================================
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(popover) {
    new bootstrap.Popover(popover);
});


// ============================================
// Smooth scroll for anchor links
// ============================================
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href');
        if (targetId !== '#') {
            e.preventDefault();
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ============================================
// Keyboard shortcuts
// ============================================
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to prevent default save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        showToast('Auto-saved', 'success');
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(function(modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }
});

// ============================================
// Lazy load images
// ============================================
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(function(img) {
        imageObserver.observe(img);
    });
}

// ============================================
// Console welcome message
// ============================================
console.log('%c Hotel & Resort Management System ', 'background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px; font-size: 16px; font-weight: bold;');
console.log('%c Version 1.0.0 ', 'color: #667eea; font-size: 12px;');
