/**
 * Login Modal JavaScript
 * Handles popup login functionality
 *
 * @package AI_Virtual_Fitting
 */

(function($) {
    'use strict';

    // Create modal HTML
    function createLoginModal() {
        const modalHTML = `
            <div class="ai-vf-login-modal-overlay" id="ai-vf-login-modal">
                <div class="ai-vf-login-modal">
                    <div class="ai-vf-login-modal-header">
                        <h2 class="ai-vf-login-modal-title">Welcome Back</h2>
                        <p class="ai-vf-login-modal-subtitle">Log in to access virtual try-on</p>
                        <button class="ai-vf-login-modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="ai-vf-login-modal-body">
                        <div id="ai-vf-login-message"></div>
                        <form class="ai-vf-login-form" id="ai-vf-login-form">
                            <div class="ai-vf-form-group">
                                <label class="ai-vf-form-label" for="ai-vf-username">Username or Email</label>
                                <input type="text" 
                                       class="ai-vf-form-input" 
                                       id="ai-vf-username" 
                                       name="username" 
                                       required 
                                       autocomplete="username">
                            </div>
                            <div class="ai-vf-form-group">
                                <label class="ai-vf-form-label" for="ai-vf-password">Password</label>
                                <input type="password" 
                                       class="ai-vf-form-input" 
                                       id="ai-vf-password" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password">
                            </div>
                            <div class="ai-vf-form-checkbox-group">
                                <input type="checkbox" 
                                       class="ai-vf-form-checkbox" 
                                       id="ai-vf-remember" 
                                       name="remember">
                                <label class="ai-vf-form-checkbox-label" for="ai-vf-remember">Remember me</label>
                            </div>
                            <button type="submit" class="ai-vf-login-submit" id="ai-vf-login-submit">
                                Log In
                            </button>
                        </form>
                        <div class="ai-vf-login-links">
                            <a href="${ai_virtual_fitting_ajax.register_url || '/wp-login.php?action=register'}" class="ai-vf-login-link">Create Account</a>
                            <a href="${ai_virtual_fitting_ajax.lost_password_url || '/wp-login.php?action=lostpassword'}" class="ai-vf-login-link">Forgot Password?</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
    }

    // Show modal
    function showLoginModal() {
        $('#ai-vf-login-modal').addClass('active');
        $('#ai-vf-username').focus();
    }

    // Hide modal
    function hideLoginModal() {
        $('#ai-vf-login-modal').removeClass('active');
        $('#ai-vf-login-form')[0].reset();
        $('#ai-vf-login-message').empty();
    }

    // Show message
    function showMessage(message, type) {
        const messageHTML = `<div class="ai-vf-login-message ${type}">${message}</div>`;
        $('#ai-vf-login-message').html(messageHTML);
    }

    // Handle login form submission
    function handleLogin(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $('#ai-vf-login-submit');
        const username = $('#ai-vf-username').val();
        const password = $('#ai-vf-password').val();
        const remember = $('#ai-vf-remember').is(':checked');
        
        // Disable submit button
        $submit.prop('disabled', true).html('<span class="ai-vf-login-loading"></span>Logging in...');
        
        // Clear previous messages
        $('#ai-vf-login-message').empty();
        
        // AJAX login request
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_vf_ajax_login',
                username: username,
                password: password,
                remember: remember,
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    
                    // Check if there's a stored redirect URL
                    let redirectUrl = window.location.href;
                    if (typeof sessionStorage !== 'undefined') {
                        const storedUrl = sessionStorage.getItem('ai_vf_redirect_after_login');
                        if (storedUrl) {
                            redirectUrl = decodeURIComponent(storedUrl);
                            sessionStorage.removeItem('ai_vf_redirect_after_login');
                        }
                    }
                    
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1000);
                } else {
                    showMessage(response.data.message || 'Login failed. Please try again.', 'error');
                    $submit.prop('disabled', false).html('Log In');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
                $submit.prop('disabled', false).html('Log In');
            }
        });
    }

    // Initialize
    $(document).ready(function() {
        // Create modal
        createLoginModal();
        
        // Intercept login links
        $(document).on('click', 'a[href*="wp-login.php"], .ai-vf-trigger-login', function(e) {
            const href = $(this).attr('href');
            // Only intercept if it's a login link (not register or lost password)
            if (!href || href.indexOf('action=register') === -1 && href.indexOf('action=lostpassword') === -1) {
                e.preventDefault();
                showLoginModal();
            }
        });
        
        // Close modal on overlay click
        $(document).on('click', '#ai-vf-login-modal', function(e) {
            if (e.target === this) {
                hideLoginModal();
            }
        });
        
        // Close modal on close button click
        $(document).on('click', '.ai-vf-login-modal-close', function() {
            hideLoginModal();
        });
        
        // Close modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#ai-vf-login-modal').hasClass('active')) {
                hideLoginModal();
            }
        });
        
        // Handle form submission
        $(document).on('submit', '#ai-vf-login-form', handleLogin);
    });

})(jQuery);
