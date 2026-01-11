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
                        <button class="floating-btn change-photo-btn" type="button">
                            <svg viewBox="0 0 24 24">
                                <path d="M9,12L11,14L15,10L20,15H4L9,12M2,6H14L16,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6M4,6V18H20V6H4Z"/>
                            </svg>
                        </button>
                        <button class="floating-btn clear-photo-btn" type="button">
                            <svg viewBox="0 0 24 24">
                                <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                            </svg>
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
                        <button class="floating-btn" id="try-another-btn">
                            <svg viewBox="0 0 24 24">
                                <path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z"/>
                            </svg>
                        </button>
                        <button class="floating-btn" id="save-image-btn">
                            <svg viewBox="0 0 24 24">
                                <path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z"/>
                            </svg>
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
                <h3 class="section-header">Select Your Dress</h3>
                
                <!-- Search Box -->
                <input type="text" class="search-box" placeholder="Search dresses..." id="search-box">
                
                <!-- Category Filters -->
                <div class="category-filters">
                    <button class="category-btn active" data-category="all">All</button>
                    <button class="category-btn" data-category="elegant">Elegant</button>
                    <button class="category-btn" data-category="boho">Boho</button>
                    <button class="category-btn" data-category="modern">Modern</button>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php 
                        // Assign categories based on product name for demo
                        $category = 'elegant'; // default
                        $product_name_lower = strtolower($product['name']);
                        if (strpos($product_name_lower, 'modern') !== false || strpos($product_name_lower, 'a-line') !== false) {
                            $category = 'modern';
                        } elseif (strpos($product_name_lower, 'boho') !== false || strpos($product_name_lower, 'vintage') !== false || strpos($product_name_lower, 'champagne') !== false) {
                            $category = 'boho';
                        }
                        ?>
                        <div class="product-card" data-product-id="<?php echo esc_attr($product['id']); ?>" data-category="<?php echo esc_attr($category); ?>" data-gallery="<?php echo esc_attr(json_encode($product['gallery'])); ?>">
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
    </div>

    <!-- Messages Container -->
    <div id="message-container"></div>
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