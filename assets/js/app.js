/**
 * SafeKeep Main JavaScript
 * Common functionality and enhancements
 */

// Global SafeKeep object
const SafeKeep = {
    // Configuration
    config: {
        apiBaseUrl: '/api',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        maxFileSize: 5 * 1024 * 1024, // 5MB
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp']
    },

    // Initialize application
    init() {
        this.setupEventListeners();
        this.initializeTooltips();
        this.initializePopovers();
        this.setupFormValidation();
        this.setupFileUploads();
        this.setupSearchFilters();
        console.log('SafeKeep initialized successfully');
    },

    // Event listeners
    setupEventListeners() {
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Loading state for forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    SafeKeep.setLoadingState(submitBtn, true);
                }
            });
        });

        // Confirmation dialogs for delete actions
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    },

    // Initialize Bootstrap tooltips
    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    // Initialize Bootstrap popovers
    initializePopovers() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    },

    // Enhanced form validation
    setupFormValidation() {
        // Real-time validation
        document.querySelectorAll('.needs-validation input, .needs-validation select, .needs-validation textarea').forEach(input => {
            input.addEventListener('blur', function() {
                this.classList.add('was-validated');
                SafeKeep.validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('was-validated')) {
                    SafeKeep.validateField(this);
                }
            });
        });

        // Password strength indicator
        const passwordInputs = document.querySelectorAll('input[type="password"][data-strength]');
        passwordInputs.forEach(input => {
            const strengthMeter = document.createElement('div');
            strengthMeter.className = 'password-strength mt-1';
            strengthMeter.innerHTML = `
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted">Password strength: <span class="strength-text">-</span></small>
            `;
            input.parentNode.appendChild(strengthMeter);

            input.addEventListener('input', function() {
                SafeKeep.updatePasswordStrength(this, strengthMeter);
            });
        });
    },

    // Validate individual form field
    validateField(field) {
        const isValid = field.checkValidity();
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);

        // Custom validation messages
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!isValid && feedback) {
            if (field.type === 'email' && field.validity.typeMismatch) {
                feedback.textContent = 'Please enter a valid email address.';
            } else if (field.required && field.validity.valueMissing) {
                feedback.textContent = `${field.labels[0]?.textContent || 'This field'} is required.`;
            }
        }

        return isValid;
    },

    // Update password strength indicator
    updatePasswordStrength(input, strengthMeter) {
        const password = input.value;
        let strength = 0;
        const progressBar = strengthMeter.querySelector('.progress-bar');
        const strengthText = strengthMeter.querySelector('.strength-text');

        // Calculate strength
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        // Update UI
        const percentage = (strength / 5) * 100;
        progressBar.style.width = percentage + '%';

        const strengthLevels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthColors = ['bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'bg-success'];

        progressBar.className = `progress-bar ${strengthColors[strength - 1] || 'bg-danger'}`;
        strengthText.textContent = strengthLevels[strength - 1] || 'Very Weak';
    },

    // File upload handling
    setupFileUploads() {
        document.querySelectorAll('.file-upload-area').forEach(area => {
            const input = area.querySelector('input[type="file"]');
            if (!input) return;

            // Drag and drop
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
            });

            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    SafeKeep.handleFileSelection(input, files[0]);
                }
            });

            // Click to upload
            area.addEventListener('click', function(e) {
                if (e.target === input) return;
                input.click();
            });

            // File selection
            input.addEventListener('change', function() {
                if (this.files.length > 0) {
                    SafeKeep.handleFileSelection(this, this.files[0]);
                }
            });
        });
    },

    // Handle file selection
    handleFileSelection(input, file) {
        const uploadArea = input.closest('.file-upload-area');
        const preview = uploadArea.querySelector('.file-preview');
        
        // Validate file
        const validation = this.validateFile(file);
        if (!validation.isValid) {
            this.showAlert(validation.message, 'danger');
            input.value = '';
            return;
        }

        // Show preview
        if (preview) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                        <p class="mt-2 mb-0"><strong>${file.name}</strong> (${SafeKeep.formatFileSize(file.size)})</p>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-file me-2"></i>
                        <strong>${file.name}</strong> (${SafeKeep.formatFileSize(file.size)})
                    </div>
                `;
            }
        }
    },

    // Validate uploaded file
    validateFile(file) {
        // Check file size
        if (file.size > this.config.maxFileSize) {
            return {
                isValid: false,
                message: `File size must be less than ${this.formatFileSize(this.config.maxFileSize)}.`
            };
        }

        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedExtensions.includes(extension)) {
            return {
                isValid: false,
                message: `File type not allowed. Allowed types: ${this.config.allowedExtensions.join(', ')}`
            };
        }

        return { isValid: true, message: '' };
    },

    // Format file size
    formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${Math.round(size * 10) / 10} ${units[unitIndex]}`;
    },

    // Search filters
    setupSearchFilters() {
        const searchForm = document.getElementById('search-form');
        if (!searchForm) return;

        // Auto-submit on filter change
        searchForm.querySelectorAll('select, input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', function() {
                searchForm.submit();
            });
        });

        // Clear filters
        const clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchForm.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });
                searchForm.querySelectorAll('input[type="text"]').forEach(input => {
                    input.value = '';
                });
                searchForm.submit();
            });
        }
    },

    // Set loading state for buttons
    setLoadingState(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Loading...
            `;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    },

    // Show alert message
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container') || document.querySelector('.container');
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        alertContainer.insertBefore(alert, alertContainer.firstChild);
        
        // Auto-hide after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        }
    },

    // AJAX helper
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.config.csrfToken
            }
        };

        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            this.showAlert('An error occurred. Please try again.', 'danger');
            throw error;
        }
    },

    // Debounce function for search inputs
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Utility: Copy to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showAlert('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy text: ', err);
            this.showAlert('Failed to copy text', 'danger');
        }
    },

    // Utility: Format date
    formatDate(dateString, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        return new Date(dateString).toLocaleDateString('en-US', { ...defaultOptions, ...options });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    SafeKeep.init();
});

// Export for use in other scripts
window.SafeKeep = SafeKeep;