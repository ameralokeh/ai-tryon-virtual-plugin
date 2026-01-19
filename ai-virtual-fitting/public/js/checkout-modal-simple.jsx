/**
 * Simplified React Checkout Modal Component
 * Clean payment method selection for AI Virtual Fitting credits
 */

const { useState, useEffect, useRef } = React;

const CheckoutModal = ({ isOpen, onClose, onSuccess }) => {
    const [step, setStep] = useState('loading'); // loading, selection, processing, success, error, cart_conflict
    const [cartTotal, setCartTotal] = useState('');
    const [creditsAmount, setCreditsAmount] = useState(20); // Dynamic credits amount
    const [stripeConfig, setStripeConfig] = useState(null);
    const [paymentRequest, setPaymentRequest] = useState(null);
    const [canMakePayment, setCanMakePayment] = useState(false);
    const [stripe, setStripe] = useState(null);
    const [errors, setErrors] = useState({});
    const [conflictMessage, setConflictMessage] = useState('');
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
        if (paymentRequest && canMakePayment && step === 'selection' && stripe) {
            const mountButton = async () => {
                try {
                    const elements = stripe.elements();
                    const prButton = elements.create('paymentRequestButton', {
                        paymentRequest: paymentRequest,
                    });
                    
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
            
            console.log('Cart initialization result:', cartResult);
            
            if (cartResult.success) {
                setCartTotal(cartResult.data.cart_total_text || '$10.00');
                setCreditsAmount(cartResult.data.credits_per_package || 20); // Set dynamic credits amount
                
                // Log if credits were already in cart (for debugging)
                if (cartResult.data.already_in_cart) {
                    console.log('ℹ️ Credits already in cart, proceeding to checkout');
                }
                
                // Check Stripe availability
                const paymentMethods = cartResult.data.payment_methods;
                
                if (!paymentMethods || !paymentMethods.stripe_available) {
                    // Stripe is not configured - show error
                    setStep('error');
                    setErrors({ 
                        general: paymentMethods?.error || 'Stripe is not configured',
                        retry_allowed: false
                    });
                } else {
                    // Stripe is available - proceed to selection
                    setStripeConfig({
                        available: true,
                        payment_method: paymentMethods.payment_method,
                        publishable_key: paymentMethods.stripe_publishable_key,
                        test_mode: paymentMethods.test_mode || false
                    });
                    
                    // Initialize Stripe and Payment Request for Apple Pay/Google Pay
                    if (paymentMethods.stripe_publishable_key) {
                        await initializeStripePaymentRequest(paymentMethods.stripe_publishable_key, cartResult.data.cart_total_text, cartResult.data.credits_per_package || 20);
                    }
                    
                    setStep('selection');
                }
            } else {
                // Backend returned error
                const errorData = cartResult.data || {};
                
                // Special handling for cart conflicts
                if (errorData.error_code === 'CART_CONFLICT_OTHER_PRODUCTS') {
                    // Show cart conflict screen instead of browser confirm
                    setConflictMessage(errorData.message);
                    setStep('cart_conflict');
                    return;
                }
                
                // Other errors - show error screen
                setStep('error');
                setErrors({ 
                    general: errorData.message || 'Failed to initialize checkout',
                    retry_allowed: errorData.retry_allowed !== false
                });
            }
        } catch (error) {
            console.error('Checkout initialization error:', error);
            setStep('error');
            setErrors({ 
                general: 'Failed to initialize checkout. Please try again.',
                retry_allowed: true
            });
        }
    };
    
    const initializeStripePaymentRequest = async (publishableKey, totalAmount, creditsAmount) => {
        try {
            console.log('🔵 Initializing Stripe Payment Request...');
            console.log('  - Publishable Key:', publishableKey ? `${publishableKey.substring(0, 20)}...` : 'MISSING');
            console.log('  - Total Amount:', totalAmount);
            console.log('  - Credits Amount:', creditsAmount);
            console.log('  - Browser:', navigator.userAgent);
            
            if (!window.Stripe) {
                console.error('❌ Stripe.js not loaded, express checkout unavailable');
                return;
            }
            
            if (!publishableKey) {
                console.error('❌ Stripe publishable key is missing');
                return;
            }
            
            const stripeInstance = window.Stripe(publishableKey);
            setStripe(stripeInstance);
            console.log('✅ Stripe instance created');
            
            // Parse total amount (remove $ and convert to cents)
            // Handle formats like "$10.00", "10.00", or "10"
            const cleanAmount = String(totalAmount).replace(/[^0-9.]/g, '');
            const amountInCents = Math.round(parseFloat(cleanAmount) * 100);
            console.log('  - Total amount string:', totalAmount);
            console.log('  - Cleaned amount:', cleanAmount);
            console.log('  - Amount in cents:', amountInCents);
            
            if (isNaN(amountInCents) || amountInCents <= 0) {
                console.error('❌ Invalid amount:', {totalAmount, cleanAmount, amountInCents});
                return;
            }
            
            // Create payment request
            const pr = stripeInstance.paymentRequest({
                country: 'US',
                currency: 'usd',
                total: {
                    label: `${creditsAmount} Virtual Fitting Credits`,
                    amount: amountInCents,
                },
                requestPayerName: true,
                requestPayerEmail: true,
                requestPayerPhone: true, // Request phone for better fraud prevention
                requestShipping: false, // Don't request shipping for digital products
            });
            console.log('✅ Payment Request created');
            
            // Check if browser supports Apple Pay or Google Pay
            console.log('🔍 Checking if browser supports Apple Pay or Google Pay...');
            const result = await pr.canMakePayment();
            console.log('  - canMakePayment result:', result);
            
            if (result) {
                console.log('✅ Express checkout available!');
                console.log('  - Apple Pay:', result.applePay ? 'YES' : 'NO');
                console.log('  - Google Pay:', result.googlePay ? 'YES' : 'NO');
                console.log('  - Link:', result.link ? 'YES' : 'NO');
                setPaymentRequest(pr);
                setCanMakePayment(true);
                
                // Handle payment method event
                pr.on('paymentmethod', async (ev) => {
                    console.log('Payment method received:', ev.paymentMethod.id);
                    console.log('Payer details:', {
                        name: ev.payerName,
                        email: ev.payerEmail,
                        phone: ev.payerPhone,
                        shippingAddress: ev.shippingAddress
                    });
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
                                payer_name: ev.payerName || '',
                                payer_phone: ev.payerPhone || '',
                                shipping_address: ev.shippingAddress ? JSON.stringify(ev.shippingAddress) : ''
                            })
                        });
                        
                        const result = await response.json();
                        
                        console.log('Backend response:', result);
                        
                        if (result.success) {
                            ev.complete('success');
                            setStep('success');
                            setTimeout(() => {
                                onSuccess(result.data);
                            }, 2000);
                        } else {
                            console.error('Payment failed:', result);
                            ev.complete('fail');
                            setStep('error');
                            setErrors({ 
                                general: result.data?.message || result.message || 'Payment failed',
                                retry_allowed: true
                            });
                        }
                    } catch (error) {
                        console.error('Express checkout error:', error);
                        ev.complete('fail');
                        setStep('error');
                        setErrors({ 
                            general: 'Payment processing failed. Please try again.',
                            retry_allowed: true
                        });
                    }
                });
            } else {
                console.log('❌ Express checkout not available on this device/browser');
                console.log('  Possible reasons:');
                console.log('  - Browser does not support Apple Pay or Google Pay');
                console.log('  - No wallet configured (no cards added to Apple Pay/Google Pay)');
                console.log('  - Using HTTP instead of HTTPS (localhost is OK)');
                console.log('  - Browser:', navigator.userAgent);
            }
        } catch (error) {
            console.error('❌ Error initializing payment request:', error);
        }
    };

    const handleCreditCardCheckout = () => {
        // Redirect to WooCommerce checkout page
        window.location.href = ai_virtual_fitting_ajax.checkout_url;
    };

    const handleClearCart = async () => {
        setStep('loading');
        
        try {
            console.log('Clearing cart...');
            const clearResponse = await fetch(ai_virtual_fitting_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ai_virtual_fitting_clear_cart',
                    nonce: ai_virtual_fitting_ajax.nonce
                })
            });
            
            const clearResult = await clearResponse.json();
            console.log('Cart clear result:', clearResult);
            
            if (clearResult.success) {
                // Retry initialization
                console.log('Cart cleared, retrying initialization...');
                await initializeCheckout();
            } else {
                setStep('error');
                setErrors({ 
                    general: clearResult.data?.message || 'Failed to clear cart. Please try again.',
                    retry_allowed: true
                });
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
            setStep('error');
            setErrors({ 
                general: 'Failed to clear cart. Please try again.',
                retry_allowed: true
            });
        }
    };

    const handleProceedWithExistingCart = () => {
        // Just proceed to checkout with existing cart
        window.location.href = ai_virtual_fitting_ajax.checkout_url;
    };

    const handleRetry = () => {
        setStep('loading');
        setErrors({});
        initializeCheckout();
    };

    if (!isOpen) return null;

    return (
        <div className="checkout-modal-overlay active" onClick={onClose}>
            <div 
                className="checkout-modal react-modal simple-modal" 
                ref={modalRef}
                onClick={(e) => e.stopPropagation()}
            >
                <div className="checkout-modal-header">
                    <h3>Purchase {creditsAmount} Credits{cartTotal ? ` - ${cartTotal}` : ''}</h3>
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

                    {step === 'selection' && (
                        <div className="checkout-step selection-step">
                            <div className="checkout-summary">
                                <div className="summary-item">
                                    <span>{creditsAmount} Virtual Fitting Credits</span>
                                    <span className="price">{cartTotal}</span>
                                </div>
                                <div className="summary-total">
                                    <span>Total</span>
                                    <span className="total-price">{cartTotal}</span>
                                </div>
                            </div>

                            <div className="payment-options-container">
                                <h4 className="payment-options-title">Choose Payment Method</h4>
                                
                                {/* Express Checkout - Apple Pay / Google Pay */}
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
                            <p>Your {creditsAmount} credits have been added to your account.</p>
                            <div className="success-details">
                                <div className="success-item">
                                    <span>Credits Added:</span>
                                    <span className="highlight">{creditsAmount} Credits</span>
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

                    {step === 'cart_conflict' && (
                        <div className="checkout-step cart-conflict-step">
                            <div className="conflict-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                                </svg>
                            </div>
                            <h4>Cart Contains Other Items</h4>
                            <p className="conflict-message">{conflictMessage}</p>
                            
                            <div className="conflict-actions">
                                <button 
                                    className="btn btn-primary" 
                                    onClick={handleClearCart}
                                >
                                    Clear Cart & Continue
                                </button>
                                <button 
                                    className="btn btn-secondary" 
                                    onClick={handleProceedWithExistingCart}
                                >
                                    Proceed to Checkout
                                </button>
                            </div>
                        </div>
                    )}

                    {step === 'error' && (
                        <div className="checkout-step error-step">
                            <div className="error-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"/>
                                </svg>
                            </div>
                            <h4>Checkout Error</h4>
                            <p className="error-message">{errors.general || 'There was an issue initializing checkout.'}</p>
                            
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
                </div>
            </div>
        </div>
    );
};

// Export for use in main application
window.CheckoutModal = CheckoutModal;
