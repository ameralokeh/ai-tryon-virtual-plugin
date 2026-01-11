/**
 * Help Tooltips for AI Virtual Fitting Admin Interface
 *
 * @package AI_Virtual_Fitting
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeHelpTooltips();
        initializeHelpModals();
        initializeContextualHelp();
    });
    
    /**
     * Initialize help tooltips
     */
    function initializeHelpTooltips() {
        // Add tooltip markup to form fields
        addTooltipMarkup();
        
        // Initialize tooltip behavior
        $('.ai-virtual-fitting-tooltip').on('mouseenter', showTooltip);
        $('.ai-virtual-fitting-tooltip').on('mouseleave', hideTooltip);
        $('.ai-virtual-fitting-tooltip').on('click', toggleTooltip);
        
        // Close tooltips when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.ai-virtual-fitting-tooltip').length) {
                hideAllTooltips();
            }
        });
    }
    
    /**
     * Add tooltip markup to form fields
     */
    function addTooltipMarkup() {
        const tooltipData = {
            'google_ai_api_key': {
                title: 'Google AI Studio API Key',
                content: 'Your API key from Google AI Studio for accessing the Gemini 2.5 Flash Image model. Keep this secure and never share it publicly.',
                link: 'https://aistudio.google.com/app/apikey',
                linkText: 'Get API Key'
            },
            'initial_credits': {
                title: 'Initial Free Credits',
                content: 'Number of free virtual fitting credits given to new users when they first access the virtual fitting feature. This allows users to try the service before purchasing.',
                example: 'Recommended: 2-5 credits'
            },
            'credits_per_package': {
                title: 'Credits per Package',
                content: 'Number of virtual fitting credits included in each purchasable package. This determines the value proposition for customers.',
                example: 'Common values: 10, 20, or 50 credits'
            },
            'credits_package_price': {
                title: 'Package Price',
                content: 'Price for each credit package in your store currency. Consider your costs for Google AI Studio API calls plus desired profit margin.',
                example: 'Example: $10 for 20 credits = $0.50 per fitting'
            },
            'max_image_size': {
                title: 'Maximum Image Size',
                content: 'Maximum file size allowed for uploaded customer images in bytes. Larger images provide better quality but use more bandwidth and processing time.',
                example: '10485760 bytes = 10MB (recommended)'
            },
            'allowed_image_types': {
                title: 'Allowed Image Types',
                content: 'Image formats accepted for upload. JPEG, PNG, and WebP provide the best balance of quality and compatibility.',
                example: 'Supported: JPEG, PNG, WebP'
            },
            'api_retry_attempts': {
                title: 'API Retry Attempts',
                content: 'Number of times to retry failed API calls before giving up. Higher values improve reliability but may increase processing time.',
                example: 'Recommended: 3 attempts'
            },
            'api_timeout': {
                title: 'API Timeout',
                content: 'Maximum time to wait for Google AI Studio API responses in seconds. Longer timeouts allow for complex processing but may impact user experience.',
                example: 'Recommended: 60-120 seconds'
            },
            'enable_logging': {
                title: 'Enable System Logging',
                content: 'Log system events, errors, and debugging information. Useful for troubleshooting but may impact performance on high-traffic sites.',
                note: 'Disable in production for better performance'
            },
            'temp_file_cleanup_hours': {
                title: 'Temporary File Cleanup',
                content: 'Hours to keep temporary files (uploaded images, processing results) before automatic cleanup. Shorter periods save disk space.',
                example: 'Recommended: 24-48 hours'
            },
            'enable_analytics': {
                title: 'Enable Analytics',
                content: 'Track usage statistics, performance metrics, and user behavior. Provides valuable insights for optimization and business decisions.',
                note: 'Anonymized data only'
            },
            'enable_email_notifications': {
                title: 'Customer Email Notifications',
                content: 'Send email notifications to customers for credit purchases, processing completion, and other events.',
                note: 'Improves customer experience'
            },
            'admin_email_notifications': {
                title: 'Admin Email Notifications',
                content: 'Send email alerts to administrators for system errors, high usage, and other important events.',
                note: 'Helps with proactive monitoring'
            }
        };
        
        // Add tooltips to existing form fields
        $.each(tooltipData, function(fieldId, data) {
            const $field = $('#' + fieldId);
            if ($field.length) {
                const $wrapper = $field.closest('tr, .form-field, .setting-row');
                if ($wrapper.length) {
                    const $helpIcon = $('<span class="ai-virtual-fitting-tooltip" data-field="' + fieldId + '">' +
                        '<span class="dashicons dashicons-editor-help"></span>' +
                        '</span>');
                    
                    $wrapper.find('th, label').first().append($helpIcon);
                }
            }
        });
        
        // Store tooltip data for access
        window.aiVirtualFittingTooltips = tooltipData;
    }
    
    /**
     * Show tooltip
     */
    function showTooltip(e) {
        const $tooltip = $(this);
        const fieldId = $tooltip.data('field');
        const tooltipData = window.aiVirtualFittingTooltips[fieldId];
        
        if (!tooltipData) return;
        
        // Remove existing tooltips
        $('.ai-virtual-fitting-tooltip-popup').remove();
        
        // Create tooltip popup
        const $popup = createTooltipPopup(tooltipData);
        
        // Position tooltip
        const offset = $tooltip.offset();
        const tooltipWidth = 300;
        const tooltipHeight = $popup.outerHeight();
        
        let left = offset.left + $tooltip.outerWidth() + 10;
        let top = offset.top - (tooltipHeight / 2);
        
        // Adjust position if tooltip goes off screen
        if (left + tooltipWidth > $(window).width()) {
            left = offset.left - tooltipWidth - 10;
        }
        
        if (top < $(window).scrollTop()) {
            top = $(window).scrollTop() + 10;
        }
        
        if (top + tooltipHeight > $(window).scrollTop() + $(window).height()) {
            top = $(window).scrollTop() + $(window).height() - tooltipHeight - 10;
        }
        
        $popup.css({
            left: left + 'px',
            top: top + 'px'
        }).fadeIn(200);
        
        $('body').append($popup);
    }
    
    /**
     * Hide tooltip
     */
    function hideTooltip() {
        $('.ai-virtual-fitting-tooltip-popup').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Toggle tooltip (for mobile)
     */
    function toggleTooltip(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if ($('.ai-virtual-fitting-tooltip-popup').length) {
            hideTooltip();
        } else {
            showTooltip.call(this, e);
        }
    }
    
    /**
     * Hide all tooltips
     */
    function hideAllTooltips() {
        $('.ai-virtual-fitting-tooltip-popup').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Create tooltip popup
     */
    function createTooltipPopup(data) {
        let content = '<div class="ai-virtual-fitting-tooltip-popup">';
        content += '<div class="tooltip-header">';
        content += '<h4>' + data.title + '</h4>';
        content += '<button class="tooltip-close" type="button">&times;</button>';
        content += '</div>';
        content += '<div class="tooltip-content">';
        content += '<p>' + data.content + '</p>';
        
        if (data.example) {
            content += '<div class="tooltip-example">';
            content += '<strong>Example:</strong> ' + data.example;
            content += '</div>';
        }
        
        if (data.note) {
            content += '<div class="tooltip-note">';
            content += '<strong>Note:</strong> ' + data.note;
            content += '</div>';
        }
        
        if (data.link) {
            content += '<div class="tooltip-link">';
            content += '<a href="' + data.link + '" target="_blank" class="button button-secondary">';
            content += data.linkText || 'Learn More';
            content += '</a>';
            content += '</div>';
        }
        
        content += '</div>';
        content += '</div>';
        
        const $popup = $(content);
        
        // Close button handler
        $popup.find('.tooltip-close').on('click', hideTooltip);
        
        return $popup;
    }
    
    /**
     * Initialize help modals
     */
    function initializeHelpModals() {
        // Add help buttons to sections
        $('.form-table').each(function() {
            const $table = $(this);
            const sectionTitle = $table.prev('h2, h3').text();
            
            if (sectionTitle) {
                const $helpButton = $('<button type="button" class="button button-secondary section-help-btn" data-section="' + 
                    sectionTitle.toLowerCase().replace(/\s+/g, '-') + '">' +
                    '<span class="dashicons dashicons-editor-help"></span> Help' +
                    '</button>');
                
                $table.prev('h2, h3').append($helpButton);
            }
        });
        
        // Help button click handler
        $('.section-help-btn').on('click', function() {
            const section = $(this).data('section');
            showHelpModal(section);
        });
    }
    
    /**
     * Show help modal
     */
    function showHelpModal(section) {
        const helpContent = getHelpContent(section);
        
        if (!helpContent) return;
        
        const modal = $('<div class="ai-virtual-fitting-help-modal">' +
            '<div class="help-modal-overlay"></div>' +
            '<div class="help-modal-content">' +
            '<div class="help-modal-header">' +
            '<h2>' + helpContent.title + '</h2>' +
            '<button class="help-modal-close" type="button">&times;</button>' +
            '</div>' +
            '<div class="help-modal-body">' +
            helpContent.content +
            '</div>' +
            '<div class="help-modal-footer">' +
            '<button type="button" class="button button-primary help-modal-close">Got it</button>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        
        // Close handlers
        modal.find('.help-modal-close, .help-modal-overlay').on('click', function() {
            modal.fadeOut(300, function() {
                modal.remove();
            });
        });
        
        // Escape key handler
        $(document).on('keyup.help-modal', function(e) {
            if (e.keyCode === 27) {
                modal.find('.help-modal-close').click();
                $(document).off('keyup.help-modal');
            }
        });
        
        modal.fadeIn(300);
    }
    
    /**
     * Get help content for section
     */
    function getHelpContent(section) {
        const helpSections = {
            'api-settings': {
                title: 'API Settings Help',
                content: '<h3>Google AI Studio Configuration</h3>' +
                    '<p>These settings control how the plugin connects to Google AI Studio for AI processing:</p>' +
                    '<ul>' +
                    '<li><strong>API Key:</strong> Your unique key from Google AI Studio. Keep this secure!</li>' +
                    '<li><strong>Timeout:</strong> How long to wait for API responses (60-120 seconds recommended)</li>' +
                    '<li><strong>Retry Attempts:</strong> Number of retries for failed requests (3 recommended)</li>' +
                    '</ul>' +
                    '<h3>Getting Your API Key</h3>' +
                    '<ol>' +
                    '<li>Visit <a href="https://aistudio.google.com" target="_blank">Google AI Studio</a></li>' +
                    '<li>Sign in with your Google account</li>' +
                    '<li>Navigate to API Keys section</li>' +
                    '<li>Create a new API key for Gemini 2.5 Flash Image</li>' +
                    '<li>Copy the key and paste it in the settings</li>' +
                    '</ol>'
            },
            'credit-system': {
                title: 'Credit System Help',
                content: '<h3>How Credits Work</h3>' +
                    '<p>The credit system controls access to virtual fitting features:</p>' +
                    '<ul>' +
                    '<li><strong>Initial Credits:</strong> Free credits for new users to try the service</li>' +
                    '<li><strong>Credit Packages:</strong> Purchasable bundles sold through WooCommerce</li>' +
                    '<li><strong>Usage:</strong> 1 credit consumed per successful virtual fitting</li>' +
                    '</ul>' +
                    '<h3>Pricing Strategy</h3>' +
                    '<p>Consider these factors when setting prices:</p>' +
                    '<ul>' +
                    '<li>Google AI Studio API costs per request</li>' +
                    '<li>Your desired profit margin</li>' +
                    '<li>Competitive pricing in your market</li>' +
                    '<li>Value provided to customers</li>' +
                    '</ul>' +
                    '<p><strong>Example:</strong> If API costs $0.10 per request, you might charge $0.50 per credit (400% markup)</p>'
            },
            'image-settings': {
                title: 'Image Settings Help',
                content: '<h3>Image Upload Configuration</h3>' +
                    '<p>These settings control image upload and processing:</p>' +
                    '<ul>' +
                    '<li><strong>Maximum Size:</strong> Balance quality vs. performance (10MB recommended)</li>' +
                    '<li><strong>Allowed Types:</strong> JPEG, PNG, WebP for best compatibility</li>' +
                    '<li><strong>Quality:</strong> Higher quality = better results but slower processing</li>' +
                    '</ul>' +
                    '<h3>Image Quality Guidelines</h3>' +
                    '<p>For best virtual fitting results, recommend customers use:</p>' +
                    '<ul>' +
                    '<li>Well-lit photos with even lighting</li>' +
                    '<li>Plain backgrounds without distractions</li>' +
                    '<li>Full-body shots facing the camera</li>' +
                    '<li>High resolution (800x600 minimum)</li>' +
                    '</ul>'
            },
            'performance-settings': {
                title: 'Performance Settings Help',
                content: '<h3>System Performance</h3>' +
                    '<p>These settings help optimize system performance:</p>' +
                    '<ul>' +
                    '<li><strong>Logging:</strong> Enable for debugging, disable for production performance</li>' +
                    '<li><strong>File Cleanup:</strong> Shorter periods save disk space</li>' +
                    '<li><strong>Analytics:</strong> Provides insights but uses some resources</li>' +
                    '</ul>' +
                    '<h3>Performance Tips</h3>' +
                    '<ul>' +
                    '<li>Use caching plugins for better performance</li>' +
                    '<li>Optimize your server for image processing</li>' +
                    '<li>Monitor API usage to avoid rate limits</li>' +
                    '<li>Consider CDN for faster image delivery</li>' +
                    '</ul>'
            }
        };
        
        return helpSections[section] || null;
    }
    
    /**
     * Initialize contextual help
     */
    function initializeContextualHelp() {
        // Add contextual help based on current settings
        checkApiKeyStatus();
        checkWooCommerceStatus();
        checkSystemRequirements();
        
        // Refresh contextual help when settings change
        $('input, select, textarea').on('change', function() {
            setTimeout(function() {
                updateContextualHelp();
            }, 100);
        });
    }
    
    /**
     * Check API key status
     */
    function checkApiKeyStatus() {
        const $apiKeyField = $('#google_ai_api_key');
        const apiKey = $apiKeyField.val();
        
        if (!apiKey || apiKey.length < 10) {
            showContextualMessage('api-key-missing', 
                'API Key Required', 
                'You need to configure your Google AI Studio API key for the plugin to work. <a href="https://aistudio.google.com" target="_blank">Get your API key here</a>.',
                'warning'
            );
        } else {
            hideContextualMessage('api-key-missing');
        }
    }
    
    /**
     * Check WooCommerce status
     */
    function checkWooCommerceStatus() {
        if (typeof woocommerce_admin === 'undefined') {
            showContextualMessage('woocommerce-missing',
                'WooCommerce Required',
                'This plugin requires WooCommerce to be installed and activated for credit purchases to work.',
                'error'
            );
        }
    }
    
    /**
     * Check system requirements
     */
    function checkSystemRequirements() {
        // This would typically be populated by PHP
        if (window.aiVirtualFittingSystemCheck) {
            const checks = window.aiVirtualFittingSystemCheck;
            
            if (!checks.php_version_ok) {
                showContextualMessage('php-version',
                    'PHP Version Warning',
                    'Your PHP version (' + checks.php_version + ') is below the recommended version. Consider upgrading to PHP 8.0+ for better performance.',
                    'warning'
                );
            }
            
            if (!checks.memory_ok) {
                showContextualMessage('memory-limit',
                    'Memory Limit Warning',
                    'Your PHP memory limit (' + checks.memory_limit + ') may be too low for image processing. Consider increasing it to 256M or higher.',
                    'warning'
                );
            }
        }
    }
    
    /**
     * Update contextual help
     */
    function updateContextualHelp() {
        checkApiKeyStatus();
    }
    
    /**
     * Show contextual message
     */
    function showContextualMessage(id, title, message, type) {
        const $container = getContextualMessageContainer();
        const existingMessage = $container.find('#contextual-' + id);
        
        if (existingMessage.length) {
            return; // Message already shown
        }
        
        const $message = $('<div class="notice notice-' + type + ' contextual-help-message" id="contextual-' + id + '">' +
            '<p><strong>' + title + ':</strong> ' + message + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
            '</div>');
        
        $message.find('.notice-dismiss').on('click', function() {
            $message.fadeOut(300, function() {
                $message.remove();
            });
        });
        
        $container.append($message);
    }
    
    /**
     * Hide contextual message
     */
    function hideContextualMessage(id) {
        $('#contextual-' + id).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    /**
     * Get contextual message container
     */
    function getContextualMessageContainer() {
        let $container = $('#contextual-help-messages');
        
        if (!$container.length) {
            $container = $('<div id="contextual-help-messages"></div>');
            $('.wrap h1').after($container);
        }
        
        return $container;
    }
    
})(jQuery);