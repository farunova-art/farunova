/**
 * FARUNOVA Common AJAX Utilities
 * Shared functions for API communication and UI interactions
 * 
 * @version 1.0
 */

// Show notification toast
function showNotification(message, type = 'info', duration = 4000) {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const html = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);
    
    if (duration > 0) {
        setTimeout(() => {
            container.remove();
        }, duration);
    }
    
    return container.querySelector('.alert');
}

// Make API request
async function apiRequest(endpoint, method = 'GET', data = null, showErrors = true) {
    try {
        const options = {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Handle FormData vs JSON
        if (data) {
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
        }
        
        const response = await fetch(endpoint, options);
        const result = await response.json();
        
        if (!response.ok) {
            if (showErrors) {
                showNotification(result.message || 'Request failed', 'error');
            }
            throw new Error(result.message || 'Request failed');
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        if (showErrors) {
            showNotification('An error occurred: ' + error.message, 'error');
        }
        throw error;
    }
}

// Show confirmation dialog
function confirmAction(message) {
    return new Promise((resolve) => {
        const html = `
            <div class="modal fade" tabindex="-1" style="display: none;" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const container = document.createElement('div');
        container.innerHTML = html;
        document.body.appendChild(container);
        
        const modal = new bootstrap.Modal(container.querySelector('.modal'));
        const confirmBtn = container.querySelector('.modal-footer .btn-danger');
        
        confirmBtn.addEventListener('click', () => {
            modal.hide();
            container.remove();
            resolve(true);
        });
        
        container.querySelector('.modal').addEventListener('hidden.bs.modal', () => {
            container.remove();
            resolve(false);
        });
        
        modal.show();
    });
}

// Update cart count in UI
async function updateCartCount() {
    try {
        const response = await apiRequest('/api/cart.php?action=count', 'GET', null, false);
        const cartCount = document.getElementById('cartCount');
        
        if (cartCount) {
            cartCount.textContent = response.count || 0;
            cartCount.style.display = response.count > 0 ? 'inline-block' : 'none';
        }
    } catch (error) {
        console.log('Cart count update skipped');
    }
}

// Format price for display
function formatPrice(price) {
    return 'KES ' + parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Debounce function for search
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
});

// Handle logout
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
