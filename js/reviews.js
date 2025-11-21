/**
 * FARUNOVA Reviews & Ratings Management
 * Handles review operations via AJAX
 * 
 * @version 1.0
 */

class Reviews {
    constructor() {
        this.init();
    }
    
    init() {
        this.attachEventListeners();
    }
    
    attachEventListeners() {
        // Submit review form
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', (e) => this.handleSubmitReview(e));
        }
        
        // Rating input changes
        document.addEventListener('change', (e) => {
            if (e.target.closest('[name="rating"]')) {
                this.handleRatingChange(e);
            }
        });
        
        // Delete review
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-delete-review]')) {
                this.handleDeleteReview(e);
            }
        });
    }
    
    async handleSubmitReview(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await apiRequest('/api/reviews.php?action=submit', 'POST', formData);
            
            if (response.success) {
                showNotification('Review submitted successfully!', 'success');
                form.reset();
                
                // Reload reviews section
                this.reloadReviews();
                
                // Reset rating display
                this.updateRatingDisplay(0);
            } else {
                showNotification(response.message || 'Failed to submit review', 'error');
            }
        } catch (error) {
            console.error('Submit review error:', error);
        }
    }
    
    handleRatingChange(e) {
        const rating = parseInt(e.target.value) || 0;
        this.updateRatingDisplay(rating);
    }
    
    updateRatingDisplay(rating) {
        const stars = document.querySelectorAll('[data-rating-display] .bi');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('bi-star');
                star.classList.add('bi-star-fill');
            } else {
                star.classList.remove('bi-star-fill');
                star.classList.add('bi-star');
            }
        });
    }
    
    async handleDeleteReview(e) {
        e.preventDefault();
        
        const btn = e.target.closest('[data-delete-review]');
        const reviewId = btn.dataset.deleteReview;
        
        if (!reviewId) return;
        
        const confirmed = await confirmAction('Delete this review? This cannot be undone.');
        if (!confirmed) return;
        
        try {
            const response = await apiRequest('/api/reviews.php?action=delete', 'POST', {
                reviewId: reviewId
            });
            
            if (response.success) {
                showNotification('Review deleted', 'success');
                
                const reviewItem = btn.closest('[data-review-item]');
                if (reviewItem) {
                    reviewItem.remove();
                }
                
                // Reload reviews to update count
                this.reloadReviews();
            } else {
                showNotification(response.message || 'Failed to delete review', 'error');
            }
        } catch (error) {
            console.error('Delete review error:', error);
        }
    }
    
    async reloadReviews() {
        const productId = document.querySelector('[data-product-id]')?.dataset.productId;
        
        if (!productId) return;
        
        try {
            const response = await apiRequest(`/api/reviews.php?action=get&productId=${productId}&limit=10`, 'GET', null, false);
            
            if (response.reviews) {
                const reviewsContainer = document.querySelector('[data-reviews-container]');
                if (reviewsContainer) {
                    reviewsContainer.innerHTML = this.renderReviews(response.reviews);
                }
                
                // Update average rating if available
                if (response.averageRating !== undefined) {
                    const avgEl = document.querySelector('[data-average-rating]');
                    if (avgEl) {
                        avgEl.textContent = response.averageRating.toFixed(1);
                    }
                }
            }
        } catch (error) {
            console.log('Error reloading reviews');
        }
    }
    
    renderReviews(reviews) {
        if (!reviews || reviews.length === 0) {
            return '<p class="text-muted">No reviews yet. Be the first to review!</p>';
        }
        
        return reviews.map(review => `
            <div class="review-item mb-3 pb-3 border-bottom" data-review-item>
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${this.escapeHtml(review.title)}</h6>
                        <p class="text-muted small mb-2">
                            By <strong>${this.escapeHtml(review.username)}</strong> 
                            <span class="ms-2">${this.formatDate(review.createdAt)}</span>
                        </p>
                        <div class="mb-2">
                            ${this.renderStars(review.rating)}
                        </div>
                        <p>${this.escapeHtml(review.comment)}</p>
                    </div>
                    ${review.canDelete ? `
                        <button class="btn btn-sm btn-danger" data-delete-review="${review.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
    
    renderStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="bi bi-star${i <= rating ? '-fill' : ''} text-warning"></i>`;
        }
        return stars;
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (days > 30) {
            return date.toLocaleDateString();
        } else if (days > 0) {
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else if (hours > 0) {
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (minutes > 0) {
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else {
            return 'Just now';
        }
    }
}

// Initialize reviews on page load
document.addEventListener('DOMContentLoaded', () => {
    new Reviews();
});
