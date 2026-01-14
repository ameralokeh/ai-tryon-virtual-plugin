<?php
/**
 * Virtual Fitting Page Template
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div id="ai-virtual-fitting-page" class="ai-virtual-fitting-container">
    <div class="virtual-fitting-header">
        <h1><?php _e('AI Virtual Fitting Experience', 'ai-virtual-fitting'); ?></h1>
        <p class="virtual-fitting-description">
            <?php _e('Try on our beautiful wedding dresses virtually using AI technology. Upload your photo and see how you look in our stunning collection.', 'ai-virtual-fitting'); ?>
        </p>
    </div>

    <?php if (!is_user_logged_in()): ?>
        <!-- Authentication Gate for Non-Logged-In Users -->
        <div class="authentication-gate">
            <div class="auth-message">
                <h3><?php _e('Please Log In to Continue', 'ai-virtual-fitting'); ?></h3>
                <p><?php _e('You need to be logged in to use our virtual fitting feature. Please log in or create an account to get started.', 'ai-virtual-fitting'); ?></p>
                <div class="auth-buttons">
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                        <?php _e('Log In', 'ai-virtual-fitting'); ?>
                    </a>
                    <a href="<?php echo wp_registration_url(); ?>" class="button button-secondary">
                        <?php _e('Create Account', 'ai-virtual-fitting'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="virtual-fitting-content <?php echo !is_user_logged_in() ? 'disabled' : ''; ?>">
        
        <!-- Credits Display -->
        <div class="credits-section">
            <div class="credits-display">
                <span class="credits-label"><?php _e('Virtual Fitting Credits:', 'ai-virtual-fitting'); ?></span>
                <span class="credits-count" id="credits-count"><?php echo is_user_logged_in() ? $credits : 0; ?></span>
            </div>
            
            <?php if (is_user_logged_in() && $credits <= 0): ?>
                <div class="no-credits-message">
                    <p><?php _e('You have no remaining credits. Purchase more to continue using virtual fitting.', 'ai-virtual-fitting'); ?></p>
                    <button id="purchase-credits-btn" class="button button-primary">
                        <?php _e('Purchase 20 Credits - $10', 'ai-virtual-fitting'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Slider Section -->
        <div class="product-slider-section">
            <h2><?php _e('Choose a Wedding Dress', 'ai-virtual-fitting'); ?></h2>
            <div class="product-slider-container">
                <div class="product-slider" id="product-slider">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                <div class="product-image">
                                    <?php if ($product['image']): ?>
                                        <img src="<?php echo esc_url($product['image'][0]); ?>" 
                                             alt="<?php echo esc_attr($product['name']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <?php _e('No Image', 'ai-virtual-fitting'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo esc_html($product['name']); ?></h3>
                                    <div class="product-price"><?php echo $product['price']; ?></div>
                                    <button class="select-product-btn button" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                        <?php _e('Select This Dress', 'ai-virtual-fitting'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products-message">
                            <p><?php _e('No products available for virtual fitting at the moment.', 'ai-virtual-fitting'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Slider Navigation -->
                <button class="slider-nav prev-btn" id="slider-prev" aria-label="<?php _e('Previous products', 'ai-virtual-fitting'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <button class="slider-nav next-btn" id="slider-next" aria-label="<?php _e('Next products', 'ai-virtual-fitting'); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>

        <!-- Image Upload Section -->
        <div class="image-upload-section">
            <h2><?php _e('Upload Your Photo', 'ai-virtual-fitting'); ?></h2>
            <div class="upload-container">
                <div class="upload-area" id="upload-area">
                    <div class="upload-placeholder">
                        <span class="dashicons dashicons-camera"></span>
                        <p><?php _e('Click to upload your photo or drag and drop', 'ai-virtual-fitting'); ?></p>
                        <p class="upload-requirements">
                            <?php _e('Supported formats: JPEG, PNG, WebP. Max size: 10MB', 'ai-virtual-fitting'); ?>
                        </p>
                    </div>
                    <input type="file" id="customer-image-input" accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>
                
                <div class="uploaded-image-preview" id="uploaded-image-preview" style="display: none;">
                    <img id="preview-image" src="" alt="<?php _e('Uploaded photo preview', 'ai-virtual-fitting'); ?>">
                    <button class="remove-image-btn" id="remove-image-btn" aria-label="<?php _e('Remove uploaded image', 'ai-virtual-fitting'); ?>">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Virtual Fitting Controls -->
        <div class="fitting-controls-section">
            <div class="selected-product-display" id="selected-product-display" style="display: none;">
                <h3><?php _e('Selected Dress:', 'ai-virtual-fitting'); ?></h3>
                <div class="selected-product-info">
                    <img id="selected-product-image" src="" alt="">
                    <div class="selected-product-details">
                        <h4 id="selected-product-name"></h4>
                        <div id="selected-product-price"></div>
                    </div>
                </div>
            </div>

            <div class="try-on-section">
                <button id="try-on-btn" class="button button-primary button-large" disabled>
                    <?php _e('Try On This Dress', 'ai-virtual-fitting'); ?>
                </button>
                <p class="try-on-note">
                    <?php _e('Select a dress and upload your photo to enable virtual fitting', 'ai-virtual-fitting'); ?>
                </p>
            </div>
        </div>

        <!-- Processing Section -->
        <div class="processing-section" id="processing-section" style="display: none;">
            <div class="processing-indicator">
                <div class="spinner"></div>
                <h3><?php _e('Creating Your Virtual Fitting...', 'ai-virtual-fitting'); ?></h3>
                <p><?php _e('Please wait while our AI processes your image. This may take a few moments.', 'ai-virtual-fitting'); ?></p>
                <div class="processing-status" id="processing-status">
                    <?php _e('Starting virtual fitting...', 'ai-virtual-fitting'); ?>
                </div>
                <div class="processing-estimate" id="processing-estimate">
                    <?php _e('Estimated time: 30-60 seconds', 'ai-virtual-fitting'); ?>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="results-section" style="display: none;">
            <h2><?php _e('Your Virtual Fitting Result', 'ai-virtual-fitting'); ?></h2>
            <div class="result-container">
                <div class="result-image-container">
                    <img id="result-image" src="" alt="<?php _e('Virtual fitting result', 'ai-virtual-fitting'); ?>">
                </div>
                <div class="result-actions">
                    <button id="download-result-btn" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Download Result', 'ai-virtual-fitting'); ?>
                    </button>
                    <button id="try-another-btn" class="button button-secondary">
                        <?php _e('Try Another Dress', 'ai-virtual-fitting'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        <div class="error-messages" id="error-messages" style="display: none;">
            <!-- Error content will be dynamically inserted here -->
        </div>

        <!-- Success Messages -->
        <div class="success-messages" id="success-messages" style="display: none;">
            <!-- Success content will be dynamically inserted here -->
        </div>

    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay" style="display: none;">
        <!-- Loading content will be dynamically inserted here -->
    </div>

    <!-- Checkout Modal -->
    <div class="checkout-modal-overlay" id="checkout-modal" style="display: none;">
        <div class="checkout-modal">
            <div class="checkout-modal-header">
                <h3>Purchase Credits</h3>
                <button class="checkout-modal-close" id="close-checkout-modal" type="button">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="checkout-modal-content">
                <div class="checkout-loading" id="checkout-loading">
                    <div class="checkout-spinner"></div>
                    <p>Loading checkout...</p>
                </div>
                <div class="checkout-form-container" id="checkout-form-container" style="display: none;">
                    <!-- WooCommerce checkout form will be loaded here -->
                </div>
                <div class="checkout-success" id="checkout-success" style="display: none;">
                    <div class="success-icon">
                        <span class="dashicons dashicons-yes"></span>
                    </div>
                    <h4>Purchase Successful!</h4>
                    <p>Your 20 credits have been added to your account. You can now continue with virtual fitting.</p>
                    <div class="success-details">
                        <div class="success-detail">
                            <span class="detail-label">Credits Added:</span>
                            <span class="detail-value">20 Credits</span>
                        </div>
                        <div class="success-detail">
                            <span class="detail-label">Amount Paid:</span>
                            <span class="detail-value">$10.00</span>
                        </div>
                    </div>
                    <p class="success-note">This modal will close automatically in a few seconds.</p>
                    <button class="button button-primary" id="continue-fitting-btn">Continue Virtual Fitting</button>
                </div>
                <div class="checkout-error" id="checkout-error" style="display: none;">
                    <div class="error-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <h4>Payment Failed</h4>
                    <p id="checkout-error-message">There was an issue processing your payment. Please try again.</p>
                    <div class="checkout-error-actions">
                        <button class="button button-primary" id="retry-checkout-btn">Try Again</button>
                        <button class="button button-secondary" id="cancel-checkout-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php get_footer();