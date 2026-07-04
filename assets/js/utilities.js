/**
 * Hotel & Resort Management System
 * JavaScript Utilities
 * 
 * Toast, Modal, Loading Spinner, Confirm Dialog, LocalStorage Manager
 */

// ============================================
// LOCAL STORAGE MANAGER
// ============================================

document.addEventListener("DOMContentLoaded", function () {

    const btn = document.querySelector(".user-dropdown-btn");
    const menu = document.querySelector(".user-dropdown");

    if (!btn || !menu) return;

    // শুরুতে hide
    menu.style.display = "none";

    btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (menu.style.display === "none") {
            menu.style.display = "block";
        } else {
            menu.style.display = "none";
        }
    });

    // বাইরে ক্লিক করলে বন্ধ হবে
    document.addEventListener("click", function () {
        menu.style.display = "none";
    });

    // dropdown-এর ভিতরে ক্লিক করলে বন্ধ হবে না
    menu.addEventListener("click", function (e) {
        e.stopPropagation();
    });

});

document.addEventListener("DOMContentLoaded", function () {

    const btn = document.querySelector(".notification-btn");
    const menu = document.querySelector(".notification-dropdown");

    if (!btn || !menu) return;

    menu.style.display = "none";

    btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        menu.style.display =
            (menu.style.display === "none" || menu.style.display === "")
                ? "block"
                : "none";
    });

    menu.addEventListener("click", function (e) {
        e.stopPropagation();
    });

    document.addEventListener("click", function () {
        menu.style.display = "none";
    });

});

const Storage = {
    /**
     * Set item in localStorage
     * @param {string} key - Storage key
     * @param {any} value - Value to store
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Storage set error:', e);
        }
    },

    /**
     * Get item from localStorage
     * @param {string} key - Storage key
     * @param {any} defaultValue - Default value if key doesn't exist
     * @returns {any} Stored value or default
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Storage get error:', e);
            return defaultValue;
        }
    },

    /**
     * Remove item from localStorage
     * @param {string} key - Storage key
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('Storage remove error:', e);
        }
    },

    /**
     * Clear all localStorage
     */
    clear() {
        try {
            localStorage.clear();
        } catch (e) {
            console.error('Storage clear error:', e);
        }
    },

    /**
     * Check if key exists
     * @param {string} key - Storage key
     * @returns {boolean}
     */
    has(key) {
        return localStorage.getItem(key) !== null;
    }
};

// ============================================
// TOAST NOTIFICATIONS
// ============================================
const Toast = {
    container: null,

    /**
     * Initialize toast container
     */
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds
     */
    show(message, type = 'success', duration = 5000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const iconMap = {
            success: 'bi-check-circle',
            error: 'bi-x-circle',
            warning: 'bi-exclamation-triangle',
            info: 'bi-info-circle'
        };

        toast.innerHTML = `
            <div class="toast-header">
                <i class="bi ${iconMap[type]} me-2"></i>
                <span class="toast-title">${type.charAt(0).toUpperCase() + type.slice(1)}</span>
                <button class="toast-close">&times;</button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

        this.container.appendChild(toast);

        // Auto remove after duration
        const timeout = setTimeout(() => {
            this.remove(toast);
        }, duration);

        // Close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            clearTimeout(timeout);
            this.remove(toast);
        });
    },

    /**
     * Remove toast
     * @param {HTMLElement} toast - Toast element
     */
    remove(toast) {
        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },

    /**
     * Show success toast
     */
    success(message, duration) {
        this.show(message, 'success', duration);
    },

    /**
     * Show error toast
     */
    error(message, duration) {
        this.show(message, 'error', duration);
    },

    /**
     * Show warning toast
     */
    warning(message, duration) {
        this.show(message, 'warning', duration);
    },

    /**
     * Show info toast
     */
    info(message, duration) {
        this.show(message, 'info', duration);
    }
};

// ============================================
// MODAL
// ============================================
const Modal = {
    backdrop: null,
    currentModal: null,

    /**
     * Show modal
     * @param {string} title - Modal title
     * @param {string} content - Modal content HTML
     * @param {object} options - Modal options
     */
    show(title, content, options = {}) {
        const {
            size = '',
            showClose = true,
            footer = null,
            onShow = null,
            onHide = null
        } = options;

        // Create backdrop
        if (!this.backdrop) {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'modal-backdrop';
            document.body.appendChild(this.backdrop);
        }

        // Create modal
        const modal = document.createElement('div');
        modal.className = `modal ${size}`;
        
        let footerHTML = '';
        if (footer) {
            footerHTML = `
                <div class="modal-footer">
                    ${footer}
                </div>
            `;
        }

        modal.innerHTML = `
            <div class="modal-header">
                <h5 class="modal-title">${title}</h5>
                ${showClose ? '<button class="modal-close">&times;</button>' : ''}
            </div>
            <div class="modal-body">
                ${content}
            </div>
            ${footerHTML}
        `;

        this.backdrop.appendChild(modal);
        this.currentModal = modal;

        // Show modal
        setTimeout(() => {
            this.backdrop.classList.add('active');
            if (onShow) onShow();
        }, 10);

        // Close button handler
        if (showClose) {
            const closeBtn = modal.querySelector('.modal-close');
            closeBtn.addEventListener('click', () => this.hide());
        }

        // Backdrop click handler
        this.backdrop.addEventListener('click', (e) => {
            if (e.target === this.backdrop) {
                this.hide();
            }
        });

        // Store onHide callback
        this.onHideCallback = onHide;
    },

    /**
     * Hide modal
     */
    hide() {
        if (this.backdrop && this.currentModal) {
            this.backdrop.classList.remove('active');
            
            setTimeout(() => {
                if (this.currentModal && this.currentModal.parentNode) {
                    this.currentModal.parentNode.removeChild(this.currentModal);
                }
                this.currentModal = null;
                if (this.onHideCallback) {
                    this.onHideCallback();
                    this.onHideCallback = null;
                }
            }, 300);
        }
    },

    /**
     * Show confirm dialog
     * @param {string} message - Confirmation message
     * @param {string} title - Dialog title
     * @returns {Promise<boolean>}
     */
    confirm(message, title = 'Are you sure?') {
        return new Promise((resolve) => {
            this.show(title, message, {
                size: '',
                showClose: true,
                footer: `
                    <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                    <button class="btn btn-danger" data-action="confirm">Confirm</button>
                `,
                onHide: () => resolve(false)
            });

            // Add confirm class
            this.currentModal.classList.add('confirm-modal');

            // Button handlers
            const buttons = this.currentModal.querySelectorAll('button[data-action]');
            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const action = btn.dataset.action;
                    this.hide();
                    resolve(action === 'confirm');
                });
            });
        });
    },

    /**
     * Show alert dialog
     * @param {string} message - Alert message
     * @param {string} title - Dialog title
     * @returns {Promise<void>}
     */
    alert(message, title = 'Alert') {
        return new Promise((resolve) => {
            this.show(title, message, {
                size: '',
                showClose: true,
                footer: `
                    <button class="btn btn-primary" data-action="ok">OK</button>
                `,
                onHide: () => resolve()
            });

            // Button handler
            const okBtn = this.currentModal.querySelector('button[data-action="ok"]');
            okBtn.addEventListener('click', () => {
                this.hide();
            });
        });
    }
};

// ============================================
// LOADING SPINNER
// ============================================
const Spinner = {
    overlay: null,

    /**
     * Show loading spinner
     * @param {string} message - Optional loading message
     */
    show(message = null) {
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.className = 'spinner-overlay';
            this.overlay.innerHTML = `
                <div class="spinner spinner-lg">
                    <div class="spinner-border"></div>
                </div>
                ${message ? `<p class="mt-3 text-center">${message}</p>` : ''}
            `;
            document.body.appendChild(this.overlay);
        }
        this.overlay.style.display = 'flex';
    },

    /**
     * Hide loading spinner
     */
    hide() {
        if (this.overlay) {
            this.overlay.style.display = 'none';
        }
    },

    /**
     * Toggle loading spinner
     * @param {boolean} show - Show or hide
     * @param {string} message - Optional loading message
     */
    toggle(show, message = null) {
        if (show) {
            this.show(message);
        } else {
            this.hide();
        }
    }
};

// ============================================
// CONFIRM DIALOG
// ============================================
const Confirm = {
    /**
     * Show delete confirmation
     * @param {string} message - Confirmation message
     * @returns {Promise<boolean>}
     */
    delete(message = 'Are you sure you want to delete this item?') {
        return Modal.confirm(message, 'Delete Confirmation');
    },

    /**
     * Show save confirmation
     * @param {string} message - Confirmation message
     * @returns {Promise<boolean>}
     */
    save(message = 'Are you sure you want to save these changes?') {
        return Modal.confirm(message, 'Save Confirmation');
    },

    /**
     * Show custom confirmation
     * @param {string} message - Confirmation message
     * @param {string} title - Dialog title
     * @returns {Promise<boolean>}
     */
    custom(message, title = 'Confirmation') {
        return Modal.confirm(message, title);
    }
};

// ============================================
// IMAGE PREVIEW
// ============================================
const ImagePreview = {
    /**
     * Show image preview
     * @param {HTMLInputElement} input - File input element
     * @param {string} previewContainerId - Preview container ID
     */
    show(input, previewContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        if (!previewContainer) return;

        const file = input.files[0];
        if (!file) return;

        // Check if file is an image
        if (!file.type.startsWith('image/')) {
            Toast.error('Please select an image file');
            return;
        }

        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            Toast.error('Image size must be less than 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewContainer.innerHTML = `
                <div class="image-preview active position-relative">
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="image-preview-remove" data-input-id="${input.id}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;

            // Remove button handler
            const removeBtn = previewContainer.querySelector('.image-preview-remove');
            removeBtn.addEventListener('click', () => {
                this.remove(input, previewContainerId);
            });
        };
        reader.readAsDataURL(file);
    },

    /**
     * Remove image preview
     * @param {HTMLInputElement} input - File input element
     * @param {string} previewContainerId - Preview container ID
     */
    remove(input, previewContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        if (!previewContainer) return;

        input.value = '';
        previewContainer.innerHTML = '';
    }
};

// ============================================
// DARK MODE
// ============================================
const DarkMode = {
    /**
     * Initialize dark mode
     */
    init() {
        const savedTheme = Storage.get('theme', 'light');
        this.set(savedTheme);
    },

    /**
     * Set theme
     * @param {string} theme - Theme (light or dark)
     */
    set(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        Storage.set('theme', theme);
        
        const icon = document.querySelector('#darkModeToggle i');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        }
    },

    /**
     * Toggle dark mode
     */
    toggle() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.set(newTheme);
    },

    /**
     * Get current theme
     * @returns {string}
     */
    get() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }
};

// ============================================
// FULLSCREEN
// ============================================
const Fullscreen = {
    /**
     * Toggle fullscreen
     */
    toggle() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error('Fullscreen error:', err);
            });
        } else {
            document.exitFullscreen();
        }
    },

    /**
     * Check if fullscreen is active
     * @returns {boolean}
     */
    isActive() {
        return !!document.fullscreenElement;
    }
};

// ============================================
// DATE & TIME
// ============================================
const DateTime = {
    interval: null,

    /**
     * Initialize date/time display
     */
    init() {
        this.update();
        this.interval = setInterval(() => this.update(), 1000);
    },

    /**
     * Update date/time display
     */
    update() {
        const now = new Date();
        const dateEl = document.getElementById('currentDate');
        const timeEl = document.getElementById('currentTime');

        if (dateEl) {
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            dateEl.textContent = now.toLocaleDateString('en-US', options);
        }

        if (timeEl) {
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            timeEl.textContent = now.toLocaleTimeString('en-US', options);
        }
    },

    /**
     * Stop date/time updates
     */
    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
};

// ============================================
// SIDEBAR STATE
// ============================================
const SidebarState = {
    /**
     * Initialize sidebar state
     */
    init() {
        const collapsed = Storage.get('sidebarCollapsed', false);
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');

        if (collapsed && sidebar && mainContent) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }

        // Initialize submenu states
        const submenuStates = Storage.get('submenuStates', {});
        document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
            const submenuId = item.querySelector('.submenu-toggle')?.getAttribute('href') || item.dataset.submenu;
            if (submenuStates[submenuId]) {
                item.classList.add('open');
            }
        });
    },

    /**
     * Toggle sidebar collapse
     */
    toggleCollapse() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (sidebar && mainContent) {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            Storage.set('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    },

    /**
     * Save submenu state
     * @param {string} submenuId - Submenu identifier
     * @param {boolean} isOpen - Is submenu open
     */
    saveSubmenuState(submenuId, isOpen) {
        const submenuStates = Storage.get('submenuStates', {});
        submenuStates[submenuId] = isOpen;
        Storage.set('submenuStates', submenuStates);
    }
};

// ============================================
// TABLE UTILITIES
// ============================================
const Table = {
    /**
     * Initialize table with search
     * @param {string} tableId - Table ID
     * @param {string} searchId - Search input ID
     */
    initSearch(tableId, searchId) {
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchId);

        if (!table || !searchInput) return;

        searchInput.addEventListener('keyup', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    },

    /**
     * Export table to CSV
     * @param {string} tableId - Table ID
     * @param {string} filename - Output filename
     */
    exportToCSV(tableId, filename = 'export.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        let csv = [];

        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            let rowData = [];

            cols.forEach(col => {
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
    },

    /**
     * Print table
     * @param {string} tableId - Table ID
     */
    print(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Table</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                    </style>
                </head>
                <body>
                    ${table.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
};

// ============================================
// FORM VALIDATION
// ============================================
const FormValidation = {
    /**
     * Validate form
     * @param {HTMLFormElement} form - Form element
     * @returns {boolean}
     */
    validate(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
                
                // Remove invalid class on input
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                }, { once: true });
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });

        return isValid;
    },

    /**
     * Validate email
     * @param {string} email - Email address
     * @returns {boolean}
     */
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    /**
     * Validate phone number
     * @param {string} phone - Phone number
     * @returns {boolean}
     */
    validatePhone(phone) {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }
};

// ============================================
// DEBOUNCE UTILITY
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
// THROTTLE UTILITY
// ============================================
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ============================================
// COPY TO CLIPBOARD
// ============================================
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        Toast.success('Copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy:', err);
        Toast.error('Failed to copy to clipboard');
    }
}

// ============================================
// DOWNLOAD FILE
// ============================================
function downloadFile(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// ============================================
// FORMAT UTILITIES
// ============================================
const Format = {
    /**
     * Format currency
     * @param {number} amount - Amount to format
     * @param {string} currency - Currency code
     * @returns {string}
     */
    currency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Format number with commas
     * @param {number} num - Number to format
     * @returns {string}
     */
    number(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /**
     * Format date
     * @param {Date|string} date - Date to format
     * @param {string} format - Date format
     * @returns {string}
     */
    date(date, format = 'YYYY-MM-DD') {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day);
    },

    /**
     * Format bytes to human readable
     * @param {number} bytes - Bytes to format
     * @returns {string}
     */
    bytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
};

// ============================================
// DOM UTILITIES
// ============================================
const DOM = {
    /**
     * Get element by ID
     * @param {string} id - Element ID
     * @returns {HTMLElement}
     */
    get(id) {
        return document.getElementById(id);
    },

    /**
     * Get elements by selector
     * @param {string} selector - CSS selector
     * @param {HTMLElement} parent - Parent element
     * @returns {NodeList}
     */
    query(selector, parent = document) {
        return parent.querySelectorAll(selector);
    },

    /**
     * Get first element by selector
     * @param {string} selector - CSS selector
     * @param {HTMLElement} parent - Parent element
     * @returns {HTMLElement}
     */
    queryFirst(selector, parent = document) {
        return parent.querySelector(selector);
    },

    /**
     * Add class to element
     * @param {HTMLElement} element - Target element
     * @param {string} className - Class name
     */
    addClass(element, className) {
        if (element) element.classList.add(className);
    },

    /**
     * Remove class from element
     * @param {HTMLElement} element - Target element
     * @param {string} className - Class name
     */
    removeClass(element, className) {
        if (element) element.classList.remove(className);
    },

    /**
     * Toggle class on element
     * @param {HTMLElement} element - Target element
     * @param {string} className - Class name
     */
    toggleClass(element, className) {
        if (element) element.classList.toggle(className);
    },

    /**
     * Check if element has class
     * @param {HTMLElement} element - Target element
     * @param {string} className - Class name
     * @returns {boolean}
     */
    hasClass(element, className) {
        return element ? element.classList.contains(className) : false;
    },

    /**
     * Create element
     * @param {string} tag - Tag name
     * @param {object} attributes - Attributes object
     * @param {string} content - Inner content
     * @returns {HTMLElement}
     */
    create(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        
        Object.keys(attributes).forEach(key => {
            if (key === 'className') {
                element.className = attributes[key];
            } else {
                element.setAttribute(key, attributes[key]);
            }
        });

        if (content) {
            element.innerHTML = content;
        }

        return element;
    }
};

// ============================================
// URL UTILITIES
// ============================================
const URL = {
    /**
     * Get URL parameter
     * @param {string} name - Parameter name
     * @returns {string|null}
     */
    getParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },

    /**
     * Get all URL parameters
     * @returns {object}
     */
    getParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = {};
        for (const [key, value] of urlParams) {
            params[key] = value;
        }
        return params;
    },

    /**
     * Set URL parameter
     * @param {string} name - Parameter name
     * @param {string} value - Parameter value
     */
    setParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    },

    /**
     * Remove URL parameter
     * @param {string} name - Parameter name
     */
    removeParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.pushState({}, '', url);
    }
};

// ============================================
// INITIALIZE ON DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Initialize dark mode
    DarkMode.init();

    // Initialize date/time
    DateTime.init();

    // Initialize sidebar state
    SidebarState.init();
});
