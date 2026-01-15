/**
 * Admin Settings JavaScript for AI Virtual Fitting Plugin
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize admin settings
        AIVirtualFittingAdmin.init();
    });
    
    /**
     * AI Virtual Fitting Admin Object
     */
    window.AIVirtualFittingAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.loadAnalytics();
            this.loadUserCredits();
            this.initSystemStatus();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // API key test button
            $('#test-api-key').on('click', this.testApiConnection);
            
            // Auto-save settings on change
            $('.ai-virtual-fitting-setting').on('change', this.autoSaveSettings);
            
            // Refresh analytics button
            $('#refresh-analytics').on('click', this.loadAnalytics);
            
            // User credit management - support both original and -tab suffixed IDs
            $('#refresh-user-credits, #refresh-user-credits-tab').on('click', this.loadUserCredits);
            $('#search-users, #search-users-tab').on('click', this.searchUsers);
            $('#clear-search, #clear-search-tab').on('click', this.clearUserSearch);
            $('#user-search, #user-search-tab').on('keypress', function(e) {
                if (e.which === 13) {
                    AIVirtualFittingAdmin.searchUsers();
                }
            });
            
            // Credit management modal
            $(document).on('click', '.manage-credits-btn', this.openCreditModal);
            $('#cancel-credit-management').on('click', this.closeCreditModal);
            $('#credit-management-form').on('submit', this.updateUserCredits);
            
            // Pagination
            $(document).on('click', '.user-credits-page', this.loadUserCreditsPage);
            
            // Image size slider
            $('#max_image_size').on('input', this.updateImageSizeDisplay);
        },
        
        /**
         * Test API connection
         */
        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#api-test-result');
            var apiKey = $('#google_ai_api_key').val().trim();
            
            if (!apiKey) {
                AIVirtualFittingAdmin.showApiResult('error', ai_virtual_fitting_admin.messages.api_error);
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text(ai_virtual_fitting_admin.messages.testing_api);
            $result.removeClass('success error').addClass('loading').show()
                   .html('<span class="spinner"></span>' + ai_virtual_fitting_admin.messages.testing_api);
            
            // Make AJAX request
            $.ajax({
                url: ai_virtual_fitting_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_test_api',
                    nonce: ai_virtual_fitting_admin.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        AIVirtualFittingAdmin.showApiResult('success', response.data);
                    } else {
                        AIVirtualFittingAdmin.showApiResult('error', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    AIVirtualFittingAdmin.showApiResult('error', 'Connection failed: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        /**
         * Show API test result
         */
        showApiResult: function(type, message) {
            var $result = $('#api-test-result');
            $result.removeClass('success error loading').addClass(type).show().text(message);
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $result.fadeOut();
                }, 5000);
            }
        },
        
        /**
         * Auto-save settings
         */
        autoSaveSettings: function() {
            var $field = $(this);
            var $indicator = $field.siblings('.save-indicator');
            
            // Show saving indicator
            if ($indicator.length === 0) {
                $indicator = $('<span class="save-indicator">Saving...</span>');
                $field.after($indicator);
            }
            
            $indicator.show().text('Saving...');
            
            // Simulate save delay
            setTimeout(function() {
                $indicator.text('Saved').fadeOut(2000);
            }, 500);
        },
        
        /**
         * Load analytics data
         */
        loadAnalytics: function() {
            var $container = $('.ai-virtual-fitting-analytics');
            var $button = $('#refresh-analytics');
            
            // Show loading state
            $container.addClass('loading');
            if ($button.length) {
                $button.prop('disabled', true).text(ai_virtual_fitting_admin.messages.loading_analytics);
            }
            
            $.ajax({
                url: ai_virtual_fitting_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_get_analytics',
                    nonce: ai_virtual_fitting_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AIVirtualFittingAdmin.updateAnalytics(response.data);
                    } else {
                        console.error('Failed to load analytics:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Analytics request failed:', error);
                },
                complete: function() {
                    $container.removeClass('loading');
                    if ($button.length) {
                        $button.prop('disabled', false).text('Refresh Analytics');
                    }
                }
            });
        },
        
        /**
         * Update analytics display
         */
        updateAnalytics: function(data) {
            // Update metric cards
            $('#metric-total-users .metric').text(data.total_users || 0);
            $('#metric-credits-purchased .metric').text(data.total_credits_purchased || 0);
            $('#metric-credits-used .metric').text(data.total_credits_used || 0);
            $('#metric-credits-remaining .metric').text(data.total_credits_remaining || 0);
            $('#metric-recent-activity .metric').text(data.recent_activity || 0);
            $('#metric-credit-sales .metric').text(data.credit_sales || 0);
            
            // Update last updated timestamp
            if (data.last_updated) {
                $('.analytics-last-updated').text('Last updated: ' + data.last_updated);
            }
        },
        
        /**
         * Initialize system status
         */
        initSystemStatus: function() {
            this.checkSystemStatus();
            
            // Refresh system status every 30 seconds
            setInterval(this.checkSystemStatus, 30000);
        },
        
        /**
         * Check system status
         */
        checkSystemStatus: function() {
            // This would make an AJAX call to check system status
            // For now, we'll just update the display based on current settings
            AIVirtualFittingAdmin.updateSystemStatus();
        },
        
        /**
         * Update system status display
         */
        updateSystemStatus: function() {
            // Check API key status
            var apiKey = $('#google_ai_api_key').val();
            var $apiStatus = $('.status-api-key .status-indicator');
            
            if (apiKey && apiKey.length > 10) {
                $apiStatus.removeClass('error warning').addClass('good');
                $('.status-api-key .status-value').text('Configured');
            } else {
                $apiStatus.removeClass('good warning').addClass('error');
                $('.status-api-key .status-value').text('Not configured');
            }
        },
        
        /**
         * Update image size display
         */
        updateImageSizeDisplay: function() {
            var bytes = parseInt($(this).val());
            var mb = (bytes / 1048576).toFixed(1);
            $(this).siblings('.size-display').text('(' + mb + ' MB)');
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.ai-virtual-fitting-admin-content').prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Format number with commas
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        /**
         * Validate form fields
         */
        validateForm: function() {
            var isValid = true;
            var errors = [];
            
            // Validate API key
            var apiKey = $('#google_ai_api_key').val().trim();
            if (!apiKey) {
                errors.push('Google AI Studio API key is required.');
                isValid = false;
            }
            
            // Validate numeric fields
            var numericFields = ['initial_credits', 'credits_per_package', 'api_retry_attempts'];
            numericFields.forEach(function(fieldName) {
                var value = parseInt($('#' + fieldName).val());
                if (isNaN(value) || value < 1) {
                    errors.push(fieldName.replace('_', ' ') + ' must be a positive number.');
                    isValid = false;
                }
            });
            
            // Validate price field
            var price = parseFloat($('#credits_package_price').val());
            if (isNaN(price) || price < 0.01) {
                errors.push('Package price must be at least 0.01.');
                isValid = false;
            }
            
            // Show errors if any
            if (!isValid) {
                this.showNotification('Please fix the following errors: ' + errors.join(' '), 'error');
            }
            
            return isValid;
        },
        
        /**
         * Load user credits data
         */
        loadUserCredits: function(page) {
            page = page || 1;
            // Support both original and -tab suffixed IDs
            var search = $('#user-search').val() || $('#user-search-tab').val() || '';
            
            // Update both tbody elements if they exist
            $('#user-credits-tbody, #user-credits-tbody-tab').html('<tr><td colspan="7" style="text-align: center; padding: 20px;">Loading...</td></tr>');
            
            $.ajax({
                url: ai_virtual_fitting_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_get_user_credits',
                    nonce: ai_virtual_fitting_admin.nonce,
                    page: page,
                    per_page: 20,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        AIVirtualFittingAdmin.renderUserCreditsTable(response.data);
                    } else {
                        AIVirtualFittingAdmin.showNotification('Failed to load user data: ' + response.data, 'error');
                    }
                },
                error: function() {
                    AIVirtualFittingAdmin.showNotification('Failed to load user data', 'error');
                }
            });
        },
        
        /**
         * Render user credits table
         */
        renderUserCreditsTable: function(data) {
            // Support both original and -tab suffixed IDs
            var tbody = $('#user-credits-tbody');
            if (tbody.length === 0) {
                tbody = $('#user-credits-tbody-tab');
            }
            tbody.empty();
            
            if (data.users.length === 0) {
                tbody.html('<tr><td colspan="7" style="text-align: center; padding: 20px;">No users found</td></tr>');
                $('#user-credits-pagination, #user-credits-pagination-tab').empty();
                return;
            }
            
            data.users.forEach(function(user) {
                var row = '<tr>' +
                    '<td><strong>' + user.display_name + '</strong><br><small>' + user.username + '</small></td>' +
                    '<td>' + user.email + '</td>' +
                    '<td><span class="credits-remaining">' + user.credits_remaining + '</span></td>' +
                    '<td>' + user.total_credits_purchased + '</td>' +
                    '<td>' + user.credits_used + '</td>' +
                    '<td>' + new Date(user.last_activity).toLocaleDateString() + '</td>' +
                    '<td>' +
                        '<button type="button" class="button button-small manage-credits-btn" ' +
                        'data-user-id="' + user.user_id + '" ' +
                        'data-user-name="' + user.display_name + '" ' +
                        'data-current-credits="' + user.credits_remaining + '">Manage</button> ' +
                        '<a href="' + user.profile_url + '" class="button button-small">Profile</a>' +
                    '</td>' +
                '</tr>';
                tbody.append(row);
            });
            
            // Render pagination
            this.renderUserCreditsPagination(data.pagination);
        },
        
        /**
         * Render pagination for user credits
         */
        renderUserCreditsPagination: function(pagination) {
            // Support both original and -tab suffixed IDs
            var container = $('#user-credits-pagination');
            if (container.length === 0) {
                container = $('#user-credits-pagination-tab');
            }
            container.empty();
            
            if (pagination.total_pages <= 1) {
                return;
            }
            
            var html = '<div class="tablenav-pages">';
            html += '<span class="displaying-num">' + pagination.total + ' items</span>';
            
            // Previous page
            if (pagination.current_page > 1) {
                html += '<a class="button user-credits-page" data-page="' + (pagination.current_page - 1) + '">&laquo; Previous</a>';
            }
            
            // Page numbers
            for (var i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += '<span class="button button-primary">' + i + '</span>';
                } else {
                    html += '<a class="button user-credits-page" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            // Next page
            if (pagination.current_page < pagination.total_pages) {
                html += '<a class="button user-credits-page" data-page="' + (pagination.current_page + 1) + '">Next &raquo;</a>';
            }
            
            html += '</div>';
            container.html(html);
        },
        
        /**
         * Search users
         */
        searchUsers: function() {
            AIVirtualFittingAdmin.loadUserCredits(1);
        },
        
        /**
         * Clear user search
         */
        clearUserSearch: function() {
            // Clear both search inputs if they exist
            $('#user-search, #user-search-tab').val('');
            AIVirtualFittingAdmin.loadUserCredits(1);
        },
        
        /**
         * Load specific page of user credits
         */
        loadUserCreditsPage: function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            AIVirtualFittingAdmin.loadUserCredits(page);
        },
        
        /**
         * Open credit management modal
         */
        openCreditModal: function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            $('#manage-user-id').val($btn.data('user-id'));
            $('#manage-user-name').text($btn.data('user-name'));
            $('#manage-current-credits').text($btn.data('current-credits'));
            $('#credit-amount').val(0);
            $('#credit-action').val('set');
            
            $('#credit-management-modal').show();
        },
        
        /**
         * Close credit management modal
         */
        closeCreditModal: function() {
            $('#credit-management-modal').hide();
        },
        
        /**
         * Update user credits
         */
        updateUserCredits: function(e) {
            e.preventDefault();
            
            var userId = $('#manage-user-id').val();
            var action = $('#credit-action').val();
            var amount = parseInt($('#credit-amount').val());
            
            if (!userId || isNaN(amount) || amount < 0) {
                AIVirtualFittingAdmin.showNotification('Please enter a valid amount', 'error');
                return;
            }
            
            $.ajax({
                url: ai_virtual_fitting_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_update_user_credits',
                    nonce: ai_virtual_fitting_admin.nonce,
                    user_id: userId,
                    credits: amount,
                    credit_action: action
                },
                success: function(response) {
                    if (response.success) {
                        AIVirtualFittingAdmin.showNotification(response.data, 'success');
                        AIVirtualFittingAdmin.closeCreditModal();
                        AIVirtualFittingAdmin.loadUserCredits();
                        AIVirtualFittingAdmin.loadAnalytics(); // Refresh analytics too
                    } else {
                        AIVirtualFittingAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    AIVirtualFittingAdmin.showNotification('Failed to update credits', 'error');
                }
            });
        }
    };
    
})(jQuery);