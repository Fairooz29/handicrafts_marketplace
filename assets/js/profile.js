// Profile Management Module
const ProfileManager = (function() {
    
    // Private variables
    let currentUser = null;
    let isLoading = false;
    
    // Private functions
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Insert at the top of the main content
        const main = document.querySelector('main');
        main.insertBefore(notification, main.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    function setLoading(element, loading) {
        if (loading) {
            element.disabled = true;
            element.dataset.originalText = element.textContent;
            element.innerHTML = '<span class="loading-spinner"></span> Loading...';
        } else {
            element.disabled = false;
            element.textContent = element.dataset.originalText || element.textContent.replace('Loading...', '').trim();
        }
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function formatCurrency(amount) {
        return '৳' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 0 });
    }
    
    // Load user profile data
    async function loadProfile() {
        try {
            console.log('Loading profile data...');
            const response = await fetch('api/profile.php?action=profile');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Profile data response:', data);
            
            if (data.success) {
                currentUser = data.data;
                populateProfileForm(data.data);
                showNotification('Profile loaded successfully', 'success');
                return data.data;
            } else {
                if (response.status === 401) {
                    // Session expired
                    showNotification('Session expired. Please log in again.', 'error');
                    localStorage.removeItem('is_logged_in');
                    localStorage.removeItem('user_id');
                    localStorage.removeItem('user_name');
                    setTimeout(() => {
                        window.location.href = 'login.html?redirect=profile.html';
                    }, 2000);
                    return null;
                }
                showNotification(data.message || 'Failed to load profile', 'error');
                return null;
            }
        } catch (error) {
            console.error('Profile load error:', error);
            showNotification('Error loading profile data. Please refresh the page.', 'error');
            return null;
        }
    }
    
    // Populate profile form with user data
    function populateProfileForm(userData) {
        // Fill form fields
        document.getElementById('firstName').value = userData.first_name || '';
        document.getElementById('lastName').value = userData.last_name || '';
        document.getElementById('email').value = userData.email || '';
        document.getElementById('phone').value = userData.phone || '';
        
        // Add address fields if they exist in the form
        const addressField = document.getElementById('address');
        const cityField = document.getElementById('city');
        const postalCodeField = document.getElementById('postalCode');
        
        if (addressField) addressField.value = userData.address || '';
        if (cityField) cityField.value = userData.city || '';
        if (postalCodeField) postalCodeField.value = userData.postal_code || '';
        
        // Update profile image if available
        if (userData.profile_image) {
            const avatarElements = document.querySelectorAll('.large-avatar, .profile-avatar');
            avatarElements.forEach(img => {
                img.src = userData.profile_image;
            });
        }
    }
    
    // Save profile changes
    async function saveProfile(formData) {
        const saveButton = document.querySelector('#profileForm button[type="submit"]');
        setLoading(saveButton, true);
        
        try {
            const profileData = {
                first_name: formData.get('firstName'),
                last_name: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone') || '',
                address: formData.get('address') || '',
                city: formData.get('city') || '',
                postal_code: formData.get('postalCode') || ''
            };
            
            const response = await fetch('api/profile.php?action=profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(profileData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Profile updated successfully!', 'success');
                
                // Update localStorage if user name changed
                const newName = `${profileData.first_name} ${profileData.last_name}`;
                localStorage.setItem('user_name', newName);
                
                // Update current user data
                currentUser = { ...currentUser, ...profileData };
                
                return true;
            } else {
                showNotification(data.message || 'Failed to update profile', 'error');
                return false;
            }
        } catch (error) {
            console.error('Profile save error:', error);
            showNotification('Error saving profile', 'error');
            return false;
        } finally {
            setLoading(saveButton, false);
        }
    }
    
    // Change password
    async function changePassword(passwordData) {
        const changeButton = document.querySelector('.password-form button[type="submit"]');
        setLoading(changeButton, true);
        
        try {
            const response = await fetch('api/profile.php?action=password', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(passwordData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Password changed successfully!', 'success');
                // Clear the form
                document.querySelector('.password-form').reset();
                return true;
            } else {
                showNotification(data.message || 'Failed to change password', 'error');
                return false;
            }
        } catch (error) {
            console.error('Password change error:', error);
            showNotification('Error changing password', 'error');
            return false;
        } finally {
            setLoading(changeButton, false);
        }
    }
    
    // Load user orders
    async function loadOrders() {
        try {
            const response = await fetch('api/profile.php?action=orders');
            const data = await response.json();
            
            if (data.success) {
                displayOrders(data.data);
                return data.data;
            } else {
                showNotification(data.message || 'Failed to load orders', 'error');
                return [];
            }
        } catch (error) {
            console.error('Orders load error:', error);
            showNotification('Error loading orders', 'error');
            return [];
        }
    }
    
    // Display orders in the UI
    function displayOrders(orders) {
        const ordersList = document.querySelector('.orders-list');
        if (!ordersList) return;
        
        if (orders.length === 0) {
            ordersList.innerHTML = `
                <div class="empty-state">
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here.</p>
                    <a href="handicrafts.html" class="shop-now-btn">Shop Now</a>
                </div>
            `;
            return;
        }
        
        ordersList.innerHTML = orders.map(order => `
            <div class="order-card">
                <div class="order-header">
                    <div class="order-info">
                        <h3 class="order-number">Order #${order.order_number}</h3>
                        <p class="order-date">Placed on ${formatDate(order.created_at)}</p>
                    </div>
                    <span class="order-status ${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                </div>
                <div class="order-items">
                    ${order.items.map(item => `
                        <div class="order-item">
                            <img src="${item.image_url || 'assets/images/placeholder.jpg'}" alt="${item.name}" class="item-image">
                            <div class="item-details">
                                <h4 class="item-name">${item.name}</h4>
                                <p class="item-price">${formatCurrency(item.price)}</p>
                            </div>
                            <div class="item-quantity">Qty: ${item.quantity}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="order-footer">
                    <span class="order-total">Total: ${formatCurrency(order.total_amount)}</span>
                    <button class="track-order-btn" onclick="ProfileManager.trackOrder('${order.order_number}')">Track Order</button>
                </div>
            </div>
        `).join('');
    }
    
    // Initialize profile page
    async function init() {
        // Check authentication
        const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
        const userId = localStorage.getItem('user_id');
        
        console.log('Profile init - isLoggedIn:', isLoggedIn, 'userId:', userId);
        
        if (!isLoggedIn || !userId) {
            showNotification('Please log in to access your profile', 'error');
            setTimeout(() => {
                window.location.href = 'login.html?redirect=profile.html';
            }, 2000);
            return;
        }
        
        // Verify session is still valid
        try {
            const sessionCheck = await fetch('api/session-check.php');
            const sessionData = await sessionCheck.json();
            
            console.log('Session check result:', sessionData);
            
            if (!sessionData.success) {
                showNotification('Session expired. Please log in again.', 'error');
                // Clear localStorage
                localStorage.removeItem('is_logged_in');
                localStorage.removeItem('user_id');
                localStorage.removeItem('user_name');
                
                setTimeout(() => {
                    window.location.href = 'login.html?redirect=profile.html';
                }, 2000);
                return;
            }
        } catch (error) {
            console.error('Session check error:', error);
            showNotification('Error verifying session', 'error');
        }
        
        // Load profile data
        await loadProfile();
        
        // Setup form handlers
        setupFormHandlers();
        
        // Setup tab navigation
        setupTabNavigation();
    }
    
    // Setup form event handlers
    function setupFormHandlers() {
        // Profile form handler
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                await saveProfile(formData);
            });
        }
        
        // Password change form handler
        const passwordForm = document.querySelector('.password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const currentPassword = this.currentPassword.value;
                const newPassword = this.newPassword.value;
                const confirmPassword = this.confirmNewPassword.value;
                
                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match', 'error');
                    return;
                }
                
                await changePassword({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                });
            });
        }
    }
    
    // Setup tab navigation
    function setupTabNavigation() {
        const tabButtons = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Remove active class from all tabs and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.classList.add('active');
                    
                    // Load data for specific tabs
                    if (targetTab === 'orders') {
                        loadOrders();
                    } else if (targetTab === 'wishlist') {
                        // Load wishlist data from favorites API
                        loadWishlist();
                    }
                }
            });
        });
    }
    
    // Load wishlist data (integrate with favorites)
    async function loadWishlist() {
        try {
            const response = await fetch('api/favorites.php?action=all');
            const data = await response.json();
            
            if (data.success) {
                displayWishlist(data.data);
            } else {
                showNotification('Failed to load wishlist', 'error');
            }
        } catch (error) {
            console.error('Wishlist load error:', error);
            showNotification('Error loading wishlist', 'error');
        }
    }
    
    // Display wishlist items
    function displayWishlist(items) {
        const wishlistGrid = document.querySelector('.wishlist-grid');
        if (!wishlistGrid) return;
        
        if (items.length === 0) {
            wishlistGrid.innerHTML = `
                <div class="empty-state">
                    <h3>Your wishlist is empty</h3>
                    <p>Add items to your wishlist by clicking the heart icon on products.</p>
                    <a href="handicrafts.html" class="shop-now-btn">Shop Now</a>
                </div>
            `;
            return;
        }
        
        wishlistGrid.innerHTML = items.map(item => `
            <div class="wishlist-item">
                <img src="${item.image_url || 'assets/images/placeholder.jpg'}" alt="${item.name}" class="item-image">
                <div class="item-info">
                    <h3 class="item-name">${item.name}</h3>
                    <p class="item-artisan">by ${item.artisan_name || 'Artisan'}</p>
                    <p class="item-price">${formatCurrency(item.price)}</p>
                </div>
                <div class="item-actions">
                    <button class="add-to-cart-btn" onclick="HandicraftsCart.addToCart(${item.id})">Add to Cart</button>
                    <button class="remove-wishlist-btn" onclick="HandicraftsFavorites.toggleFavorite(${item.id})">Remove</button>
                </div>
            </div>
        `).join('');
    }
    
    // Public API
    return {
        init: init,
        loadProfile: loadProfile,
        saveProfile: saveProfile,
        changePassword: changePassword,
        loadOrders: loadOrders,
        trackOrder: function(orderNumber) {
            showNotification(`Tracking for order ${orderNumber} - Feature coming soon!`, 'info');
        }
    };
})();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.profile-container')) {
        ProfileManager.init();
    }
});
