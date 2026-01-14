/**
 * AI Virtual Fitting Frontend JavaScript
 *
 * @package AI_Virtual_Fitting
 */

(function($) {
    'use strict';
    
    // Global variables
    let selectedProductId = null;
    let uploadedImageFile = null;
    let currentCredits = 0;
    let processingTimeout = null;
    let systemLoadLevel = 'normal'; // normal, high, very_high
    let estimatedWaitTime = 30; // seconds
    let processingStartTime = null;
    
    $(document).ready(function() {
        console.log('AI Virtual Fitting frontend loaded');
        
        // Initialize the application
        initializeApp();
    });
    
    /**
     * Initialize the application
     */
    function initializeApp() {
        // Detect system load first
        detectSystemLoad();
        
        // Check if user is logged in and get initial credits
        checkCredits();
        
        // Initialize event listeners
        initializeEventListeners();
        
        // Initialize product slider
        initializeProductSlider();
        
        // Initialize image upload
        initializeImageUpload();
        
        // Load products
        loadProducts();
    }
    
    /**
     * Initialize all event listeners
     */
    function initializeEventListeners() {
        // Product selection
        $(document).on('click', '.select-product-btn', handleProductSelection);
        
        // Image upload
        $('#customer-image-input').on('change', handleImageUpload);
        $('#upload-area').on('click', function() {
            $('#customer-image-input').click();
        });
        
        // Drag and drop for image upload
        $('#upload-area').on('dragover', handleDragOver);
        $('#upload-area').on('dragleave', handleDragLeave);
        $('#upload-area').on('drop', handleImageDrop);
        
        // Remove uploaded image
        $('#remove-image-btn').on('click', removeUploadedImage);
        
        // Try on button
        $('#try-on-btn').on('click', handleTryOn);
        
        // Purchase credits
        $('#purchase-credits-btn').on('click', handlePurchaseCredits);
        
        // Global purchase button handler for dynamically created buttons
        $(document).on('click', '#purchase-credits-btn', handlePurchaseCredits);
        $(document).on('click', '.credits-purchase-btn', handlePurchaseCredits);
        
        // Result actions
        $('#download-result-btn').on('click', handleDownloadResult);
        $('#try-another-btn').on('click', handleTryAnother);
        
        // Error dismissal
        $('#dismiss-error-btn').on('click', dismissError);
        
        // Slider navigation
        $('#slider-prev').on('click', function() {
            scrollSlider('prev');
        });
        $('#slider-next').on('click', function() {
            scrollSlider('next');
        });
    }
    
    /**
     * Initialize product slider functionality
     */
    function initializeProductSlider() {
        const slider = $('#product-slider');
        const prevBtn = $('#slider-prev');
        const nextBtn = $('#slider-next');
        
        // Update navigation button states
        function updateNavButtons() {
            const scrollLeft = slider.scrollLeft();
            const maxScroll = slider[0].scrollWidth - slider.outerWidth();
            
            prevBtn.prop('disabled', scrollLeft <= 0);
            nextBtn.prop('disabled', scrollLeft >= maxScroll);
        }
        
        // Listen for scroll events
        slider.on('scroll', updateNavButtons);
        
        // Initial update
        updateNavButtons();
    }
    
    /**
     * Scroll the product slider
     */
    function scrollSlider(direction) {
        const slider = $('#product-slider');
        const scrollAmount = 300;
        const currentScroll = slider.scrollLeft();
        
        if (direction === 'prev') {
            slider.animate({
                scrollLeft: currentScroll - scrollAmount
            }, 300);
        } else {
            slider.animate({
                scrollLeft: currentScroll + scrollAmount
            }, 300);
        }
    }
    
    /**
     * Initialize image upload functionality
     */
    function initializeImageUpload() {
        // Prevent default drag behaviors
        $(document).on('dragenter dragover dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    }
    
    /**
     * Check user credits with enhanced feedback
     */
    function checkCredits() {
        showLoading('Checking your credits...', false);
        
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_check_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    currentCredits = response.data.credits;
                    $('#credits-count').text(currentCredits);
                    
                    // Update UI based on credits
                    updateCreditsUI();
                    
                    // Show welcome message for new users
                    if (response.data.logged_in && currentCredits === 2) {
                        showSuccess('Welcome! You have 2 free virtual fitting credits to get started.', {
                            autoHide: true,
                            hideDelay: 6000
                        });
                    }
                } else {
                    showError('Unable to check your credits. Please refresh the page.', 'CREDITS_CHECK_FAILED');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                
                if (status === 'timeout') {
                    showError('Connection timeout while checking credits. Please check your internet connection.', 'TIMEOUT');
                } else {
                    showError('Failed to check credits. Please refresh the page.', 'NETWORK_ERROR');
                }
            },
            timeout: 10000 // 10 second timeout
        });
    }
    
    /**
     * Update UI based on current credits
     */
    function updateCreditsUI() {
        const noCreditsMessage = $('.no-credits-message');
        
        if (currentCredits <= 0) {
            noCreditsMessage.show();
            $('#try-on-btn').prop('disabled', true);
        } else {
            noCreditsMessage.hide();
            updateTryOnButton();
        }
    }
    
    /**
     * Load products with enhanced feedback
     */
    function loadProducts() {
        showLoading('Loading wedding dresses...', true);
        updateLoadingProgress(0);
        
        // Simulate progress for better UX
        let progressInterval = setInterval(function() {
            let currentProgress = parseInt($('.loading-progress-bar').css('width')) || 0;
            if (currentProgress < 80) {
                updateLoadingProgress(currentProgress + 10);
            }
        }, 200);
        
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_get_products',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                clearInterval(progressInterval);
                updateLoadingProgress(100);
                
                setTimeout(function() {
                    hideLoading();
                    
                    if (response.success && response.data.products) {
                        console.log('Products loaded:', response.data.products.length);
                        
                        if (response.data.products.length === 0) {
                            showError('No wedding dresses are currently available for virtual fitting.', 'NO_PRODUCTS', {
                                autoHide: false
                            });
                        } else {
                            // Show success message for product loading
                            updateLoadingStatus(`Loaded ${response.data.products.length} wedding dresses`);
                        }
                    } else {
                        showError('Failed to load products. Please refresh the page.', 'PRODUCT_LOAD_FAILED', {
                            allowRetry: true,
                            retryCallback: loadProducts
                        });
                    }
                }, 500);
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                hideLoading();
                
                let errorMessage = 'Failed to load products. Please check your connection.';
                let errorCode = 'NETWORK_ERROR';
                
                if (status === 'timeout') {
                    errorMessage = 'Loading products is taking longer than expected. Please try again.';
                    errorCode = 'TIMEOUT';
                } else if (xhr.status === 0) {
                    errorMessage = 'No internet connection. Please check your connection and try again.';
                    errorCode = 'NO_CONNECTION';
                }
                
                showError(errorMessage, errorCode, {
                    allowRetry: true,
                    retryCallback: loadProducts
                });
            },
            timeout: 15000 // 15 second timeout
        });
    }
    
    /**
     * Handle product selection
     */
    function handleProductSelection(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const productItem = $(this).closest('.product-item');
        
        // Update selected state
        $('.product-item').removeClass('selected');
        productItem.addClass('selected');
        
        // Update selected product display
        selectedProductId = productId;
        updateSelectedProductDisplay(productItem);
        
        // Update try on button
        updateTryOnButton();
    }
    
    /**
     * Update selected product display
     */
    function updateSelectedProductDisplay(productItem) {
        const productName = productItem.find('.product-name').text();
        const productPrice = productItem.find('.product-price').html();
        const productImage = productItem.find('.product-image img').attr('src');
        
        $('#selected-product-name').text(productName);
        $('#selected-product-price').html(productPrice);
        $('#selected-product-image').attr('src', productImage).attr('alt', productName);
        
        $('#selected-product-display').show();
    }
    
    /**
     * Handle drag over event
     */
    function handleDragOver(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    }
    
    /**
     * Handle drag leave event
     */
    function handleDragLeave(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    }
    
    /**
     * Handle image drop
     */
    function handleImageDrop(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            processImageFile(files[0]);
        }
    }
    
    /**
     * Handle image upload from input
     */
    function handleImageUpload(e) {
        const file = e.target.files[0];
        if (file) {
            processImageFile(file);
        }
    }
    
    /**
     * Process uploaded image file
     */
    function processImageFile(file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showError('Please upload a JPEG, PNG, or WebP image file.');
            return;
        }
        
        // Validate file size (10MB)
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            showError('Image file must be smaller than 10MB.');
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#preview-image').attr('src', e.target.result);
            $('#upload-area').hide();
            $('#uploaded-image-preview').show();
            
            uploadedImageFile = file;
            updateTryOnButton();
        };
        reader.readAsDataURL(file);
        
        // Upload file to server
        uploadImageToServer(file);
    }
    
    /**
     * Enhanced image upload with better error handling
     */
    function uploadImageToServer(file) {
        const formData = new FormData();
        formData.append('action', 'ai_virtual_fitting_upload');
        formData.append('nonce', ai_virtual_fitting_ajax.nonce);
        formData.append('customer_image', file);
        
        // Show upload progress
        showLoading('Uploading your image...', true);
        updateLoadingProgress(0);
        
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                // Upload progress
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        updateLoadingProgress(percentComplete);
                        updateLoadingStatus(`Uploading... ${Math.round(percentComplete)}%`);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    // Store the temporary filename for later use
                    uploadedImageFile.tempFilename = response.data.temp_file;
                    showSuccess('Image uploaded successfully!');
                } else {
                    const errorCode = response.data.error_code || null;
                    const allowRetry = ['UPLOAD_FAILED', 'TEMP_DIR_FAILED', 'SAVE_FAILED'].includes(errorCode);
                    
                    showError(
                        response.data.message || 'Failed to upload image.',
                        errorCode,
                        {
                            allowRetry: allowRetry,
                            retryCallback: () => uploadImageToServer(file)
                        }
                    );
                    removeUploadedImage();
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                
                let errorMessage = 'Failed to upload image. Please try again.';
                let allowRetry = true;
                
                if (status === 'timeout') {
                    errorMessage = 'Upload timed out. Please check your connection and try again.';
                } else if (xhr.status === 413) {
                    errorMessage = 'Image file is too large. Please use a smaller image.';
                    allowRetry = false;
                } else if (xhr.status === 0) {
                    errorMessage = 'Connection lost. Please check your internet connection.';
                }
                
                showError(errorMessage, 'UPLOAD_ERROR', {
                    allowRetry: allowRetry,
                    retryCallback: allowRetry ? () => uploadImageToServer(file) : null
                });
                removeUploadedImage();
            }
        });
    }
    
    /**
     * Remove uploaded image
     */
    function removeUploadedImage() {
        $('#uploaded-image-preview').hide();
        $('#upload-area').show();
        $('#customer-image-input').val('');
        
        uploadedImageFile = null;
        updateTryOnButton();
    }
    
    /**
     * Update try on button state
     */
    function updateTryOnButton() {
        const hasProduct = selectedProductId !== null;
        const hasImage = uploadedImageFile !== null;
        const hasCredits = currentCredits > 0;
        
        const canTryOn = hasProduct && hasImage && hasCredits;
        
        $('#try-on-btn').prop('disabled', !canTryOn);
        
        // Update note text
        let noteText = '';
        if (!hasProduct && !hasImage) {
            noteText = ai_virtual_fitting_ajax.messages.select_product + ' and ' + ai_virtual_fitting_ajax.messages.upload_image.toLowerCase();
        } else if (!hasProduct) {
            noteText = ai_virtual_fitting_ajax.messages.select_product;
        } else if (!hasImage) {
            noteText = ai_virtual_fitting_ajax.messages.upload_image;
        } else if (!hasCredits) {
            noteText = ai_virtual_fitting_ajax.messages.insufficient_credits;
        } else {
            noteText = 'Ready to try on! Click the button above.';
        }
        
        $('.try-on-note').text(noteText);
    }
    
    /**
     * Handle try on button click
     */
    function handleTryOn() {
        if (!selectedProductId || !uploadedImageFile || currentCredits <= 0) {
            return;
        }
        
        // Show processing section
        showProcessing();
        
        // Start virtual fitting process
        processVirtualFitting();
    }
    
    /**
     * Show processing section
     */
    function showProcessing() {
        $('#processing-section').show();
        $('#results-section').hide();
        
        // Animate progress bar
        animateProgressBar();
        
        // Scroll to processing section
        $('html, body').animate({
            scrollTop: $('#processing-section').offset().top - 100
        }, 500);
    }
    
    /**
     * Animate progress bar
     */
    function animateProgressBar() {
        const progressFill = $('#progress-fill');
        let progress = 0;
        
        const interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) {
                progress = 90;
                clearInterval(interval);
            }
            
            progressFill.css('width', progress + '%');
        }, 500);
        
        // Store interval for cleanup
        processingTimeout = interval;
    }
    
    /**
     * Enhanced virtual fitting processing with better progress tracking
     */
    function processVirtualFitting() {
        // Show enhanced processing UI
        showProcessingWithEstimate();
        
        const startTime = Date.now();
        let progressInterval;
        let statusUpdateInterval;
        
        // Simulate realistic progress updates
        progressInterval = setInterval(function() {
            const elapsed = Date.now() - startTime;
            let progress = Math.min(85, (elapsed / 45000) * 85); // 85% over 45 seconds
            
            $('#progress-fill').css('width', progress + '%');
            
            // Update status messages based on progress
            if (progress < 20) {
                updateProcessingStatus('Analyzing your image...');
            } else if (progress < 40) {
                updateProcessingStatus('Loading product details...');
            } else if (progress < 60) {
                updateProcessingStatus('Processing with AI...');
            } else if (progress < 80) {
                updateProcessingStatus('Generating virtual fitting...');
            } else {
                updateProcessingStatus('Finalizing results...');
            }
        }, 1000);
        
        // Estimate completion time with dynamic updates
        statusUpdateInterval = setInterval(function() {
            const elapsed = Date.now() - startTime;
            const estimatedTotal = estimatedWaitTime * 1000; // Convert to milliseconds
            const remaining = Math.max(0, estimatedTotal - elapsed);
            const remainingSeconds = Math.ceil(remaining / 1000);
            
            if (remainingSeconds > 0) {
                if (remainingSeconds > 60) {
                    const minutes = Math.ceil(remainingSeconds / 60);
                    updateProcessingEstimate(`Estimated time remaining: ${minutes} minute${minutes > 1 ? 's' : ''}`);
                } else {
                    updateProcessingEstimate(`Estimated time remaining: ${remainingSeconds} seconds`);
                }
            } else {
                updateProcessingEstimate('Processing is taking longer than expected...');
            }
            
            // Show additional feedback for long processing times
            if (elapsed > 60000 && elapsed % 30000 < 2000) { // Every 30 seconds after 1 minute
                showSuccess('Still processing your virtual fitting. Thank you for your patience!', {
                    autoHide: true,
                    hideDelay: 3000
                });
            }
        }, 2000);
        
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_process',
                nonce: ai_virtual_fitting_ajax.nonce,
                temp_file: uploadedImageFile.tempFilename,
                product_id: selectedProductId
            },
            timeout: 90000, // 90 second timeout
            success: function(response) {
                // Clear intervals
                clearInterval(progressInterval);
                clearInterval(statusUpdateInterval);
                
                // Complete progress bar
                $('#progress-fill').css('width', '100%');
                updateProcessingStatus('Complete!');
                
                setTimeout(function() {
                    if (response.success) {
                        // Update credits
                        currentCredits = response.data.credits;
                        $('#credits-count').text(currentCredits);
                        updateCreditsUI();
                        
                        // Show result
                        showResult(response.data.result_image_url);
                        showSuccess('Virtual fitting completed successfully!');
                    } else {
                        const errorCode = response.data.error_code || null;
                        const allowRetry = !['INSUFFICIENT_CREDITS', 'AUTH_REQUIRED'].includes(errorCode);
                        
                        showError(
                            response.data.message || 'Virtual fitting failed. Please try again.',
                            errorCode,
                            {
                                allowRetry: allowRetry,
                                retryCallback: allowRetry ? processVirtualFitting : null
                            }
                        );
                        hideProcessing();
                    }
                }, 1000);
            },
            error: function(xhr, status, error) {
                // Clear intervals
                clearInterval(progressInterval);
                clearInterval(statusUpdateInterval);
                
                hideProcessing();
                
                let errorMessage = 'Virtual fitting failed. Please check your connection and try again.';
                let errorCode = 'PROCESSING_ERROR';
                let allowRetry = true;
                
                if (status === 'timeout') {
                    errorMessage = 'Processing is taking longer than expected. This might be due to high server load. Please try again in a few minutes.';
                    errorCode = 'PROCESSING_TIMEOUT';
                } else if (xhr.status === 0) {
                    errorMessage = 'Connection lost during processing. Please check your internet connection.';
                    errorCode = 'CONNECTION_LOST';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error occurred. Please try again later.';
                    errorCode = 'SERVER_ERROR';
                }
                
                showError(errorMessage, errorCode, {
                    allowRetry: allowRetry,
                    retryCallback: allowRetry ? processVirtualFitting : null,
                    showDetails: true,
                    details: `HTTP ${xhr.status}: ${error}`
                });
            }
        });
    }
    
    /**
     * Show enhanced processing section with dynamic estimates
     */
    function showProcessingWithEstimate() {
        $('#processing-section').show();
        $('#results-section').hide();
        
        // Reset progress
        $('#progress-fill').css('width', '0%');
        updateProcessingStatus('Starting virtual fitting...');
        updateProcessingEstimate(getWaitTimeMessage());
        
        // Show load notification if needed
        showLoadNotificationIfNeeded();
        
        // Store processing start time
        processingStartTime = Date.now();
        
        // Scroll to processing section
        $('html, body').animate({
            scrollTop: $('#processing-section').offset().top - 100
        }, 500);
    }
    
    /**
     * Update processing status message
     */
    function updateProcessingStatus(status) {
        $('#processing-status').text(status);
    }
    
    /**
     * Update processing time estimate
     */
    function updateProcessingEstimate(estimate) {
        $('#processing-estimate').text(estimate);
    }
    
    /**
     * Show virtual fitting result
     */
    function showResult(resultImageUrl) {
        // Hide processing
        hideProcessing();
        
        // Set result image
        $('#result-image').attr('src', resultImageUrl);
        
        // Show results section
        $('#results-section').show();
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#results-section').offset().top - 100
        }, 500);
        
        // Store result URL for download
        $('#download-result-btn').data('result-url', resultImageUrl);
    }
    
    /**
     * Hide processing section
     */
    function hideProcessing() {
        $('#processing-section').hide();
        
        // Clear progress animation
        if (processingTimeout) {
            clearInterval(processingTimeout);
            processingTimeout = null;
        }
        
        // Reset progress bar
        $('#progress-fill').css('width', '0%');
    }
    
    /**
     * Handle purchase credits - Updated to use modal instead of redirect
     */
    function handlePurchaseCredits() {
        // Check if modal system is available (modern interface)
        if (typeof openCheckoutModal === 'function') {
            // Use the modal system
            openCheckoutModal();
            return;
        }
        
        // Fallback: Check if modal HTML exists in the page
        if ($('#checkout-modal').length > 0) {
            // Modal exists, try to open it manually
            openCheckoutModalFallback();
            return;
        }
        
        // Final fallback: Use the old redirect system for backward compatibility
        handlePurchaseCreditsLegacy();
    }
    
    /**
     * Fallback function to open checkout modal manually
     */
    function openCheckoutModalFallback() {
        const modal = $('#checkout-modal');
        
        if (modal.length === 0) {
            // Modal doesn't exist, fall back to legacy
            handlePurchaseCreditsLegacy();
            return;
        }
        
        // Reset modal state
        resetCheckoutModalFallback();
        
        // Show modal
        modal.addClass('active').show();
        
        // Prevent body scrolling
        $('body').addClass('modal-open').css('overflow', 'hidden');
        
        // Load checkout form
        loadCheckoutFormFallback();
        
        // Bind close events
        bindCheckoutModalEventsFallback();
    }
    
    /**
     * Reset checkout modal to initial state (fallback)
     */
    function resetCheckoutModalFallback() {
        $('#checkout-loading').show();
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error').hide();
    }
    
    /**
     * Load checkout form (fallback)
     */
    function loadCheckoutFormFallback() {
        // Add credit product to cart first
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_add_credits_to_cart',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Load WooCommerce checkout form
                    loadWooCommerceCheckoutFallback();
                } else {
                    showCheckoutErrorFallback('Failed to add credits to cart. Please try again.');
                }
            },
            error: function() {
                showCheckoutErrorFallback('Network error. Please try again.');
            }
        });
    }
    
    /**
     * Load WooCommerce checkout form (fallback)
     */
    function loadWooCommerceCheckoutFallback() {
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_load_checkout',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#checkout-loading').hide();
                    $('#checkout-form-container').html(response.data.checkout_html).show();
                    
                    // Initialize WooCommerce checkout scripts
                    if (typeof wc_checkout_params !== 'undefined') {
                        $('body').trigger('init_checkout');
                    }
                } else {
                    showCheckoutErrorFallback('Failed to load checkout form. Please try again.');
                }
            },
            error: function() {
                showCheckoutErrorFallback('Network error while loading checkout. Please try again.');
            }
        });
    }
    
    /**
     * Show checkout error (fallback)
     */
    function showCheckoutErrorFallback(message) {
        $('#checkout-loading').hide();
        $('#checkout-form-container').hide();
        $('#checkout-error-message').text(message);
        $('#checkout-error').show();
    }
    
    /**
     * Bind checkout modal events (fallback)
     */
    function bindCheckoutModalEventsFallback() {
        // Close modal events
        $(document).off('click.checkout-modal');
        $(document).on('click.checkout-modal', '#close-checkout-modal, #cancel-checkout-btn', function() {
            closeCheckoutModalFallback();
        });
        
        // Close on overlay click
        $(document).on('click.checkout-modal', '.checkout-modal-overlay', function(e) {
            if (e.target === this) {
                closeCheckoutModalFallback();
            }
        });
        
        // ESC key to close
        $(document).on('keydown.checkout-modal', function(e) {
            if (e.keyCode === 27) {
                closeCheckoutModalFallback();
            }
        });
        
        // Success button
        $(document).on('click.checkout-modal', '#continue-fitting-btn', function() {
            closeCheckoutModalFallback();
        });
        
        // Retry button
        $(document).on('click.checkout-modal', '#retry-checkout-btn', function() {
            resetCheckoutModalFallback();
            loadCheckoutFormFallback();
        });
    }
    
    /**
     * Close checkout modal (fallback)
     */
    function closeCheckoutModalFallback() {
        const modal = $('#checkout-modal');
        
        // Hide modal
        modal.removeClass('active').hide();
        
        // Restore body scrolling
        $('body').removeClass('modal-open').css('overflow', '');
        
        // Clear cart
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_clear_cart',
                nonce: ai_virtual_fitting_ajax.nonce
            }
        });
        
        // Unbind events
        $(document).off('.checkout-modal');
    }
    
    /**
     * Legacy purchase credits function (for backward compatibility)
     */
    function handlePurchaseCreditsLegacy() {
        showLoading('Adding credits to your cart...', true);
        updateLoadingProgress(0);
        
        // Simulate progress
        let progressInterval = setInterval(function() {
            let currentProgress = parseInt($('.loading-progress-bar').css('width')) || 0;
            if (currentProgress < 80) {
                updateLoadingProgress(currentProgress + 20);
            }
        }, 300);
        
        // Add credits product to cart and redirect to checkout
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'add_virtual_fitting_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                clearInterval(progressInterval);
                updateLoadingProgress(100);
                
                setTimeout(function() {
                    hideLoading();
                    
                    if (response.success) {
                        showSuccess('Credits added to cart! Redirecting to checkout...', {
                            autoHide: true,
                            hideDelay: 2000
                        });
                        
                        // Redirect after showing success message
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || '/cart/';
                        }, 2000);
                    } else {
                        showError(
                            response.data.message || 'Failed to add credits to cart.',
                            'CART_ADD_FAILED',
                            {
                                allowRetry: true,
                                retryCallback: handlePurchaseCredits
                            }
                        );
                    }
                }, 500);
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                hideLoading();
                
                let errorMessage = 'Failed to process credit purchase. Please try again.';
                let errorCode = 'PURCHASE_ERROR';
                
                if (status === 'timeout') {
                    errorMessage = 'Purchase request timed out. Please try again.';
                    errorCode = 'PURCHASE_TIMEOUT';
                } else if (xhr.status === 0) {
                    errorMessage = 'No internet connection. Please check your connection.';
                    errorCode = 'NO_CONNECTION';
                }
                
                showError(errorMessage, errorCode, {
                    allowRetry: true,
                    retryCallback: handlePurchaseCredits
                });
            },
            timeout: 15000 // 15 second timeout
        });
    }
    
    /**
     * Handle download result
     */
    function handleDownloadResult() {
        const resultUrl = $(this).data('result-url');
        if (!resultUrl) {
            showError('No result image available for download.');
            return;
        }
        
        // Extract filename from URL
        const filename = resultUrl.split('/').pop();
        
        // Create download URL with nonce
        const downloadUrl = ai_virtual_fitting_ajax.ajax_url + 
            '?action=ai_virtual_fitting_download' +
            '&nonce=' + ai_virtual_fitting_ajax.nonce +
            '&result_file=' + encodeURIComponent(filename);
        
        // Trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'virtual-fitting-result.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Handle try another dress
     */
    function handleTryAnother() {
        // Hide results
        $('#results-section').hide();
        
        // Reset selections but keep uploaded image
        selectedProductId = null;
        $('.product-item').removeClass('selected');
        $('#selected-product-display').hide();
        
        // Update try on button
        updateTryOnButton();
        
        // Scroll back to product selection
        $('html, body').animate({
            scrollTop: $('.product-slider-section').offset().top - 100
        }, 500);
    }
    
    /**
     * Show error message with enhanced error handling
     */
    function showError(message, errorCode = null, options = {}) {
        // Default options
        const defaultOptions = {
            autoHide: true,
            hideDelay: 8000,
            allowRetry: false,
            retryCallback: null,
            showDetails: false,
            details: null
        };
        
        const config = Object.assign(defaultOptions, options);
        
        // Clear any existing error
        dismissError();
        
        // Create error content
        let errorHtml = `
            <div class="error-content">
                <span class="dashicons dashicons-warning"></span>
                <div class="error-text">
                    <div class="error-message">${message}</div>
                    ${errorCode ? `<div class="error-code">Error Code: ${errorCode}</div>` : ''}
                    ${config.showDetails && config.details ? `<div class="error-details">${config.details}</div>` : ''}
                </div>
                <div class="error-actions">
                    ${config.allowRetry ? '<button class="retry-error-btn" type="button">Retry</button>' : ''}
                    <button class="dismiss-error-btn" type="button">&times;</button>
                </div>
            </div>
        `;
        
        $('#error-messages').html(errorHtml).show();
        
        // Bind retry action
        if (config.allowRetry && config.retryCallback) {
            $('.retry-error-btn').on('click', function() {
                dismissError();
                config.retryCallback();
            });
        }
        
        // Bind dismiss action
        $('.dismiss-error-btn').on('click', dismissError);
        
        // Auto-hide if enabled
        if (config.autoHide) {
            setTimeout(function() {
                dismissError();
            }, config.hideDelay);
        }
        
        // Log error for debugging
        console.error('AI Virtual Fitting Error:', {
            message: message,
            errorCode: errorCode,
            options: config
        });
    }
    
    /**
     * Show success message
     */
    function showSuccess(message, options = {}) {
        const defaultOptions = {
            autoHide: true,
            hideDelay: 5000
        };
        
        const config = Object.assign(defaultOptions, options);
        
        // Clear any existing messages
        dismissError();
        dismissSuccess();
        
        let successHtml = `
            <div class="success-content">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="success-text">${message}</div>
                <button class="dismiss-success-btn" type="button">&times;</button>
            </div>
        `;
        
        $('#success-messages').html(successHtml).show();
        
        // Bind dismiss action
        $('.dismiss-success-btn').on('click', dismissSuccess);
        
        // Auto-hide if enabled
        if (config.autoHide) {
            setTimeout(function() {
                dismissSuccess();
            }, config.hideDelay);
        }
    }
    
    /**
     * Dismiss success message
     */
    function dismissSuccess() {
        $('#success-messages').hide();
    }
    
    /**
     * Enhanced loading overlay with progress and status
     */
    function showLoading(message = 'Loading...', showProgress = false) {
        let loadingHtml = `
            <div class="loading-content">
                <div class="spinner large"></div>
                <p class="loading-message">${message}</p>
                ${showProgress ? '<div class="loading-progress"><div class="loading-progress-bar"></div></div>' : ''}
                <div class="loading-status"></div>
            </div>
        `;
        
        $('#loading-overlay').html(loadingHtml).show();
    }
    
    /**
     * Update loading status
     */
    function updateLoadingStatus(status) {
        $('.loading-status').text(status);
    }
    
    /**
     * Update loading progress
     */
    function updateLoadingProgress(percent) {
        $('.loading-progress-bar').css('width', percent + '%');
    }
    
    /**
     * Dismiss error message
     */
    function dismissError() {
        $('#error-messages').hide();
    }
    
    /**
     * Show loading overlay
     */
    function showLoading() {
        $('#loading-overlay').show();
    }
    
    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('#loading-overlay').hide();
    }
    
})(jQuery);