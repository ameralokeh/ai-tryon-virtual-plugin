/**
 * Virtual Try-On Button JavaScript
 * 
 * Handles click events, analytics logging, and authentication redirects
 * for the "Try on Virtually" button.
 *
 * @package AI_Virtual_Fitting
 */

(function($) {
    'use strict';
    
    /**
     * Initialize button functionality when DOM is ready
     */
    $(document).ready(function() {
        initTryOnButton();
        initOverlayButton();
    });
    
    /**
     * Initialize Try-On button event handlers
     */
    function initTryOnButton() {
        // Find all Try-On buttons
        const $buttons = $('.ai-virtual-fitting-tryon-button');
        
        if ($buttons.length === 0) {
            return;
        }
        
        // Attach click handler to each button
        $buttons.on('click', handleButtonClick);
        
        // Attach keyboard handler for accessibility
        $buttons.on('keydown', handleButtonKeydown);
        
        // Log initialization
        if (window.console && window.console.log) {
            console.log('AI Virtual Fitting: Try-On button initialized');
        }
    }
    
    /**
     * Handle button click event
     * 
     * @param {Event} e Click event
     */
    function handleButtonClick(e) {
        const $button = $(this);
        const productId = $button.data('product-id');
        const href = $button.attr('href');
        
        console.log('AI Virtual Fitting: Button clicked, href:', href);
        
        // Stop event from bubbling to parent elements (like product image links)
        e.stopPropagation();
        
        // Log button click for analytics
        logButtonClick(productId);
        
        // Check if user is logged in (check if href contains wp-login.php)
        if (href && href.indexOf('wp-login.php') !== -1) {
            // User is not logged in - show login modal instead of redirecting
            e.preventDefault();
            
            // Store the intended destination for after login
            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('ai_vf_redirect_after_login', href.split('redirect_to=')[1] || href);
            }
            
            // Trigger login modal (from login-modal.js)
            if ($('#ai-vf-login-modal').length > 0) {
                $('#ai-vf-login-modal').addClass('active');
                $('#ai-vf-username').focus();
            } else {
                // Fallback: redirect to login page if modal not available
                window.location.href = href;
            }
            
            return false;
        }
        
        // User is logged in - proceed with navigation
        $button.addClass('loading');
        
        // Announce to screen readers
        announceToScreenReader('Navigating to virtual try-on page');
        
        // Navigate programmatically to ensure it works
        console.log('AI Virtual Fitting: Navigating to:', href);
        window.location.href = href;
        
        // Prevent default to stop any parent link behavior
        e.preventDefault();
        return false;
    }
    
    /**
     * Handle keyboard navigation
     * 
     * @param {Event} e Keyboard event
     */
    function handleButtonKeydown(e) {
        // Handle Enter and Space keys
        if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
            e.preventDefault();
            $(this).trigger('click');
        }
    }
    
    /**
     * Announce message to screen readers
     * 
     * @param {string} message Message to announce
     */
    function announceToScreenReader(message) {
        // Create or get the live region
        let $liveRegion = $('#ai-virtual-fitting-sr-live');
        
        if ($liveRegion.length === 0) {
            $liveRegion = $('<div>', {
                id: 'ai-virtual-fitting-sr-live',
                'class': 'ai-virtual-fitting-sr-only',
                'role': 'status',
                'aria-live': 'polite',
                'aria-atomic': 'true'
            }).appendTo('body');
        }
        
        // Clear and set new message
        $liveRegion.text('');
        setTimeout(function() {
            $liveRegion.text(message);
        }, 100);
    }
    
    /**
     * Log button click event via AJAX
     * 
     * @param {number} productId Product ID
     */
    function logButtonClick(productId) {
        // Check if AJAX is available
        if (typeof ai_virtual_fitting_tryon === 'undefined' || !ai_virtual_fitting_tryon.ajax_url) {
            return;
        }
        
        // Use sendBeacon for better performance (non-blocking)
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('action', 'ai_virtual_fitting_log_tryon_button_click');
            formData.append('nonce', ai_virtual_fitting_tryon.nonce);
            formData.append('product_id', productId);
            
            navigator.sendBeacon(ai_virtual_fitting_tryon.ajax_url, formData);
        } else {
            // Fallback to AJAX for older browsers
            $.ajax({
                url: ai_virtual_fitting_tryon.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_log_tryon_button_click',
                    nonce: ai_virtual_fitting_tryon.nonce,
                    product_id: productId
                },
                // Don't wait for response
                async: true,
                timeout: 1000,
                success: function(response) {
                    if (window.console && window.console.log) {
                        console.log('AI Virtual Fitting: Button click logged', response);
                    }
                },
                error: function(xhr, status, error) {
                    // Silently fail - analytics shouldn't block navigation
                    if (window.console && window.console.warn) {
                        console.warn('AI Virtual Fitting: Failed to log button click', error);
                    }
                }
            });
        }
    }
    
    /**
     * Initialize overlay button on product image
     */
    function initOverlayButton() {
        // Check if overlay button is enabled
        if (typeof ai_virtual_fitting_tryon === 'undefined' || !ai_virtual_fitting_tryon.show_overlay) {
            console.log('AI Virtual Fitting: Overlay button disabled or config not found');
            return;
        }
        
        console.log('AI Virtual Fitting: Initializing overlay button...');
        
        // Find the main product image container - try multiple selectors
        let $productImage = $('.woocommerce-product-gallery__wrapper').first();
        
        if ($productImage.length === 0) {
            $productImage = $('.product-images').first();
        }
        
        if ($productImage.length === 0) {
            $productImage = $('.images').first();
        }
        
        if ($productImage.length === 0) {
            $productImage = $('.woocommerce-product-gallery').first();
        }
        
        if ($productImage.length === 0) {
            console.log('AI Virtual Fitting: Product image container not found');
            return;
        }
        
        console.log('AI Virtual Fitting: Found product image container', $productImage);
        
        // Get product ID from existing button or page
        let productId = $('.ai-virtual-fitting-tryon-button').data('product-id');
        
        if (!productId) {
            // Try to get from form
            productId = $('input[name="product_id"], input[name="add-to-cart"]').val();
        }
        
        if (!productId) {
            console.log('AI Virtual Fitting: Product ID not found');
            return;
        }
        
        console.log('AI Virtual Fitting: Product ID:', productId);
        
        // Build the URL
        let buttonUrl = ai_virtual_fitting_tryon.page_url + '?product_id=' + productId;
        
        // Check if login is required and user is not logged in
        if (ai_virtual_fitting_tryon.require_login && !ai_virtual_fitting_tryon.user_logged_in) {
            // Build login URL with redirect
            buttonUrl = '/wp-login.php?redirect_to=' + encodeURIComponent(buttonUrl);
        }
        
        console.log('AI Virtual Fitting: Button URL:', buttonUrl);
        
        // Create overlay button HTML
        const overlayButton = $('<a>', {
            'href': buttonUrl,
            'class': 'ai-virtual-fitting-overlay-button',
            'data-product-id': productId,
            'role': 'button',
            'aria-label': 'Try on virtually',
            'title': ai_virtual_fitting_tryon.button_text || 'Try On'
        }).html(`
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z" 
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 5.5V3M12 21V18.5M18.5 12H21M3 12H5.5M17.5 6.5L19.5 4.5M4.5 19.5L6.5 17.5M17.5 17.5L19.5 19.5M4.5 4.5L6.5 6.5" 
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="ai-virtual-fitting-overlay-button-text">Try On</span>
        `);
        
        // Find the first image element
        const $firstImage = $productImage.find('img').first();
        
        if ($firstImage.length === 0) {
            console.log('AI Virtual Fitting: No image found in container');
            return;
        }
        
        // Get the parent of the image (could be <a> or <div>)
        let $imageWrapper = $firstImage.parent();
        
        // Make sure the wrapper has position relative
        if (!$imageWrapper.hasClass('ai-virtual-fitting-image-wrapper')) {
            $imageWrapper.addClass('ai-virtual-fitting-image-wrapper').css('position', 'relative');
        }
        
        // Append overlay button to the image wrapper
        $imageWrapper.append(overlayButton);
        
        console.log('AI Virtual Fitting: Overlay button added to', $imageWrapper);
        
        // Use the same click handler as the main Try-On button
        overlayButton.on('click', handleButtonClick);
        overlayButton.on('keydown', handleButtonKeydown);
        
        // Log initialization
        if (window.console && window.console.log) {
            console.log('AI Virtual Fitting: Overlay button initialized successfully');
        }
    }
    
})(jQuery);