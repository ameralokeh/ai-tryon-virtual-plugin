/**
 * React Checkout Modal Component (Compiled)
 * Modern, interactive checkout experience for AI Virtual Fitting credits
 */

(function() {
    'use strict';
    
    // Ensure React is available
    if (typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
        console.error('React or ReactDOM not available for CheckoutModal component');
        return;
    }
    
    const { useState, useEffect, useRef } = React;
    
    const CheckoutModal = function(props) {
        const { isOpen, onClose, onSuccess } = props;
        
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
            payment_method: '' // Will be set dynamically from available methods
        });
        const [errors, setErrors] = useState({});
        const [isProcessing, setIsProcessing] = useState(false);
        const [cartTotal, setCartTotal] = useState('$10.00');
        const [paymentMethods, setPaymentMethods] = useState([]); // Store available payment methods
        const modalRef = useRef(null);

        // Initialize checkout when modal opens
        useEffect(function() {
            if (isOpen) {
                setStep('loading');
                setErrors({});
                initializeCheckout();
            }
        }, [isOpen]);

        // Handle escape key
        useEffect(function() {
            const handleEscape = function(e) {
                if (e.key === 'Escape' && isOpen) {
                    onClose();
                }
            };
            document.addEventListener('keydown', handleEscape);
            return function() {
                document.removeEventListener('keydown', handleEscape);
            };
        }, [isOpen, onClose]);

        // Prevent body scroll when modal is open
        useEffect(function() {
            if (isOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
            return function() {
                document.body.style.overflow = '';
            };
        }, [isOpen]);

        const initializeCheckout = async function() {
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
                    
                    // Set available payment methods
                    const methods = cartResult.data.payment_methods || [];
                    setPaymentMethods(methods);
                    
                    // Set default payment method to first available
                    if (methods.length > 0) {
                        setFormData(function(prev) {
                            const newData = Object.assign({}, prev);
                            newData.payment_method = methods[0].id;
                            return newData;
                        });
                    }
                    
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

        const handleInputChange = function(field, value) {
            setFormData(function(prev) {
                const newData = Object.assign({}, prev);
                newData[field] = value;
                return newData;
            });
            
            // Clear field error when user starts typing
            if (errors[field]) {
                setErrors(function(prev) {
                    const newErrors = Object.assign({}, prev);
                    newErrors[field] = '';
                    return newErrors;
                });
            }
        };

        const validateForm = function() {
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
            if (!formData.payment_method.trim()) {
                newErrors.payment_method = 'Please select a payment method';
            }
            
            // Validate credit card fields if test_credit_card is selected
            if (formData.payment_method === 'test_credit_card') {
                if (!formData['test_credit_card-card-number'] || !formData['test_credit_card-card-number'].trim()) {
                    newErrors['test_credit_card-card-number'] = 'Card number is required';
                } else {
                    const cardNumber = formData['test_credit_card-card-number'].replace(/\s/g, '');
                    if (cardNumber.length < 13 || cardNumber.length > 19) {
                        newErrors['test_credit_card-card-number'] = 'Please enter a valid card number';
                    }
                }
                
                if (!formData['test_credit_card-card-expiry'] || !formData['test_credit_card-card-expiry'].trim()) {
                    newErrors['test_credit_card-card-expiry'] = 'Expiry date is required';
                } else if (!/^\d{2}\/\d{2}$/.test(formData['test_credit_card-card-expiry'])) {
                    newErrors['test_credit_card-card-expiry'] = 'Please enter expiry as MM/YY';
                }
                
                if (!formData['test_credit_card-card-cvc'] || !formData['test_credit_card-card-cvc'].trim()) {
                    newErrors['test_credit_card-card-cvc'] = 'CVC is required';
                } else if (formData['test_credit_card-card-cvc'].length < 3) {
                    newErrors['test_credit_card-card-cvc'] = 'CVC must be at least 3 digits';
                }
            }

            setErrors(newErrors);
            return Object.keys(newErrors).length === 0;
        };

        const handleSubmit = async function(e) {
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
                    body: new URLSearchParams(Object.assign({
                        action: 'ai_virtual_fitting_process_checkout',
                        nonce: ai_virtual_fitting_ajax.nonce
                    }, formData))
                });

                const result = await response.json();
                
                if (result.success) {
                    setStep('success');
                    // Call success callback after a short delay
                    setTimeout(function() {
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

        const handleRetry = function() {
            setStep('form');
            setErrors({});
        };

        if (!isOpen) return null;

        return React.createElement('div', {
            className: 'checkout-modal-overlay active',
            onClick: onClose
        }, React.createElement('div', {
            className: 'checkout-modal react-modal',
            ref: modalRef,
            onClick: function(e) { e.stopPropagation(); }
        }, [
            React.createElement('div', {
                key: 'header',
                className: 'checkout-modal-header'
            }, [
                React.createElement('h3', { key: 'title' }, 'Purchase 20 Credits - $10.00'),
                React.createElement('button', {
                    key: 'close',
                    className: 'checkout-modal-close',
                    onClick: onClose,
                    type: 'button'
                }, React.createElement('svg', {
                    viewBox: '0 0 24 24'
                }, React.createElement('path', {
                    d: 'M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z'
                })))
            ]),
            
            React.createElement('div', {
                key: 'content',
                className: 'checkout-modal-content'
            }, [
                // Loading Step
                step === 'loading' && React.createElement('div', {
                    key: 'loading',
                    className: 'checkout-step loading-step'
                }, [
                    React.createElement('div', { key: 'spinner', className: 'checkout-spinner' }),
                    React.createElement('p', { key: 'text' }, 'Preparing your checkout...')
                ]),

                // Form Step
                step === 'form' && React.createElement('div', {
                    key: 'form',
                    className: 'checkout-step form-step'
                }, [
                    React.createElement('div', {
                        key: 'summary',
                        className: 'checkout-summary'
                    }, [
                        React.createElement('div', {
                            key: 'item',
                            className: 'summary-item'
                        }, [
                            React.createElement('span', { key: 'label' }, '20 Virtual Fitting Credits'),
                            React.createElement('span', { key: 'price', className: 'price' }, cartTotal)
                        ]),
                        React.createElement('div', {
                            key: 'total',
                            className: 'summary-total'
                        }, [
                            React.createElement('span', { key: 'label' }, 'Total'),
                            React.createElement('span', { key: 'price', className: 'total-price' }, cartTotal)
                        ])
                    ]),

                    React.createElement('form', {
                        key: 'form',
                        onSubmit: handleSubmit,
                        className: 'checkout-form'
                    }, [
                        React.createElement('div', {
                            key: 'billing',
                            className: 'form-section'
                        }, [
                            React.createElement('h4', { key: 'title' }, 'Billing Information'),
                            
                            React.createElement('div', {
                                key: 'name-row',
                                className: 'form-row'
                            }, [
                                React.createElement('div', {
                                    key: 'first-name',
                                    className: 'form-field'
                                }, [
                                    React.createElement('label', { key: 'label' }, 'First Name *'),
                                    React.createElement('input', {
                                        key: 'input',
                                        type: 'text',
                                        value: formData.billing_first_name,
                                        onChange: function(e) { handleInputChange('billing_first_name', e.target.value); },
                                        className: errors.billing_first_name ? 'error' : '',
                                        placeholder: 'Enter your first name'
                                    }),
                                    errors.billing_first_name && React.createElement('span', {
                                        key: 'error',
                                        className: 'field-error'
                                    }, errors.billing_first_name)
                                ]),
                                React.createElement('div', {
                                    key: 'last-name',
                                    className: 'form-field'
                                }, [
                                    React.createElement('label', { key: 'label' }, 'Last Name *'),
                                    React.createElement('input', {
                                        key: 'input',
                                        type: 'text',
                                        value: formData.billing_last_name,
                                        onChange: function(e) { handleInputChange('billing_last_name', e.target.value); },
                                        className: errors.billing_last_name ? 'error' : '',
                                        placeholder: 'Enter your last name'
                                    }),
                                    errors.billing_last_name && React.createElement('span', {
                                        key: 'error',
                                        className: 'field-error'
                                    }, errors.billing_last_name)
                                ])
                            ]),

                            React.createElement('div', {
                                key: 'email',
                                className: 'form-field'
                            }, [
                                React.createElement('label', { key: 'label' }, 'Email Address *'),
                                React.createElement('input', {
                                    key: 'input',
                                    type: 'email',
                                    value: formData.billing_email,
                                    onChange: function(e) { handleInputChange('billing_email', e.target.value); },
                                    className: errors.billing_email ? 'error' : '',
                                    placeholder: 'Enter your email address'
                                }),
                                errors.billing_email && React.createElement('span', {
                                    key: 'error',
                                    className: 'field-error'
                                }, errors.billing_email)
                            ]),

                            React.createElement('div', {
                                key: 'phone',
                                className: 'form-field'
                            }, [
                                React.createElement('label', { key: 'label' }, 'Phone Number *'),
                                React.createElement('input', {
                                    key: 'input',
                                    type: 'tel',
                                    value: formData.billing_phone,
                                    onChange: function(e) { handleInputChange('billing_phone', e.target.value); },
                                    className: errors.billing_phone ? 'error' : '',
                                    placeholder: 'Enter your phone number'
                                }),
                                errors.billing_phone && React.createElement('span', {
                                    key: 'error',
                                    className: 'field-error'
                                }, errors.billing_phone)
                            ]),

                            React.createElement('div', {
                                key: 'address',
                                className: 'form-field'
                            }, [
                                React.createElement('label', { key: 'label' }, 'Address *'),
                                React.createElement('input', {
                                    key: 'input',
                                    type: 'text',
                                    value: formData.billing_address_1,
                                    onChange: function(e) { handleInputChange('billing_address_1', e.target.value); },
                                    className: errors.billing_address_1 ? 'error' : '',
                                    placeholder: 'Enter your street address'
                                }),
                                errors.billing_address_1 && React.createElement('span', {
                                    key: 'error',
                                    className: 'field-error'
                                }, errors.billing_address_1)
                            ]),

                            React.createElement('div', {
                                key: 'city-postal-row',
                                className: 'form-row'
                            }, [
                                React.createElement('div', {
                                    key: 'city',
                                    className: 'form-field'
                                }, [
                                    React.createElement('label', { key: 'label' }, 'City *'),
                                    React.createElement('input', {
                                        key: 'input',
                                        type: 'text',
                                        value: formData.billing_city,
                                        onChange: function(e) { handleInputChange('billing_city', e.target.value); },
                                        className: errors.billing_city ? 'error' : '',
                                        placeholder: 'Enter your city'
                                    }),
                                    errors.billing_city && React.createElement('span', {
                                        key: 'error',
                                        className: 'field-error'
                                    }, errors.billing_city)
                                ]),
                                React.createElement('div', {
                                    key: 'postal',
                                    className: 'form-field'
                                }, [
                                    React.createElement('label', { key: 'label' }, 'Postal Code *'),
                                    React.createElement('input', {
                                        key: 'input',
                                        type: 'text',
                                        value: formData.billing_postcode,
                                        onChange: function(e) { handleInputChange('billing_postcode', e.target.value); },
                                        className: errors.billing_postcode ? 'error' : '',
                                        placeholder: 'Enter postal code'
                                    }),
                                    errors.billing_postcode && React.createElement('span', {
                                        key: 'error',
                                        className: 'field-error'
                                    }, errors.billing_postcode)
                                ])
                            ])
                        ]),

                        React.createElement('div', {
                            key: 'payment',
                            className: 'form-section'
                        }, [
                            React.createElement('h4', { key: 'title' }, 'Payment Method'),
                            React.createElement('div', {
                                key: 'methods',
                                className: 'payment-methods'
                            }, paymentMethods.map(function(method, index) {
                                const isSelected = formData.payment_method === method.id;
                                return React.createElement('div', {
                                    key: method.id,
                                    className: 'payment-method' + (isSelected ? ' active' : '')
                                }, [
                                    React.createElement('div', {
                                        key: 'payment-header',
                                        className: 'payment-header'
                                    }, [
                                        React.createElement('input', {
                                            key: 'radio',
                                            type: 'radio',
                                            id: method.id,
                                            name: 'payment_method',
                                            value: method.id,
                                            checked: isSelected,
                                            onChange: function(e) { handleInputChange('payment_method', e.target.value); }
                                        }),
                                        React.createElement('label', {
                                            key: 'label',
                                            htmlFor: method.id
                                        }, [
                                            React.createElement('div', { 
                                                key: 'icon', 
                                                className: 'payment-icon' 
                                            }, method.icon ? React.createElement('div', {
                                                dangerouslySetInnerHTML: { __html: method.icon }
                                            }) : '💳'),
                                            React.createElement('div', {
                                                key: 'info',
                                                className: 'payment-info'
                                            }, [
                                                React.createElement('span', { 
                                                    key: 'name', 
                                                    className: 'payment-name' 
                                                }, method.title),
                                                React.createElement('span', { 
                                                    key: 'desc', 
                                                    className: 'payment-desc' 
                                                }, method.description || 'Secure payment processing')
                                            ])
                                        ])
                                    ])
                                ].concat(
                                    // Add credit card fields if this method has fields and is selected
                                    method.has_fields && isSelected ? [
                                        React.createElement('div', {
                                            key: 'card-fields',
                                            className: 'credit-card-fields'
                                        }, [
                                            React.createElement('div', {
                                                key: 'card-number-field',
                                                className: 'form-field'
                                            }, [
                                                React.createElement('label', { key: 'label' }, 'Card Number *'),
                                                React.createElement('input', {
                                                    key: 'input',
                                                    type: 'text',
                                                    name: method.id + '-card-number',
                                                    placeholder: '1234 1234 1234 1234',
                                                    maxLength: '19',
                                                    value: formData[method.id + '-card-number'] || '',
                                                    onChange: function(e) { 
                                                        // Format card number with spaces
                                                        let value = e.target.value.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();
                                                        handleInputChange(method.id + '-card-number', value);
                                                    }
                                                }),
                                                errors[method.id + '-card-number'] && React.createElement('span', {
                                                    key: 'error',
                                                    className: 'field-error'
                                                }, errors[method.id + '-card-number'])
                                            ]),
                                            React.createElement('div', {
                                                key: 'expiry-cvc-row',
                                                className: 'form-row'
                                            }, [
                                                React.createElement('div', {
                                                    key: 'expiry-field',
                                                    className: 'form-field'
                                                }, [
                                                    React.createElement('label', { key: 'label' }, 'Expiry (MM/YY) *'),
                                                    React.createElement('input', {
                                                        key: 'input',
                                                        type: 'text',
                                                        name: method.id + '-card-expiry',
                                                        placeholder: 'MM/YY',
                                                        maxLength: '5',
                                                        value: formData[method.id + '-card-expiry'] || '',
                                                        onChange: function(e) { 
                                                            // Format expiry as MM/YY
                                                            let value = e.target.value.replace(/\D/g, '');
                                                            if (value.length >= 2) {
                                                                value = value.substring(0,2) + '/' + value.substring(2,4);
                                                            }
                                                            handleInputChange(method.id + '-card-expiry', value);
                                                        }
                                                    }),
                                                    errors[method.id + '-card-expiry'] && React.createElement('span', {
                                                        key: 'error',
                                                        className: 'field-error'
                                                    }, errors[method.id + '-card-expiry'])
                                                ]),
                                                React.createElement('div', {
                                                    key: 'cvc-field',
                                                    className: 'form-field'
                                                }, [
                                                    React.createElement('label', { key: 'label' }, 'CVC *'),
                                                    React.createElement('input', {
                                                        key: 'input',
                                                        type: 'text',
                                                        name: method.id + '-card-cvc',
                                                        placeholder: '123',
                                                        maxLength: '4',
                                                        value: formData[method.id + '-card-cvc'] || '',
                                                        onChange: function(e) { 
                                                            // Only allow numbers
                                                            let value = e.target.value.replace(/\D/g, '');
                                                            handleInputChange(method.id + '-card-cvc', value);
                                                        }
                                                    }),
                                                    errors[method.id + '-card-cvc'] && React.createElement('span', {
                                                        key: 'error',
                                                        className: 'field-error'
                                                    }, errors[method.id + '-card-cvc'])
                                                ])
                                            ]),
                                            React.createElement('p', {
                                                key: 'test-note',
                                                className: 'test-card-note'
                                            }, 'Test Mode: Use card 4242424242424242 with any future expiry and any CVC')
                                        ])
                                    ] : []
                                ));
                            }))
                        ]),

                        errors.general && React.createElement('div', {
                            key: 'general-error',
                            className: 'general-error'
                        }, errors.general),

                        React.createElement('div', {
                            key: 'actions',
                            className: 'form-actions'
                        }, [
                            React.createElement('button', {
                                key: 'cancel',
                                type: 'button',
                                className: 'btn btn-secondary',
                                onClick: onClose
                            }, 'Cancel'),
                            React.createElement('button', {
                                key: 'submit',
                                type: 'submit',
                                className: 'btn btn-primary',
                                disabled: isProcessing
                            }, isProcessing ? 'Processing...' : 'Pay ' + cartTotal)
                        ])
                    ])
                ]),

                // Processing Step
                step === 'processing' && React.createElement('div', {
                    key: 'processing',
                    className: 'checkout-step processing-step'
                }, [
                    React.createElement('div', {
                        key: 'animation',
                        className: 'processing-animation'
                    }, [
                        React.createElement('div', { key: 'spinner', className: 'processing-spinner' }),
                        React.createElement('div', {
                            key: 'dots',
                            className: 'processing-dots'
                        }, [
                            React.createElement('span', { key: '1' }),
                            React.createElement('span', { key: '2' }),
                            React.createElement('span', { key: '3' })
                        ])
                    ]),
                    React.createElement('h4', { key: 'title' }, 'Processing Your Payment'),
                    React.createElement('p', { key: 'desc' }, 'Please wait while we securely process your payment...'),
                    React.createElement('div', {
                        key: 'steps',
                        className: 'processing-steps'
                    }, [
                        React.createElement('div', { key: '1', className: 'step completed' }, '✓ Validating payment details'),
                        React.createElement('div', { key: '2', className: 'step active' }, '⏳ Processing payment'),
                        React.createElement('div', { key: '3', className: 'step' }, '⏳ Adding credits to account')
                    ])
                ]),

                // Success Step
                step === 'success' && React.createElement('div', {
                    key: 'success',
                    className: 'checkout-step success-step'
                }, [
                    React.createElement('div', {
                        key: 'animation',
                        className: 'success-animation'
                    }, [
                        React.createElement('div', {
                            key: 'checkmark',
                            className: 'success-checkmark'
                        }, React.createElement('svg', {
                            viewBox: '0 0 24 24'
                        }, React.createElement('path', {
                            d: 'M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z'
                        })))
                    ]),
                    React.createElement('h4', { key: 'title' }, 'Payment Successful!'),
                    React.createElement('p', { key: 'desc' }, 'Your 20 credits have been added to your account.'),
                    React.createElement('div', {
                        key: 'details',
                        className: 'success-details'
                    }, [
                        React.createElement('div', {
                            key: 'credits',
                            className: 'success-item'
                        }, [
                            React.createElement('span', { key: 'label' }, 'Credits Added:'),
                            React.createElement('span', { key: 'value', className: 'highlight' }, '20 Credits')
                        ]),
                        React.createElement('div', {
                            key: 'amount',
                            className: 'success-item'
                        }, [
                            React.createElement('span', { key: 'label' }, 'Amount Paid:'),
                            React.createElement('span', { key: 'value', className: 'highlight' }, cartTotal)
                        ])
                    ]),
                    React.createElement('p', {
                        key: 'note',
                        className: 'success-note'
                    }, 'You can now continue with virtual fitting. This modal will close automatically.')
                ]),

                // Error Step
                step === 'error' && React.createElement('div', {
                    key: 'error',
                    className: 'checkout-step error-step'
                }, [
                    React.createElement('div', {
                        key: 'icon',
                        className: 'error-icon'
                    }, React.createElement('svg', {
                        viewBox: '0 0 24 24'
                    }, React.createElement('path', {
                        d: 'M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z'
                    }))),
                    React.createElement('h4', { key: 'title' }, 'Payment Failed'),
                    React.createElement('p', { key: 'desc' }, errors.general || 'There was an issue processing your payment.'),
                    React.createElement('div', {
                        key: 'actions',
                        className: 'error-actions'
                    }, [
                        React.createElement('button', {
                            key: 'retry',
                            className: 'btn btn-primary',
                            onClick: handleRetry
                        }, 'Try Again'),
                        React.createElement('button', {
                            key: 'cancel',
                            className: 'btn btn-secondary',
                            onClick: onClose
                        }, 'Cancel')
                    ])
                ])
            ])
        ]));
    };

    // Export for use in main application
    window.CheckoutModal = CheckoutModal;
    
})();