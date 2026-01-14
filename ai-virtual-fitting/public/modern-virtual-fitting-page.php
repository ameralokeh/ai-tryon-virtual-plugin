<?php
/**
 * Modern Virtual Fitting Page Template - Three Panel Design
 * Left: Upload & Result | Center: Main Preview | Right: Product Selection
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ai-virtual-fitting-app">
    <!-- Credits Banner -->
    <div class="credits-banner" id="credits-banner">
        <div class="credits-banner-content">
            <div class="credits-info">
                <div class="credits-title">
                    <svg viewBox="0 0 24 24" class="credits-icon">
                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
                    </svg>
                    <span>Virtual Try-On Credits</span>
                </div>
                <div class="credits-stats">
                    <div class="credit-stat">
                        <div class="credit-number" id="total-credits"><?php echo $is_logged_in ? $credits : '0'; ?></div>
                        <div class="credit-label">Available</div>
                    </div>
                    <div class="credit-divider"></div>
                    <div class="credit-stat free-stat">
                        <div class="credit-number free-number" id="free-credits"><?php echo $is_logged_in ? $free_credits : '0'; ?></div>
                        <div class="credit-label">Free Remaining</div>
                    </div>
                </div>
            </div>
            <div class="credits-actions">
                <?php if ($is_logged_in): ?>
                    <button class="btn btn-primary credits-purchase-btn" id="add-credits-btn">
                        <svg viewBox="0 0 24 24" class="btn-icon">
                            <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                        </svg>
                        Get More Credits
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary credits-purchase-btn">
                        <svg viewBox="0 0 24 24" class="btn-icon">
                            <path d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z"/>
                        </svg>
                        Login to Start
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="fitting-container">
        <!-- Left Panel - Upload & Virtual Result -->
        <div class="fitting-panel">
            <!-- Upload Section -->
            <div class="upload-section">
                <div class="upload-area" id="upload-area">
                    <div class="upload-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                    </div>
                    <div class="upload-text">Upload Your Photo</div>
                    <div class="upload-hint">Drag & drop or browse</div>
                    
                    <!-- Floating action buttons -->
                    <div class="floating-buttons" style="display: none;">
                        <button class="floating-btn reset-btn" type="button" title="Reset">
                            Reset
                        </button>
                        <button class="floating-btn download-btn" type="button" title="Download">
                            Download
                        </button>
                    </div>
                </div>
                
                <!-- Hidden file input - moved outside upload area to prevent event bubbling -->
                <input type="file" id="customer-image-input" accept="image/*" style="display: none;">
            </div>

            <!-- Virtual Fitting Result -->
            <div class="virtual-result" id="virtual-result">
                <div class="result-preview">
                    <img id="virtual-result-image" class="result-image" alt="Virtual fitting result">
                    
                    <!-- Floating action buttons for result -->
                    <div class="floating-buttons result-buttons">
                        <button class="floating-btn reset-btn" id="try-another-btn" type="button" title="Try Another">
                            Try Another
                        </button>
                        <button class="floating-btn download-btn" id="save-image-btn" type="button" title="Save Result">
                            Save Result
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Center Panel - Main Preview -->
        <div class="main-preview">
            <div class="main-preview-gallery" id="main-preview-gallery">
                <div class="preview-placeholder" id="preview-placeholder">
                    Select a dress to see the preview
                </div>
                <img id="main-preview-image" class="main-preview-image" style="display: none;" alt="Dress preview">
                
                <!-- Product Thumbnails -->
                <div class="product-thumbnails" id="product-thumbnails" style="display: none;">
                    <!-- Thumbnails will be populated here -->
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" id="try-on-btn" disabled>
                    Try On Dress
                </button>
            </div>
        </div>

        <!-- Right Panel - Product Selection -->
        <div class="products-panel">
            <!-- Header -->
            <div class="products-header">
                <!-- Search Box -->
                <input type="text" class="search-box" placeholder="Search dresses..." id="search-box">
                
                <!-- Category Dropdown -->
                <div class="category-dropdown-container">
                    <select class="category-dropdown" id="category-dropdown">
                        <option value="all">All Categories</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category['slug']); ?>">
                                    <?php echo esc_html($category['name']); ?> (<?php echo $category['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php 
                        // Use actual WooCommerce categories
                        $product_categories = !empty($product['categories']) ? implode(' ', $product['categories']) : 'uncategorized';
                        ?>
                        <div class="product-card" 
                             data-product-id="<?php echo esc_attr($product['id']); ?>" 
                             data-categories="<?php echo esc_attr($product_categories); ?>" 
                             data-gallery="<?php echo esc_attr(json_encode($product['gallery'])); ?>">
                            <!-- Selection Indicator -->
                            <div class="selection-indicator">
                                <svg viewBox="0 0 24 24">
                                    <path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                                </svg>
                            </div>

                            <!-- Product Image -->
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo esc_url($product['image'][0]); ?>" 
                                     alt="<?php echo esc_attr($product['name']); ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <svg viewBox="0 0 24 24" style="width: 48px; height: 48px; fill: #bdc3c7;">
                                        <path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Product Info Overlay -->
                            <div class="product-info">
                                <h4 class="product-name"><?php echo esc_html($product['name']); ?></h4>
                                <div class="product-price"><?php echo wp_kses_post($product['price']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- See More Button -->
                    <div class="see-more-btn" id="see-more-btn">
                        See More
                    </div>
                <?php else: ?>
                    <div class="no-products-message">
                        <p>No dresses available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Processing your virtual fitting...</div>
        <div class="loading-fact" id="loading-fact"></div>
    </div>

    <!-- Messages Container -->
    <div id="message-container"></div>

    <!-- Checkout Modal -->
    <div class="checkout-modal-overlay" id="checkout-modal" style="display: none;">
        <div class="checkout-modal">
            <div class="checkout-modal-header">
                <h3>Purchase Credits</h3>
                <button class="checkout-modal-close" id="close-checkout-modal" type="button">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
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
                        <svg viewBox="0 0 24 24">
                            <path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                        </svg>
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
                    <button class="btn btn-primary" id="continue-fitting-btn">Continue Virtual Fitting</button>
                </div>
                <div class="checkout-error" id="checkout-error" style="display: none;">
                    <div class="error-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                        </svg>
                    </div>
                    <h4>Payment Failed</h4>
                    <p id="checkout-error-message">There was an issue processing your payment. Please try again.</p>
                    <div class="checkout-error-actions">
                        <button class="btn btn-primary" id="retry-checkout-btn">Try Again</button>
                        <button class="btn btn-secondary" id="cancel-checkout-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login Modal (if not logged in) -->
<?php if (!$is_logged_in): ?>
<div class="login-modal-overlay" id="login-modal" style="display: none;">
    <div class="login-modal">
        <div class="login-modal-content">
            <h3>Login Required</h3>
            <p>Please log in to use the virtual fitting feature.</p>
            <div class="login-modal-actions">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">Login</a>
                <button class="btn btn-secondary" id="close-login-modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Login Modal Styles */
.login-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.login-modal {
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.login-modal h3 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: #2c3e50;
}

.login-modal p {
    font-size: 14px;
    color: #7f8c8d;
    margin: 0 0 24px 0;
    line-height: 1.5;
}

.login-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.login-modal-actions .btn {
    flex: 1;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Product Image Placeholder */
.product-image-placeholder {
    width: 100%;
    height: 120px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

/* No Products Message */
.no-products-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
    font-size: 16px;
}

.no-products-message p {
    margin: 0;
    font-weight: 500;
}

/* Credits Display */
.credits-display {
    position: absolute;
    top: 20px;
    right: 20px;
    background: white;
    padding: 8px 16px;
    border-radius: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    font-weight: 500;
    color: #2c3e50;
}

.credits-count {
    color: #4a90e2;
    font-weight: 600;
    margin-left: 4px;
}
</style>
<?php endif; ?>