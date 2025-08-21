// Handle Registration Form
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, looking for registration form...');
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        console.log('Registration form found, adding event listener...');
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Registration form submitted');
            
            // Get form data
            const formData = new FormData(this);
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            console.log('Form data collected:', {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                passwordLength: password.length,
                confirmPasswordLength: confirmPassword.length
            });
            
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
                
                console.log('Sending registration request to api/auth.php...');
                
                // Send request
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response received:', response);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    console.error('Raw response:', responseText);
                    throw new Error('Invalid response format from server');
                }
                
                console.log('Registration response parsed:', data);
                
                if (data.success) {
                    // Show success message
                    showMessage('Account created successfully! Redirecting to login page...', 'success');
                    
                    // Clear form
                    this.reset();
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        console.log('Redirecting to login.html...');
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
        console.log('Registration form event listener added successfully');
    } else {
        console.error('Registration form not found');
    }
});

// Handle Login Form
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Login form submitted');
            
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
                
                console.log('Sending login request...');
                
                // Send request
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response received:', response);
                
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
    } else {
        console.log('Login form not found (this is normal on register page)');
    }
});

// Show message function
function showMessage(message, type = 'success') {
    console.log('Showing message:', message, 'Type:', type);
    
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
        position: relative;
        z-index: 1000;
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
    
    // Add message to page - try to find the best place to insert it
    const form = document.querySelector('.auth-form');
    if (form) {
        // Insert before the form
        form.parentNode.insertBefore(messageDiv, form);
        console.log('Message inserted before form');
    } else {
        // Fallback: insert at the top of the auth-card
        const authCard = document.querySelector('.auth-card');
        if (authCard) {
            authCard.insertBefore(messageDiv, authCard.firstChild);
            console.log('Message inserted at top of auth-card');
        } else {
            // Last resort: append to body
            document.body.appendChild(messageDiv);
            console.log('Message appended to body');
        }
    }
    
    // Remove success message after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Clear messages function
function clearMessages() {
    const messages = document.querySelectorAll('.message');
    console.log('Clearing', messages.length, 'messages');
    messages.forEach(msg => {
        if (msg.parentNode) {
            msg.remove();
        }
    });
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