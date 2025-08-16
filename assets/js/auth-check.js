// Check if user is logged in
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
    const currentPage = window.location.pathname.split('/').pop();
    
    // Update header based on login status
    updateHeader(isLoggedIn);
    
    // Check if user needs to be redirected
    if (isProtectedPage(currentPage) && !isLoggedIn) {
        // Redirect to login page with return URL
        window.location.href = `login.html?return=${encodeURIComponent(currentPage)}`;
    }
});

// Update header based on login status
function updateHeader(isLoggedIn) {
    const headerActions = document.querySelector('.header-actions');
    if (!headerActions) return;
    
    // Get profile link/button
    const profileLink = document.querySelector('.profile-avatar-link');
    
    if (isLoggedIn) {
        // User is logged in
        const userName = localStorage.getItem('user_name');
        
        // If profile link exists, update it
        if (profileLink) {
            // Add user name to title attribute
            profileLink.setAttribute('title', `${userName}'s Profile`);
        } else {
            // Create profile link if it doesn't exist
            const newProfileLink = document.createElement('a');
            newProfileLink.href = 'profile.html';
            newProfileLink.className = 'profile-avatar-link';
            newProfileLink.setAttribute('title', `${userName}'s Profile`);
            
            const profileImg = document.createElement('img');
            profileImg.src = 'assets/images/profile.jpg';
            profileImg.alt = 'Profile';
            profileImg.className = 'profile-avatar';
            
            newProfileLink.appendChild(profileImg);
            headerActions.parentNode.appendChild(newProfileLink);
        }
        
        // Add logout button if it doesn't exist
        if (!document.querySelector('.logout-btn')) {
            const logoutBtn = document.createElement('button');
            logoutBtn.className = 'icon-btn logout-btn';
            logoutBtn.setAttribute('aria-label', 'Logout');
            logoutBtn.innerHTML = '<span class="icon-logout"></span>';
            logoutBtn.setAttribute('title', 'Logout');
            
            logoutBtn.addEventListener('click', function() {
                logout();
            });
            
            headerActions.appendChild(logoutBtn);
        }
    } else {
        // User is not logged in
        // Remove profile link if exists
        if (profileLink) {
            profileLink.remove();
        }
        
        // Remove logout button if exists
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.remove();
        }
        
        // Add login link if it doesn't exist
        if (!document.querySelector('.auth-links')) {
            const authLinks = document.createElement('div');
            authLinks.className = 'auth-links';
            
            const loginLink = document.createElement('a');
            loginLink.href = 'login.html';
            loginLink.className = 'auth-link';
            loginLink.textContent = 'Login';
            
            // Register button removed as requested
            
            authLinks.appendChild(loginLink);
            
            headerActions.appendChild(authLinks);
        }
    }
}

// Check if page requires authentication
function isProtectedPage(page) {
    const protectedPages = [
        'profile.html',
        'cart.html',
        'checkout.html',
        'orders.html'
    ];
    
    return protectedPages.includes(page);
}

// Logout function
function logout() {
    // Clear user data from localStorage
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_name');
    localStorage.removeItem('is_logged_in');
    
    // Clear session cookie
    document.cookie = 'session_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    
    // Update header before redirect
    updateHeader(false);
    
    // Redirect to home page
    window.location.href = 'index.html';
}
