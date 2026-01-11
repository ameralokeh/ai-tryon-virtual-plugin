<?php
/**
 * Help Documentation for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Help Documentation Class
 */
class AI_Virtual_Fitting_Help_Documentation {
    
    /**
     * Get setup guide content
     *
     * @return array
     */
    public static function get_setup_guide() {
        return array(
            'title' => __('Setup Guide', 'ai-virtual-fitting'),
            'steps' => array(
                array(
                    'title' => __('Get Google AI Studio API Key', 'ai-virtual-fitting'),
                    'description' => __('Visit Google AI Studio and create an API key for the Gemini 2.5 Flash Image model.', 'ai-virtual-fitting'),
                    'link' => 'https://aistudio.google.com/app/apikey',
                    'link_text' => __('Get API Key', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Configure API Settings', 'ai-virtual-fitting'),
                    'description' => __('Enter your API key in the settings and test the connection to ensure it works correctly.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Set Credit System Parameters', 'ai-virtual-fitting'),
                    'description' => __('Configure how many free credits new users receive and the pricing for credit packages.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Adjust System Settings', 'ai-virtual-fitting'),
                    'description' => __('Set maximum image size limits and API retry attempts based on your server capabilities.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Test Virtual Fitting', 'ai-virtual-fitting'),
                    'description' => __('Visit the virtual fitting page and test the complete workflow with sample images.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Monitor Analytics', 'ai-virtual-fitting'),
                    'description' => __('Use the analytics dashboard to track usage, credit consumption, and system performance.', 'ai-virtual-fitting')
                )
            )
        );
    }
    
    /**
     * Get troubleshooting guide
     *
     * @return array
     */
    public static function get_troubleshooting_guide() {
        return array(
            'title' => __('Troubleshooting Guide', 'ai-virtual-fitting'),
            'issues' => array(
                array(
                    'problem' => __('API Connection Test Fails', 'ai-virtual-fitting'),
                    'solutions' => array(
                        __('Verify your Google AI Studio API key is correct and active.', 'ai-virtual-fitting'),
                        __('Check that your server can make outbound HTTPS requests.', 'ai-virtual-fitting'),
                        __('Ensure your API key has permissions for the Gemini 2.5 Flash Image model.', 'ai-virtual-fitting'),
                        __('Check if your server IP is blocked by Google AI Studio.', 'ai-virtual-fitting')
                    )
                ),
                array(
                    'problem' => __('Virtual Fitting Processing Fails', 'ai-virtual-fitting'),
                    'solutions' => array(
                        __('Check that uploaded images meet size and format requirements.', 'ai-virtual-fitting'),
                        __('Verify that products have exactly 4 images (1 featured + 3 gallery).', 'ai-virtual-fitting'),
                        __('Enable logging to see detailed error messages.', 'ai-virtual-fitting'),
                        __('Check API rate limits and retry settings.', 'ai-virtual-fitting')
                    )
                ),
                array(
                    'problem' => __('Credit System Not Working', 'ai-virtual-fitting'),
                    'solutions' => array(
                        __('Verify that database tables were created during plugin activation.', 'ai-virtual-fitting'),
                        __('Check that WooCommerce is properly installed and configured.', 'ai-virtual-fitting'),
                        __('Ensure the virtual fitting credits product was created.', 'ai-virtual-fitting'),
                        __('Test the WooCommerce checkout process with a test order.', 'ai-virtual-fitting')
                    )
                ),
                array(
                    'problem' => __('Image Upload Issues', 'ai-virtual-fitting'),
                    'solutions' => array(
                        __('Check PHP upload_max_filesize and post_max_size settings.', 'ai-virtual-fitting'),
                        __('Verify WordPress upload directory permissions.', 'ai-virtual-fitting'),
                        __('Ensure images are in supported formats (JPEG, PNG, WebP).', 'ai-virtual-fitting'),
                        __('Check that images meet minimum and maximum dimension requirements.', 'ai-virtual-fitting')
                    )
                ),
                array(
                    'problem' => __('Performance Issues', 'ai-virtual-fitting'),
                    'solutions' => array(
                        __('Optimize server resources and increase PHP memory limit.', 'ai-virtual-fitting'),
                        __('Enable caching for product images and API responses.', 'ai-virtual-fitting'),
                        __('Consider implementing queue processing for high traffic.', 'ai-virtual-fitting'),
                        __('Monitor API usage and implement rate limiting if needed.', 'ai-virtual-fitting')
                    )
                )
            )
        );
    }
    
    /**
     * Get system requirements
     *
     * @return array
     */
    public static function get_system_requirements() {
        return array(
            'title' => __('System Requirements', 'ai-virtual-fitting'),
            'requirements' => array(
                'wordpress' => array(
                    'name' => __('WordPress', 'ai-virtual-fitting'),
                    'minimum' => '5.0',
                    'recommended' => '6.0+',
                    'current' => get_bloginfo('version')
                ),
                'php' => array(
                    'name' => __('PHP', 'ai-virtual-fitting'),
                    'minimum' => '7.4',
                    'recommended' => '8.0+',
                    'current' => PHP_VERSION
                ),
                'woocommerce' => array(
                    'name' => __('WooCommerce', 'ai-virtual-fitting'),
                    'minimum' => '5.0',
                    'recommended' => '7.0+',
                    'current' => class_exists('WooCommerce') ? WC()->version : __('Not installed', 'ai-virtual-fitting')
                ),
                'memory' => array(
                    'name' => __('PHP Memory Limit', 'ai-virtual-fitting'),
                    'minimum' => '128M',
                    'recommended' => '256M+',
                    'current' => ini_get('memory_limit')
                ),
                'upload_size' => array(
                    'name' => __('Max Upload Size', 'ai-virtual-fitting'),
                    'minimum' => '10M',
                    'recommended' => '50M+',
                    'current' => ini_get('upload_max_filesize')
                )
            )
        );
    }
    
    /**
     * Get API documentation
     *
     * @return array
     */
    public static function get_api_documentation() {
        return array(
            'title' => __('Google AI Studio Integration', 'ai-virtual-fitting'),
            'sections' => array(
                array(
                    'title' => __('API Key Setup', 'ai-virtual-fitting'),
                    'content' => __('The plugin uses Google AI Studio\'s Gemini 2.5 Flash Image model for virtual fitting processing. You need to obtain an API key from Google AI Studio and configure it in the plugin settings.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Image Processing', 'ai-virtual-fitting'),
                    'content' => __('The system sends customer photos along with 4 product images to the AI model. The AI generates a realistic virtual try-on image showing how the dress would look on the customer.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Rate Limits', 'ai-virtual-fitting'),
                    'content' => __('Google AI Studio has rate limits for API calls. The plugin includes retry logic and error handling to manage these limits gracefully.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Error Handling', 'ai-virtual-fitting'),
                    'content' => __('The plugin implements comprehensive error handling for API failures, network issues, and invalid responses. Failed requests are retried automatically up to the configured limit.', 'ai-virtual-fitting')
                )
            )
        );
    }
    
    /**
     * Get credit system documentation
     *
     * @return array
     */
    public static function get_credit_system_documentation() {
        return array(
            'title' => __('Credit System Overview', 'ai-virtual-fitting'),
            'sections' => array(
                array(
                    'title' => __('How Credits Work', 'ai-virtual-fitting'),
                    'content' => __('Each virtual fitting session consumes 1 credit. New users receive free credits, and additional credits can be purchased through WooCommerce.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Free Credits', 'ai-virtual-fitting'),
                    'content' => __('New users automatically receive free credits when they first access the virtual fitting feature. This allows them to try the service before purchasing.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Credit Packages', 'ai-virtual-fitting'),
                    'content' => __('Credits are sold in packages through WooCommerce. The plugin automatically creates a virtual product for credit sales and handles order completion.', 'ai-virtual-fitting')
                ),
                array(
                    'title' => __('Credit Deduction', 'ai-virtual-fitting'),
                    'content' => __('Credits are only deducted after successful virtual fitting processing. If the AI processing fails, no credits are consumed.', 'ai-virtual-fitting')
                )
            )
        );
    }
    
    /**
     * Get FAQ content
     *
     * @return array
     */
    public static function get_faq() {
        return array(
            'title' => __('Frequently Asked Questions', 'ai-virtual-fitting'),
            'questions' => array(
                array(
                    'question' => __('What image formats are supported?', 'ai-virtual-fitting'),
                    'answer' => __('The plugin supports JPEG, PNG, and WebP image formats. Images must be between 512x512 and 2048x2048 pixels and under 10MB in size.', 'ai-virtual-fitting')
                ),
                array(
                    'question' => __('How many product images are required?', 'ai-virtual-fitting'),
                    'answer' => __('Each product must have exactly 4 images: 1 featured image and 3 gallery images. This provides the AI with multiple angles of the dress for better virtual fitting results.', 'ai-virtual-fitting')
                ),
                array(
                    'question' => __('Can I customize the credit pricing?', 'ai-virtual-fitting'),
                    'answer' => __('Yes, you can configure the number of credits per package and the price per package in the plugin settings. Changes will be reflected in the WooCommerce product.', 'ai-virtual-fitting')
                ),
                array(
                    'question' => __('What happens if the AI processing fails?', 'ai-virtual-fitting'),
                    'answer' => __('If AI processing fails due to technical issues, no credits are deducted from the user\'s account. The system includes retry logic and comprehensive error handling.', 'ai-virtual-fitting')
                ),
                array(
                    'question' => __('How long are virtual fitting results stored?', 'ai-virtual-fitting'),
                    'answer' => __('Virtual fitting results are stored temporarily for 24 hours to allow users to download them. After that, they are automatically cleaned up to save server space.', 'ai-virtual-fitting')
                ),
                array(
                    'question' => __('Can users download their virtual fitting results?', 'ai-virtual-fitting'),
                    'answer' => __('Yes, users can download their virtual fitting results as high-quality JPEG images. Downloads are tracked for analytics purposes.', 'ai-virtual-fitting')
                )
            )
        );
    }
}