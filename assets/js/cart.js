// Cart functionality
const HandicraftsCart = {
    // Add item to cart
    addToCart: async function(productId, quantity = 1) {
        try {
            // Check if user is logged in
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            
            if (!isLoggedIn) {
                this.showNotification('Please login to add items to your cart', 'info');
                setTimeout(() => {
                    window.location.href = `login.html?redirect=product.html?id=${productId}`;
                }, 1500);
                return false;
            }
            
            // Show loading state
            const addToCartBtn = document.querySelector(`.add-to-cart-btn[data-product-id="${productId}"]`) || 
                                document.getElementById('add-to-cart-btn');
            
            let originalText = 'Add to Cart';
            if (addToCartBtn) {
                originalText = addToCartBtn.textContent;
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Adding to Cart...';
            }
            
            // Send request to add item to cart
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const data = await response.json();
            
            console.log('Cart API response:', data); // Debug log
            console.log('Success check:', { 
                'data.success': data.success, 
                'data.status': data.status,
                'condition': (data.success === true || data.status === 'success')
            });
            
            // FIXED: Check both success indicators and handle properly
            if (data.success === true || data.status === 'success') {
                // Success case
                this.showNotification('Item added to cart!', 'success');
                this.updateCartCount();
                
                // Reset button state with success indicator
                if (addToCartBtn) {
                    addToCartBtn.disabled = false;
                    addToCartBtn.textContent = 'Added to Cart ✓';
                    addToCartBtn.style.backgroundColor = '#28a745'; // Green success color
                    
                    setTimeout(() => {
                        addToCartBtn.textContent = originalText;
                        addToCartBtn.style.backgroundColor = ''; // Reset color
                    }, 2000);
                }
                
                return true;
            } else {
                // Error case
                console.error('Add to cart failed:', data);
                this.showNotification(data.message || 'Failed to add item to cart', 'error');
                
                // Reset button state
                if (addToCartBtn) {
                    addToCartBtn.disabled = false;
                    addToCartBtn.textContent = originalText;
                }
                
                return false;
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            
            // Check if this is an authentication error
            if (error.name === 'SyntaxError' || (error.message && error.message.includes('Unexpected token'))) {
                // Likely a redirect to login page
                this.showNotification('Please login to add items to your cart', 'info');
                setTimeout(() => {
                    window.location.href = `login.html?redirect=handicrafts.html`;
                }, 1500);
            } else {
                this.showNotification('Failed to add item to cart. Please try again.', 'error');
            }
            
            // Reset button state for any button that might be in loading state
            const addToCartBtn = document.querySelector('.add-to-cart-btn[disabled]') || 
                               document.getElementById('add-to-cart-btn');
            if (addToCartBtn) {
                addToCartBtn.disabled = false;
                addToCartBtn.textContent = 'Add to Cart';
            }
            
            return false;
        }
    },
    
    // Update cart count in header
    updateCartCount: async function() {
        try {
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            if (!isLoggedIn) return;
            
            const response = await fetch('api/cart.php');
            const data = await response.json();
            
            console.log('Cart count API response:', data);
            
            // FIXED: Check both success indicators
            if (data.success === true || data.status === 'success') {
                // Handle both API response formats
                let cartItems = [];
                let subtotal = 0;
                
                if (data.data && data.data.items) {
                    cartItems = data.data.items;
                    subtotal = data.data.summary?.subtotal || 0;
                } else if (data.items) {
                    cartItems = data.items;
                    // Calculate subtotal if not provided
                    subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                }
                
                const cartCount = cartItems.length;
                
                // Save cart data to localStorage for quick access
                localStorage.setItem('cart_count', cartCount);
                localStorage.setItem('cart_total', subtotal);
                
                // Update cart icon with count
                const cartIcon = document.querySelector('.icon-cart');
                if (cartIcon) {
                    // Find existing badge or create new one
                    let badge = document.querySelector('.cart-badge');
                    if (!badge && cartCount > 0) {
                        badge = document.createElement('span');
                        badge.className = 'cart-badge';
                        badge.style.cssText = `
                            position: absolute;
                            top: -5px;
                            right: -5px;
                            background-color: #e74c3c;
                            color: white;
                            font-size: 10px;
                            font-weight: bold;
                            width: 16px;
                            height: 16px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                            animation: bounce 0.5s;
                        `;
                        
                        // Find parent element with position relative
                        const iconBtn = cartIcon.closest('.icon-btn');
                        if (iconBtn) {
                            iconBtn.style.position = 'relative';
                            iconBtn.appendChild(badge);
                            
                            // Add tooltip with total price
                            iconBtn.title = `Cart: ৳${subtotal.toLocaleString()}`;
                            
                            // Add hover tooltip with more details
                            iconBtn.addEventListener('mouseenter', function() {
                                let tooltip = document.getElementById('cart-tooltip');
                                if (!tooltip) {
                                    tooltip = document.createElement('div');
                                    tooltip.id = 'cart-tooltip';
                                    tooltip.style.cssText = `
                                        position: absolute;
                                        top: 100%;
                                        right: 0;
                                        background: white;
                                        border-radius: 4px;
                                        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                                        padding: 8px 12px;
                                        font-size: 12px;
                                        color: #333;
                                        z-index: 1000;
                                        width: max-content;
                                        min-width: 120px;
                                        text-align: center;
                                        opacity: 0;
                                        transform: translateY(10px);
                                        transition: all 0.2s ease;
                                        pointer-events: none;
                                    `;
                                    document.body.appendChild(tooltip);
                                }
                                
                                tooltip.innerHTML = `
                                    <div style="font-weight: 600; margin-bottom: 4px;">Cart Total</div>
                                    <div style="font-size: 14px; color: #e74c3c; font-weight: 700;">৳${subtotal.toLocaleString()}</div>
                                    <div style="margin-top: 4px; font-size: 11px; color: #666;">${cartCount} item${cartCount !== 1 ? 's' : ''}</div>
                                `;
                                
                                // Position tooltip
                                const rect = iconBtn.getBoundingClientRect();
                                tooltip.style.top = (rect.bottom + window.scrollY) + 'px';
                                tooltip.style.right = (window.innerWidth - rect.right) + 'px';
                                
                                // Show tooltip
                                setTimeout(() => {
                                    tooltip.style.opacity = '1';
                                    tooltip.style.transform = 'translateY(0)';
                                }, 10);
                            });
                            
                            iconBtn.addEventListener('mouseleave', function() {
                                const tooltip = document.getElementById('cart-tooltip');
                                if (tooltip) {
                                    tooltip.style.opacity = '0';
                                    tooltip.style.transform = 'translateY(10px)';
                                    
                                    setTimeout(() => {
                                        if (tooltip.parentNode) {
                                            tooltip.parentNode.removeChild(tooltip);
                                        }
                                    }, 200);
                                }
                            });
                        }
                    } else if (badge && cartCount > 0) {
                        // Update existing badge
                        const iconBtn = cartIcon.closest('.icon-btn');
                        if (iconBtn) {
                            iconBtn.title = `Cart: ৳${subtotal.toLocaleString()}`;
                        }
                    }
                    
                    // Update badge text or remove if count is 0
                    if (badge) {
                        if (cartCount > 0) {
                            badge.textContent = cartCount;
                        } else {
                            badge.remove();
                            
                            // Remove tooltip title if cart is empty
                            const iconBtn = cartIcon.closest('.icon-btn');
                            if (iconBtn) {
                                iconBtn.title = 'Cart';
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    },
    
    // Show notification
    showNotification: function(message, type = 'info') {
        console.log('Showing notification:', message, 'Type:', type);
        
        // Create notification element if it doesn't exist
        let notification = document.getElementById('cart-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'cart-notification';
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
        
        // Set notification message
        notification.textContent = message;
        
        // Show notification
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 10);
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            
            // Remove notification after animation
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
};

// Initialize cart count when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count in header
    HandicraftsCart.updateCartCount();
});