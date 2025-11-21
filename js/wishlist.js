/**
 * FARUNOVA Wishlist Management
 * Handles wishlist operations via AJAX
 * 
 * @version 1.0
 */

class Wishlist {
    constructor() {
        this.init();
    }
    
    init() {
        this.attachEventListeners();
    }
    
    attachEventListeners() {
        // Wishlist buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-wishlist-btn]')) {
                this.handleWishlistToggle(e);
            }
            
            if (e.target.closest('[data-remove-wishlist]')) {
                this.handleRemoveFromWishlist(e);
            }
            
            if (e.target.closest('[data-add-to-cart-wishlist]')) {
                this.handleAddToCartFromWishlist(e);
            }
        });
    }
    
    async handleWishlistToggle(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-wishlist-btn]');
        const productId = btn.dataset.wishlistBtn;
        
        if (!productId) return;
        
        try {
            const isInWishlist = btn.classList.contains('in-wishlist');
            
            if (isInWishlist) {
                // Remove from wishlist
                const response = await apiRequest(`/api/wishlist.php?action=remove`, 'POST', {
                    productId: productId
                });
                
                if (response.success) {
                    btn.classList.remove('in-wishlist');
                    btn.innerHTML = '<i class="bi bi-heart"></i> Add to Wishlist';
                    showNotification('Removed from wishlist', 'success');
                } else {
                    showNotification(response.message || 'Failed to remove from wishlist', 'error');
                }
            } else {
                // Add to wishlist
                const response = await apiRequest(`/api/wishlist.php?action=add`, 'POST', {
                    productId: productId
                });
                
                if (response.success) {
                    btn.classList.add('in-wishlist');
                    btn.innerHTML = '<i class="bi bi-heart-fill"></i> In Wishlist';
                    showNotification('Added to wishlist', 'success');
                } else {
                    showNotification(response.message || 'Failed to add to wishlist', 'error');
                }
            }
        } catch (error) {
            console.error('Wishlist toggle error:', error);
        }
    }
    
    async handleRemoveFromWishlist(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-remove-wishlist]');
        const wishlistId = btn.dataset.removeWishlist;
        
        if (!wishlistId) return;
        
        const confirmed = await confirmAction('Remove from wishlist?');
        if (!confirmed) return;
        
        try {
            const response = await apiRequest(`/api/wishlist.php?action=remove`, 'POST', {
                wishlistId: wishlistId
            });
            
            if (response.success) {
                showNotification('Removed from wishlist', 'success');
                
                const row = btn.closest('.card') || btn.closest('.wishlist-item');
                if (row) {
                    row.remove();
                }
                
                // Check if wishlist is empty
                const items = document.querySelectorAll('[data-wishlist-item]');
                if (items.length === 0) {
                    const container = document.querySelector('[data-wishlist-container]');
                    if (container) {
                        container.innerHTML = `
                            <div class="alert alert-info">
                                Your wishlist is empty. <a href="products.php">Explore products</a>
                            </div>
                        `;
                    }
                }
            } else {
                showNotification(response.message || 'Failed to remove', 'error');
            }
        } catch (error) {
            console.error('Remove from wishlist error:', error);
        }
    }
    
    async handleAddToCartFromWishlist(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-add-to-cart-wishlist]');
        const productId = btn.dataset.addToCartWishlist;
        
        if (!productId) return;
        
        try {
            const response = await apiRequest(`/api/cart.php?action=add`, 'POST', {
                productId: productId,
                quantity: 1
            });
            
            if (response.success) {
                showNotification('Added to cart', 'success');
                updateCartCount();
                
                // Change button text
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-check"></i> Added to Cart';
                
                setTimeout(() => {
                    btn.disabled = false;
                }, 1000);
            } else {
                showNotification(response.message || 'Failed to add to cart', 'error');
            }
        } catch (error) {
            console.error('Add to cart from wishlist error:', error);
        }
    }
}

// Initialize wishlist on page load
document.addEventListener('DOMContentLoaded', () => {
    new Wishlist();
});
