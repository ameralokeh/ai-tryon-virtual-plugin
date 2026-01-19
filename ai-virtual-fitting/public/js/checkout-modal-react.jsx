/**
 * React Checkout Modal Component
 * Modern, interactive checkout experience for AI Virtual Fitting credits
 */

const { useState, useEffect, useRef } = React;

const CheckoutModal = ({ isOpen, onClose, onSuccess }) => {
    const [step, setStep] = useState('loading'); // loading, form, processing, success, error, stripe_unavailable
    const [formData, setFormData] = useState({
        billing_first_name: '',
        billing_last_name: '',
        billing_email: '',
        billing_phone: '',
        billing_address_1: '',
        billing_city: '',
        billing_postcode: '',
        billing_country: 'US',
        billing_state: '',
        payment_method: 'stripe',
        card_number: '',
        card_expiry: '',
        card_cvc: ''
    });
    const [errors, setErrors] = useState({});
    const [isProcessing, setIsProcessing] = useState(false);
    const [cartTotal, setCartTotal] = useState('$10.00');
    const [stripeConfig, setStripeConfig] = useState(null);
    const [paymentRequest, setPaymentRequest] = useState(null);
    const [canMakePayment, setCanMakePayment] = useState(false);
    const [stripe, setStripe] = useState(null);
    const modalRef = useRef(null);
    const paymentRequestButtonRef = useRef(null);

    // Initialize checkout when modal opens
    useEffect(() => {
        if (isOpen) {
            setStep('loading');
            setErrors({});
            initializeCheckout();
        }
    }, [isOpen]);

    // Handle escape key
    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape' && isOpen) {
                onClose();
            }
        };
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [isOpen, onClose]);

    // Prevent body scroll when modal is open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);
    
    // Mount Payment Request Button when available
    useEffect(() => {
        if (paymentRequest && canMakePayment && step === 'form' && stripe) {
            const mountButton = async () => {
                try {
                    const elements = stripe.elements();
                    const prButton = elements.create('paymentRequestButton', {
                        paymentRequest: paymentRequest,
                    });
                    
                    // Check if button can be mounted
                    const container = document.getElementById('payment-request-button');
                    if (container && !paymentRequestButtonRef.current) {
                        await prButton.mount('#payment-request-button');
                        paymentRequestButtonRef.current = prButton;
                        console.log('Payment Request Button mounted successfully');
                    }
                } catch (error) {
                    console.error('Error mounting payment request button:', error);
                }
            };
            
            mountButton();
            
            return () => {
                if (paymentRequestButtonRef.current) {
                    paymentRequestButtonRef.current.unmount();
                    paymentRequestButtonRef.current = null;
                }
            };
        }
    }, [paymentRequest, canMakePayment, step, stripe]);

    const initializeCheckout = async () => {
        try {
            // Add credits to cart
            const cartResponse = await fetch(ai_virtual_fitting_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ai_virtual_fitting_add_credits_to_cart',
                    nonce: ai_virtual_fitting_ajax.nonce
                })
            });

            const cartResult = await cartResponse.json();
            
            if (cartResult.success) {
                setCartTotal(cartResult.data.cart_total_text || '$10.00');
                
                // Check Stripe availability
                const paymentMethods = cartResult.data.payment_methods;
                
                if (!paymentMethods || !paymentMethods.stripe_available) {
                    // Stripe is not configured - show setup instructions
                    setStripeConfig({
                        available: false,
                        error: paymentMethods?.error || 'Stripe is not configured',
                        setup_instructions: paymentMethods?.setup_instructions || []
                    });
                    setStep('stripe_unavailable');
                } else {
                    // Stripe is available - proceed to form
                    setStripeConfig({
                        available: true,
                        payment_method: paymentMethods.payment_method,
                        publishable_key: paymentMethods.stripe_publishable_key,
                        test_mode: paymentMethods.test_mode || false
                    });
                    // Set the correct Stripe gateway ID from backend
                    setFormData(prev => ({
                        ...prev,
                        payment_method: paymentMethods.payment_method.id
                    }));
                    
                    // Initialize Stripe and Payment Request for Apple Pay/Google Pay
                    if (paymentMethods.stripe_publishable_key) {
                        await initializeStripePaymentRequest(paymentMethods.stripe_publishable_key, cartResult.data.cart_total);
                    }
                    
                    setStep('form');
                }
            } else {
                // Handle cart conflicts or errors gracefully
                const errorData = cartResult.data || {};
                const errorMessage = errorData.message || 'Failed to initialize checkout';
                const errorCode = errorData.error_code || 'CHECKOUT_INIT_FAILED';
                
                // Check if this is a cart conflict that needs user confirmation
                if (errorData.cart_action_required === 'clear_cart') {
                    setStep('error');
                    setErrors({ 
                        general: errorMessage,
                        error_type: errorCode,
                        retry_allowed: errorData.retry_allowed !== false
                    });
                } else {
                    // Other errors - show error screen
                    setStep('error');
                    setErrors({ 
                        general: errorMessage,
                        error_type: errorCode,
                        retry_allowed: errorData.retry_allowed !== false
                    });
                }
            }
        } catch (error) {
            console.error('Checkout initialization error:', error);
            setStep('error');
            setErrors({ general: error.message });
        }
    };
    
    const initializeStripePaymentRequest = async (publishableKey, totalAmount) => {
        try {
            // Check if Stripe.js is loaded
            if (!window.Stripe) {
                console.warn('Stripe.js not loaded, express checkout unavailable');
                return;
            }
            
            // Initialize Stripe
            const stripeInstance = window.Stripe(publishableKey);
            setStripe(stripeInstance);
            
            // Parse total amount (remove $ and convert to cents)
            const amountInCents = Math.round(parseFloat(totalAmount) * 100);
            
            // Create payment request
            const pr = stripeInstance.paymentRequest({
                country: 'US',
                currency: 'usd',
                total: {
                    label: '20 Virtual Fitting Credits',
                    amount: amountInCents,
                },
                requestPayerName: true,
                requestPayerEmail: true,
            });
            
            // Check if browser supports Apple Pay or Google Pay
            const result = await pr.canMakePayment();
            if (result) {
                console.log('Express checkout available:', result);
                setPaymentRequest(pr);
                setCanMakePayment(true);
                
                // Handle payment method event
                pr.on('paymentmethod', async (ev) => {
                    console.log('Payment method received:', ev.paymentMethod.id);
                    setStep('processing');
                    
                    try {
                        // Process payment on backend
                        const response = await fetch(ai_virtual_fitting_ajax.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'ai_virtual_fitting_process_express_checkout',
                                nonce: ai_virtual_fitting_ajax.nonce,
                                payment_method_id: ev.paymentMethod.id,
                                payer_email: ev.payerEmail || '',
                                payer_name: ev.payerName || ''
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            ev.complete('success');
                            setStep('success');
                            setTimeout(() => {
                                onSuccess(result.data);
                            }, 2000);
                        } else {
                            ev.complete('fail');
                            setStep('error');
                            setErrors({ 
                                general: result.data.message || 'Payment failed',
                                error_type: result.data.error_code || 'payment_failed',
                                retry_allowed: true
                            });
                        }
                    } catch (error) {
                        console.error('Express checkout error:', error);
                        ev.complete('fail');
                        setStep('error');
                        setErrors({ 
                            general: 'Payment processing failed. Please try again.',
                            error_type: 'network_error',
                            retry_allowed: true
                        });
                    }
                });
            } else {
                console.log('Express checkout not available on this device/browser');
            }
        } catch (error) {
            console.error('Error initializing payment request:', error);
            // Don't show error to user, just disable express checkout
        }
    };

    const handleInputChange = (field, value) => {
        let formattedValue = value;
        
        // Format card number with spaces
        if (field === 'card_number') {
            formattedValue = value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
        }
        
        // Format expiry date as MM / YY
        if (field === 'card_expiry') {
            // Remove all non-digits
            const digitsOnly = value.replace(/\D/g, '');
            
            if (digitsOnly.length === 0) {
                formattedValue = '';
            } else if (digitsOnly.length <= 2) {
                formattedValue = digitsOnly;
            } else {
                // Add space-slash-space after first 2 digits
                formattedValue = digitsOnly.slice(0, 2) + ' / ' + digitsOnly.slice(2, 4);
            }
        }
        
        // Only allow digits for CVC
        if (field === 'card_cvc') {
            formattedValue = value.replace(/\D/g, '');
        }
        
        setFormData(prev => ({ ...prev, [field]: formattedValue }));
        // Clear field error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const validateForm = () => {
        const newErrors = {};
        
        if (!formData.billing_first_name.trim()) {
            newErrors.billing_first_name = 'First name is required';
        }
        if (!formData.billing_last_name.trim()) {
            newErrors.billing_last_name = 'Last name is required';
        }
        if (!formData.billing_email.trim()) {
            newErrors.billing_email = 'Email is required';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.billing_email)) {
            newErrors.billing_email = 'Please enter a valid email address';
        }
        if (!formData.billing_phone.trim()) {
            newErrors.billing_phone = 'Phone number is required';
        }
        if (!formData.billing_address_1.trim()) {
            newErrors.billing_address_1 = 'Address is required';
        }
        if (!formData.billing_city.trim()) {
            newErrors.billing_city = 'City is required';
        }
        if (!formData.billing_postcode.trim()) {
            newErrors.billing_postcode = 'Postal code is required';
        }
        
        // Stripe card validation
        if (!formData.card_number.trim()) {
            newErrors.card_number = 'Card number is required';
        } else {
            // Remove spaces and validate card number format
            const cardNumber = formData.card_number.replace(/\s/g, '');
            if (!/^\d{13,19}$/.test(cardNumber)) {
                newErrors.card_number = 'Please enter a valid card number';
            }
        }
        
        if (!formData.card_expiry.trim()) {
            newErrors.card_expiry = 'Expiry date is required';
        } else {
            // Validate expiry format (MM/YY or MM / YY)
            const expiry = formData.card_expiry.replace(/\s/g, '');
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                newErrors.card_expiry = 'Please enter a valid expiry date (MM/YY)';
            } else {
                // Check if expiry is in the future
                const [month, year] = expiry.split('/');
                const expiryDate = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const now = new Date();
                if (expiryDate < now) {
                    newErrors.card_expiry = 'Card has expired';
                }
            }
        }
        
        if (!formData.card_cvc.trim()) {
            newErrors.card_cvc = 'CVC is required';
        } else if (!/^\d{3,4}$/.test(formData.card_cvc)) {
            newErrors.card_cvc = 'Please enter a valid CVC (3-4 digits)';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        setIsProcessing(true);
        setStep('processing');

        try {
            const response = await fetch(ai_virtual_fitting_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ai_virtual_fitting_process_checkout',
                    nonce: ai_virtual_fitting_ajax.nonce,
                    ...formData
                })
            });

            const result = await response.json();
            
            if (result.success) {
                // Check if 3D Secure authentication is required
                if (result.data.requires_action && result.data.redirect_url) {
                    // Handle 3D Secure authentication
                    await handle3DSecure(result.data.redirect_url, result.data.order_id);
                } else {
                    // Payment successful without 3D Secure
                    setStep('success');
                    // Call success callback after a short delay
                    setTimeout(() => {
                        onSuccess(result.data);
                    }, 2000);
                }
            } else {
                // Handle payment errors
                handlePaymentError(result.data);
            }
        } catch (error) {
            console.error('Payment processing error:', error);
            setStep('error');
            setErrors({ 
                general: 'Network error. Please check your connection and try again.',
                error_type: 'network_error',
                retry_allowed: true
            });
        } finally {
            setIsProcessing(false);
        }
    };
    
    const handle3DSecure = async (redirectUrl, orderId) => {
        // Display 3D Secure authentication message
        setStep('3d_secure');
        
        try {
            // Open 3D Secure authentication in a popup or iframe
            // For now, we'll redirect to the authentication URL
            // In a production environment, you'd use Stripe's SDK for better UX
            window.location.href = redirectUrl;
        } catch (error) {
            console.error('3D Secure authentication error:', error);
            setStep('error');
            setErrors({ 
                general: '3D Secure authentication failed. Please try again or use a different card.',
                error_type: '3d_secure_failed',
                retry_allowed: true
            });
        }
    };
    
    const handlePaymentError = (errorData) => {
        setStep('error');
        
        // Determine error type and customize message
        let errorMessage = errorData.message || 'Payment failed. Please try again.';
        let retryAllowed = errorData.retry_allowed !== false;
        let errorType = errorData.error_code || 'unknown_error';
        
        // Stripe-specific error handling
        if (errorType.includes('card_declined')) {
            errorMessage = 'Your card was declined. Please check your card details or try a different card.';
            retryAllowed = false;
        } else if (errorType.includes('insufficient_funds')) {
            errorMessage = 'Your card has insufficient funds. Please use a different card.';
            retryAllowed = false;
        } else if (errorType.includes('expired_card')) {
            errorMessage = 'Your card has expired. Please use a different card.';
            retryAllowed = false;
        } else if (errorType.includes('incorrect_cvc')) {
            errorMessage = 'The CVC code is incorrect. Please check and try again.';
            retryAllowed = true;
        } else if (errorType.includes('invalid_number')) {
            errorMessage = 'The card number is invalid. Please check and try again.';
            retryAllowed = true;
        } else if (errorType.includes('processing_error')) {
            errorMessage = 'A processing error occurred. Please try again in a few moments.';
            retryAllowed = true;
        } else if (errorType.includes('rate_limit')) {
            errorMessage = 'Too many payment attempts. Please wait a few minutes and try again.';
            retryAllowed = true;
        }
        
        setErrors({ 
            general: errorMessage,
            error_type: errorType,
            retry_allowed: retryAllowed
        });
    };

    const handleRetry = () => {
        setStep('form');
        setErrors({});
    };

    const handleCreditCardCheckout = () => {
        // Redirect to WooCommerce checkout page
        window.location.href = ai_virtual_fitting_ajax.checkout_url;
    };

    if (!isOpen) return null;

    return (
        <div className="checkout-modal-overlay active" onClick={onClose}>
            <div 
                className="checkout-modal react-modal" 
                ref={modalRef}
                onClick={(e) => e.stopPropagation()}
            >
                <div className="checkout-modal-header">
                    <h3>Purchase 20 Credits - {cartTotal}</h3>
                    <button 
                        className="checkout-modal-close" 
                        onClick={onClose}
                        type="button"
                    >
                        <svg viewBox="0 0 24 24">
                            <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>

                <div className="checkout-modal-content">
                    {step === 'loading' && (
                        <div className="checkout-step loading-step">
                            <div className="checkout-spinner"></div>
                            <p>Preparing your checkout...</p>
                        </div>
                    )}

                    {step === 'form' && (
                        <div className="checkout-step form-step">
                            <div className="checkout-summary">
                                <div className="summary-item">
                                    <span>20 Virtual Fitting Credits</span>
                                    <span className="price">{cartTotal}</span>
                                </div>
                                <div className="summary-total">
                                    <span>Total</span>
                                    <span className="total-price">{cartTotal}</span>
                                </div>
                            </div>

                            <div className="payment-options-container">
                                <h4 className="payment-options-title">Choose Payment Method</h4>
                                
                                {/* Express Checkout Section - Apple Pay / Google Pay */}
                                {canMakePayment && paymentRequest && (
                                    <div className="express-checkout-section">
                                        <div id="payment-request-button" className="payment-request-button-container">
                                            {/* Stripe Payment Request Button will be mounted here */}
                                        </div>
                                    </div>
                                )}

                                {/* Credit Card Checkout Button */}
                                <div className="credit-card-checkout-section">
                                    {canMakePayment && paymentRequest && (
                                        <div className="divider-with-text">
                                            <span className="divider-line"></span>
                                            <span className="divider-text">or</span>
                                            <span className="divider-line"></span>
                                        </div>
                                    )}
                                    <button 
                                        type="button"
                                        className="btn btn-checkout-card"
                                        onClick={handleCreditCardCheckout}
                                    >
                                        <svg className="card-icon" viewBox="0 0 24 24" width="20" height="20">
                                            <path fill="currentColor" d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" />
                                        </svg>
                                        Checkout with Credit Card
                                    </button>
                                </div>
                            </div>

                            {errors.general && (
                                <div className="general-error">
                                    {errors.general}
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="checkout-form" style={{display: 'none'}}>
                                <div className="form-section">
                                    <h4>Billing Information</h4>
                                    
                                    <div className="form-row">
                                        <div className="form-field">
                                            <label>First Name *</label>
                                            <input
                                                type="text"
                                                value={formData.billing_first_name}
                                                onChange={(e) => handleInputChange('billing_first_name', e.target.value)}
                                                className={errors.billing_first_name ? 'error' : ''}
                                                placeholder="Enter your first name"
                                            />
                                            {errors.billing_first_name && (
                                                <span className="field-error">{errors.billing_first_name}</span>
                                            )}
                                        </div>
                                        <div className="form-field">
                                            <label>Last Name *</label>
                                            <input
                                                type="text"
                                                value={formData.billing_last_name}
                                                onChange={(e) => handleInputChange('billing_last_name', e.target.value)}
                                                className={errors.billing_last_name ? 'error' : ''}
                                                placeholder="Enter your last name"
                                            />
                                            {errors.billing_last_name && (
                                                <span className="field-error">{errors.billing_last_name}</span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="form-field">
                                        <label>Email Address *</label>
                                        <input
                                            type="email"
                                            value={formData.billing_email}
                                            onChange={(e) => handleInputChange('billing_email', e.target.value)}
                                            className={errors.billing_email ? 'error' : ''}
                                            placeholder="Enter your email address"
                                        />
                                        {errors.billing_email && (
                                            <span className="field-error">{errors.billing_email}</span>
                                        )}
                                    </div>

                                    <div className="form-field">
                                        <label>Phone Number *</label>
                                        <input
                                            type="tel"
                                            value={formData.billing_phone}
                                            onChange={(e) => handleInputChange('billing_phone', e.target.value)}
                                            className={errors.billing_phone ? 'error' : ''}
                                            placeholder="Enter your phone number"
                                        />
                                        {errors.billing_phone && (
                                            <span className="field-error">{errors.billing_phone}</span>
                                        )}
                                    </div>

                                    <div className="form-field">
                                        <label>Address *</label>
                                        <input
                                            type="text"
                                            value={formData.billing_address_1}
                                            onChange={(e) => handleInputChange('billing_address_1', e.target.value)}
                                            className={errors.billing_address_1 ? 'error' : ''}
                                            placeholder="Enter your street address"
                                        />
                                        {errors.billing_address_1 && (
                                            <span className="field-error">{errors.billing_address_1}</span>
                                        )}
                                    </div>

                                    <div className="form-row">
                                        <div className="form-field">
                                            <label>City *</label>
                                            <input
                                                type="text"
                                                value={formData.billing_city}
                                                onChange={(e) => handleInputChange('billing_city', e.target.value)}
                                                className={errors.billing_city ? 'error' : ''}
                                                placeholder="Enter your city"
                                            />
                                            {errors.billing_city && (
                                                <span className="field-error">{errors.billing_city}</span>
                                            )}
                                        </div>
                                        <div className="form-field">
                                            <label>Postal Code *</label>
                                            <input
                                                type="text"
                                                value={formData.billing_postcode}
                                                onChange={(e) => handleInputChange('billing_postcode', e.target.value)}
                                                className={errors.billing_postcode ? 'error' : ''}
                                                placeholder="Enter postal code"
                                            />
                                            {errors.billing_postcode && (
                                                <span className="field-error">{errors.billing_postcode}</span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="form-section">
                                    <h4>Payment Method</h4>
                                    <div className="payment-methods">
                                        <div className="payment-method active">
                                            <div className="payment-header">
                                                <div className="payment-icon">💳</div>
                                                <div className="payment-info">
                                                    <span className="payment-name">Credit Card</span>
                                                    <span className="payment-desc">Secure payment via Stripe</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {/* Stripe Card Input Fields */}
                                    <div className="credit-card-fields">
                                        <div className="form-field">
                                            <label>Card Number *</label>
                                            <input
                                                type="text"
                                                value={formData.card_number || ''}
                                                onChange={(e) => handleInputChange('card_number', e.target.value)}
                                                className={errors.card_number ? 'error' : ''}
                                                placeholder="1234 5678 9012 3456"
                                                maxLength="19"
                                            />
                                            {errors.card_number && (
                                                <span className="field-error">{errors.card_number}</span>
                                            )}
                                        </div>
                                        
                                        <div className="form-row">
                                            <div className="form-field">
                                                <label>Expiry Date *</label>
                                                <input
                                                    type="text"
                                                    value={formData.card_expiry || ''}
                                                    onChange={(e) => handleInputChange('card_expiry', e.target.value)}
                                                    className={errors.card_expiry ? 'error' : ''}
                                                    placeholder="MM / YY"
                                                    maxLength="7"
                                                />
                                                {errors.card_expiry && (
                                                    <span className="field-error">{errors.card_expiry}</span>
                                                )}
                                            </div>
                                            
                                            <div className="form-field">
                                                <label>CVC *</label>
                                                <input
                                                    type="text"
                                                    value={formData.card_cvc || ''}
                                                    onChange={(e) => handleInputChange('card_cvc', e.target.value)}
                                                    className={errors.card_cvc ? 'error' : ''}
                                                    placeholder="123"
                                                    maxLength="4"
                                                />
                                                {errors.card_cvc && (
                                                    <span className="field-error">{errors.card_cvc}</span>
                                                )}
                                            </div>
                                        </div>
                                        
                                        {/* Only show test card note if in test mode */}
                                        {stripeConfig?.test_mode && (
                                            <p className="test-card-note">
                                                <strong>Test Mode:</strong> Use card number 4242 4242 4242 4242 with any future expiry date and any 3-digit CVC.
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {errors.general && (
                                    <div className="general-error">
                                        {errors.general}
                                    </div>
                                )}

                                <div className="form-actions">
                                    <button 
                                        type="button" 
                                        className="btn btn-secondary"
                                        onClick={onClose}
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        type="submit" 
                                        className="btn btn-primary"
                                        disabled={isProcessing}
                                    >
                                        {isProcessing ? 'Processing...' : `Pay ${cartTotal}`}
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {step === 'processing' && (
                        <div className="checkout-step processing-step">
                            <div className="processing-animation">
                                <div className="processing-spinner"></div>
                                <div className="processing-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                            <h4>Processing Your Payment</h4>
                            <p>Please wait while we securely process your payment...</p>
                            <div className="processing-steps">
                                <div className="step completed">✓ Validating payment details</div>
                                <div className="step active">⏳ Processing payment</div>
                                <div className="step">⏳ Adding credits to account</div>
                            </div>
                        </div>
                    )}

                    {step === 'success' && (
                        <div className="checkout-step success-step">
                            <div className="success-animation">
                                <div className="success-checkmark">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                                    </svg>
                                </div>
                            </div>
                            <h4>Payment Successful!</h4>
                            <p>Your 20 credits have been added to your account.</p>
                            <div className="success-details">
                                <div className="success-item">
                                    <span>Credits Added:</span>
                                    <span className="highlight">20 Credits</span>
                                </div>
                                <div className="success-item">
                                    <span>Amount Paid:</span>
                                    <span className="highlight">{cartTotal}</span>
                                </div>
                            </div>
                            <p className="success-note">
                                You can now continue with virtual fitting. This modal will close automatically.
                            </p>
                        </div>
                    )}

                    {step === '3d_secure' && (
                        <div className="checkout-step processing-step">
                            <div className="processing-animation">
                                <div className="processing-spinner"></div>
                            </div>
                            <h4>3D Secure Authentication Required</h4>
                            <p>Please complete the authentication with your bank to proceed with the payment.</p>
                            <div className="processing-steps">
                                <div className="step completed">✓ Payment details verified</div>
                                <div className="step active">🔒 Authenticating with your bank</div>
                                <div className="step">⏳ Completing payment</div>
                            </div>
                            <p className="success-note">
                                You may be redirected to your bank's authentication page.
                            </p>
                        </div>
                    )}

                    {step === 'error' && (
                        <div className="checkout-step error-step">
                            <div className="error-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                                </svg>
                            </div>
                            <h4>Payment Failed</h4>
                            <p>{errors.general || 'There was an issue processing your payment.'}</p>
                            
                            {/* Show specific guidance based on error type */}
                            {errors.error_type && (
                                <div className="error-guidance">
                                    {errors.error_type.includes('card_declined') && (
                                        <p className="error-tip">💡 Your card issuer declined the transaction. Please contact your bank or try a different card.</p>
                                    )}
                                    {errors.error_type.includes('insufficient_funds') && (
                                        <p className="error-tip">💡 Please ensure your card has sufficient funds or try a different payment method.</p>
                                    )}
                                    {errors.error_type.includes('expired_card') && (
                                        <p className="error-tip">💡 Please check your card's expiry date or use a different card.</p>
                                    )}
                                    {errors.error_type.includes('incorrect_cvc') && (
                                        <p className="error-tip">💡 Please verify the 3-digit security code on the back of your card.</p>
                                    )}
                                    {errors.error_type.includes('invalid_number') && (
                                        <p className="error-tip">💡 Please check your card number for any typos.</p>
                                    )}
                                    {errors.error_type.includes('3d_secure') && (
                                        <p className="error-tip">💡 3D Secure authentication failed. Please try again or contact your bank.</p>
                                    )}
                                </div>
                            )}
                            
                            <div className="error-actions">
                                {errors.retry_allowed !== false ? (
                                    <>
                                        <button 
                                            className="btn btn-primary" 
                                            onClick={handleRetry}
                                        >
                                            Try Again
                                        </button>
                                        <button 
                                            className="btn btn-secondary" 
                                            onClick={onClose}
                                        >
                                            Cancel
                                        </button>
                                    </>
                                ) : (
                                    <button 
                                        className="btn btn-secondary" 
                                        onClick={onClose}
                                    >
                                        Close
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {step === 'stripe_unavailable' && (
                        <div className="checkout-step error-step">
                            <div className="error-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z"/>
                                </svg>
                            </div>
                            <h4>Stripe Payment Not Configured</h4>
                            <p>{stripeConfig?.error || 'Stripe payment gateway is not available.'}</p>
                            
                            {stripeConfig?.setup_instructions && stripeConfig.setup_instructions.length > 0 && (
                                <div className="setup-instructions">
                                    <h5>Setup Instructions for Administrators:</h5>
                                    <ol>
                                        {stripeConfig.setup_instructions.map((instruction, index) => (
                                            <li key={index}>{instruction}</li>
                                        ))}
                                    </ol>
                                </div>
                            )}
                            
                            <div className="error-actions">
                                <button 
                                    className="btn btn-secondary" 
                                    onClick={onClose}
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

// Export for use in main application
window.CheckoutModal = CheckoutModal;