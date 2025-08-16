// Favorites functionality
const HandicraftsFavorites = {
    // Toggle favorite status
    toggleFavorite: async function(productId, buttonElement) {
        try {
            // Check if user is logged in
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            
            if (!isLoggedIn) {
                this.showNotification('Please login to add favorites', 'info');
                setTimeout(() => {
                    window.location.href = `login.html?redirect=product.html?id=${productId}`;
                }, 1500);
                return false;
            }
            
            // Disable button during request
            if (buttonElement) {
                buttonElement.disabled = true;
            }
            
            // Send request to toggle favorite
            const response = await fetch('api/favorites.php?action=toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update button state
                if (buttonElement) {
                    if (data.data.action === 'added') {
                        buttonElement.classList.add('active');
                        this.showNotification('Added to favorites!', 'success');
                    } else {
                        buttonElement.classList.remove('active');
                        this.showNotification('Removed from favorites', 'success');
                    }
                    buttonElement.disabled = false;
                }
                
                // Update favorites count
                this.updateFavoritesCount();
                return true;
            } else {
                this.showNotification(data.message || 'Failed to update favorites', 'error');
                if (buttonElement) buttonElement.disabled = false;
                return false;
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            this.showNotification('Failed to update favorites. Please try again.', 'error');
            if (buttonElement) buttonElement.disabled = false;
            return false;
        }
    },
    
    // Check if product is in favorites
    checkFavoriteStatus: async function(productId, buttonElement) {
        try {
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            if (!isLoggedIn) return false;
            
            const response = await fetch(`api/favorites.php?action=check&product_id=${productId}`);
            const data = await response.json();
            
            if (data.success && buttonElement) {
                if (data.data.is_favorite) {
                    buttonElement.classList.add('active');
                } else {
                    buttonElement.classList.remove('active');
                }
                return data.data.is_favorite;
            }
            return false;
        } catch (error) {
            console.error('Error checking favorite status:', error);
            return false;
        }
    },
    
    // Update favorites count in header
    updateFavoritesCount: async function() {
        try {
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            if (!isLoggedIn) return;
            
            const response = await fetch('api/favorites.php?action=all');
            const data = await response.json();
            
            if (data.success) {
                const favoritesCount = data.data.favorites.length;
                
                // Update favorites icon with count
                const favoritesIcon = document.querySelector('.icon-heart');
                if (favoritesIcon) {
                    // Find existing badge or create new one
                    let badge = document.querySelector('.favorites-badge');
                    if (!badge && favoritesCount > 0) {
                        badge = document.createElement('span');
                        badge.className = 'favorites-badge';
                        badge.style.cssText = `
                            position: absolute;
                            top: -5px;
                            right: -5px;
                            background-color: #ff6b6b;
                            color: white;
                            font-size: 10px;
                            font-weight: bold;
                            width: 16px;
                            height: 16px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        `;
                        favoritesIcon.parentElement.style.position = 'relative';
                        favoritesIcon.parentElement.appendChild(badge);
                    }
                    
                    // Update badge text or remove if count is 0
                    if (badge) {
                        if (favoritesCount > 0) {
                            badge.textContent = favoritesCount;
                        } else {
                            badge.remove();
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error updating favorites count:', error);
        }
    },
    
    // Show notification
    showNotification: function(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 4px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
                box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            `;
            document.body.appendChild(notification);
        }
        
        // Set notification type
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#28a745';
                break;
            case 'error':
                notification.style.backgroundColor = '#dc3545';
                break;
            case 'info':
                notification.style.backgroundColor = '#17a2b8';
                break;
            default:
                notification.style.backgroundColor = '#343a40';
        }
        
        // Set message and show notification
        notification.textContent = message;
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
        
        // Hide after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
        }, 3000);
    }
};

// Initialize favorites functionality
document.addEventListener('DOMContentLoaded', function() {
    // Update favorites count on page load
    HandicraftsFavorites.updateFavoritesCount();
    
    // Add event listeners to favorite buttons on product page
    const favoriteBtn = document.querySelector('.product-favorite-btn');
    if (favoriteBtn) {
        const productId = favoriteBtn.dataset.productId;
        
        // Check initial favorite status
        HandicraftsFavorites.checkFavoriteStatus(productId, favoriteBtn);
        
        // Add click handler
        favoriteBtn.addEventListener('click', function() {
            HandicraftsFavorites.toggleFavorite(productId, this);
        });
    }
});
