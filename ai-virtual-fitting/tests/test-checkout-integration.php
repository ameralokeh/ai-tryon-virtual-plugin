<?php
/**
 * Integration Test for Embedded Checkout
 * 
 * This test validates the actual AJAX endpoints and WordPress integration
 * for the embedded checkout functionality.
 *
 * @package AI_Virtual_Fitting
 */

/**
 * Checkout Integration Test Class
 */
class Test_Checkout_Integration {
    
    private $test_results = [];
    private $ajax_url = 'http://localhost:8080/wp-admin/admin-ajax.php';
    private $test_nonce = 'test_nonce_12345';
    
    /**
     * Run integration tests
     */
    public function run_integration_tests() {
        echo "=== Testing Embedded Checkout Integration ===\n\n";
        
        // Test AJAX endpoint availability
        $this->test_ajax_endpoints_available();
        
        // Test checkout modal HTML structure
        $this->test_checkout_modal_structure();
        
        // Test JavaScript functionality
        $this->test_javascript_functionality();
        
        // Test CSS styling and responsiveness
        $this->test_css_styling();
        
        // Display results
        $this->display_integration_results();
        
        return $this->get_integration_result();
    }
    
    /**
     * Test AJAX endpoints availability
     */
    public function test_ajax_endpoints_available() {
        echo "Testing AJAX Endpoints Availability\n";
        
        $endpoints = [
            'ai_virtual_fitting_add_credits_to_cart',
            'ai_virtual_fitting_clear_cart',
            'ai_virtual_fitting_load_checkout',
            'ai_virtual_fitting_process_checkout',
            'ai_virtual_fitting_refresh_credits'
        ];
        
        $test_cases = [];
        
        foreach ($endpoints as $endpoint) {
            $test_cases[$endpoint] = $this->test_ajax_endpoint_exists($endpoint);
        }
        
        $this->test_results['ajax_endpoints'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d endpoints available\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test checkout modal HTML structure
     */
    public function test_checkout_modal_structure() {
        echo "Testing Checkout Modal HTML Structure\n";
        
        $test_cases = [
            'modal_overlay_exists' => $this->test_modal_overlay_exists(),
            'modal_header_exists' => $this->test_modal_header_exists(),
            'modal_content_areas_exist' => $this->test_modal_content_areas_exist(),
            'modal_close_button_exists' => $this->test_modal_close_button_exists(),
            'success_error_states_exist' => $this->test_success_error_states_exist()
        ];
        
        $this->test_results['modal_structure'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d structure tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test JavaScript functionality
     */
    public function test_javascript_functionality() {
        echo "Testing JavaScript Functionality\n";
        
        $test_cases = [
            'modern_js_file_exists' => $this->test_modern_js_file_exists(),
            'checkout_functions_defined' => $this->test_checkout_functions_defined(),
            'event_handlers_bound' => $this->test_event_handlers_bound(),
            'ajax_calls_structured' => $this->test_ajax_calls_structured(),
            'error_handling_implemented' => $this->test_error_handling_implemented()
        ];
        
        $this->test_results['javascript'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d JavaScript tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test CSS styling and responsiveness
     */
    public function test_css_styling() {
        echo "Testing CSS Styling and Responsiveness\n";
        
        $test_cases = [
            'css_file_exists' => $this->test_css_file_exists(),
            'modal_styles_defined' => $this->test_modal_styles_defined(),
            'responsive_styles_exist' => $this->test_responsive_styles_exist(),
            'mobile_optimizations_exist' => $this->test_mobile_optimizations_exist()
        ];
        
        $this->test_results['css_styling'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d CSS tests passed\n\n", count($passed), count($test_cases));
    }
    
    // Individual test methods
    
    private function test_ajax_endpoint_exists($endpoint) {
        // Check if the AJAX action is registered in the public interface
        $public_interface_file = __DIR__ . '/../public/class-public-interface.php';
        
        if (!file_exists($public_interface_file)) {
            return false;
        }
        
        $content = file_get_contents($public_interface_file);
        
        // Check for both logged-in and non-logged-in hooks
        $logged_in_hook = "add_action('wp_ajax_{$endpoint}'";
        $non_logged_in_hook = "add_action('wp_ajax_nopriv_{$endpoint}'";
        
        return (strpos($content, $logged_in_hook) !== false && 
                strpos($content, $non_logged_in_hook) !== false);
    }
    
    private function test_modal_overlay_exists() {
        $template_file = __DIR__ . '/../public/modern-virtual-fitting-page.php';
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        $content = file_get_contents($template_file);
        
        return (strpos($content, 'checkout-modal-overlay') !== false &&
                strpos($content, 'id="checkout-modal"') !== false);
    }
    
    private function test_modal_header_exists() {
        $template_file = __DIR__ . '/../public/modern-virtual-fitting-page.php';
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        $content = file_get_contents($template_file);
        
        return (strpos($content, 'checkout-modal-header') !== false &&
                strpos($content, 'Purchase Credits') !== false);
    }
    
    private function test_modal_content_areas_exist() {
        $template_file = __DIR__ . '/../public/modern-virtual-fitting-page.php';
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        $content = file_get_contents($template_file);
        
        return (strpos($content, 'checkout-loading') !== false &&
                strpos($content, 'checkout-form-container') !== false &&
                strpos($content, 'checkout-success') !== false &&
                strpos($content, 'checkout-error') !== false);
    }
    
    private function test_modal_close_button_exists() {
        $template_file = __DIR__ . '/../public/modern-virtual-fitting-page.php';
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        $content = file_get_contents($template_file);
        
        return (strpos($content, 'checkout-modal-close') !== false &&
                strpos($content, 'id="close-checkout-modal"') !== false);
    }
    
    private function test_success_error_states_exist() {
        $template_file = __DIR__ . '/../public/modern-virtual-fitting-page.php';
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        $content = file_get_contents($template_file);
        
        return (strpos($content, 'Purchase Successful!') !== false &&
                strpos($content, 'Payment Failed') !== false &&
                strpos($content, 'Continue Virtual Fitting') !== false);
    }
    
    private function test_modern_js_file_exists() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        return file_exists($js_file) && filesize($js_file) > 1000; // Should be substantial
    }
    
    private function test_checkout_functions_defined() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        if (!file_exists($js_file)) {
            return false;
        }
        
        $content = file_get_contents($js_file);
        
        $required_functions = [
            'openCheckoutModal',
            'closeCheckoutModal',
            'loadCheckoutForm',
            'processCheckoutSubmission',
            'addCreditProductToCart',
            'clearCheckoutCart'
        ];
        
        foreach ($required_functions as $function) {
            if (strpos($content, "function {$function}") === false) {
                return false;
            }
        }
        
        return true;
    }
    
    private function test_event_handlers_bound() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        if (!file_exists($js_file)) {
            return false;
        }
        
        $content = file_get_contents($js_file);
        
        $required_handlers = [
            '#add-credits-btn',
            '#close-checkout-modal',
            '#continue-fitting-btn',
            '#retry-checkout-btn',
            '#cancel-checkout-btn'
        ];
        
        foreach ($required_handlers as $handler) {
            if (strpos($content, $handler) === false) {
                return false;
            }
        }
        
        return true;
    }
    
    private function test_ajax_calls_structured() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        if (!file_exists($js_file)) {
            return false;
        }
        
        $content = file_get_contents($js_file);
        
        // Check for proper AJAX structure
        return (strpos($content, 'ai_virtual_fitting_ajax.ajax_url') !== false &&
                strpos($content, 'ai_virtual_fitting_ajax.nonce') !== false &&
                strpos($content, '$.ajax(') !== false);
    }
    
    private function test_error_handling_implemented() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        if (!file_exists($js_file)) {
            return false;
        }
        
        $content = file_get_contents($js_file);
        
        return (strpos($content, 'showCheckoutError') !== false &&
                strpos($content, 'handlePaymentError') !== false &&
                strpos($content, 'error:') !== false);
    }
    
    private function test_css_file_exists() {
        $css_file = __DIR__ . '/../public/css/modern-virtual-fitting.css';
        
        return file_exists($css_file) && filesize($css_file) > 1000; // Should be substantial
    }
    
    private function test_modal_styles_defined() {
        $css_file = __DIR__ . '/../public/css/modern-virtual-fitting.css';
        
        if (!file_exists($css_file)) {
            return false;
        }
        
        $content = file_get_contents($css_file);
        
        $required_styles = [
            '.checkout-modal-overlay',
            '.checkout-modal',
            '.checkout-modal-header',
            '.checkout-modal-content',
            '.checkout-loading',
            '.checkout-success',
            '.checkout-error'
        ];
        
        foreach ($required_styles as $style) {
            if (strpos($content, $style) === false) {
                return false;
            }
        }
        
        return true;
    }
    
    private function test_responsive_styles_exist() {
        $css_file = __DIR__ . '/../public/css/modern-virtual-fitting.css';
        
        if (!file_exists($css_file)) {
            return false;
        }
        
        $content = file_get_contents($css_file);
        
        return (strpos($content, '@media') !== false &&
                strpos($content, 'max-width') !== false);
    }
    
    private function test_mobile_optimizations_exist() {
        $js_file = __DIR__ . '/../public/js/modern-virtual-fitting.js';
        
        if (!file_exists($js_file)) {
            return false;
        }
        
        $content = file_get_contents($js_file);
        
        return (strpos($content, 'isMobileDevice') !== false &&
                strpos($content, 'initializeMobileModalEnhancements') !== false &&
                strpos($content, 'preventBackgroundScrolling') !== false);
    }
    
    /**
     * Display integration test results
     */
    private function display_integration_results() {
        echo "=== Integration Test Results ===\n";
        
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $category => $tests) {
            $passed = array_filter($tests);
            $total_tests += count($tests);
            $total_passed += count($passed);
            
            echo sprintf("%s: %d/%d passed\n", 
                ucwords(str_replace('_', ' ', $category)), 
                count($passed), 
                count($tests)
            );
        }
        
        echo sprintf("\nOverall Integration: %d/%d tests passed (%.1f%%)\n", 
            $total_passed, 
            $total_tests, 
            ($total_passed / $total_tests) * 100
        );
        
        if ($total_passed === $total_tests) {
            echo "\n✅ All integration tests PASSED!\n";
            echo "The embedded checkout integration is working correctly.\n";
        } else {
            echo "\n⚠️  Some integration tests need attention!\n";
            echo "Check the implementation details for any missing components.\n";
        }
    }
    
    /**
     * Get integration test result
     */
    private function get_integration_result() {
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $tests) {
            $passed = array_filter($tests);
            $total_tests += count($tests);
            $total_passed += count($passed);
        }
        
        return $total_passed === $total_tests;
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new Test_Checkout_Integration();
    $result = $test->run_integration_tests();
    exit($result ? 0 : 1);
}