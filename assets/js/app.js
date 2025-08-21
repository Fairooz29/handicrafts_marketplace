/**
 * Handicrafts Marketplace - Main JavaScript Application
 * Handles dynamic functionality, API calls, and user interactions
 */

// Global variables
let currentUser = null;
let cartCount = 0;
let favoritesCount = 0;

// API Base URL
const API_BASE = 'api/';

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Check user session
    checkUserSession();
    
    // Update header counts
    updateCartCount();
    updateFavoritesCount();
    
    // Initialize page-specific functionality
    const currentPage = getCurrentPage();
    
    switch (currentPage) {
        case 'handicrafts':
            initializeHandicraftsPage();
            break;
        case 'product':
            initializeProductPage();
            break;
        case 'cart':
            initializeCartPage();
            break;
        case 'favorites':
            initializeFavoritesPage();
            break;
        case 'payment':
            initializePaymentPage();
            break;
    }
    
    // Bind global event handlers
    bindGlobalEventHandlers();
}

/**
 * Get current page name from URL
 */
function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.substring(path.lastIndexOf('/') + 1);
    
    if (page.includes('handicrafts')) return 'handicrafts';
    if (page.includes('product')) return 'product';
    if (page.includes('cart')) return 'cart';
    if (page.includes('favorites')) return 'favorites';
    if (page.includes('payment')) return 'payment';
    
    return 'home';
}

/**
 * Check user session status
 */
function checkUserSession() {
    // In a real application, this would check server session
    // For now, we'll use localStorage as a simple session mechanism
    const userData = localStorage.getItem('user_session');
    if (userData) {
        currentUser = JSON.parse(userData);
    }
}

/**
 * Update cart count in header
 */
async function updateCartCount() {
    try {
        const response = await fetch(`${API_BASE}cart.php?action=count`);
        const data = await response.json();
        
        if (data.success) {
            cartCount = data.count;
            updateCartDisplay();
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

/**
 * Update favorites count in header
 */
async function updateFavoritesCount() {
    try {
        const response = await fetch(`${API_BASE}favorites.php?action=count`);
        const data = await response.json();
        
        if (data.success) {
            favoritesCount = data.count;
            updateFavoritesDisplay();
        }
    } catch (error) {
        console.error('Error updating favorites count:', error);
    }
}

/**
 * Update cart count display in header
 */
function updateCartDisplay() {
    const cartButtons = document.querySelectorAll('.icon-cart');
    cartButtons.forEach(button => {
        let badge = button.querySelector('.count-badge');
        
        if (cartCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'count-badge';
                button.appendChild(badge);
            }
            badge.textContent = cartCount;
            badge.style.display = 'inline';
        } else if (badge) {
            badge.style.display = 'none';
        }
    });
}

/**
 * Update favorites count display in header
 */
function updateFavoritesDisplay() {
    const favoriteButtons = document.querySelectorAll('.icon-heart');
    favoriteButtons.forEach(button => {
        let badge = button.querySelector('.count-badge');
        
        if (favoritesCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'count-badge';
                button.appendChild(badge);
            }
            badge.textContent = favoritesCount;
            badge.style.display = 'inline';
        } else if (badge) {
            badge.style.display = 'none';
        }
    });
}

/**
 * Bind global event handlers
 */
function bindGlobalEventHandlers() {
    // Search functionality
    const searchForms = document.querySelectorAll('.search-bar');
    searchForms.forEach(form => {
        form.addEventListener('submit', handleSearch);
    });
    
    // Favorites button in header
    const favoritesButtons = document.querySelectorAll('.header-actions .icon-btn[aria-label="Favorites"]');
    favoritesButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'favorites.html';
        });
    });
}

/**
 * Handle search form submission
 */
function handleSearch(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const searchQuery = formData.get('search') || e.target.querySelector('input[type="text"]').value;
    
    if (searchQuery.trim()) {
        window.location.href = `handicrafts.html?search=${encodeURIComponent(searchQuery.trim())}`;
    }
}

/**
 * Initialize Handicrafts page functionality
 */
function initializeHandicraftsPage() {
    loadCategories();
    loadArtisans();
    loadProducts();
    
    // Handle filter changes
    const categorySelect = document.getElementById('category-select');
    const artisanSelect = document.getElementById('artisan-select');
    const sortSelect = document.getElementById('sort-select');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', applyFilters);
    }
    
    if (artisanSelect) {
        artisanSelect.addEventListener('change', applyFilters);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', applyFilters);
    }
}

/**
 * Load categories from database
 */
async function loadCategories() {
    try {
        const response = await fetch(`${API_BASE}categories.php?action=all`);
        const data = await response.json();
        
        if (data.success && data.categories) {
            const categorySelect = document.getElementById('category-select');
            if (categorySelect) {
                // Clear existing options except "All Categories"
                categorySelect.innerHTML = '<option value="">All Categories</option>';
                
                // Add categories from database
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    categorySelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Load artisans from database
 */
async function loadArtisans() {
    try {
        const response = await fetch(`${API_BASE}artisans.php?action=all`);
        const data = await response.json();
        
        if (data.success && data.artisans) {
            const artisanSelect = document.getElementById('artisan-select');
            if (artisanSelect) {
                // Clear existing options except "All Artisans"
                artisanSelect.innerHTML = '<option value="">All Artisans</option>';
                
                // Add artisans from database
                data.artisans.forEach(artisan => {
                    const option = document.createElement('option');
                    option.value = artisan.id;
                    option.textContent = artisan.name;
                    artisanSelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading artisans:', error);
    }
}

/**
 * Apply filters and load filtered products
 */
async function applyFilters() {
    const categorySelect = document.getElementById('category-select');
    const artisanSelect = document.getElementById('artisan-select');
    const sortSelect = document.getElementById('sort-select');
    
    const filters = {
        category_id: categorySelect ? categorySelect.value : '',
        artisan_id: artisanSelect ? artisanSelect.value : '',
        sort: sortSelect ? sortSelect.value : ''
    };
    
    await loadFilteredProducts(filters);
}

/**
 * Load filtered products from API
 */
async function loadFilteredProducts(filters = {}) {
    // Show loading indicator
    const loadingIndicator = document.getElementById('loading-indicator');
    const productsGrid = document.getElementById('products-grid');
    const noProducts = document.getElementById('no-products');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (noProducts) noProducts.style.display = 'none';
    
    try {
        // Build API URL with filters
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('search');
        
        let apiUrl = `${API_BASE}products.php?action=filter`;
        
        // Add search query if exists (use 'q' parameter for search action, 'search' for filter action)
        if (searchQuery) {
            apiUrl += `&search=${encodeURIComponent(searchQuery)}`;
        }
        
        // Add filters
        if (filters.category_id) {
            apiUrl += `&category_id=${filters.category_id}`;
        }
        
        if (filters.artisan_id) {
            apiUrl += `&artisan_id=${filters.artisan_id}`;
        }
        
        if (filters.sort) {
            apiUrl += `&sort=${filters.sort}`;
        }
        
        console.log('Loading filtered products from:', apiUrl);
        
        const response = await fetch(apiUrl);
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        if (data.success) {
            displayProducts(data.products);
            
            // Update filter counts if available
            if (data.filter_counts) {
                updateFilterCounts(data.filter_counts);
            }
        } else {
            console.error('API Error:', data.error);
            showError('Failed to load products: ' + (data.error || 'Unknown error'));
            if (noProducts) {
                noProducts.style.display = 'block';
                noProducts.innerHTML = `
                    <h3 style="color:#e74c3c;margin-bottom:1rem;">Error loading products</h3>
                    <p style="color:#666;">${data.error || 'Unknown error occurred'}</p>
                    <button onclick="loadProducts()" class="hero-button" style="margin-top:1rem;">Try Again</button>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading filtered products:', error);
        
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        showError('Error loading products: ' + error.message);
        
        if (noProducts) {
            noProducts.style.display = 'block';
            noProducts.innerHTML = `
                <h3 style="color:#e74c3c;margin-bottom:1rem;">Connection Error</h3>
                <p style="color:#666;">Failed to connect to the server. Please check your connection.</p>
                <button onclick="applyFilters()" class="hero-button" style="margin-top:1rem;">Try Again</button>
            `;
        }
    }
}

/**
 * Update filter counts display
 */
function updateFilterCounts(counts) {
    // This function can be used to show product counts for each filter option
    console.log('Filter counts:', counts);
}

/**
 * Load products from API
 */
async function loadProducts() {
    // Use the filtering system for initial load
    await loadFilteredProducts();
}

/**
 * Display products in grid
 */
function displayProducts(products) {
    const container = document.querySelector('.products-grid') || document.querySelector('#products-grid');
    const noProducts = document.getElementById('no-products');
    
    if (!container) {
        console.error('Products container not found');
        return;
    }
    
    if (products.length === 0) {
        container.innerHTML = '';
        if (noProducts) {
            noProducts.style.display = 'block';
            noProducts.innerHTML = `
                <h3 style="color:#8a7c6d;margin-bottom:1rem;">No products found</h3>
                <p style="color:#999;">Try adjusting your search or filter criteria.</p>
            `;
        }
        return;
    }
    
    // Hide no products message
    if (noProducts) noProducts.style.display = 'none';
    
    console.log('Displaying', products.length, 'products');
    container.innerHTML = products.map(product => createProductCard(product)).join('');
    
    // Bind product card events
    bindProductCardEvents();
}

/**
 * Create professional product card HTML
 */
function createProductCard(product) {
    const discount = product.original_price > product.price ? 
        Math.round(((product.original_price - product.price) / product.original_price) * 100) : 0;
    const isOnSale = discount > 0;
    const stockStatus = product.stock_quantity > 0 ? 'in-stock' : 'out-of-stock';
    const formattedPrice = parseFloat(product.price).toLocaleString();
    const formattedOriginalPrice = product.original_price ? parseFloat(product.original_price).toLocaleString() : 0;
    
    return `
        <div class="product-card ${stockStatus}" data-product-id="${product.id}">
            <div class="product-image-container">
                ${isOnSale ? `<div class="discount-badge">${discount}% OFF</div>` : ''}
                ${product.stock_quantity <= 5 && product.stock_quantity > 0 ? 
                    '<div class="stock-badge">Only ' + product.stock_quantity + ' left!</div>' : ''}
                ${product.stock_quantity === 0 ? '<div class="stock-badge out-of-stock">Out of Stock</div>' : ''}
                
                <img src="${product.image}" alt="${product.name}" class="product-image" />
                
                <div class="product-overlay">
                    <button class="favorite-btn" data-product-id="${product.id}" aria-label="Add to favorites">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                    <button class="quick-view-btn" data-product-id="${product.id}" aria-label="Quick view">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="product-info">
                <div class="product-header">
                    <div class="product-category">${product.category_name || 'Handicrafts'}</div>
                    <div class="product-rating">
                        <div class="stars">
                            ${'★'.repeat(Math.floor(Math.random() * 2) + 4)}${'☆'.repeat(5 - (Math.floor(Math.random() * 2) + 4))}
                        </div>
                        <span class="rating-count">(${Math.floor(Math.random() * 50) + 10})</span>
                    </div>
                </div>
                
                <h3 class="product-title">${product.name}</h3>
                <p class="product-description">${product.short_description}</p>
                

                
                <div class="product-footer">
                    <div class="product-price-section">
                        <div class="price-main">
                            <span class="current-price">৳${formattedPrice}</span>
                            ${isOnSale ? 
                                `<span class="original-price">৳${formattedOriginalPrice}</span>` : ''
                            }
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <button class="add-to-cart-btn" data-product-id="${product.id}" 
                                ${product.stock_quantity === 0 ? 'disabled' : ''}>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            ${product.stock_quantity === 0 ? 'Sold Out' : 'Add to Cart'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Bind product card events
 */
function bindProductCardEvents() {
    // Product card click events (excluding action buttons)
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.favorite-btn') && 
                !e.target.closest('.add-to-cart-btn') && 
                !e.target.closest('.quick-view-btn') &&
                !e.target.closest('.product-actions')) {
                const productId = this.dataset.productId;
                window.location.href = `product.html?id=${productId}`;
            }
        });
    });
    
    // Favorite button events
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Use HandicraftsFavorites module if available, otherwise fallback to local function
            if (typeof HandicraftsFavorites !== 'undefined' && HandicraftsFavorites.toggleFavorite) {
                HandicraftsFavorites.toggleFavorite(this.dataset.productId, this);
            } else {
                toggleFavorite(this.dataset.productId, this);
            }
        });
    });
    
    // Add to cart button events
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!this.disabled) {
                const productId = this.dataset.productId;
                
                // Always use HandicraftsCart module which is now properly loaded
                console.log('Adding product to cart:', productId);
                HandicraftsCart.addToCart(productId, 1);
                
                // Visual feedback
                const originalText = this.innerHTML;
                this.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Added!
                `;
                this.style.background = '#27ae60';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = '';
                }, 2000);
            }
        });
    });
    
    // Quick view button events
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.dataset.productId;
            window.location.href = `product.html?id=${productId}`;
        });
    });
    
    // Load favorite status for all products
    loadFavoriteStatuses();
}

/**
 * Toggle favorite status
 */
async function toggleFavorite(productId, buttonElement) {
    try {
        const response = await fetch(`${API_BASE}favorites.php?action=toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_id: parseInt(productId) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update button appearance
            buttonElement.classList.toggle('active', data.is_favorite);
            
            // Update favorites count
            favoritesCount = data.favorites_count;
            updateFavoritesDisplay();
            
            // Show feedback
            showToast(data.message, 'success');
        } else {
            if (response.status === 401) {
                showToast('Please login to add favorites', 'error');
                // Optionally redirect to login
            } else {
                showToast(data.error || 'Failed to update favorites', 'error');
            }
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
        showToast('Error updating favorites', 'error');
    }
}

/**
 * Load favorite statuses for all visible products
 */
async function loadFavoriteStatuses() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    for (const button of favoriteButtons) {
        try {
            const productId = button.dataset.productId;
            const response = await fetch(`${API_BASE}favorites.php?action=check&product_id=${productId}`);
            const data = await response.json();
            
            if (data.success && data.is_favorite) {
                button.classList.add('active');
            }
        } catch (error) {
            console.error('Error checking favorite status:', error);
        }
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Hide and remove toast
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

/**
 * Show error message
 */
function showError(message) {
    showToast(message, 'error');
}

/**
 * Show success message
 */
function showSuccess(message) {
    showToast(message, 'success');
}

/**
 * Initialize Product page functionality
 */
function initializeProductPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');
    
    if (productId) {
        loadSingleProduct(productId);
    } else {
        showError('Product ID not found');
    }
}

/**
 * Load single product details
 */
async function loadSingleProduct(productId) {
    try {
        const response = await fetch(`${API_BASE}products.php?action=single&id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            displayProductDetails(data.product);
            bindProductPageEvents(data.product);
        } else {
            showError('Product not found');
        }
    } catch (error) {
        console.error('Error loading product:', error);
        showError('Error loading product details');
    }
}

/**
 * Display product details on product page
 */
function displayProductDetails(product) {
    // Update page title
    document.title = `${product.name} | Crafts of Bengal`;
    
    // Update product elements
    const elements = {
        '#product-title': product.name,
        '#product-description': product.description,
        '#product-price': `৳${parseFloat(product.price).toLocaleString()}`,
        '#artisan-name': product.artisan_name,
        '#artisan-bio': product.artisan_bio
    };
    
    Object.entries(elements).forEach(([selector, content]) => {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = content;
        }
    });
    
    // Update product image
    const productImage = document.querySelector('#product-image');
    if (productImage) {
        productImage.src = product.image;
        productImage.alt = product.name;
        productImage.style.display = 'block';
    }
    
    // Update breadcrumb
    const breadcrumbProduct = document.querySelector('#breadcrumb-product');
    if (breadcrumbProduct) {
        breadcrumbProduct.textContent = product.name;
    }
}

/**
 * Bind product page events
 */
function bindProductPageEvents(product) {
    // Add to cart button
    const addToCartBtn = document.querySelector('#add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => addToCart(product.id));
    }
    
    // Favorite button
    const favoriteBtn = document.querySelector('.product-favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', () => toggleFavorite(product.id, favoriteBtn));
        
        // Load favorite status
        loadFavoriteStatus(product.id, favoriteBtn);
    }
}

/**
 * Add product to cart
 */
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch(`${API_BASE}cart.php?action=add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                product_id: parseInt(productId),
                quantity: quantity 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update cart count
            cartCount = data.cart_count;
            updateCartDisplay();
            
            // Show success message
            showSuccess(data.message);
        } else {
            if (response.status === 401) {
                showError('Please login to add items to cart');
            } else {
                showError(data.error || 'Failed to add to cart');
            }
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showError('Error adding to cart');
    }
}

/**
 * Load favorite status for single product
 */
async function loadFavoriteStatus(productId, buttonElement) {
    try {
        const response = await fetch(`${API_BASE}favorites.php?action=check&product_id=${productId}`);
        const data = await response.json();
        
        if (data.success && data.is_favorite) {
            buttonElement.classList.add('active');
        }
    } catch (error) {
        console.error('Error checking favorite status:', error);
    }
}

// Export functions for global access
window.HandicraftsApp = {
    addToCart,
    toggleFavorite,
    updateCartCount,
    updateFavoritesCount,
    showToast,
    showError,
    showSuccess
};
