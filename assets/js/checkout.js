// Checkout functionality for Fishing Gear Store

class CheckoutManager {
    constructor(app) {
        this.app = app;
        this.currentStep = 1;
        this.orderData = {
            shipping_address: {},
            billing_address: {},
            payment_method: 'credit_card',
            promotion_code: '',
            notes: ''
        };
    }

    async showCheckout() {
        // Check if user is logged in
        const authResponse = await fetch('api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (!authData.logged_in) {
            this.app.showNotification('Please login to proceed with checkout', 'warning');
            this.app.showLoginModal();
            return;
        }

        this.currentStep = 1;
        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4">Checkout</h2>
                        
                        <!-- Progress Steps -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="checkout-step ${this.currentStep >= 1 ? 'active' : ''}" id="step1">
                                        <i class="fas fa-shipping-fast fa-2x"></i>
                                        <p class="mt-2">Shipping</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="checkout-step ${this.currentStep >= 2 ? 'active' : ''}" id="step2">
                                        <i class="fas fa-credit-card fa-2x"></i>
                                        <p class="mt-2">Payment</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="checkout-step ${this.currentStep >= 3 ? 'active' : ''}" id="step3">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                        <p class="mt-2">Review</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="checkout-step ${this.currentStep >= 4 ? 'active' : ''}" id="step4">
                                        <i class="fas fa-receipt fa-2x"></i>
                                        <p class="mt-2">Complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="checkoutContent">
                            ${this.renderShippingStep()}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
    }

    renderShippingStep() {
        return `
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="shippingForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingFirstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="shippingFirstName" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingLastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="shippingLastName" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="shippingAddress" required>
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress2" class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control" id="shippingAddress2">
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="shippingCity" class="form-label">City</label>
                                        <input type="text" class="form-control" id="shippingCity" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="shippingState" class="form-label">State</label>
                                        <select class="form-control" id="shippingState" required>
                                            <option value="">Select State</option>
                                            <option value="AL">Alabama</option>
                                            <option value="AK">Alaska</option>
                                            <option value="AZ">Arizona</option>
                                            <option value="AR">Arkansas</option>
                                            <option value="CA">California</option>
                                            <option value="CO">Colorado</option>
                                            <option value="CT">Connecticut</option>
                                            <option value="DE">Delaware</option>
                                            <option value="FL">Florida</option>
                                            <option value="GA">Georgia</option>
                                            <option value="HI">Hawaii</option>
                                            <option value="ID">Idaho</option>
                                            <option value="IL">Illinois</option>
                                            <option value="IN">Indiana</option>
                                            <option value="IA">Iowa</option>
                                            <option value="KS">Kansas</option>
                                            <option value="KY">Kentucky</option>
                                            <option value="LA">Louisiana</option>
                                            <option value="ME">Maine</option>
                                            <option value="MD">Maryland</option>
                                            <option value="MA">Massachusetts</option>
                                            <option value="MI">Michigan</option>
                                            <option value="MN">Minnesota</option>
                                            <option value="MS">Mississippi</option>
                                            <option value="MO">Missouri</option>
                                            <option value="MT">Montana</option>
                                            <option value="NE">Nebraska</option>
                                            <option value="NV">Nevada</option>
                                            <option value="NH">New Hampshire</option>
                                            <option value="NJ">New Jersey</option>
                                            <option value="NM">New Mexico</option>
                                            <option value="NY">New York</option>
                                            <option value="NC">North Carolina</option>
                                            <option value="ND">North Dakota</option>
                                            <option value="OH">Ohio</option>
                                            <option value="OK">Oklahoma</option>
                                            <option value="OR">Oregon</option>
                                            <option value="PA">Pennsylvania</option>
                                            <option value="RI">Rhode Island</option>
                                            <option value="SC">South Carolina</option>
                                            <option value="SD">South Dakota</option>
                                            <option value="TN">Tennessee</option>
                                            <option value="TX">Texas</option>
                                            <option value="UT">Utah</option>
                                            <option value="VT">Vermont</option>
                                            <option value="VA">Virginia</option>
                                            <option value="WA">Washington</option>
                                            <option value="WV">West Virginia</option>
                                            <option value="WI">Wisconsin</option>
                                            <option value="WY">Wyoming</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="shippingZip" class="form-label">ZIP Code</label>
                                        <input type="text" class="form-control" id="shippingZip" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="shippingPhone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="shippingPhone" required>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="sameAsBilling">
                                    <label class="form-check-label" for="sameAsBilling">
                                        Use same address for billing
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body" id="orderSummary">
                            <!-- Order summary will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button class="btn btn-primary" onclick="checkoutManager.nextStep()">Continue to Payment</button>
                </div>
            </div>
        `;
    }

    renderPaymentStep() {
        return `
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="paymentForm">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="paymentMethod" 
                                               id="creditCard" value="credit_card" checked>
                                        <label class="form-check-label" for="creditCard">
                                            <i class="fas fa-credit-card me-2"></i>Credit Card
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="paymentMethod" 
                                               id="paypal" value="paypal">
                                        <label class="form-check-label" for="paypal">
                                            <i class="fab fa-paypal me-2"></i>PayPal
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="creditCardFields">
                                    <div class="mb-3">
                                        <label for="cardNumber" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="cardNumber" 
                                               placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="expiryDate" class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" id="expiryDate" 
                                                   placeholder="MM/YY" maxlength="5">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="cvv" 
                                                   placeholder="123" maxlength="4">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cardName" class="form-label">Name on Card</label>
                                        <input type="text" class="form-control" id="cardName">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="promotionCode" class="form-label">Promotion Code (Optional)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="promotionCode" 
                                               placeholder="Enter code">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="checkoutManager.applyPromotionCode()">Apply</button>
                                    </div>
                                    <div id="promotionMessage" class="mt-2"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="orderNotes" class="form-label">Order Notes (Optional)</label>
                                    <textarea class="form-control" id="orderNotes" rows="3" 
                                              placeholder="Special instructions for your order"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body" id="orderSummary">
                            <!-- Order summary will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" onclick="checkoutManager.previousStep()">Back</button>
                    <button class="btn btn-primary" onclick="checkoutManager.nextStep()">Review Order</button>
                </div>
            </div>
        `;
    }

    renderReviewStep() {
        return `
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-check-circle me-2"></i>Review Your Order</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Shipping Address</h6>
                                    <p id="shippingAddressReview"></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Payment Method</h6>
                                    <p id="paymentMethodReview"></p>
                                </div>
                            </div>
                            
                            <h6>Order Items</h6>
                            <div id="orderItemsReview">
                                <!-- Order items will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Final Total</h5>
                        </div>
                        <div class="card-body" id="finalOrderSummary">
                            <!-- Final order summary will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" onclick="checkoutManager.previousStep()">Back</button>
                    <button class="btn btn-success btn-lg" onclick="checkoutManager.placeOrder()">
                        <i class="fas fa-lock me-2"></i>Place Order
                    </button>
                </div>
            </div>
        `;
    }

    renderCompleteStep(orderId) {
        return `
            <div class="text-center">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h2 class="text-success mb-3">Order Placed Successfully!</h2>
                        <p class="lead">Thank you for your order. Your order number is: <strong>#${orderId}</strong></p>
                        <p>You will receive an email confirmation shortly.</p>
                        
                        <div class="mt-4">
                            <button class="btn btn-primary me-3" onclick="checkoutManager.downloadReceipt(${orderId})">
                                <i class="fas fa-download me-2"></i>Download Receipt
                            </button>
                            <button class="btn btn-outline-primary" onclick="app.showProducts()">
                                Continue Shopping
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async loadOrderSummary() {
        try {
            const response = await fetch('api/cart.php?action=get');
            const data = await response.json();
            
            if (data.success) {
                const subtotal = data.total;
                const tax = subtotal * 0.08;
                const shipping = subtotal >= 75 ? 0 : 9.99;
                const discount = this.orderData.discount_amount || 0;
                const total = subtotal + tax + shipping - discount;
                
                const summaryHtml = `
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>$${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Tax (8%):</span>
                        <span>$${tax.toFixed(2)}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Shipping:</span>
                        <span>${shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`}</span>
                    </div>
                    ${discount > 0 ? `
                    <div class="d-flex justify-content-between text-success">
                        <span>Discount:</span>
                        <span>-$${discount.toFixed(2)}</span>
                    </div>
                    ` : ''}
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span>$${total.toFixed(2)}</span>
                    </div>
                `;
                
                document.getElementById('orderSummary').innerHTML = summaryHtml;
                document.getElementById('finalOrderSummary').innerHTML = summaryHtml;
            }
        } catch (error) {
            console.error('Failed to load order summary:', error);
        }
    }

    async nextStep() {
        if (this.currentStep === 1) {
            if (!this.validateShippingForm()) {
                return;
            }
            this.saveShippingData();
            this.currentStep = 2;
            document.getElementById('checkoutContent').innerHTML = this.renderPaymentStep();
            this.loadOrderSummary();
        } else if (this.currentStep === 2) {
            if (!this.validatePaymentForm()) {
                return;
            }
            this.savePaymentData();
            this.currentStep = 3;
            document.getElementById('checkoutContent').innerHTML = this.renderReviewStep();
            this.loadReviewData();
        } else if (this.currentStep === 3) {
            await this.placeOrder();
        }
        
        this.updateProgressSteps();
    }

    previousStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            
            if (this.currentStep === 1) {
                document.getElementById('checkoutContent').innerHTML = this.renderShippingStep();
            } else if (this.currentStep === 2) {
                document.getElementById('checkoutContent').innerHTML = this.renderPaymentStep();
                this.loadOrderSummary();
            }
            
            this.updateProgressSteps();
        }
    }

    validateShippingForm() {
        const requiredFields = ['shippingFirstName', 'shippingLastName', 'shippingAddress', 
                               'shippingCity', 'shippingState', 'shippingZip', 'shippingPhone'];
        
        for (const field of requiredFields) {
            const element = document.getElementById(field);
            if (!element.value.trim()) {
                this.app.showNotification(`Please fill in ${field.replace('shipping', '').toLowerCase()}`, 'danger');
                element.focus();
                return false;
            }
        }
        
        return true;
    }

    validatePaymentForm() {
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        
        if (paymentMethod === 'credit_card') {
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('cardName').value;
            
            if (!cardNumber || cardNumber.length < 13) {
                this.app.showNotification('Please enter a valid card number', 'danger');
                return false;
            }
            
            if (!expiryDate || !/^\d{2}\/\d{2}$/.test(expiryDate)) {
                this.app.showNotification('Please enter a valid expiry date (MM/YY)', 'danger');
                return false;
            }
            
            if (!cvv || cvv.length < 3) {
                this.app.showNotification('Please enter a valid CVV', 'danger');
                return false;
            }
            
            if (!cardName.trim()) {
                this.app.showNotification('Please enter the name on card', 'danger');
                return false;
            }
        }
        
        return true;
    }

    saveShippingData() {
        this.orderData.shipping_address = {
            first_name: document.getElementById('shippingFirstName').value,
            last_name: document.getElementById('shippingLastName').value,
            address_line_1: document.getElementById('shippingAddress').value,
            address_line_2: document.getElementById('shippingAddress2').value,
            city: document.getElementById('shippingCity').value,
            state: document.getElementById('shippingState').value,
            zip_code: document.getElementById('shippingZip').value,
            phone: document.getElementById('shippingPhone').value
        };
        
        // If same as billing is checked, copy shipping to billing
        if (document.getElementById('sameAsBilling').checked) {
            this.orderData.billing_address = { ...this.orderData.shipping_address };
        }
    }

    savePaymentData() {
        this.orderData.payment_method = document.querySelector('input[name="paymentMethod"]:checked').value;
        this.orderData.promotion_code = document.getElementById('promotionCode').value;
        this.orderData.notes = document.getElementById('orderNotes').value;
    }

    async applyPromotionCode() {
        const code = document.getElementById('promotionCode').value.trim();
        if (!code) {
            this.app.showNotification('Please enter a promotion code', 'warning');
            return;
        }
        
        try {
            const response = await fetch('api/orders.php?action=validate_promotion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    order_amount: await this.getCartTotal()
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.orderData.discount_amount = data.discount;
                this.app.showNotification('Promotion code applied successfully!', 'success');
                document.getElementById('promotionMessage').innerHTML = 
                    `<div class="alert alert-success">Discount: $${data.discount.toFixed(2)}</div>`;
                this.loadOrderSummary();
            } else {
                this.app.showNotification(data.message, 'danger');
                document.getElementById('promotionMessage').innerHTML = 
                    `<div class="alert alert-danger">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Failed to apply promotion code:', error);
            this.app.showNotification('Failed to apply promotion code', 'danger');
        }
    }

    async getCartTotal() {
        try {
            const response = await fetch('api/cart.php?action=get');
            const data = await response.json();
            return data.success ? data.total : 0;
        } catch (error) {
            return 0;
        }
    }

    async loadReviewData() {
        // Load shipping address review
        const shipping = this.orderData.shipping_address;
        document.getElementById('shippingAddressReview').innerHTML = `
            ${shipping.first_name} ${shipping.last_name}<br>
            ${shipping.address_line_1}<br>
            ${shipping.address_line_2 ? shipping.address_line_2 + '<br>' : ''}
            ${shipping.city}, ${shipping.state} ${shipping.zip_code}<br>
            ${shipping.phone}
        `;
        
        // Load payment method review
        const paymentMethod = this.orderData.payment_method;
        document.getElementById('paymentMethodReview').innerHTML = 
            paymentMethod === 'credit_card' ? 'Credit Card ending in ****' : 'PayPal';
        
        // Load order items
        try {
            const response = await fetch('api/cart.php?action=get');
            const data = await response.json();
            
            if (data.success) {
                let itemsHtml = '';
                data.items.forEach(item => {
                    itemsHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>${item.name}</strong><br>
                                <small class="text-muted">Qty: ${item.quantity}</small>
                            </div>
                            <span>$${(item.quantity * item.price).toFixed(2)}</span>
                        </div>
                    `;
                });
                document.getElementById('orderItemsReview').innerHTML = itemsHtml;
            }
        } catch (error) {
            console.error('Failed to load order items:', error);
        }
        
        this.loadOrderSummary();
    }

    async placeOrder() {
        try {
            const response = await fetch('api/orders.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...this.orderData,
                    csrf_token: this.app.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentStep = 4;
                document.getElementById('checkoutContent').innerHTML = this.renderCompleteStep(data.order_id);
                this.updateProgressSteps();
                this.app.loadCartCount();
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to place order:', error);
            this.app.showNotification('Failed to place order', 'danger');
        }
    }

    async downloadReceipt(orderId) {
        try {
            const response = await fetch(`api/orders.php?action=receipt&id=${orderId}`);
            const data = await response.json();
            
            if (data.success) {
                // Create a new window with the receipt
                const receiptWindow = window.open('', '_blank');
                receiptWindow.document.write(data.receipt);
                receiptWindow.document.close();
                
                // Trigger print dialog
                receiptWindow.print();
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to download receipt:', error);
            this.app.showNotification('Failed to download receipt', 'danger');
        }
    }

    updateProgressSteps() {
        for (let i = 1; i <= 4; i++) {
            const step = document.getElementById(`step${i}`);
            if (step) {
                step.classList.toggle('active', i <= this.currentStep);
            }
        }
    }
}

// Initialize checkout manager
const checkoutManager = new CheckoutManager(app);

// Add checkout styles
const checkoutStyles = `
    <style>
        .checkout-step {
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .checkout-step.active {
            background: var(--primary-color);
            color: white;
        }
        
        .checkout-step i {
            margin-bottom: 10px;
        }
        
        #creditCardFields {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .form-check-input:checked + .form-check-label {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
`;

// Add styles to head
document.head.insertAdjacentHTML('beforeend', checkoutStyles);