/**
 * React Checkout Modal Component
 * Modern, interactive checkout experience for AI Virtual Fitting credits
 */

const { useState, useEffect, useRef } = React;

const CheckoutModal = ({ isOpen, onClose, onSuccess }) => {
    const [step, setStep] = useState('loading'); // loading, form, processing, success, error
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
        payment_method: 'stripe'
    });
    const [errors, setErrors] = useState({});
    const [isProcessing, setIsProcessing] = useState(false);
    const [cartTotal, setCartTotal] = useState('$10.00');
    const modalRef = useRef(null);

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
                setCartTotal(cartResult.data.cart_total || '$10.00');
                setStep('form');
            } else {
                throw new Error(cartResult.data.message || 'Failed to initialize checkout');
            }
        } catch (error) {
            console.error('Checkout initialization error:', error);
            setStep('error');
            setErrors({ general: error.message });
        }
    };

    const handleInputChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
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
                setStep('success');
                // Call success callback after a short delay
                setTimeout(() => {
                    onSuccess(result.data);
                }, 2000);
            } else {
                setStep('error');
                setErrors({ general: result.data.message || 'Payment failed. Please try again.' });
            }
        } catch (error) {
            console.error('Payment processing error:', error);
            setStep('error');
            setErrors({ general: 'Network error. Please check your connection and try again.' });
        } finally {
            setIsProcessing(false);
        }
    };

    const handleRetry = () => {
        setStep('form');
        setErrors({});
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
                    <h3>Purchase 20 Credits - $10.00</h3>
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

                            <form onSubmit={handleSubmit} className="checkout-form">
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
                                            <input
                                                type="radio"
                                                id="stripe"
                                                name="payment_method"
                                                value="stripe"
                                                checked={formData.payment_method === 'stripe'}
                                                onChange={(e) => handleInputChange('payment_method', e.target.value)}
                                            />
                                            <label htmlFor="stripe">
                                                <div className="payment-icon">💳</div>
                                                <div className="payment-info">
                                                    <span className="payment-name">Credit Card</span>
                                                    <span className="payment-desc">Secure payment via Stripe</span>
                                                </div>
                                            </label>
                                        </div>
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

                    {step === 'error' && (
                        <div className="checkout-step error-step">
                            <div className="error-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                                </svg>
                            </div>
                            <h4>Payment Failed</h4>
                            <p>{errors.general || 'There was an issue processing your payment.'}</p>
                            <div className="error-actions">
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