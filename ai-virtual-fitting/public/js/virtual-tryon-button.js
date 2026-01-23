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
        
        // Allow default navigation to proceed
        return true;
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
    
})(jQuery);
