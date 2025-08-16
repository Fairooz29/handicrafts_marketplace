// Handle Registration Form
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Clear previous messages
    clearMessages();
    
    // Validate passwords match
    if (password !== confirmPassword) {
        showMessage('Passwords do not match!', 'error');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';
    
    try {
        // Add action to form data
        formData.append('action', 'register');
        
        // Send request
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Registration response:', data);
        
        if (data.success) {
            // Show success message
            showMessage('Account created successfully! Redirecting...', 'success');
            
            // Clear form
            this.reset();
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
                } else {
            showMessage(data.message || 'Registration failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
});

// Handle Login Form
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    
    // Clear previous messages
    clearMessages();
    
    // Disable submit button and show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    submitBtn.classList.add('loading');
    
    try {
        // Add action to form data
        formData.append('action', 'login');
        
        // Send request
        const response = await fetch('api/auth.php', {
                method: 'POST',
            body: formData
            });
            
            const data = await response.json();
        console.log('Login response:', data);
            
            if (data.success) {
            // Show success message
            showMessage('Login successful! Redirecting to homepage...', 'success');
            
            // Store user data in localStorage
            localStorage.setItem('user_id', data.data.userId);
            localStorage.setItem('user_name', data.data.userName);
            localStorage.setItem('is_logged_in', 'true');
            
            // Redirect after 1 second
                setTimeout(() => {
                window.location.href = 'index.html';
                }, 1000);
            } else {
            showMessage(data.message || 'Login failed. Please check your credentials.', 'error');
            }
        } catch (error) {
        console.error('Login error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
        submitBtn.classList.remove('loading');
    }
});

// Show message function
function showMessage(message, type = 'success') {
    // Remove any existing messages
    clearMessages();
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;
    messageDiv.style.cssText = `
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        text-align: center;
        font-weight: 500;
        animation: slideDown 0.3s ease-out;
    `;
    
    // Set colors based on message type
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        messageDiv.style.backgroundColor = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.border = '1px solid #f5c6cb';
    }
    
    messageDiv.textContent = message;
    
    // Add message to page
    const form = document.querySelector('.auth-form');
    form.insertBefore(messageDiv, form.firstChild);
    
    // Remove success message after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Clear messages function
function clearMessages() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => msg.remove());
}

// Add CSS for animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Add loading indicator to form
document.querySelectorAll('.auth-form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.style.position = 'relative';
            submitBtn.style.transition = 'all 0.3s ease';
        }
    });
});