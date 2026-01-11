/**
 * Modern Virtual Fitting JavaScript
 * Handles the trending-style split-screen interface
 *
 * @package AI_Virtual_Fitting
 */

(function($) {
    'use strict';

    // Global state
    let selectedProductId = null;
    let tempFileName = null;
    let isProcessing = false;
    let currentProductGallery = [];

    // Initialize when document is ready
    $(document).ready(function() {
        initializeInterface();
        bindEvents();
        checkUserCredits();
    });

    /**
     * Initialize the interface
     */
    function initializeInterface() {
        // Add smooth scrolling to products panel
        $('.products-panel').css('scroll-behavior', 'smooth');
        
        // Animate product cards on load
        $('.product-card').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's',
                'opacity': '0'
            }).animate({ opacity: 1 }, 600);
        });

        // Initialize drag and drop
        initializeDragAndDrop();
    }

    /**
     * Bind all event handlers
     */
    function bindEvents() {
        // Product selection
        $(document).on('click', '.product-card', handleProductSelection);
        
        // File upload
        $('#customer-image-input').on('change', handleFileSelection);
        $('#upload-area').on('click', function(e) {
            // Prevent event bubbling to avoid recursion
            e.stopPropagation();
            // Only trigger file input if no preview exists and click wasn't on file input itself
            if (!$(this).find('.image-preview').length && e.target.id !== 'customer-image-input') {
                $('#customer-image-input').trigger('click');
            }
        });
        
        // Reset button (upload area only)
        $(document).on('click', '.upload-section .reset-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            clearImagePreview();
            tempFileName = null;
            updateTryOnButton();
        });
        
        // Download button (upload area only)
        $(document).on('click', '.upload-section .download-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            handleDownloadImage();
        });
        
        // Action buttons
        $('#try-on-btn').on('click', handleTryOnRequest);
        
        // Result area buttons (specific handlers)
        $(document).on('click', '#try-another-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            handleTryAnother();
        });
        
        $(document).on('click', '#save-image-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            handleDownloadResult();
        });
        
        // Category dropdown (replaces old category buttons)
        $(document).on('change', '#category-dropdown', handleCategoryFilter);
        
        // Search functionality
        $('#search-box').on('input', handleProductSearch);
        
        // Product thumbnail clicks
        $(document).on('click', '.product-thumbnail', handleProductThumbnailClick);
        
        // Purchase credits button (global binding)
        $(document).on('click', '#purchase-credits-btn', function(e) {
            e.preventDefault();
            window.location.href = '/shop/?add-to-cart=virtual-fitting-credits';
        });
        
        // Add credits button (banner)
        $(document).on('click', '#add-credits-btn', function(e) {
            e.preventDefault();
            window.location.href = '/shop/?add-to-cart=virtual-fitting-credits';
        });
        
        // Login modal
        $('#close-login-modal').on('click', function() {
            $('#login-modal').fadeOut(300);
        });
        
        // Close modal on overlay click
        $('#login-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
        });
    }

    /**
     * Initialize drag and drop functionality
     */
    function initializeDragAndDrop() {
        const uploadArea = $('#upload-area')[0];
        
        if (!uploadArea) return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        uploadArea.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight() {
            $('#upload-area').addClass('dragover');
        }

        function unhighlight() {
            $('#upload-area').removeClass('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        }
    }

    /**
     * Handle product selection
     */
    function handleProductSelection() {
        if (isProcessing) return;

        // Remove previous selection
        $('.product-card').removeClass('selected');
        
        // Add selection to clicked product
        $(this).addClass('selected');
        
        // Store selected product ID
        selectedProductId = $(this).data('product-id');
        
        // Get product data from the card
        const productData = getProductDataFromCard($(this));
        
        // Update main preview with selected product image
        const productImage = $(this).find('.product-image').attr('src');
        if (productImage) {
            $('#main-preview-image').attr('src', productImage).show();
            $('#preview-placeholder').hide();
        }
        
        // Update gallery with product images
        updateProductGallery(productData);
        
        // Update try-on button state
        updateTryOnButton();
        
        // Smooth scroll to show selection - Fix: Use .products-grid instead of .products-panel
        const $grid = $('.products-grid');
        const gridTop = $grid.offset().top;
        const cardTop = $(this).offset().top;
        
        $grid.animate({
            scrollTop: $grid.scrollTop() + (cardTop - gridTop) - 80
        }, 400);
    }

    /**
     * Get product data from card element
     */
    function getProductDataFromCard($card) {
        const productId = $card.data('product-id');
        
        // Try to get gallery data from the data attribute
        let galleryData = $card.data('gallery');
        
        // Parse JSON if it's a string
        if (typeof galleryData === 'string') {
            try {
                galleryData = JSON.parse(galleryData);
            } catch (e) {
                console.warn('Failed to parse gallery data:', e);
                galleryData = null;
            }
        }
        
        if (galleryData && Array.isArray(galleryData) && galleryData.length > 0) {
            return {
                id: productId,
                images: galleryData
            };
        }
        
        // Fallback: use the main image only
        const mainImage = $card.find('.product-image').attr('src');
        const galleryImages = [];
        if (mainImage) {
            galleryImages.push(mainImage);
        }
        
        return {
            id: productId,
            images: galleryImages
        };
    }

    /**
     * Update product gallery thumbnails
     */
    function updateProductGallery(productData) {
        const thumbnailsContainer = $('#product-thumbnails');
        
        // Clear existing thumbnails
        thumbnailsContainer.empty();
        
        if (!productData.images || productData.images.length <= 1) {
            thumbnailsContainer.hide();
            return;
        }
        
        // Store current gallery data
        currentProductGallery = productData.images;
        
        // Set main preview to first image and show thumbnails
        if (productData.images && productData.images.length) {
            $('#main-preview-image')
                .attr('src', productData.images[0])
                .show();
            $('#preview-placeholder').hide();
        }
        
        // Create thumbnail elements
        productData.images.forEach((imageUrl, index) => {
            const thumbnail = $(`
                <div class="product-thumbnail ${index === 0 ? 'active' : ''}" data-image-index="${index}">
                    <img src="${imageUrl}" alt="Product view ${index + 1}">
                </div>
            `);
            
            thumbnailsContainer.append(thumbnail);
        });
        
        // Show thumbnails if we have multiple images
        thumbnailsContainer.show();
    }

    /**
     * Handle product thumbnail click
     */
    function handleProductThumbnailClick() {
        const $thumbnail = $(this);
        const imageIndex = parseInt($thumbnail.data('image-index'));
        
        if (imageIndex >= 0 && imageIndex < currentProductGallery.length) {
            // Add loading state
            const $mainImage = $('#main-preview-image');
            $mainImage.addClass('is-loading');
            
            // Remove active class from all thumbnails
            $('.product-thumbnail').removeClass('active');
            
            // Add active class to clicked thumbnail
            $thumbnail.addClass('active');
            
            // Update main preview image
            const imageUrl = currentProductGallery[imageIndex];
            if (imageUrl) {
                // Simulate loading delay for smooth transition
                setTimeout(() => {
                    $mainImage.attr('src', imageUrl).show();
                    $('#preview-placeholder').hide();
                    $mainImage.removeClass('is-loading');
                }, 150);
            }
        }
    }

    /**
     * Handle file selection from input
     */
    function handleFileSelection(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileUpload(file);
        }
    }

    /**
     * Handle file upload (from input or drag & drop)
     */
    function handleFileUpload(file) {
        if (!file) return;

        // Reset temp upload state
        tempFileName = null;
        updateTryOnButton();

        // Validate file type
        if (!file.type.startsWith('image/')) {
            showMessage('Please select a valid image file.', 'error');
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            showMessage('Image file is too large. Please select an image under 10MB.', 'error');
            return;
        }
        
        // Show image preview
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
        };
        reader.readAsDataURL(file);

        // Upload file to server
        uploadImageToServer(file);
    }

    /**
     * Show image preview in upload area
     */
    function showImagePreview(imageSrc) {
        const uploadArea = $('#upload-area');
        
        console.log('=== DEBUGGING FLOATING BUTTONS ===');
        console.log('1. Upload area element:', uploadArea[0]);
        console.log('2. Upload area HTML before:', uploadArea.html().substring(0, 200) + '...');
        
        // Remove existing preview
        uploadArea.find('.image-preview').remove();
        
        // Add new preview
        const preview = $('<img>', {
            src: imageSrc,
            class: 'image-preview',
            alt: 'Customer photo preview'
        });
        
        uploadArea.append(preview);
        uploadArea.addClass('has-preview');
        
        console.log('3. Added has-preview class:', uploadArea.hasClass('has-preview'));
        console.log('4. Upload area classes:', uploadArea.attr('class'));
        
        // Check if floating buttons exist in HTML
        const floatingButtons = uploadArea.find('.floating-buttons');
        console.log('5. Floating buttons container found:', floatingButtons.length);
        console.log('6. Floating buttons HTML:', floatingButtons[0]);
        
        if (floatingButtons.length > 0) {
            console.log('7. Buttons before show:', {
                display: floatingButtons.css('display'),
                visibility: floatingButtons.css('visibility'),
                opacity: floatingButtons.css('opacity'),
                zIndex: floatingButtons.css('z-index')
            });
            
            // Show floating buttons explicitly
            floatingButtons.show().css({
                'display': 'flex',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            console.log('8. Buttons after show:', {
                display: floatingButtons.css('display'),
                visibility: floatingButtons.css('visibility'),
                opacity: floatingButtons.css('opacity'),
                zIndex: floatingButtons.css('z-index'),
                position: floatingButtons.css('position'),
                top: floatingButtons.css('top'),
                right: floatingButtons.css('right')
            });
            
            // Check individual buttons
            const resetBtn = uploadArea.find('.reset-btn');
            const downloadBtn = uploadArea.find('.download-btn');
            console.log('9. Reset button found:', resetBtn.length);
            console.log('10. Download button found:', downloadBtn.length);
            
        } else {
            console.error('❌ FLOATING BUTTONS CONTAINER NOT FOUND!');
            console.log('Upload area HTML:', uploadArea.html());
        }
        
        console.log('=== END DEBUGGING ===');
        
        // Update button states
        updateTryOnButton();
    }

    /**
     * Upload image to server
     */
    function uploadImageToServer(file) {
        if (!ai_virtual_fitting_ajax.nonce) {
            showMessage('Security error. Please refresh the page.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'ai_virtual_fitting_upload');
        formData.append('nonce', ai_virtual_fitting_ajax.nonce);
        formData.append('customer_image', file);

        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    tempFileName = response.data.temp_file;
                    showMessage('Image uploaded successfully!', 'success');
                } else {
                    showMessage(response.data.message || 'Upload failed. Please try again.', 'error');
                    clearImagePreview();
                }
            },
            error: function() {
                showMessage('Network error. Please check your connection and try again.', 'error');
                clearImagePreview();
            }
        });
    }

    /**
     * Handle try-on request
     */
    function handleTryOnRequest() {
        if (isProcessing) return;

        // Check if user is logged in
        if (!ai_virtual_fitting_ajax.user_logged_in) {
            $('#login-modal').fadeIn(300);
            return;
        }

        // Validate requirements
        if (!selectedProductId) {
            showMessage('Please select a product to try on.', 'error');
            return;
        }

        if (!tempFileName) {
            showMessage('Please upload your image first.', 'error');
            return;
        }

        // Check credits
        checkCreditsAndProcess();
    }

    /**
     * Check credits and process if sufficient
     */
    function checkCreditsAndProcess() {
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_check_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.credits > 0) {
                        processVirtualFitting();
                    } else {
                        showInsufficientCreditsMessage();
                    }
                } else {
                    showMessage('Error checking credits. Please try again.', 'error');
                }
            },
            error: function() {
                showMessage('Network error. Please try again.', 'error');
            }
        });
    }

    /**
     * Process virtual fitting
     */
    function processVirtualFitting() {
        isProcessing = true;
        
        // Log the request details for debugging
        console.log('Processing Virtual Fitting:', {
            tempFileName: tempFileName,
            selectedProductId: selectedProductId,
            timestamp: new Date().toISOString()
        });
        
        // Show loading overlay
        $('#loading-overlay').fadeIn(300);
        
        // Disable buttons and UI elements
        $('#try-on-btn').prop('disabled', true).addClass('btn-loading');
        $('.product-card').css('pointer-events', 'none');
        $('.category-btn, #search-box').prop('disabled', true);

        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_process',
                nonce: ai_virtual_fitting_ajax.nonce,
                temp_file: tempFileName,
                product_id: selectedProductId
            },
            timeout: 60000, // 60 second timeout
            success: function(response) {
                console.log('Virtual Fitting Response:', response);
                if (response.success) {
                    showVirtualFittingResult(response.data);
                    updateCreditsDisplay(response.data.credits);
                } else {
                    showMessage(response.data.message || 'Processing failed. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Virtual Fitting Error:', { xhr, status, error });
                if (status === 'timeout') {
                    showMessage('Processing is taking longer than expected. Please try again.', 'error');
                } else {
                    showMessage('Network error during processing. Please try again.', 'error');
                }
            },
            complete: function() {
                isProcessing = false;
                $('#loading-overlay').fadeOut(300);
                $('#try-on-btn').prop('disabled', false).removeClass('btn-loading');
                $('.product-card').css('pointer-events', 'auto');
                $('.category-btn, #search-box').prop('disabled', false);
            }
        });
    }

    /**
     * Show virtual fitting result
     */
    function showVirtualFittingResult(data) {
        // Hide upload section and show result in left panel
        $('.upload-section').slideUp(400);
        
        // Show virtual result section in left panel ONLY
        const virtualResult = $('#virtual-result');
        $('#virtual-result-image').attr('src', data.result_image);
        virtualResult.addClass('active').slideDown(400);
        
        // IMPORTANT: Keep the main preview showing the selected product, NOT the result
        // The main preview should continue showing the product and its gallery
        // Only the left panel shows the AI result
        
        showMessage('Virtual fitting completed successfully!', 'success');
    }

    /**
     * Handle category filter (dropdown)
     */
    function handleCategoryFilter() {
        const selectedCategory = $(this).val();
        
        // Filter products based on selected category
        if (selectedCategory === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').each(function() {
                const productCategories = $(this).data('categories');
                
                // Handle both string and array formats
                let categoryList = [];
                if (typeof productCategories === 'string') {
                    categoryList = productCategories.split(' ');
                } else if (Array.isArray(productCategories)) {
                    categoryList = productCategories;
                }
                
                // Show product if it belongs to the selected category
                if (categoryList.includes(selectedCategory)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    }

    /**
     * Handle product search
     */
    function handleProductSearch() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.product-card').each(function() {
            const productName = $(this).find('.product-name').text().toLowerCase();
            const productPrice = $(this).find('.product-price').text().toLowerCase();
            
            if (productName.includes(searchTerm) || productPrice.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    // Global variables to prevent multiple downloads
    let isDownloading = false;

    /**
     * Handle download image
     */
    function handleDownloadImage() {
        if (isDownloading) {
            console.log('Download already in progress, ignoring click');
            return;
        }
        
        isDownloading = true;
        
        const imagePreview = $('#upload-area .image-preview');
        if (!imagePreview.length) {
            showMessage('No image to download.', 'error');
            isDownloading = false;
            return;
        }

        console.log('Downloading customer image...');
        
        // Create download link
        const link = document.createElement('a');
        link.href = imagePreview.attr('src');
        link.download = 'customer-image.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showMessage('Customer image downloaded successfully!', 'success');
        
        // Reset download flag after a short delay
        setTimeout(() => {
            isDownloading = false;
        }, 1000);
    }

    /**
     * Handle download result
     */
    function handleDownloadResult() {
        if (isDownloading) {
            console.log('Download already in progress, ignoring click');
            return;
        }
        
        isDownloading = true;
        
        const resultImage = $('#virtual-result-image').attr('src');
        if (!resultImage) {
            isDownloading = false;
            return;
        }

        console.log('Downloading AI result image...');

        // Create download link
        const link = document.createElement('a');
        link.href = resultImage;
        link.download = 'virtual-fitting-result.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showMessage('AI result downloaded successfully!', 'success');
        
        // Reset download flag after a short delay
        setTimeout(() => {
            isDownloading = false;
        }, 1000);
    }

    /**
     * Handle try another request
     */
    function handleTryAnother() {
        // Hide virtual result section
        $('#virtual-result').removeClass('active').slideUp(400);
        
        // Show upload section
        $('.upload-section').slideDown(400);
        
        // CRITICAL: Restore the original customer image preview in the upload area
        // The tempFileName still points to the original customer image
        if (tempFileName) {
            // Find the original customer image preview
            const uploadArea = $('#upload-area');
            const existingPreview = uploadArea.find('.image-preview');
            
            if (existingPreview.length === 0) {
                // If no preview exists, we need to restore it
                // Note: We can't easily get the original image data back from the server
                // So we'll show a placeholder indicating the image is still uploaded
                const placeholder = $('<div class="image-uploaded-indicator">✓ Your photo is ready</div>');
                uploadArea.append(placeholder);
                uploadArea.addClass('has-preview');
                uploadArea.find('.floating-buttons').show();
            }
        }
        
        // The main preview should already be showing the correct product image
        // since we never changed it in showVirtualFittingResult()
        // But let's ensure the gallery is still visible if it was hidden
        const selectedCard = $('.product-card.selected');
        if (selectedCard.length) {
            const productData = getProductDataFromCard(selectedCard);
            updateProductGallery(productData);
        }
        
        // Update button states
        updateTryOnButton();
        
        // Log for debugging
        console.log('Try Another: tempFileName =', tempFileName, 'selectedProductId =', selectedProductId);
    }

    /**
     * Clear image preview
     */
    function clearImagePreview() {
        const uploadArea = $('#upload-area');
        uploadArea.find('.image-preview').remove();
        uploadArea.find('.image-uploaded-indicator').remove();
        uploadArea.removeClass('has-preview');
        uploadArea.find('.floating-buttons').hide();
        $('#customer-image-input').val('');
    }

    /**
     * Update try-on button state
     */
    function updateTryOnButton() {
        const hasImage = tempFileName !== null;
        const hasProduct = selectedProductId !== null;
        const canTryOn = hasImage && hasProduct && !isProcessing;
        
        $('#try-on-btn').prop('disabled', !canTryOn);
        
        // Update button text
        let buttonText = 'Try On Dress';
        if (!hasImage) {
            buttonText = 'Upload Image First';
        } else if (!hasProduct) {
            buttonText = 'Select Dress First';
        }
        
        $('#try-on-btn').text(buttonText);
    }

    /**
     * Check user credits
     */
    function checkUserCredits() {
        if (!ai_virtual_fitting_ajax.user_logged_in) {
            // For non-logged-in users, show 0 credits
            updateCreditsDisplay(0);
            return;
        }

        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_check_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCreditsDisplay(response.data.credits);
                }
            }
        });
    }

    /**
     * Update credits display
     */
    function updateCreditsDisplay(credits) {
        // Update main credits count (if exists)
        $('#credits-count').text(credits);
        
        // Update banner credits
        $('#total-credits').text(credits);
        
        // Add animation to both displays
        $('#credits-count, #total-credits').addClass('updated');
        setTimeout(function() {
            $('#credits-count, #total-credits').removeClass('updated');
        }, 1000);
        
        // Update banner visibility based on credits
        updateBannerState(credits);
    }
    
    /**
     * Update banner state based on credits
     */
    function updateBannerState(credits) {
        const banner = $('#credits-banner');
        
        if (credits <= 0) {
            banner.addClass('low-credits');
        } else {
            banner.removeClass('low-credits');
        }
    }

    /**
     * Show insufficient credits message
     */
    function showInsufficientCreditsMessage() {
        const message = `
            <div class="insufficient-credits-message">
                <h4>No Credits Remaining</h4>
                <p>You need credits to use virtual fitting. Purchase a credit package to continue.</p>
                <a href="#" class="btn btn-primary" id="purchase-credits-btn">Buy 20 Credits - $10</a>
            </div>
        `;
        
        showMessage(message, 'info', false, true);
    }

    /**
     * Show message to user
     */
    function showMessage(message, type = 'info', autoHide = true, allowHtml = false) {
        const messageContainer = $('#message-container');
        const messageElement = $(`<div class="message ${type}"></div>`);
        
        if (allowHtml) {
            messageElement.html(message);
        } else {
            messageElement.text(message);
        }
        
        // Clear existing messages
        messageContainer.empty();
        
        // Add new message
        messageContainer.append(messageElement);
        
        // Auto-hide after 5 seconds
        if (autoHide) {
            setTimeout(function() {
                messageElement.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Add CSS for credits animation
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .credits-count.updated {
                animation: pulse 0.6s ease-in-out;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            
            .insufficient-credits-message {
                text-align: center;
                padding: 20px;
                background: rgba(255, 255, 255, 0.95);
                border-radius: 15px;
                margin: 15px 0;
            }
            
            .insufficient-credits-message h4 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 18px;
                font-weight: 600;
            }
            
            .insufficient-credits-message p {
                margin: 0 0 20px 0;
                color: #666;
                line-height: 1.4;
            }
            
            .image-uploaded-indicator {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(76, 175, 80, 0.9);
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                font-weight: 600;
                font-size: 14px;
                box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
                z-index: 10;
            }
        `)
        .appendTo('head');

})(jQuery);