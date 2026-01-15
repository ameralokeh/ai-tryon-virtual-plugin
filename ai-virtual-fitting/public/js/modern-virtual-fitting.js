/**
 * Modern Virtual Fitting JavaScript
 * Handles the trending-style split-screen interface
 * Version: 1.5.4
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
    let factRotationInterval = null;
    let consentGiven = false;

    // Wedding dress facts for loading screen
    const weddingDressFacts = [
        "Did you know? The tradition of white wedding dresses started with Queen Victoria in 1840.",
        "Custom wedding dresses can take 4-6 months to create from start to finish.",
        "The average wedding dress has over 100 hours of handwork and craftsmanship.",
        "Colored wedding dresses are becoming increasingly popular, with blush and champagne leading the trend.",
        "Vintage lace from the 1920s is one of the most sought-after materials for custom gowns.",
        "A-line silhouettes are the most universally flattering wedding dress style.",
        "Hand-sewn beading can add 50+ hours to a dress's creation time.",
        "Gothic wedding dresses with black accents represent strength and individuality.",
        "3D floral appliqués are hand-placed one petal at a time for realistic dimension.",
        "Custom embroidery can include hidden messages or dates sewn into the dress.",
        "Detachable sleeves allow brides to have two looks in one dress.",
        "Champagne-colored dresses photograph beautifully and complement all skin tones.",
        "Cathedral trains can extend up to 12 feet behind the bride.",
        "Silk charmeuse is prized for its luxurious drape and subtle sheen.",
        "Many brides choose to add a pop of color with colored petticoats or sashes.",
        "Corset backs allow for size adjustments of up to 2 inches.",
        "Vintage-inspired dresses often feature authentic period construction techniques.",
        "Tulle skirts can contain over 100 yards of fabric for maximum volume.",
        "Custom dresses allow for perfect fit without extensive alterations.",
        "Illusion necklines create the appearance of floating lace and embellishments."
    ];

    // Initialize when document is ready
    $(document).ready(function() {
        initializeInterface();
        bindEvents();
        checkUserCredits();
        
        // Initialize mobile optimizations
        if (isMobileDevice()) {
            initializeMobileBrowserOptimizations();
        }
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
        // Consent checkbox
        $('#consent-checkbox').on('change', handleConsentChange);
        
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
            openCheckoutModal();
        });
        
        // Add credits button (banner)
        $(document).on('click', '#add-credits-btn', function(e) {
            e.preventDefault();
            openCheckoutModal();
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
        
        // Zero Credits Modal
        $('#close-zero-credits').on('click', function() {
            $('#zero-credits-modal').fadeOut(300);
        });
        
        $('#purchase-credits-btn').on('click', function(e) {
            e.preventDefault();
            // Close zero credits modal
            $('#zero-credits-modal').fadeOut(300);
            // Open checkout modal
            openCheckoutModal();
        });
        
        // Close zero credits modal on overlay click
        $('#zero-credits-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
        });
        
        // Prevent modal close when clicking inside modal content
        $('.zero-credits-modal').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Checkout modal events
        $(document).on('click', '#close-checkout-modal', closeCheckoutModal);
        $(document).on('click', '#continue-fitting-btn', closeCheckoutModal);
        $(document).on('click', '#retry-checkout-btn', retryCheckout);
        $(document).on('click', '#cancel-checkout-btn', closeCheckoutModal);
        
        // Close checkout modal on overlay click
        $(document).on('click', '.checkout-modal-overlay', function(e) {
            if (e.target === this) {
                closeCheckoutModal();
            }
        });
        
        // Prevent modal close when clicking inside modal content
        $(document).on('click', '.checkout-modal', function(e) {
            e.stopPropagation();
        });
        
        // Handle keyboard events
        $(document).on('keydown', function(e) {
            // ESC key closes modal
            if (e.keyCode === 27 && $('#checkout-modal').hasClass('active')) {
                closeCheckoutModal();
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
            // Hide navigation arrows if they exist
            $('.thumbnail-nav-arrow').remove();
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
        
        // Add navigation arrows if more than 3 images
        if (productData.images.length > 3) {
            addThumbnailNavigationArrows();
        }
    }
    
    /**
     * Add navigation arrows for thumbnail scrolling
     */
    function addThumbnailNavigationArrows() {
        const thumbnailsContainer = $('#product-thumbnails');
        
        // Remove existing arrows if any
        $('.thumbnail-nav-arrow').remove();
        
        // Create left arrow
        const leftArrow = $(`
            <div class="thumbnail-nav-arrow left disabled">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </div>
        `);
        
        // Create right arrow
        const rightArrow = $(`
            <div class="thumbnail-nav-arrow right">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                </svg>
            </div>
        `);
        
        // Insert arrows before and after thumbnails container
        thumbnailsContainer.before(leftArrow);
        thumbnailsContainer.after(rightArrow);
        
        // Bind click events
        leftArrow.on('click', function() {
            scrollThumbnails('left');
        });
        
        rightArrow.on('click', function() {
            scrollThumbnails('right');
        });
        
        // Update arrow states on scroll
        thumbnailsContainer.on('scroll', updateArrowStates);
        
        // Initial arrow state
        updateArrowStates();
    }
    
    /**
     * Scroll thumbnails left or right
     */
    function scrollThumbnails(direction) {
        const thumbnailsContainer = $('#product-thumbnails')[0];
        const scrollAmount = 250; // Scroll by ~2 thumbnails
        
        if (direction === 'left') {
            thumbnailsContainer.scrollLeft -= scrollAmount;
        } else {
            thumbnailsContainer.scrollLeft += scrollAmount;
        }
    }
    
    /**
     * Update navigation arrow states based on scroll position
     */
    function updateArrowStates() {
        const thumbnailsContainer = $('#product-thumbnails')[0];
        if (!thumbnailsContainer) return;
        
        const leftArrow = $('.thumbnail-nav-arrow.left');
        const rightArrow = $('.thumbnail-nav-arrow.right');
        
        // Check if at start
        if (thumbnailsContainer.scrollLeft <= 0) {
            leftArrow.addClass('disabled');
        } else {
            leftArrow.removeClass('disabled');
        }
        
        // Check if at end
        const maxScroll = thumbnailsContainer.scrollWidth - thumbnailsContainer.clientWidth;
        if (thumbnailsContainer.scrollLeft >= maxScroll - 5) { // 5px tolerance
            rightArrow.addClass('disabled');
        } else {
            rightArrow.removeClass('disabled');
        }
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
     * Handle consent checkbox change
     */
    function handleConsentChange() {
        consentGiven = $('#consent-checkbox').is(':checked');
        
        if (consentGiven) {
            // Hide consent box and show upload area with animation
            $('#consent-box').fadeOut(300, function() {
                $('#upload-area').fadeIn(300);
            });
        }
    }

    /**
     * Handle file selection from input
     */
    function handleFileSelection(e) {
        // Check if consent was given
        if (!consentGiven) {
            showMessage('Please agree to the terms before uploading an image.', 'error');
            // Reset file input
            e.target.value = '';
            return;
        }
        
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
        
        // Check if consent was given
        if (!consentGiven) {
            showMessage('Please agree to the terms before uploading an image.', 'error');
            return;
        }

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
        
        // Start rotating wedding dress facts
        startFactRotation();
        
        // Disable buttons and UI elements
        $('#try-on-btn').prop('disabled', true).addClass('btn-loading');
        $('.product-card').css('pointer-events', 'none');
        $('#category-dropdown, #search-box').prop('disabled', true);

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
                    // Refresh credits display to get both total and free credits
                    refreshCreditsDisplay();
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
                stopFactRotation();
                $('#loading-overlay').fadeOut(300);
                $('#try-on-btn').prop('disabled', false).removeClass('btn-loading');
                $('.product-card').css('pointer-events', 'auto');
                $('#category-dropdown, #search-box').prop('disabled', false);
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
        
        const tryOnBtn = $('#try-on-btn');
        tryOnBtn.prop('disabled', !canTryOn);
        
        // Update button text based on state
        let buttonText = 'Try On Dress';
        if (isProcessing) {
            buttonText = 'Processing...';
        } else if (!hasImage) {
            buttonText = 'Upload Image First';
        } else if (!hasProduct) {
            buttonText = 'Select Dress First';
        } else if (canTryOn) {
            buttonText = 'Try On Dress';
        }
        
        tryOnBtn.text(buttonText);
        
        // Add visual feedback when button becomes enabled after purchase
        if (canTryOn && !tryOnBtn.hasClass('recently-enabled')) {
            tryOnBtn.addClass('recently-enabled');
            setTimeout(() => {
                tryOnBtn.removeClass('recently-enabled');
            }, 2000);
        }
    }

    /**
     * Check user credits
     */
    function checkUserCredits() {
        // Use the new refresh function for consistency
        refreshCreditsDisplay();
    }

    /**
     * Update credits display
     */
    function updateCreditsDisplay(credits, freeCredits) {
        // Handle both old single parameter and new dual parameter calls
        if (typeof freeCredits === 'undefined') {
            // Old single parameter call - refresh to get both values
            refreshCreditsDisplay();
            return;
        }
        
        // Update main credits count (if exists)
        $('#credits-count').text(credits);
        
        // Update banner credits
        $('#total-credits').text(credits);
        $('#free-credits').text(freeCredits);
        
        // Add animation to all displays
        $('#credits-count, #total-credits, #free-credits').addClass('updated');
        setTimeout(function() {
            $('#credits-count, #total-credits, #free-credits').removeClass('updated');
        }, 1000);
        
        // Update banner visibility based on credits
        updateBannerState(credits);
    }
    
    /**
     * Refresh credits display from server
     */
    function refreshCreditsDisplay() {
        if (!ai_virtual_fitting_ajax.user_logged_in) {
            // For non-logged-in users, show 0 credits
            updateCreditsDisplay(0, 0);
            return;
        }

        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_refresh_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCreditsDisplay(response.data.credits, response.data.free_credits);
                } else {
                    console.warn('Failed to refresh credits:', response.data.message);
                }
            },
            error: function() {
                console.warn('Network error while refreshing credits');
            }
        });
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
        console.log('showInsufficientCreditsMessage called');
        console.log('Modal element exists:', $('#zero-credits-modal').length);
        // Show the zero credits modal
        $('#zero-credits-modal').fadeIn(300);
    }

    /**
     * Start rotating wedding dress facts
     */
    function startFactRotation() {
        // Clear any existing interval
        stopFactRotation();
        
        // Show first fact immediately
        showRandomFact();
        
        // Rotate facts every 4 seconds
        factRotationInterval = setInterval(showRandomFact, 4000);
    }

    /**
     * Stop rotating facts
     */
    function stopFactRotation() {
        if (factRotationInterval) {
            clearInterval(factRotationInterval);
            factRotationInterval = null;
        }
    }

    /**
     * Show a random wedding dress fact
     */
    function showRandomFact() {
        const randomIndex = Math.floor(Math.random() * weddingDressFacts.length);
        const fact = weddingDressFacts[randomIndex];
        
        const factElement = $('#loading-fact');
        
        // Fade out, change text, fade in
        factElement.fadeOut(300, function() {
            factElement.text(fact).fadeIn(300);
        });
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

    /**
     * Open checkout modal with React component
     */
    function openCheckoutModal() {
        // Check if React and CheckoutModal are available
        if (typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
            console.error('React or ReactDOM not loaded. Falling back to traditional modal.');
            openCheckoutModalFallback();
            return;
        }
        
        if (typeof window.CheckoutModal === 'undefined') {
            console.error('CheckoutModal React component not loaded. Falling back to traditional modal.');
            openCheckoutModalFallback();
            return;
        }
        
        // Create or get the React modal container
        let modalContainer = document.getElementById('react-checkout-modal-container');
        if (!modalContainer) {
            modalContainer = document.createElement('div');
            modalContainer.id = 'react-checkout-modal-container';
            modalContainer.className = 'ai-virtual-fitting-app'; // Add wrapper class here
            document.body.appendChild(modalContainer);
        }
        
        // Prevent body scrolling (mobile-friendly)
        preventBackgroundScrolling(true);
        
        // Create React root and render the checkout modal
        const root = ReactDOM.createRoot(modalContainer);
        
        const handleClose = () => {
            // Restore body scrolling
            preventBackgroundScrolling(false);
            
            // Unmount React component
            root.unmount();
            
            // Remove container
            if (modalContainer && modalContainer.parentNode) {
                modalContainer.parentNode.removeChild(modalContainer);
            }
            
            // Clear cart after modal closes
            setTimeout(() => {
                clearCheckoutCart();
            }, 300);
        };
        
        const handleSuccess = (data) => {
            console.log('Checkout success:', data);
            
            // Refresh credits display
            refreshCreditsDisplay();
            
            // Enable "Try On Dress" button if it was disabled due to insufficient credits
            setTimeout(() => {
                const tryOnBtn = $('#try-on-btn');
                if (selectedProductId && tempFileName) {
                    // Re-evaluate button state with updated credits
                    updateTryOnButton();
                    
                    // If button is still disabled, force enable it since we just purchased credits
                    if (tryOnBtn.prop('disabled')) {
                        tryOnBtn.prop('disabled', false).text('Try On Dress');
                    }
                }
            }, 500);
            
            // Update banner state to remove low-credits styling
            const banner = $('#credits-banner');
            banner.removeClass('low-credits');
            
            // Show success message in main interface as well
            showMessage('Credits purchased successfully! You can now continue virtual fitting.', 'success');
            
            // Auto-close modal after showing success
            setTimeout(() => {
                handleClose();
            }, 2000);
        };
        
        // Render the React checkout modal
        root.render(React.createElement(window.CheckoutModal, {
            isOpen: true,
            onClose: handleClose,
            onSuccess: handleSuccess
        }));
        
        // Add mobile-specific enhancements
        initializeMobileModalEnhancements();
    }
    
    /**
     * Fallback checkout modal (original implementation)
     */
    function openCheckoutModalFallback() {
        const modal = $('#checkout-modal');
        
        // Guard: Check if modal exists in DOM
        if (!modal.length) {
            console.error('Checkout modal markup (#checkout-modal) not found in DOM.');
            showMessage('Checkout popup is missing from the page template.', 'error');
            return;
        }
        
        // Reset modal state
        resetCheckoutModal();
        
        // Show modal with animation
        modal.addClass('active');
        
        // Prevent body scrolling (mobile-friendly)
        preventBackgroundScrolling(true);
        
        // Load checkout form
        loadCheckoutForm();
        
        // Add mobile-specific enhancements
        initializeMobileModalEnhancements();
    }
    
    /**
     * Close checkout modal
     */
    function closeCheckoutModal() {
        const modal = $('#checkout-modal');
        
        // Hide modal with animation
        modal.removeClass('active');
        
        // Restore body scrolling (mobile-friendly)
        preventBackgroundScrolling(false);
        
        // Clear cart after modal closes
        setTimeout(() => {
            clearCheckoutCart();
        }, 300);
        
        // Clean up mobile enhancements
        cleanupMobileModalEnhancements();
    }
    
    /**
     * Prevent background scrolling on mobile devices
     */
    function preventBackgroundScrolling(prevent) {
        const body = $('body');
        
        if (prevent) {
            // Store current scroll position
            const scrollTop = $(window).scrollTop();
            body.data('scroll-position', scrollTop);
            
            // Apply modal-open class and styles
            body.addClass('modal-open').css({
                'overflow': 'hidden',
                'position': 'fixed',
                'width': '100%',
                'top': -scrollTop + 'px'
            });
        } else {
            // Restore scroll position
            const scrollTop = body.data('scroll-position') || 0;
            
            // Remove modal-open class and styles
            body.removeClass('modal-open').css({
                'overflow': '',
                'position': '',
                'width': '',
                'top': ''
            });
            
            // Restore scroll position
            $(window).scrollTop(scrollTop);
        }
    }
    
    /**
     * Initialize mobile-specific modal enhancements
     */
    function initializeMobileModalEnhancements() {
        // Add swipe-to-close gesture for mobile
        if (isMobileDevice()) {
            initializeSwipeToClose();
        }
        
        // Handle virtual keyboard on mobile
        initializeVirtualKeyboardHandling();
        
        // Add pull-to-close indicator
        addPullToCloseIndicator();
        
        // Optimize scroll behavior
        optimizeModalScrolling();
        
        // Initialize mobile browser optimizations
        initializeMobileBrowserOptimizations();
        
        // Initialize mobile form validation
        initializeMobileFormValidation();
        
        // Initialize mobile accessibility features
        initializeMobileAccessibility();
        
        // Trigger modal opened event for accessibility
        $(document).trigger('checkout-modal-opened');
    }
    
    /**
     * Clean up mobile modal enhancements
     */
    function cleanupMobileModalEnhancements() {
        // Remove swipe listeners
        $(document).off('touchstart.modal touchmove.modal touchend.modal');
        
        // Remove keyboard listeners
        $(window).off('resize.modal');
        
        // Remove pull indicator
        $('.pull-to-close-indicator').remove();
        
        // Remove mobile-specific classes
        $('.checkout-modal').removeClass('keyboard-open landscape-mode');
        
        // Trigger modal closed event for accessibility
        $(document).trigger('checkout-modal-closed');
    }
    
    /**
     * Check if device is mobile
     */
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               window.innerWidth <= 768;
    }
    
    /**
     * Initialize swipe-to-close gesture
     */
    function initializeSwipeToClose() {
        let startY = 0;
        let currentY = 0;
        let isDragging = false;
        const modal = $('.checkout-modal');
        const threshold = 100; // Minimum swipe distance to close
        
        $(document).on('touchstart.modal', '.checkout-modal-header', function(e) {
            startY = e.originalEvent.touches[0].clientY;
            isDragging = true;
            modal.css('transition', 'none');
        });
        
        $(document).on('touchmove.modal', function(e) {
            if (!isDragging) return;
            
            currentY = e.originalEvent.touches[0].clientY;
            const deltaY = currentY - startY;
            
            // Only allow downward swipes
            if (deltaY > 0) {
                e.preventDefault();
                const translateY = Math.min(deltaY, 200); // Limit maximum drag
                modal.css('transform', `translateY(${translateY}px)`);
                
                // Add visual feedback
                const opacity = Math.max(0.3, 1 - (deltaY / 300));
                $('.checkout-modal-overlay').css('background', `rgba(0, 0, 0, ${opacity * 0.8})`);
            }
        });
        
        $(document).on('touchend.modal', function(e) {
            if (!isDragging) return;
            
            isDragging = false;
            const deltaY = currentY - startY;
            
            // Restore transition
            modal.css('transition', '');
            
            if (deltaY > threshold) {
                // Close modal
                closeCheckoutModal();
            } else {
                // Snap back to position
                modal.css('transform', '');
                $('.checkout-modal-overlay').css('background', '');
            }
        });
    }
    
    /**
     * Handle virtual keyboard on mobile devices
     */
    function initializeVirtualKeyboardHandling() {
        let initialViewportHeight = window.innerHeight;
        
        $(window).on('resize.modal', function() {
            const currentHeight = window.innerHeight;
            const heightDifference = initialViewportHeight - currentHeight;
            
            // If height decreased significantly, keyboard is likely open
            if (heightDifference > 150) {
                $('.checkout-modal').addClass('keyboard-open');
                
                // Scroll active input into view
                const activeInput = $('.checkout-modal input:focus, .checkout-modal select:focus, .checkout-modal textarea:focus');
                if (activeInput.length) {
                    setTimeout(() => {
                        activeInput[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            } else {
                $('.checkout-modal').removeClass('keyboard-open');
            }
        });
        
        // Handle input focus for better mobile experience
        $('.checkout-modal').on('focus', 'input, select, textarea', function() {
            const $input = $(this);
            
            // Add focused class for styling
            $input.addClass('input-focused');
            
            // Scroll into view on mobile
            if (isMobileDevice()) {
                setTimeout(() => {
                    $input[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        });
        
        $('.checkout-modal').on('blur', 'input, select, textarea', function() {
            $(this).removeClass('input-focused');
        });
    }
    
    /**
     * Add pull-to-close indicator
     */
    function addPullToCloseIndicator() {
        if (!isMobileDevice()) return;
        
        const indicator = $('<div class="pull-to-close-indicator"></div>');
        $('.checkout-modal-header').prepend(indicator);
    }
    
    /**
     * Optimize modal scrolling for mobile
     */
    function optimizeModalScrolling() {
        const modalContent = $('.checkout-modal-content');
        
        // Enable momentum scrolling on iOS
        modalContent.css('-webkit-overflow-scrolling', 'touch');
        
        // Prevent scroll chaining (prevent background scroll when modal content is at top/bottom)
        modalContent.on('touchstart', function() {
            const scrollTop = this.scrollTop;
            const scrollHeight = this.scrollHeight;
            const height = this.clientHeight;
            
            if (scrollTop === 0) {
                // At top, set scrollTop to 1 to prevent background scroll
                this.scrollTop = 1;
            } else if (scrollTop + height >= scrollHeight) {
                // At bottom, set scrollTop to prevent background scroll
                this.scrollTop = scrollHeight - height - 1;
            }
        });
    }
    
    /**
     * Handle mobile browser testing and optimization
     */
    function initializeMobileBrowserOptimizations() {
        // Detect mobile browsers
        const userAgent = navigator.userAgent.toLowerCase();
        const isSafari = /safari/.test(userAgent) && !/chrome/.test(userAgent);
        const isChrome = /chrome/.test(userAgent) && /android/.test(userAgent);
        const isFirefox = /firefox/.test(userAgent) && /mobile/.test(userAgent);
        const isSamsung = /samsungbrowser/.test(userAgent);
        
        // Apply browser-specific optimizations
        if (isSafari) {
            initializeSafariOptimizations();
        }
        
        if (isChrome) {
            initializeChromeAndroidOptimizations();
        }
        
        if (isFirefox) {
            initializeFirefoxMobileOptimizations();
        }
        
        if (isSamsung) {
            initializeSamsungBrowserOptimizations();
        }
        
        // Handle viewport changes for mobile browsers
        handleMobileViewportChanges();
        
        // Optimize touch interactions
        optimizeTouchInteractions();
        
        // Handle device orientation changes
        handleOrientationChanges();
    }
    
    /**
     * Safari-specific optimizations
     */
    function initializeSafariOptimizations() {
        // Fix iOS Safari viewport height issues
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };
        
        setViewportHeight();
        $(window).on('resize orientationchange', setViewportHeight);
        
        // Fix iOS Safari scroll bounce
        $('.checkout-modal-overlay').on('touchmove', function(e) {
            e.preventDefault();
        });
        
        // Handle iOS Safari address bar hiding/showing
        let lastScrollTop = 0;
        $(window).on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            if (Math.abs(scrollTop - lastScrollTop) > 5) {
                setViewportHeight();
                lastScrollTop = scrollTop;
            }
        });
    }
    
    /**
     * Chrome Android optimizations
     */
    function initializeChromeAndroidOptimizations() {
        // Handle Chrome Android's aggressive form autofill
        $('.checkout-modal input').on('focus', function() {
            // Delay to allow autofill to complete
            setTimeout(() => {
                $(this)[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 500);
        });
        
        // Handle Chrome's pull-to-refresh
        let startY = 0;
        $('.checkout-modal').on('touchstart', function(e) {
            startY = e.originalEvent.touches[0].clientY;
        });
        
        $('.checkout-modal').on('touchmove', function(e) {
            const currentY = e.originalEvent.touches[0].clientY;
            if (currentY > startY && this.scrollTop === 0) {
                e.preventDefault(); // Prevent pull-to-refresh
            }
        });
    }
    
    /**
     * Firefox Mobile optimizations
     */
    function initializeFirefoxMobileOptimizations() {
        // Firefox mobile has different touch behavior
        $('.checkout-modal .btn').on('touchstart', function() {
            $(this).addClass('touch-active');
        });
        
        $('.checkout-modal .btn').on('touchend touchcancel', function() {
            $(this).removeClass('touch-active');
        });
        
        // Handle Firefox mobile's viewport quirks
        const handleFirefoxResize = () => {
            const modal = $('.checkout-modal');
            if (modal.hasClass('active')) {
                modal.css('height', window.innerHeight + 'px');
            }
        };
        
        $(window).on('resize', handleFirefoxResize);
    }
    
    /**
     * Samsung Browser optimizations
     */
    function initializeSamsungBrowserOptimizations() {
        // Samsung Browser has unique scrolling behavior
        $('.checkout-modal-content').css({
            'overscroll-behavior': 'contain',
            '-ms-scroll-chaining': 'none'
        });
        
        // Handle Samsung Browser's edge swipe gestures
        $('.checkout-modal').on('touchstart', function(e) {
            const touch = e.originalEvent.touches[0];
            const edgeThreshold = 20;
            
            if (touch.clientX < edgeThreshold || touch.clientX > window.innerWidth - edgeThreshold) {
                e.preventDefault(); // Prevent edge swipe navigation
            }
        });
    }
    
    /**
     * Handle mobile viewport changes
     */
    function handleMobileViewportChanges() {
        let resizeTimer;
        
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                adjustModalForViewport();
            }, 100);
        });
        
        // Handle visual viewport API if available (modern browsers)
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                adjustModalForViewport();
            });
        }
    }
    
    /**
     * Adjust modal for current viewport
     */
    function adjustModalForViewport() {
        const modal = $('.checkout-modal');
        if (!modal.hasClass('active')) return;
        
        const viewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
        const keyboardHeight = window.innerHeight - viewportHeight;
        
        if (keyboardHeight > 150) {
            // Keyboard is open
            modal.addClass('keyboard-open');
            modal.css('max-height', viewportHeight * 0.9 + 'px');
            
            // Ensure focused element is visible
            const focusedElement = $('.checkout-modal input:focus, .checkout-modal select:focus, .checkout-modal textarea:focus');
            if (focusedElement.length) {
                setTimeout(() => {
                    focusedElement[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        } else {
            // Keyboard is closed
            modal.removeClass('keyboard-open');
            modal.css('max-height', '');
        }
    }
    
    /**
     * Optimize touch interactions
     */
    function optimizeTouchInteractions() {
        // Add touch feedback to interactive elements
        $('.checkout-modal').on('touchstart', '.btn, .payment_methods li, .checkout-modal-close', function() {
            $(this).addClass('touch-feedback');
        });
        
        $('.checkout-modal').on('touchend touchcancel', '.btn, .payment_methods li, .checkout-modal-close', function() {
            const $element = $(this);
            setTimeout(() => {
                $element.removeClass('touch-feedback');
            }, 150);
        });
        
        // Improve form field touch interactions
        $('.checkout-modal').on('touchstart', 'input, select, textarea', function() {
            $(this).addClass('touch-focused');
        });
        
        $('.checkout-modal').on('blur', 'input, select, textarea', function() {
            $(this).removeClass('touch-focused');
        });
        
        // Handle double-tap prevention on buttons
        let lastTouchTime = 0;
        $('.checkout-modal .btn').on('touchend', function(e) {
            const currentTime = new Date().getTime();
            const timeDiff = currentTime - lastTouchTime;
            
            if (timeDiff < 300) {
                e.preventDefault(); // Prevent double-tap
                return false;
            }
            
            lastTouchTime = currentTime;
        });
    }
    
    /**
     * Handle device orientation changes
     */
    function handleOrientationChanges() {
        $(window).on('orientationchange', function() {
            // Delay to allow orientation change to complete
            setTimeout(() => {
                const modal = $('.checkout-modal');
                if (modal.hasClass('active')) {
                    // Recalculate modal dimensions
                    adjustModalForViewport();
                    
                    // Re-center modal if needed
                    if (window.orientation === 90 || window.orientation === -90) {
                        // Landscape mode
                        modal.addClass('landscape-mode');
                    } else {
                        // Portrait mode
                        modal.removeClass('landscape-mode');
                    }
                    
                    // Ensure focused element remains visible
                    const focusedElement = $('.checkout-modal input:focus, .checkout-modal select:focus, .checkout-modal textarea:focus');
                    if (focusedElement.length) {
                        setTimeout(() => {
                            focusedElement[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                }
            }, 500);
        });
    }
    
    /**
     * Enhanced mobile form validation
     */
    function initializeMobileFormValidation() {
        // Real-time validation with mobile-friendly feedback
        $('.checkout-modal').on('input', 'input[type="email"]', function() {
            const $input = $(this);
            const email = $input.val();
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            $input.toggleClass('field-valid', isValid && email.length > 0);
            $input.toggleClass('field-invalid', !isValid && email.length > 0);
        });
        
        $('.checkout-modal').on('input', 'input[type="tel"]', function() {
            const $input = $(this);
            const phone = $input.val();
            const isValid = /^[\d\s\-\+\(\)]+$/.test(phone) && phone.length >= 10;
            
            $input.toggleClass('field-valid', isValid);
            $input.toggleClass('field-invalid', !isValid && phone.length > 0);
        });
        
        // Enhanced required field validation
        $('.checkout-modal').on('blur', 'input[required], select[required]', function() {
            const $input = $(this);
            const hasValue = $input.val().trim().length > 0;
            
            $input.toggleClass('field-valid', hasValue);
            $input.toggleClass('field-invalid', !hasValue);
            
            // Show/hide error message
            const errorMsg = $input.siblings('.field-error');
            if (!hasValue) {
                if (errorMsg.length === 0) {
                    $input.after('<span class="field-error">This field is required</span>');
                }
            } else {
                errorMsg.remove();
            }
        });
    }
    
    /**
     * Initialize mobile accessibility features
     */
    function initializeMobileAccessibility() {
        // Announce modal opening to screen readers
        $('.checkout-modal').attr({
            'role': 'dialog',
            'aria-modal': 'true',
            'aria-labelledby': 'checkout-modal-title'
        });
        
        $('.checkout-modal-header h3').attr('id', 'checkout-modal-title');
        
        // Manage focus for accessibility
        let previouslyFocusedElement;
        
        $(document).on('checkout-modal-opened', function() {
            previouslyFocusedElement = document.activeElement;
            
            // Focus first interactive element in modal
            setTimeout(() => {
                const firstInput = $('.checkout-modal input, .checkout-modal select, .checkout-modal button').first();
                if (firstInput.length) {
                    firstInput.focus();
                }
            }, 300);
        });
        
        $(document).on('checkout-modal-closed', function() {
            // Restore focus to previously focused element
            if (previouslyFocusedElement) {
                previouslyFocusedElement.focus();
            }
        });
        
        // Trap focus within modal
        $('.checkout-modal').on('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusableElements = $(this).find('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
                const firstElement = focusableElements.first();
                const lastElement = focusableElements.last();
                
                if (e.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement[0]) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement[0]) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
    }
    
    /**
     * Reset checkout modal to initial state
     */
    function resetCheckoutModal() {
        $('#checkout-loading').show();
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error').hide();
    }
    
    /**
     * Load checkout form via AJAX
     */
    function loadCheckoutForm() {
        // Add credit product to cart first
        addCreditProductToCart()
            .then(() => {
                // Then load the checkout form
                return loadWooCommerceCheckout();
            })
            .then(() => {
                // Show the form
                $('#checkout-loading').hide();
                $('#checkout-form-container').show();
            })
            .catch((error) => {
                console.error('Checkout loading error:', error);
                showCheckoutError('Failed to load checkout. Please try again.');
            });
    }
    
    /**
     * Add credit product to cart
     */
    function addCreditProductToCart() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_add_credits_to_cart',
                    nonce: ai_virtual_fitting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        // Handle specific cart error types
                        handleCartError(response.data, 'add');
                        reject(new Error(response.data.message || 'Failed to add credits to cart'));
                    }
                },
                error: function() {
                    reject(new Error('Network error while adding credits to cart'));
                }
            });
        });
    }
    
    /**
     * Handle cart-specific errors
     */
    function handleCartError(errorData, operation) {
        const errorCode = errorData.error_code || 'UNKNOWN_CART_ERROR';
        const retryAllowed = errorData.retry_allowed !== false;
        
        console.log('Cart error:', {
            operation: operation,
            code: errorCode,
            message: errorData.message,
            retryAllowed: retryAllowed
        });
        
        switch (errorCode) {
            case 'CART_CONFLICT_OTHER_PRODUCTS':
                handleCartConflictError(errorData);
                break;
                
            case 'PRODUCT_NOT_FOUND':
            case 'PRODUCT_NOT_PURCHASABLE':
            case 'PRODUCT_NOT_PUBLISHED':
                showCheckoutError('Credits are temporarily unavailable. Please contact support.');
                break;
                
            case 'CART_ADD_FAILED':
            case 'CART_VALIDATION_FAILED':
                if (retryAllowed) {
                    showCartRecoveryMessage('Cart error detected. Attempting to recover...', operation);
                } else {
                    showCheckoutError(errorData.message);
                }
                break;
                
            case 'EMPTY_CART_VALIDATION':
            case 'NO_VALID_ITEMS':
            case 'INVALID_CART_TOTAL':
                showCartRecoveryMessage('Cart validation failed. Recovering...', operation);
                break;
                
            default:
                if (retryAllowed) {
                    showCartRecoveryMessage(errorData.message + ' Recovering...', operation);
                } else {
                    showCheckoutError(errorData.message);
                }
                break;
        }
    }
    
    /**
     * Handle cart conflict errors (other products in cart)
     */
    function handleCartConflictError(errorData) {
        const conflictHtml = `
            <div class="cart-conflict-message">
                <h4>Cart Conflict Detected</h4>
                <p>${errorData.message}</p>
                <div class="conflict-actions">
                    <button class="btn btn-primary" id="clear-cart-and-continue">Clear Cart & Continue</button>
                    <button class="btn btn-secondary" id="cancel-checkout">Cancel</button>
                </div>
            </div>
        `;
        
        $('#checkout-loading').html(conflictHtml).show();
        $('#checkout-form-container').hide();
        
        // Bind conflict resolution actions
        $(document).off('click', '#clear-cart-and-continue');
        $(document).on('click', '#clear-cart-and-continue', function() {
            resolveCartConflict();
        });
        
        $(document).off('click', '#cancel-checkout');
        $(document).on('click', '#cancel-checkout', function() {
            closeCheckoutModal();
        });
    }
    
    /**
     * Resolve cart conflict by clearing and retrying
     */
    function resolveCartConflict() {
        showCartRecoveryMessage('Clearing cart and adding credits...', 'conflict_resolution');
        
        // Clear cart first, then add credits
        clearCheckoutCart()
            .then(() => {
                return addCreditProductToCart();
            })
            .then(() => {
                return loadWooCommerceCheckout();
            })
            .then(() => {
                $('#checkout-loading').hide();
                $('#checkout-form-container').show();
            })
            .catch((error) => {
                console.error('Cart conflict resolution failed:', error);
                showCheckoutError('Failed to resolve cart conflict. Please refresh and try again.');
            });
    }
    
    /**
     * Show cart recovery message
     */
    function showCartRecoveryMessage(message, operation) {
        const recoveryHtml = `
            <div class="cart-recovery-message">
                <div class="recovery-spinner"></div>
                <p>${message}</p>
                <div class="recovery-details">
                    <span>Recovering cart for ${operation}...</span>
                </div>
            </div>
        `;
        
        $('#checkout-loading').html(recoveryHtml).show();
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error').hide();
        
        // Attempt automatic recovery
        setTimeout(() => {
            attemptCartRecovery(operation);
        }, 2000);
    }
    
    /**
     * Attempt automatic cart recovery
     */
    function attemptCartRecovery(operation) {
        console.log('Attempting cart recovery for operation:', operation);
        
        // Clear cart and retry the original operation
        clearCheckoutCart()
            .then(() => {
                if (operation === 'add' || operation === 'conflict_resolution') {
                    return addCreditProductToCart();
                } else {
                    return Promise.resolve();
                }
            })
            .then(() => {
                return loadWooCommerceCheckout();
            })
            .then(() => {
                $('#checkout-loading').hide();
                $('#checkout-form-container').show();
                showMessage('Cart recovered successfully!', 'success');
            })
            .catch((error) => {
                console.error('Cart recovery failed:', error);
                showCheckoutError('Cart recovery failed. Please refresh the page and try again.');
            });
    }
    
    /**
     * Clear checkout cart (enhanced with error handling)
     */
    function clearCheckoutCart() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_clear_cart',
                    nonce: ai_virtual_fitting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Cart cleared:', response.data);
                        resolve(response.data);
                    } else {
                        console.warn('Cart clear failed:', response.data.message);
                        // Don't reject for cart clear failures - continue anyway
                        resolve({ message: 'Cart clear had issues but continuing', cleared_items: 0 });
                    }
                },
                error: function() {
                    console.warn('Network error while clearing cart');
                    // Don't reject for network errors - continue anyway
                    resolve({ message: 'Network error during cart clear but continuing', cleared_items: 0 });
                }
            });
        });
    }
    
    /**
     * Load WooCommerce checkout form
     */
    function loadWooCommerceCheckout() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_load_checkout',
                    nonce: ai_virtual_fitting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#checkout-form-container').html(response.data.checkout_html);
                        
                        // Initialize WooCommerce checkout scripts if available
                        if (typeof wc_checkout_params !== 'undefined') {
                            $(document.body).trigger('init_checkout');
                        }
                        
                        // Bind checkout form submission and validation
                        bindCheckoutFormSubmission();
                        initializeCheckoutValidation();
                        
                        resolve();
                    } else {
                        reject(new Error(response.data.message || 'Failed to load checkout form'));
                    }
                },
                error: function() {
                    reject(new Error('Network error while loading checkout'));
                }
            });
        });
    }
    
    /**
     * Bind checkout form submission
     */
    function bindCheckoutFormSubmission() {
        // Remove any existing handlers to prevent duplicates
        $(document).off('submit', '.checkout-modal form.checkout');
        
        // Bind new handler for modal checkout form
        $(document).on('submit', '.checkout-modal form.checkout', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $form = $(this);
            
            // Prevent double submission
            if ($form.hasClass('processing')) {
                return false;
            }
            
            $form.addClass('processing');
            
            // Process the checkout
            const result = processCheckoutSubmission($form);
            
            // Remove processing class after a delay to prevent rapid resubmission
            setTimeout(() => {
                $form.removeClass('processing');
            }, 1000);
            
            return result;
        });
        
        // Also handle place order button clicks specifically
        $(document).off('click', '.checkout-modal #place_order');
        $(document).on('click', '.checkout-modal #place_order', function(e) {
            e.preventDefault();
            
            const $form = $(this).closest('form.checkout');
            if ($form.length) {
                $form.trigger('submit');
            }
            
            return false;
        });
    }
    
    /**
     * Process checkout form submission
     */
    function processCheckoutSubmission($form) {
        // Validate form before submission
        if (!validateCheckoutForm($form)) {
            return false;
        }
        
        // Show loading state
        showCheckoutLoading();
        
        // Disable form to prevent double submission
        $form.find('input, select, button').prop('disabled', true);
        
        // Get form data
        const formData = $form.serialize();
        
        // Enhanced AJAX with retry logic and timeout handling
        processPaymentWithRetry(formData, $form, 0);
        
        return false; // Prevent default form submission
    }
    
    /**
     * Process payment with retry logic
     */
    function processPaymentWithRetry(formData, $form, retryCount = 0) {
        const maxRetries = 2;
        const retryDelay = 2000; // 2 seconds
        
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=ai_virtual_fitting_process_checkout&nonce=' + ai_virtual_fitting_ajax.nonce,
            timeout: 45000, // 45 second timeout for payment processing
            success: function(response) {
                if (response.success) {
                    // Payment successful
                    showCheckoutSuccess();
                    
                    // Handle redirect if needed (for some payment gateways)
                    if (response.data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    }
                    
                    // Log successful payment
                    console.log('Payment processed successfully:', {
                        order_id: response.data.order_id,
                        credits: response.data.credits
                    });
                    
                } else {
                    // Payment failed - handle based on error type
                    handlePaymentError(response.data, $form, formData, retryCount, maxRetries);
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment processing error:', { xhr, status, error });
                
                // Handle different types of network errors
                if (status === 'timeout') {
                    handleNetworkTimeout($form, formData, retryCount, maxRetries);
                } else if (status === 'abort') {
                    // Request was aborted - don't retry
                    showCheckoutError('Payment request was cancelled. Please try again.');
                    $form.find('input, select, button').prop('disabled', false);
                } else {
                    // Network error - attempt retry if possible
                    handleNetworkError($form, formData, retryCount, maxRetries, error);
                }
            }
        });
    }
    
    /**
     * Handle payment errors with specific error types
     */
    function handlePaymentError(errorData, $form, formData, retryCount, maxRetries) {
        const errorCode = errorData.error_code || 'UNKNOWN_ERROR';
        const retryAllowed = errorData.retry_allowed !== false; // Default to true if not specified
        const errorMessage = errorData.message || 'Payment failed. Please try again.';
        
        console.log('Payment error:', {
            code: errorCode,
            message: errorMessage,
            retryAllowed: retryAllowed,
            retryCount: retryCount
        });
        
        // Handle specific error types
        switch (errorCode) {
            case 'VALIDATION_FAILED':
                handleValidationErrors(errorData, $form);
                break;
                
            case 'PAYMENT_GATEWAY_ERROR':
                handlePaymentGatewayError(errorData, $form, formData, retryCount, maxRetries);
                break;
                
            case 'EMPTY_CART':
                handleEmptyCartError($form);
                break;
                
            case 'INVALID_PAYMENT_METHOD':
                showCheckoutError('Please select a valid payment method and try again.');
                $form.find('input, select, button').prop('disabled', false);
                break;
                
            case 'ORDER_CREATION_FAILED':
            case 'ORDER_RETRIEVAL_FAILED':
                if (retryAllowed && retryCount < maxRetries) {
                    showRetryMessage('Order processing failed. Retrying...', retryCount + 1, maxRetries);
                    setTimeout(() => {
                        processPaymentWithRetry(formData, $form, retryCount + 1);
                    }, 2000);
                } else {
                    showCheckoutError('Order creation failed. Please refresh the page and try again.');
                    $form.find('input, select, button').prop('disabled', false);
                }
                break;
                
            default:
                // Generic error handling
                if (retryAllowed && retryCount < maxRetries) {
                    showRetryMessage(errorMessage + ' Retrying...', retryCount + 1, maxRetries);
                    setTimeout(() => {
                        processPaymentWithRetry(formData, $form, retryCount + 1);
                    }, 2000);
                } else {
                    showCheckoutError(errorMessage);
                    $form.find('input, select, button').prop('disabled', false);
                }
                break;
        }
    }
    
    /**
     * Handle validation errors
     */
    function handleValidationErrors(errorData, $form) {
        // Show general error message
        showCheckoutError(errorData.message);
        
        // Highlight specific field errors if provided
        if (errorData.field_errors) {
            Object.keys(errorData.field_errors).forEach(fieldName => {
                const $field = $form.find(`[name="${fieldName}"]`);
                if ($field.length) {
                    $field.addClass('field-invalid');
                    $field.siblings('.field-error').remove();
                    $field.after(`<span class="field-error">${errorData.field_errors[fieldName]}</span>`);
                }
            });
        }
        
        // Re-enable form for correction
        $form.find('input, select, button').prop('disabled', false);
        
        // Scroll to first error
        const firstError = $form.find('.field-invalid').first();
        if (firstError.length) {
            firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Handle payment gateway errors
     */
    function handlePaymentGatewayError(errorData, $form, formData, retryCount, maxRetries) {
        const retryAllowed = errorData.retry_allowed !== false;
        const gatewayCode = errorData.gateway_code;
        
        // Check for specific gateway error codes that shouldn't be retried
        const nonRetryableGatewayCodes = [
            'card_declined_permanently',
            'insufficient_funds',
            'invalid_account',
            'account_closed',
            'fraud_detected'
        ];
        
        if (gatewayCode && nonRetryableGatewayCodes.includes(gatewayCode)) {
            showCheckoutError(errorData.message + ' Please use a different payment method.');
            $form.find('input, select, button').prop('disabled', false);
            return;
        }
        
        // Attempt retry for temporary gateway issues
        if (retryAllowed && retryCount < maxRetries) {
            showRetryMessage('Payment gateway error. Retrying...', retryCount + 1, maxRetries);
            setTimeout(() => {
                processPaymentWithRetry(formData, $form, retryCount + 1);
            }, 3000); // Longer delay for gateway errors
        } else {
            showCheckoutError(errorData.message + ' Please try again or use a different payment method.');
            $form.find('input, select, button').prop('disabled', false);
        }
    }
    
    /**
     * Handle empty cart error
     */
    function handleEmptyCartError($form) {
        showCheckoutError('Your cart is empty. Refreshing checkout...');
        
        // Attempt to reload the checkout form
        setTimeout(() => {
            resetCheckoutModal();
            loadCheckoutForm();
        }, 2000);
    }
    
    /**
     * Handle network timeout
     */
    function handleNetworkTimeout($form, formData, retryCount, maxRetries) {
        if (retryCount < maxRetries) {
            showRetryMessage('Payment processing timed out. Retrying...', retryCount + 1, maxRetries);
            setTimeout(() => {
                processPaymentWithRetry(formData, $form, retryCount + 1);
            }, 3000);
        } else {
            showCheckoutError('Payment processing timed out. Please check your order status or try again.');
            $form.find('input, select, button').prop('disabled', false);
        }
    }
    
    /**
     * Handle network errors
     */
    function handleNetworkError($form, formData, retryCount, maxRetries, error) {
        if (retryCount < maxRetries) {
            showRetryMessage('Network error. Retrying...', retryCount + 1, maxRetries);
            setTimeout(() => {
                processPaymentWithRetry(formData, $form, retryCount + 1);
            }, 2000);
        } else {
            showCheckoutError('Network error during payment. Please check your connection and try again.');
            $form.find('input, select, button').prop('disabled', false);
        }
    }
    
    /**
     * Show retry message with progress indicator
     */
    function showRetryMessage(message, currentRetry, maxRetries) {
        const retryHtml = `
            <div class="retry-message">
                <div class="retry-spinner"></div>
                <p>${message}</p>
                <div class="retry-progress">
                    <span>Attempt ${currentRetry} of ${maxRetries}</span>
                </div>
            </div>
        `;
        
        $('#checkout-loading').html(retryHtml).show();
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error').hide();
    }
    
    /**
     * Show checkout loading state
     */
    function showCheckoutLoading() {
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error').hide();
        $('#checkout-loading').show();
    }
    
    /**
     * Show checkout success state
     */
    function showCheckoutSuccess() {
        $('#checkout-loading').hide();
        $('#checkout-form-container').hide();
        $('#checkout-error').hide();
        $('#checkout-success').show();
        
        // Refresh credits display from server to get latest values
        refreshCreditsDisplay();
        
        // Enable "Try On Dress" button if it was disabled due to insufficient credits
        setTimeout(() => {
            const tryOnBtn = $('#try-on-btn');
            if (selectedProductId && tempFileName) {
                // Re-evaluate button state with updated credits
                updateTryOnButton();
                
                // If button is still disabled, force enable it since we just purchased credits
                if (tryOnBtn.prop('disabled')) {
                    tryOnBtn.prop('disabled', false).text('Try On Dress');
                }
            }
        }, 500); // Small delay to ensure credits are refreshed first
        
        // Update banner state to remove low-credits styling
        const banner = $('#credits-banner');
        banner.removeClass('low-credits');
        
        // Show success message in main interface as well
        showMessage('Credits purchased successfully! You can now continue virtual fitting.', 'success');
        
        // Auto-close modal after 3 seconds to return to virtual fitting
        setTimeout(() => {
            closeCheckoutModal();
        }, 3000);
    }
    
    /**
     * Show checkout error state
     */
    function showCheckoutError(message) {
        $('#checkout-loading').hide();
        $('#checkout-form-container').hide();
        $('#checkout-success').hide();
        $('#checkout-error-message').text(message);
        $('#checkout-error').show();
    }
    
    /**
     * Retry checkout
     */
    function retryCheckout() {
        resetCheckoutModal();
        loadCheckoutForm();
    }

    /**
     * Initialize checkout form validation within modal context
     */
    function initializeCheckoutValidation() {
        // Add real-time validation for required fields
        $(document).on('blur', '.checkout-modal input[required], .checkout-modal select[required]', function() {
            validateCheckoutField($(this));
        });
        
        // Validate on form submission
        $(document).on('submit', '.checkout-modal form.checkout', function(e) {
            if (!validateCheckoutForm($(this))) {
                e.preventDefault();
                return false;
            }
        });
        
        // Handle payment method changes
        $(document).on('change', '.checkout-modal input[name="payment_method"]', function() {
            updatePaymentMethodDisplay();
        });
        
        // Initialize payment method display
        updatePaymentMethodDisplay();
    }
    
    /**
     * Validate individual checkout field
     */
    function validateCheckoutField($field) {
        const fieldValue = $field.val().trim();
        const fieldName = $field.attr('name');
        const isRequired = $field.prop('required') || $field.hasClass('validate-required');
        
        // Remove existing validation classes
        $field.removeClass('field-valid field-invalid');
        $field.siblings('.field-error').remove();
        
        if (isRequired && !fieldValue) {
            $field.addClass('field-invalid');
            $field.after('<span class="field-error">This field is required.</span>');
            return false;
        }
        
        // Email validation
        if (fieldName === 'billing_email' && fieldValue) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(fieldValue)) {
                $field.addClass('field-invalid');
                $field.after('<span class="field-error">Please enter a valid email address.</span>');
                return false;
            }
        }
        
        // Phone validation (basic)
        if (fieldName === 'billing_phone' && fieldValue) {
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(fieldValue)) {
                $field.addClass('field-invalid');
                $field.after('<span class="field-error">Please enter a valid phone number.</span>');
                return false;
            }
        }
        
        $field.addClass('field-valid');
        return true;
    }
    
    /**
     * Validate entire checkout form
     */
    function validateCheckoutForm($form) {
        let isValid = true;
        
        // Clear previous error messages
        $('.checkout-error-message').remove();
        
        // Validate all required fields
        $form.find('input[required], select[required], .validate-required').each(function() {
            if (!validateCheckoutField($(this))) {
                isValid = false;
            }
        });
        
        // Check if payment method is selected
        const selectedPaymentMethod = $form.find('input[name="payment_method"]:checked').val();
        if (!selectedPaymentMethod) {
            isValid = false;
            $('#payment .woocommerce-error, #payment .woocommerce-message').remove();
            $('#payment').prepend('<div class="woocommerce-error checkout-error-message">Please select a payment method.</div>');
        }
        
        // Scroll to first error if validation failed
        if (!isValid) {
            const firstError = $('.field-invalid, .checkout-error-message').first();
            if (firstError.length) {
                firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }
    
    /**
     * Update payment method display
     */
    function updatePaymentMethodDisplay() {
        const selectedMethod = $('.checkout-modal input[name="payment_method"]:checked').val();
        
        // Hide all payment method descriptions
        $('.checkout-modal .payment_box').hide();
        
        // Show selected payment method description
        if (selectedMethod) {
            $('.checkout-modal .payment_box.payment_method_' + selectedMethod).show();
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
            
            /* Checkout form validation styles */
            .checkout-modal .field-valid {
                border-color: #28a745 !important;
                box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1) !important;
            }
            
            .checkout-modal .field-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1) !important;
            }
            
            .checkout-modal .field-error {
                display: block;
                color: #dc3545;
                font-size: 12px;
                margin-top: 4px;
                font-weight: 500;
            }
            
            .checkout-modal .checkout-error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 16px;
                border: 1px solid #f5c6cb;
                font-size: 14px;
            }
            
            .checkout-modal .woocommerce-error {
                background: #f8d7da;
                color: #721c24;
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 16px;
                border: 1px solid #f5c6cb;
            }
            
            .checkout-modal .payment_box {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                margin-top: 10px;
                border: 1px solid #e9ecef;
            }
            
            .checkout-modal .payment_methods {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .checkout-modal .payment_methods li {
                margin-bottom: 10px;
                padding: 10px;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                background: white;
            }
            
            .checkout-modal .payment_methods li.wc_payment_method input[type="radio"] {
                margin-right: 10px;
            }
            
            .checkout-modal .payment_methods li label {
                cursor: pointer;
                font-weight: 500;
            }
            
            /* Success modal enhancements */
            .checkout-success .success-details {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 16px;
                margin: 20px 0;
                border: 1px solid #e9ecef;
            }
            
            .checkout-success .success-detail {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
            }
            
            .checkout-success .success-detail:last-child {
                margin-bottom: 0;
            }
            
            .checkout-success .detail-label {
                font-weight: 500;
                color: #495057;
            }
            
            .checkout-success .detail-value {
                font-weight: 600;
                color: #28a745;
            }
            
            .checkout-success .success-note {
                font-size: 12px;
                color: #6c757d;
                font-style: italic;
                margin: 16px 0 8px 0;
            }
            
            /* Try-on button enhancement after purchase */
            .btn.recently-enabled {
                animation: enabledPulse 2s ease-in-out;
                box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.3);
            }
            
            @keyframes enabledPulse {
                0% { 
                    box-shadow: 0 0 0 0 rgba(74, 144, 226, 0.7);
                }
                50% { 
                    box-shadow: 0 0 0 8px rgba(74, 144, 226, 0.3);
                }
                100% { 
                    box-shadow: 0 0 0 0 rgba(74, 144, 226, 0);
                }
            }
            
            /* Enhanced error handling styles */
            .retry-message {
                text-align: center;
                padding: 30px 20px;
                background: #f8f9fa;
                border-radius: 12px;
                margin: 20px;
            }
            
            .retry-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #e3e3e3;
                border-top: 4px solid #4a90e2;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px auto;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .retry-message p {
                margin: 0 0 10px 0;
                font-size: 16px;
                font-weight: 500;
                color: #333;
            }
            
            .retry-progress {
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
            
            /* Enhanced field validation styles */
            .checkout-modal .field-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.15) !important;
                background-color: rgba(220, 53, 69, 0.05) !important;
            }
            
            .checkout-modal .field-valid {
                border-color: #28a745 !important;
                box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.15) !important;
                background-color: rgba(40, 167, 69, 0.05) !important;
            }
            
            .checkout-modal .field-error {
                display: block;
                color: #dc3545;
                font-size: 12px;
                margin-top: 4px;
                font-weight: 500;
                padding: 4px 8px;
                background: rgba(220, 53, 69, 0.1);
                border-radius: 4px;
                border-left: 3px solid #dc3545;
            }
            
            /* Enhanced error message styles */
            .checkout-modal .checkout-error-message {
                background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
                color: #721c24;
                padding: 16px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
                font-size: 14px;
                font-weight: 500;
                box-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
            }
            
            .checkout-modal .woocommerce-error {
                background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
                color: #721c24;
                padding: 16px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
                box-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
            }
            
            /* Payment method enhancement */
            .checkout-modal .payment_methods li {
                margin-bottom: 12px;
                padding: 12px 16px;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                background: white;
                transition: all 0.2s ease;
                cursor: pointer;
            }
            
            .checkout-modal .payment_methods li:hover {
                border-color: #4a90e2;
                background: rgba(74, 144, 226, 0.05);
            }
            
            .checkout-modal .payment_methods li.wc_payment_method input[type="radio"]:checked + label {
                color: #4a90e2;
                font-weight: 600;
            }
            
            .checkout-modal .payment_methods li:has(input[type="radio"]:checked) {
                border-color: #4a90e2;
                background: rgba(74, 144, 226, 0.1);
            }
            
            /* Loading state enhancements */
            .checkout-modal form.processing {
                opacity: 0.7;
                pointer-events: none;
            }
            
            .checkout-modal form.processing::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            
            /* Mobile responsiveness for error handling */
            @media (max-width: 768px) {
                .retry-message {
                    margin: 10px;
                    padding: 20px 15px;
                }
                
                .checkout-modal .field-error {
                    font-size: 11px;
                    padding: 3px 6px;
                }
                
                .checkout-modal .checkout-error-message,
                .checkout-modal .woocommerce-error {
                    padding: 12px 16px;
                    font-size: 13px;
                }
            }
            
            /* Cart error handling styles */
            .cart-conflict-message,
            .cart-recovery-message {
                text-align: center;
                padding: 30px 20px;
                background: #f8f9fa;
                border-radius: 12px;
                margin: 20px;
            }
            
            .cart-conflict-message h4 {
                margin: 0 0 15px 0;
                color: #e67e22;
                font-size: 18px;
                font-weight: 600;
            }
            
            .cart-conflict-message p {
                margin: 0 0 20px 0;
                color: #666;
                line-height: 1.5;
            }
            
            .conflict-actions {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .conflict-actions .btn {
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .conflict-actions .btn-primary {
                background: #4a90e2;
                color: white;
            }
            
            .conflict-actions .btn-primary:hover {
                background: #357abd;
            }
            
            .conflict-actions .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .conflict-actions .btn-secondary:hover {
                background: #545b62;
            }
            
            .recovery-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #e3e3e3;
                border-top: 4px solid #28a745;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px auto;
            }
            
            .cart-recovery-message p {
                margin: 0 0 10px 0;
                font-size: 16px;
                font-weight: 500;
                color: #333;
            }
            
            .recovery-details {
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
            
            /* Enhanced cart validation styles */
            .cart-validation-error {
                background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                color: #856404;
                padding: 16px 20px;
                border-radius: 8px;
                margin: 20px;
                border: 1px solid #ffeaa7;
                text-align: center;
            }
            
            .cart-validation-error h4 {
                margin: 0 0 10px 0;
                color: #856404;
                font-size: 16px;
                font-weight: 600;
            }
            
            .cart-validation-error p {
                margin: 0 0 15px 0;
                line-height: 1.4;
            }
            
            .cart-validation-error .validation-actions {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .cart-validation-error .btn {
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                border: none;
                cursor: pointer;
            }
            
            /* Mobile responsiveness for cart errors */
            @media (max-width: 768px) {
                .cart-conflict-message,
                .cart-recovery-message {
                    margin: 10px;
                    padding: 20px 15px;
                }
                
                .conflict-actions {
                    flex-direction: column;
                    align-items: center;
                }
                
                .conflict-actions .btn {
                    width: 100%;
                    max-width: 200px;
                }
                
                .cart-validation-error {
                    margin: 10px;
                    padding: 12px 16px;
                }
                
                .cart-validation-error .validation-actions {
                    flex-direction: column;
                }
                
                .cart-validation-error .btn {
                    width: 100%;
                }
            }
            
            /* Mobile-specific modal enhancements */
            .pull-to-close-indicator {
                position: absolute;
                top: 8px;
                left: 50%;
                transform: translateX(-50%);
                width: 40px;
                height: 4px;
                background: #bdc3c7;
                border-radius: 2px;
                opacity: 0.6;
                z-index: 1;
            }
            
            .checkout-modal.keyboard-open {
                max-height: 60vh;
            }
            
            .checkout-modal .input-focused {
                border-color: #4a90e2 !important;
                box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2) !important;
                background-color: rgba(74, 144, 226, 0.05) !important;
            }
            
            /* Touch feedback for mobile buttons */
            @media (hover: none) and (pointer: coarse) {
                .checkout-modal .btn:active {
                    transform: scale(0.98);
                    transition: transform 0.1s ease;
                }
                
                .checkout-modal-close:active {
                    transform: scale(0.95);
                    background: rgba(0, 0, 0, 0.3) !important;
                }
                
                .checkout-modal .payment_methods li:active {
                    transform: scale(0.99);
                    transition: transform 0.1s ease;
                }
            }
            
            /* Improved accessibility for mobile */
            .checkout-modal input:focus,
            .checkout-modal select:focus,
            .checkout-modal textarea:focus,
            .checkout-modal button:focus {
                outline: 2px solid #4a90e2;
                outline-offset: 2px;
            }
            
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .checkout-modal {
                    border: 2px solid;
                }
                
                .checkout-modal .btn {
                    border: 2px solid;
                }
                
                .checkout-modal input,
                .checkout-modal select,
                .checkout-modal textarea {
                    border: 2px solid;
                }
            }
            
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .checkout-modal-overlay,
                .checkout-modal,
                .checkout-modal * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
            
            /* Dark mode support for mobile */
            @media (prefers-color-scheme: dark) {
                .checkout-modal {
                    background: #2c3e50;
                    color: #ecf0f1;
                }
                
                .checkout-modal-header {
                    background: #34495e;
                    border-bottom-color: #4a5f7a;
                }
                
                .checkout-modal input,
                .checkout-modal select,
                .checkout-modal textarea {
                    background: #34495e;
                    border-color: #4a5f7a;
                    color: #ecf0f1;
                }
                
                .checkout-modal .btn-primary {
                    background: #3498db;
                }
                
                .checkout-modal .btn-secondary {
                    background: #7f8c8d;
                }
            }
            
            /* Mobile browser-specific optimizations */
            .checkout-modal.landscape-mode {
                max-height: 85vh;
            }
            
            .checkout-modal.landscape-mode .checkout-modal-content {
                max-height: 70vh;
            }
            
            /* Touch feedback styles */
            .touch-feedback {
                opacity: 0.7;
                transform: scale(0.98);
                transition: all 0.1s ease;
            }
            
            .touch-focused {
                border-color: #4a90e2 !important;
                box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.3) !important;
            }
            
            .touch-active {
                background-color: rgba(74, 144, 226, 0.1) !important;
            }
            
            /* Enhanced mobile form validation styles */
            .checkout-modal .field-valid {
                border-color: #28a745 !important;
                box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2) !important;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m6.564.75-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 12px center;
                background-size: 16px;
                padding-right: 40px;
            }
            
            .checkout-modal .field-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 12px center;
                background-size: 16px;
                padding-right: 40px;
            }
            
            /* Safari-specific fixes */
            @supports (-webkit-touch-callout: none) {
                .checkout-modal {
                    height: 100vh;
                    height: calc(var(--vh, 1vh) * 100);
                }
                
                .checkout-modal-content {
                    -webkit-overflow-scrolling: touch;
                    overscroll-behavior: contain;
                }
            }
            
            /* Chrome Android specific fixes */
            @media screen and (-webkit-min-device-pixel-ratio: 0) and (min-resolution: 0.001dpcm) {
                .checkout-modal input[type="email"],
                .checkout-modal input[type="tel"],
                .checkout-modal input[type="text"] {
                    font-size: 16px; /* Prevent zoom on focus */
                }
            }
            
            /* Firefox Mobile specific fixes */
            @-moz-document url-prefix() {
                .checkout-modal .btn.touch-active {
                    background-color: rgba(74, 144, 226, 0.2) !important;
                }
            }
            
            /* Samsung Browser specific fixes */
            @media screen and (-webkit-min-device-pixel-ratio: 0) {
                .checkout-modal {
                    overscroll-behavior: contain;
                }
            }
            
            /* Enhanced accessibility styles for mobile */
            .checkout-modal:focus-within {
                outline: none;
            }
            
            .checkout-modal [role="dialog"] {
                outline: none;
            }
            
            /* High DPI display optimizations */
            @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
                .checkout-modal .btn {
                    border: 0.5px solid transparent;
                }
                
                .checkout-modal input,
                .checkout-modal select,
                .checkout-modal textarea {
                    border-width: 0.5px;
                }
            }
            
            /* Notch and safe area support for newer mobile devices */
            @supports (padding: max(0px)) {
                .checkout-modal {
                    padding-left: max(20px, env(safe-area-inset-left));
                    padding-right: max(20px, env(safe-area-inset-right));
                }
                
                .checkout-modal-header {
                    padding-top: max(20px, env(safe-area-inset-top));
                }
                
                .checkout-modal-content {
                    padding-bottom: max(20px, env(safe-area-inset-bottom));
                }
            }
            
            /* Foldable device support */
            @media (spanning: single-fold-vertical) {
                .checkout-modal {
                    max-width: 50vw;
                }
            }
            
            @media (spanning: single-fold-horizontal) {
                .checkout-modal {
                    max-height: 50vh;
                }
            }
        `)
        .appendTo('head');

    // Export functions globally for backward compatibility
    window.openCheckoutModal = openCheckoutModal;
    window.closeCheckoutModal = closeCheckoutModal;

})(jQuery);