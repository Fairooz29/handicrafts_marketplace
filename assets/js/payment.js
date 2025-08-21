/**
 * Enhanced Payment Processing Module
 * Handles all payment-related functionality for the checkout process
 */
const PaymentManager = (function() {
    
    // Private variables
    let cartData = [];
    let orderTotal = 0;
    let isProcessing = false;
    let shippingAddress = {};
    let billingAddress = {};
    
    /**
     * Format currency in Bengali Taka format
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    function formatCurrency(amount) {
        return '৳' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 0 });
    }
    
    /**
     * Show modal popup with custom message
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {string} type - Modal type (success, error, warning, info)
     * @param {Function} callback - Optional callback function
     */
    function showModal(title, message, type = 'success', callback = null) {
        const modal = document.getElementById('modalOverlay');
        const modalTitle = modal.querySelector('.modal-title');
        const modalMessage = modal.querySelector('.modal-message');
        const modalIcon = modal.querySelector('.modal-icon');
        const modalBtn = modal.querySelector('.modal-btn');
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        // Set icon based on type
        modalIcon.className = `modal-icon ${type}`;
        const iconElement = modalIcon.querySelector('i');
        
        switch(type) {
            case 'success':
                iconElement.className = 'fas fa-check-circle';
                break;
            case 'error':
                iconElement.className = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                iconElement.className = 'fas fa-exclamation-triangle';
                break;
            default:
                iconElement.className = 'fas fa-info-circle';
        }
        
        modal.classList.add('show');
        
        // Set callback
        if (callback) {
            modalBtn.onclick = function() {
                closeModal();
                callback();
            };
        } else {
            modalBtn.onclick = closeModal;
        }
    }
    
    /**
     * Close modal popup
     */
    function closeModal() {
        const modal = document.getElementById('modalOverlay');
        modal.classList.remove('show');
    }
    
    /**
     * Show success animation
     */
    function showSuccessAnimation() {
        const animation = document.getElementById('successAnimation');
        animation.classList.add('show');
        
        setTimeout(() => {
            animation.classList.remove('show');
        }, 3000);
    }
    
    /**
     * Show notification message
     * @param {string} message - Notification message
     * @param {string} type - Notification type (success, error, warning, info)
     */
    function showNotification(message, type = 'info') {
        // Create notifications container if it doesn't exist
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'notifications-container';
            document.body.appendChild(container);
        }
        
        // Create new notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Add to container
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
                
                // Remove container if empty
                if (container.children.length === 0) {
                    container.remove();
                }
            }
        }, 5000);
    }
    
    /**
     * Load cart data from API
     */
    async function loadCartData() {
        try {
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            
            if (isLoggedIn) {
                // Show loading state
                const orderItemsContainer = document.getElementById('orderItems');
                orderItemsContainer.innerHTML = `
                    <div class="loading-order">
                        <div class="loading-spinner"></div>
                        <p>Loading your items...</p>
                    </div>
                `;
                
                // Load from database
                const response = await fetch('api/cart.php');
                const data = await response.json();
                
                console.log('Cart data response:', data);
                
                if (data.success === true || data.status === 'success') {
                    // Handle both API response formats
                    if (data.data && data.data.items) {
                        cartData = data.data.items;
                    } else if (data.items) {
                        cartData = data.items;
                    } else {
                        cartData = [];
                    }
                    
                    displayCartSummary();
                    
                    // Update mobile payment amount
                    const mobilePayAmount = document.getElementById('mobilePayAmount');
                    if (mobilePayAmount) {
                        mobilePayAmount.textContent = document.getElementById('totalAmount').textContent;
                    }
                } else {
                    showNotification('Failed to load cart data', 'error');
                    displayEmptyCart();
                }
            } else {
                // Redirect to login
                showModal('Login Required', 'Please log in to continue with checkout', 'warning', () => {
                    window.location.href = 'login.html?redirect=payment.html';
                });
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            showNotification('Error loading cart data', 'error');
            displayEmptyCart();
        }
    }
    
    /**
     * Display empty cart message
     */
    function displayEmptyCart() {
        const orderItemsContainer = document.getElementById('orderItems');
        
        orderItemsContainer.innerHTML = `
            <div class="empty-cart-message">
                <p>Your cart is empty</p>
                <a href="handicrafts.html" class="shop-now-btn">Continue Shopping</a>
            </div>
        `;
        
        // Disable checkout button
        const checkoutBtn = document.getElementById('completeOrderBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
        }
    }
    
    /**
     * Display cart summary in order summary
     */
    function displayCartSummary() {
        const orderItemsContainer = document.getElementById('orderItems');
        
        if (!cartData || cartData.length === 0) {
            displayEmptyCart();
            return;
        }
        
        let subtotal = 0;
        
        orderItemsContainer.innerHTML = cartData.map(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            // Handle image URL
            const imageUrl = item.image_url || item.image || 'assets/images/placeholder.jpg';
            
            // Handle artisan name
            const artisanName = item.artisan_name || item.artisan || 'Artisan';
            
            return `
                <div class="order-item" data-product-id="${item.product_id || item.id}">
                    <img src="${imageUrl}" alt="${item.name}" class="item-image">
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-artisan">by ${artisanName}</div>
                        <div class="item-quantity">Qty: ${item.quantity}</div>
                    </div>
                    <div class="item-price">${formatCurrency(itemTotal)}</div>
                </div>
            `;
        }).join('');
        
        updateOrderTotals(subtotal);
    }
    
    /**
     * Update order totals
     * @param {number} subtotal - Order subtotal
     */
    function updateOrderTotals(subtotal) {
        const shippingCost = getShippingCost();
        const tax = Math.round(subtotal * 0.05); // 5% tax
        const total = subtotal + shippingCost + tax;
        
        document.getElementById('subtotal').textContent = formatCurrency(subtotal);
        document.getElementById('shippingCost').textContent = formatCurrency(shippingCost);
        document.getElementById('tax').textContent = formatCurrency(tax);
        document.getElementById('totalAmount').textContent = formatCurrency(total);
        
        // Update mobile payment amount if it exists
        const mobilePayAmount = document.getElementById('mobilePayAmount');
        if (mobilePayAmount) {
            mobilePayAmount.textContent = formatCurrency(total);
        }
        
        orderTotal = total;
        
        // Enable/disable checkout button based on cart contents
        const checkoutBtn = document.getElementById('completeOrderBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = subtotal <= 0;
        }
    }
    
    /**
     * Get shipping cost based on selected method
     * @returns {number} Shipping cost
     */
    function getShippingCost() {
        const selectedShipping = document.querySelector('input[name="shipping"]:checked');
        if (!selectedShipping) return 120;
        
        switch(selectedShipping.value) {
            case 'express': return 250;
            case 'overnight': return 500;
            default: return 120;
        }
    }
    
    /**
     * Load user data into form
     */
    async function loadUserData() {
        try {
            const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
            if (!isLoggedIn) return;
            
            // Show loading state
            showNotification('Loading your saved information...', 'info');
            
            const response = await fetch('api/profile.php?action=profile');
            const data = await response.json();
            
            console.log('User profile data:', data);
            
            if (data.success) {
                const user = data.data;
                
                // Fill form with user data
                document.getElementById('email').value = user.email || '';
                document.getElementById('phone').value = user.phone || '';
                document.getElementById('firstName').value = user.first_name || '';
                document.getElementById('lastName').value = user.last_name || '';
                document.getElementById('address').value = user.address || '';
                document.getElementById('city').value = user.city || '';
                document.getElementById('postalCode').value = user.postal_code || '';
                
                // Set division if available
                const divisionSelect = document.getElementById('division');
                if (user.division && divisionSelect) {
                    for (let i = 0; i < divisionSelect.options.length; i++) {
                        if (divisionSelect.options[i].value === user.division) {
                            divisionSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
                
                // Trigger label animation for filled fields
                document.querySelectorAll('.form-group input, .form-group select').forEach(input => {
                    if (input.value) {
                        input.classList.add('has-value');
                    }
                });
                
                showNotification('Your information has been loaded', 'success');
            }
        } catch (error) {
            console.error('Error loading user data:', error);
            showNotification('Could not load your saved information', 'error');
        }
    }
    
    /**
     * Setup form event handlers
     */
    function setupFormHandlers() {
        const form = document.getElementById('paymentForm');
        
        // Form submission
        form.addEventListener('submit', handleFormSubmission);
        
        // Shipping method change
        const shippingInputs = document.querySelectorAll('input[name="shipping"]');
        shippingInputs.forEach(input => {
            input.addEventListener('change', () => {
                const subtotal = cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                updateOrderTotals(subtotal);
            });
        });
        
        // Payment method change
        const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
        paymentInputs.forEach(input => {
            input.addEventListener('change', handlePaymentMethodChange);
        });
        
        // Card number formatting
        const cardNumberInput = document.getElementById('cardNumber');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', formatCardNumber);
        }
        
        // Expiry date formatting
        const expiryInput = document.getElementById('expiryDate');
        if (expiryInput) {
            expiryInput.addEventListener('input', formatExpiryDate);
        }
        
        // Use saved address button
        const useSavedAddressBtn = document.getElementById('useSavedAddress');
        if (useSavedAddressBtn) {
            useSavedAddressBtn.addEventListener('click', loadUserData);
        }
        
        // Billing address checkbox
        const sameAsShippingCheckbox = document.getElementById('sameAsShipping');
        if (sameAsShippingCheckbox) {
            sameAsShippingCheckbox.addEventListener('change', handleBillingAddressChange);
        }
        
        // Add input event listeners for floating labels
        document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.add('has-value');
                } else {
                    this.classList.remove('has-value');
                }
            });
            
            // Initialize on load
            if (input.value) {
                input.classList.add('has-value');
            }
        });
    }
    
    /**
     * Handle payment method change
     * @param {Event} e - Change event
     */
    function handlePaymentMethodChange(e) {
        const cardDetails = document.getElementById('cardDetails');
        const mobileDetails = document.getElementById('mobileDetails');
        
        // Hide all payment details
        cardDetails.style.display = 'none';
        mobileDetails.style.display = 'none';
        
        // Show relevant payment details
        switch(e.target.value) {
            case 'card':
                cardDetails.style.display = 'block';
                break;
            case 'mobile':
                mobileDetails.style.display = 'block';
                
                // Update mobile payment amount
                const mobilePayAmount = document.getElementById('mobilePayAmount');
                if (mobilePayAmount) {
                    mobilePayAmount.textContent = document.getElementById('totalAmount').textContent;
                }
                break;
            case 'cash':
                // No additional details needed for cash on delivery
                break;
        }
    }
    
    /**
     * Format card number with spaces
     * @param {Event} e - Input event
     */
    function formatCardNumber(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
        if (formattedValue.length > 19) {
            formattedValue = formattedValue.substring(0, 19);
        }
        e.target.value = formattedValue;
    }
    
    /**
     * Format expiry date as MM/YY
     * @param {Event} e - Input event
     */
    function formatExpiryDate(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    }
    
    /**
     * Handle billing address checkbox change
     * @param {Event} e - Change event
     */
    function handleBillingAddressChange(e) {
        const billingAddressForm = document.getElementById('billingAddressForm');
        
        if (e.target.checked) {
            billingAddressForm.style.display = 'none';
        } else {
            billingAddressForm.style.display = 'block';
        }
    }
    
    /**
     * Validate form data
     * @returns {boolean} True if form is valid
     */
    function validateForm() {
        const requiredFields = [
            'email', 'phone', 'firstName', 'lastName', 
            'address', 'city', 'division', 'postalCode'
        ];
        
        for (let fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field || !field.value.trim()) {
                showNotification(`Please fill in the ${fieldId.replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
                field?.focus();
                return false;
            }
        }
        
        // Validate email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNotification('Please enter a valid email address', 'error');
            document.getElementById('email').focus();
            return false;
        }
        
        // Validate phone
        const phone = document.getElementById('phone').value;
        if (phone.length < 10) {
            showNotification('Please enter a valid phone number', 'error');
            document.getElementById('phone').focus();
            return false;
        }
        
        // Validate payment method specific fields
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'card') {
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('cardName').value;
            
            if (!cardNumber || cardNumber.length < 13) {
                showNotification('Please enter a valid card number', 'error');
                document.getElementById('cardNumber').focus();
                return false;
            }
            
            if (!expiryDate || !expiryDate.match(/^\d{2}\/\d{2}$/)) {
                showNotification('Please enter a valid expiry date (MM/YY)', 'error');
                document.getElementById('expiryDate').focus();
                return false;
            }
            
            if (!cvv || cvv.length < 3) {
                showNotification('Please enter a valid CVV', 'error');
                document.getElementById('cvv').focus();
                return false;
            }
            
            if (!cardName.trim()) {
                showNotification('Please enter the name on card', 'error');
                document.getElementById('cardName').focus();
                return false;
            }
        } else if (paymentMethod === 'mobile') {
            const mobileProvider = document.querySelector('input[name="mobile_provider"]:checked');
            const mobileNumber = document.getElementById('mobileNumber').value;
            const transactionId = document.getElementById('transactionId').value;
            
            if (!mobileProvider) {
                showNotification('Please select a mobile banking provider', 'error');
                return false;
            }
            
            if (!mobileNumber || mobileNumber.length < 10) {
                showNotification('Please enter a valid mobile number', 'error');
                document.getElementById('mobileNumber').focus();
                return false;
            }
            
            if (!transactionId) {
                showNotification('Please enter the transaction ID', 'error');
                document.getElementById('transactionId').focus();
                return false;
            }
        }
        
        // Validate billing address if different from shipping
        const sameAsShipping = document.getElementById('sameAsShipping').checked;
        if (!sameAsShipping) {
            const billingRequiredFields = [
                'billingFirstName', 'billingLastName', 'billingAddress', 
                'billingCity', 'billingDivision', 'billingPostalCode'
            ];
            
            for (let fieldId of billingRequiredFields) {
                const field = document.getElementById(fieldId);
                if (!field || !field.value.trim()) {
                    showNotification(`Please fill in the billing ${fieldId.replace('billing', '').replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
                    field?.focus();
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Handle form submission
     * @param {Event} e - Submit event
     */
    async function handleFormSubmission(e) {
        e.preventDefault();
        
        if (isProcessing) return;
        
        if (!validateForm()) return;
        
        if (cartData.length === 0) {
            showNotification('Your cart is empty', 'error');
            return;
        }
        
        isProcessing = true;
        const submitBtn = document.getElementById('completeOrderBtn');
        const btnText = submitBtn.querySelector('span');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'block';
        
        // Show processing overlay
        showProcessingOverlay('Processing Your Order', 'Please wait while we process your payment...');
        
        try {
            const orderData = collectOrderData();
            
            console.log('Submitting order data:', orderData);
            
            // Simulate network delay for better UX
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Update processing message
            updateProcessingMessage('Verifying Payment Information...');
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Get user ID from localStorage
            const userId = localStorage.getItem('user_id');
            
            // Add user ID to request headers and data
            const response = await fetch('api/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-User-Id': userId || ''
                },
                body: JSON.stringify({
                    ...orderData,
                    user_id: userId
                })
            });
            
            // Update processing message
            updateProcessingMessage('Finalizing Your Order...');
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Clone the response to allow multiple reads
            const responseClone = response.clone();
            
            // Check if response is valid JSON
            const contentType = response.headers.get('content-type');
            
            // Parse JSON response
            let result;
            try {
                // Try to parse as JSON first
                result = await response.json();
                console.log('Order submission result:', result);
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                
                // If JSON parsing fails, check content type
                if (!contentType || !contentType.includes('application/json')) {
                    // Not a JSON response, handle as error
                    const textResponse = await safelyReadResponseText(responseClone);
                    console.error('Non-JSON response:', textResponse);
                    
                    throw new Error('Server returned an invalid response format. Please try again later.');
                } else {
                    // It was supposed to be JSON but parsing failed
                    throw new Error('Failed to parse server response. Please try again later.');
                }
            }
            
            // Hide processing overlay
            hideProcessingOverlay();
            
            if (result.success) {
                // Show success animation
                showSuccessAnimation();
                
                // Update progress step
                updateProgressStep('confirmation');
                
                // Clear cart
                await clearCart();
                
                // Store order info in localStorage for confirmation page
                localStorage.setItem('last_order', JSON.stringify({
                    order_number: result.data.order_number,
                    order_date: new Date().toISOString(),
                    total_amount: orderTotal,
                    payment_method: orderData.payment_method,
                    shipping_method: orderData.shipping_method,
                    shipping_address: orderData.shipping_address,
                    order_items: cartData
                }));
                
                // Show success modal with order details
                setTimeout(() => {
                    showModal(
                        'Order Placed Successfully!', 
                        `Your order #${result.data.order_number} has been placed successfully. You will receive a confirmation email shortly.`,
                        'success',
                        () => {
                            window.location.href = `order-confirmation.html?order=${result.data.order_number}`;
                        }
                    );
                }, 2000);
                
            } else {
                showModal('Order Failed', result.message || 'Failed to place order. Please try again.', 'error');
            }
            
        } catch (error) {
            console.error('Order submission error:', error);
            hideProcessingOverlay();
            
            // Try to get more specific error information
            let errorMessage = 'An error occurred while placing your order.';
            let errorDetails = '';
            
            if (error.message && error.message.includes('body stream already read')) {
                // This is the specific error we're handling
                errorMessage = 'There was a problem processing your order.';
                errorDetails = 'The system encountered a technical issue. Please try again in a few moments.';
                
                // Log additional details for debugging
                console.error('Body stream already read error:', error);
                
                // Reload the page after a short delay to reset the state
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            } else if (error.message && (error.message.includes('JSON') || error.message.includes('parse'))) {
                // This is likely a JSON parsing error
                errorMessage = 'The server returned an invalid response.';
                errorDetails = 'This might be due to a temporary server issue. Please try again in a few moments.';
                
                // Log additional details for debugging
                console.error('JSON parse error details:', error);
            } else if (error.response) {
                // The request was made and the server responded with a status code
                // that falls out of the range of 2xx
                console.error('Error response:', error.response);
                errorMessage += ' Server responded with an error.';
            } else if (error.request) {
                // The request was made but no response was received
                console.error('Error request:', error.request);
                errorMessage += ' No response received from server.';
                errorDetails = 'Please check your internet connection and try again.';
            } else if (error.message) {
                // Something happened in setting up the request that triggered an Error
                console.error('Error message:', error.message);
                errorMessage += ' ' + error.message;
            }
            
            // Show a more detailed error message
            showModal('Order Error', errorMessage + (errorDetails ? '\n\n' + errorDetails : '') + '\n\nPlease try again.', 'error');
            
            // Also show notification for visibility
            showNotification(errorMessage, 'error');
        } finally {
            isProcessing = false;
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    }
    
    /**
     * Collect order data from form
     * @returns {Object} Order data
     */
    function collectOrderData() {
        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);
        
        const shippingCost = getShippingCost();
        const subtotal = cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = Math.round(subtotal * 0.05);
        
        // Collect shipping address with full address string for API compatibility
        const addressStr = formData.get('address');
        const apartmentStr = formData.get('apartment') ? formData.get('apartment') + ', ' : '';
        const cityStr = formData.get('city');
        const divisionStr = formData.get('division');
        const postalStr = formData.get('postalCode');
        
        // Create complete address string for API
        const fullAddressStr = `${addressStr}, ${apartmentStr}${cityStr}, ${divisionStr} ${postalStr}`;
        
        shippingAddress = {
            first_name: formData.get('firstName'),
            last_name: formData.get('lastName'),
            address: addressStr, // Individual field
            apartment: formData.get('apartment'),
            city: formData.get('city'),
            division: formData.get('division'),
            postal_code: formData.get('postalCode'),
            full_address: fullAddressStr // Full address string for API
        };
        
        // Collect billing address
        const sameAsShipping = document.getElementById('sameAsShipping').checked;
        if (sameAsShipping) {
            billingAddress = {...shippingAddress};
        } else {
            const billingAddressStr = formData.get('billingAddress');
            const billingCityStr = formData.get('billingCity');
            const billingDivisionStr = formData.get('billingDivision');
            const billingPostalStr = formData.get('billingPostalCode');
            
            // Create complete billing address string
            const fullBillingAddressStr = `${billingAddressStr}, ${billingCityStr}, ${billingDivisionStr} ${billingPostalStr}`;
            
            billingAddress = {
                first_name: formData.get('billingFirstName'),
                last_name: formData.get('billingLastName'),
                address: billingAddressStr,
                city: formData.get('billingCity'),
                division: formData.get('billingDivision'),
                postal_code: formData.get('billingPostalCode'),
                full_address: fullBillingAddressStr
            };
        }
        
        return {
            customer_info: {
                email: formData.get('email') || '',
                phone: formData.get('phone') || '',
                first_name: shippingAddress.first_name || '',
                last_name: shippingAddress.last_name || ''
            },
            shipping_address: {
                ...shippingAddress,
                address: fullAddressStr // Use full address string for API compatibility
            },
            billing_address: billingAddress,
            shipping_method: formData.get('shipping'),
            payment_method: formData.get('payment_method'),
            payment_details: collectPaymentDetails(formData),
            order_items: cartData,
            order_summary: {
                subtotal: subtotal,
                shipping_cost: shippingCost,
                tax: tax,
                total: orderTotal
            },
            order_notes: formData.get('orderNotes') || ''
        };
    }
    
    /**
     * Collect payment details based on method
     * @param {FormData} formData - Form data
     * @returns {Object} Payment details
     */
    function collectPaymentDetails(formData) {
        const paymentMethod = formData.get('payment_method');
        const timestamp = new Date().toISOString();
        
        switch(paymentMethod) {
            case 'card':
                const cardNumber = formData.get('cardNumber')?.replace(/\s/g, '') || '';
                const last4 = cardNumber.slice(-4);
                
                return {
                    card_number: cardNumber,
                    expiry_date: formData.get('expiryDate') || '',
                    cvv: formData.get('cvv') || '',
                    card_name: formData.get('cardName') || '',
                    card_type: detectCardType(cardNumber),
                    last_four_digits: last4,
                    payment_timestamp: timestamp
                };
            case 'mobile':
                return {
                    provider: formData.get('mobile_provider') || 'unknown',
                    mobile_number: formData.get('mobileNumber') || '',
                    transaction_id: formData.get('transactionId') || '',
                    payment_timestamp: timestamp
                };
            case 'cash':
                return {
                    delivery_instructions: 'Cash on delivery',
                    payment_timestamp: timestamp,
                    cod_reference: 'COD_' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0')
                };
            default:
                return {
                    payment_timestamp: timestamp
                };
        }
    }
    
    /**
     * Detect credit card type from number
     * @param {string} cardNumber - Card number
     * @returns {string} Card type
     */
    function detectCardType(cardNumber) {
        // Remove spaces and dashes
        cardNumber = cardNumber.replace(/[\s-]/g, '');
        
        // Visa
        if (/^4/.test(cardNumber)) {
            return 'visa';
        }
        
        // Mastercard
        if (/^(5[1-5]|2[2-7])/.test(cardNumber)) {
            return 'mastercard';
        }
        
        // American Express
        if (/^3[47]/.test(cardNumber)) {
            return 'amex';
        }
        
        // Discover
        if (/^(6011|65|64[4-9]|622)/.test(cardNumber)) {
            return 'discover';
        }
        
        return 'unknown';
    }
    
    /**
     * Safely read a response body as text
     * @param {Response} response - The fetch response
     * @returns {Promise<string>} The response text or error message
     */
    async function safelyReadResponseText(response) {
        try {
            // Always clone the response before reading
            const clonedResponse = response.clone();
            return await clonedResponse.text();
        } catch (error) {
            console.error('Error reading response text:', error);
            return 'Unable to read response body';
        }
    }
    
    /**
     * Clear cart after successful order
     */
    async function clearCart() {
        try {
            await fetch('api/cart.php', {
                method: 'DELETE'
            });
            
            // Update cart count in header
            if (typeof HandicraftsCart !== 'undefined' && HandicraftsCart.updateCartCount) {
                HandicraftsCart.updateCartCount();
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
        }
    }
    
    /**
     * Initialize payment page
     */
    function init() {
        // Check if user is logged in
        const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
        if (!isLoggedIn) {
            showModal('Login Required', 'Please log in to continue with checkout', 'warning', () => {
                window.location.href = 'login.html?redirect=payment.html';
            });
            return;
        }
        
        // Load data and setup handlers
        loadCartData();
        loadUserData();
        setupFormHandlers();
        
        // Set default payment method
        handlePaymentMethodChange({ target: { value: 'card' } });
    }
    
    /**
     * Update progress step
     * @param {string} step - Step to activate (cart, payment, confirmation)
     */
    function updateProgressStep(step) {
        const steps = document.querySelectorAll('.step');
        
        steps.forEach(stepEl => {
            stepEl.classList.remove('active');
            stepEl.classList.remove('completed');
        });
        
        let activeFound = false;
        
        steps.forEach(stepEl => {
            const stepId = stepEl.getAttribute('data-step') || stepEl.querySelector('.step-text').textContent.toLowerCase();
            
            if (stepId === step || stepId.includes(step)) {
                stepEl.classList.add('active');
                activeFound = true;
            } else if (!activeFound) {
                stepEl.classList.add('completed');
            }
        });
    }
    
    /**
     * Show processing overlay with loading animation
     * @param {string} title - Processing title
     * @param {string} message - Processing message
     */
    function showProcessingOverlay(title, message) {
        // Create overlay if it doesn't exist
        let overlay = document.getElementById('processingOverlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'processingOverlay';
            overlay.className = 'processing-overlay';
            
            overlay.innerHTML = `
                <div class="processing-container">
                    <div class="processing-loader">
                        <div class="processing-spinner"></div>
                    </div>
                    <h3 class="processing-title">Processing Your Order</h3>
                    <p class="processing-message">Please wait while we process your payment...</p>
                    <div class="processing-progress">
                        <div class="processing-progress-bar"></div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        // Update title and message
        const titleEl = overlay.querySelector('.processing-title');
        const messageEl = overlay.querySelector('.processing-message');
        
        if (title) titleEl.textContent = title;
        if (message) messageEl.textContent = message;
        
        // Show overlay with animation
        setTimeout(() => {
            overlay.classList.add('show');
        }, 10);
        
        // Start progress bar animation
        const progressBar = overlay.querySelector('.processing-progress-bar');
        progressBar.style.width = '0%';
        
        setTimeout(() => {
            progressBar.style.width = '30%';
        }, 300);
    }
    
    /**
     * Update processing message
     * @param {string} message - New processing message
     */
    function updateProcessingMessage(message) {
        const overlay = document.getElementById('processingOverlay');
        if (!overlay) return;
        
        const messageEl = overlay.querySelector('.processing-message');
        const progressBar = overlay.querySelector('.processing-progress-bar');
        
        // Update message with fade effect
        messageEl.style.opacity = '0';
        
        setTimeout(() => {
            messageEl.textContent = message;
            messageEl.style.opacity = '1';
        }, 300);
        
        // Update progress bar
        const currentWidth = parseInt(progressBar.style.width) || 30;
        const newWidth = Math.min(currentWidth + 30, 90);
        progressBar.style.width = `${newWidth}%`;
    }
    
    /**
     * Hide processing overlay
     */
    function hideProcessingOverlay() {
        const overlay = document.getElementById('processingOverlay');
        if (!overlay) return;
        
        // Complete progress bar
        const progressBar = overlay.querySelector('.processing-progress-bar');
        progressBar.style.width = '100%';
        
        // Hide overlay with animation
        setTimeout(() => {
            overlay.classList.remove('show');
            
            // Remove overlay after animation
            setTimeout(() => {
                overlay.remove();
            }, 500);
        }, 500);
    }
    
    // Public API
    return {
        init: init,
        showModal: showModal,
        closeModal: closeModal
    };
})();

// Global function for modal close button
function closeModal() {
    PaymentManager.closeModal();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.payment-container')) {
        PaymentManager.init();
    }
});