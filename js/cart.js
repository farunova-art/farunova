/**
 * FARUNOVA Cart Management
 * Handles shopping cart operations via AJAX
 * 
 * @version 1.0
 */

class Cart {
    constructor() {
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        this.updateTotal();
    }
    
    attachEventListeners() {
        // Remove from cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-remove-from-cart]')) {
                this.handleRemoveFromCart(e);
            }
            
            if (e.target.closest('[data-clear-cart]')) {
                this.handleClearCart(e);
            }
        });
        
        // Quantity inputs in cart
        document.addEventListener('change', (e) => {
            if (e.target.closest('[data-quantity-input]')) {
                this.handleQuantityChange(e);
            }
        });
        
        // Update quantity button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-update-quantity]')) {
                this.handleUpdateQuantity(e);
            }
        });
    }
    
    async handleRemoveFromCart(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-remove-from-cart]');
        const cartId = btn.dataset.removeFromCart;
        
        if (!cartId) return;
        
        const confirmed = await confirmAction('Remove this item from cart?');
        if (!confirmed) return;
        
        try {
            const response = await apiRequest(`/api/cart.php?action=remove`, 'POST', {
                cartItemId: cartId
            });
            
            if (response.success) {
                showNotification('Item removed from cart', 'success');
                
                // Remove from DOM
                const row = btn.closest('tr');
                if (row) {
                    row.remove();
                }
                
                // Update totals
                updateCartCount();
                this.updateTotal();
                this.checkEmptyCart();
            } else {
                showNotification(response.message || 'Failed to remove item', 'error');
            }
        } catch (error) {
            console.error('Remove from cart error:', error);
        }
    }
    
    async handleClearCart(e) {
        e.preventDefault();
        
        const confirmed = await confirmAction('Clear your entire cart? This cannot be undone.');
        if (!confirmed) return;
        
        try {
            const response = await apiRequest(`/api/cart.php?action=clear`, 'POST', {});
            
            if (response.success) {
                showNotification('Cart cleared', 'success');
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                showNotification(response.message || 'Failed to clear cart', 'error');
            }
        } catch (error) {
            console.error('Clear cart error:', error);
        }
    }
    
    async handleUpdateQuantity(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-update-quantity]');
        const cartId = btn.dataset.updateQuantity;
        const input = btn.closest('.input-group')?.querySelector('[data-quantity-input]');
        
        if (!cartId || !input) return;
        
        const quantity = parseInt(input.value) || 1;
        
        if (quantity < 1) {
            input.value = 1;
            showNotification('Quantity must be at least 1', 'warning');
            return;
        }
        
        try {
            const response = await apiRequest(`/api/cart.php?action=update`, 'POST', {
                cartItemId: cartId,
                quantity: quantity
            });
            
            if (response.success) {
                showNotification('Cart updated', 'success');
                updateCartCount();
                this.updateTotal();
            } else {
                showNotification(response.message || 'Failed to update', 'error');
                input.value = response.currentQuantity || 1;
            }
        } catch (error) {
            console.error('Update quantity error:', error);
        }
    }
    
    handleQuantityChange(e) {
        const input = e.target;
        const quantity = parseInt(input.value) || 1;
        
        if (quantity < 1) {
            input.value = 1;
            return;
        }
        
        // Auto-update after user stops typing
        const row = input.closest('tr');
        const updateBtn = row?.querySelector('[data-update-quantity]');
        
        if (updateBtn) {
            clearTimeout(this.updateTimeout);
            this.updateTimeout = setTimeout(() => {
                updateBtn.click();
            }, 500);
        }
    }
    
    updateTotal() {
        const subtotalEl = document.getElementById('subtotal');
        const taxEl = document.getElementById('tax');
        const totalEl = document.getElementById('total');
        
        if (!subtotalEl) return;
        
        let subtotal = 0;
        document.querySelectorAll('[data-cart-item]').forEach(row => {
            const priceText = row.querySelector('[data-item-price]')?.textContent || '0';
            const quantityText = row.querySelector('[data-quantity-input]')?.value || '1';
            
            const price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
            const quantity = parseInt(quantityText) || 0;
            
            subtotal += price * quantity;
        });
        
        const tax = subtotal * 0.1;
        const total = subtotal + tax;
        
        subtotalEl.textContent = formatPrice(subtotal);
        if (taxEl) taxEl.textContent = formatPrice(tax);
        if (totalEl) totalEl.textContent = formatPrice(total);
    }
    
    checkEmptyCart() {
        const items = document.querySelectorAll('[data-cart-item]');
        
        if (items.length === 0) {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr><td colspan="100%"><div class="alert alert-info">Your cart is empty. <a href="products.php">Continue shopping</a></div></td></tr>
                `;
            }
        }
    }
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', () => {
    new Cart();
});
