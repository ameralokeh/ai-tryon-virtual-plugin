<?php
/**
 * Admin Settings for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Admin Settings Class
 */
class AI_Virtual_Fitting_Admin_Settings {
    
    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'ai-virtual-fitting-settings';
    
    /**
     * Settings group
     */
    const SETTINGS_GROUP = 'ai_virtual_fitting_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ai_virtual_fitting_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_ai_virtual_fitting_get_analytics', array($this, 'get_analytics_data'));
        add_action('wp_ajax_ai_virtual_fitting_get_user_credits', array($this, 'get_user_credits'));
        add_action('wp_ajax_ai_virtual_fitting_update_user_credits', array($this, 'update_user_credits'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('AI Virtual Fitting Settings', 'ai-virtual-fitting'),
            __('AI Virtual Fitting', 'ai-virtual-fitting'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_api_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_provider'),
                'default' => 'google_ai_studio'
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_google_ai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_ai_prompt_template',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_ai_prompt'),
                'default' => ''
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_vertex_credentials',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_vertex_credentials'),
                'default' => ''
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_initial_credits',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_positive_integer'),
                'default' => 2
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_credits_per_package',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_positive_integer'),
                'default' => 20
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_credits_package_price',
            array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_price'),
                'default' => 10.00
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_max_image_size',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_file_size'),
                'default' => 10485760 // 10MB
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_api_retry_attempts',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_retry_attempts'),
                'default' => 3
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_enable_logging',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean'),
                'default' => true
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_temp_file_cleanup_hours',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_cleanup_hours'),
                'default' => 24
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_enable_analytics',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean'),
                'default' => true
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_require_login',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean'),
                'default' => true
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_allowed_user_roles',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_user_roles'),
                'default' => array('customer', 'subscriber', 'administrator')
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_api_timeout',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_timeout'),
                'default' => 60
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_enable_email_notifications',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean'),
                'default' => true
            )
        );
        
        register_setting(
            self::SETTINGS_GROUP,
            'ai_virtual_fitting_admin_email_notifications',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean'),
                'default' => false
            )
        );
        
        // Add settings sections
        add_settings_section(
            'ai_virtual_fitting_api_section',
            __('Google AI Studio Configuration', 'ai-virtual-fitting'),
            array($this, 'render_api_section_description'),
            self::PAGE_SLUG
        );
        
        add_settings_section(
            'ai_virtual_fitting_credits_section',
            __('Credit System Settings', 'ai-virtual-fitting'),
            array($this, 'render_credits_section_description'),
            self::PAGE_SLUG
        );
        
        add_settings_section(
            'ai_virtual_fitting_system_section',
            __('System Settings', 'ai-virtual-fitting'),
            array($this, 'render_system_section_description'),
            self::PAGE_SLUG
        );
        
        add_settings_section(
            'ai_virtual_fitting_monitoring_section',
            __('Monitoring & Analytics', 'ai-virtual-fitting'),
            array($this, 'render_monitoring_section_description'),
            self::PAGE_SLUG
        );
        
        add_settings_section(
            'ai_virtual_fitting_advanced_section',
            __('Advanced Settings', 'ai-virtual-fitting'),
            array($this, 'render_advanced_section_description'),
            self::PAGE_SLUG
        );
        
        add_settings_section(
            'ai_virtual_fitting_help_section',
            __('Help & Documentation', 'ai-virtual-fitting'),
            array($this, 'render_help_section_description'),
            self::PAGE_SLUG
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // API Configuration fields
        add_settings_field(
            'google_ai_api_key',
            __('Google AI Studio API Key', 'ai-virtual-fitting'),
            array($this, 'render_api_key_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_api_section'
        );
        
        add_settings_field(
            'ai_prompt_template',
            __('AI Prompt Template', 'ai-virtual-fitting'),
            array($this, 'render_ai_prompt_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_api_section'
        );
        
        // Credit System fields
        add_settings_field(
            'initial_credits',
            __('Initial Free Credits', 'ai-virtual-fitting'),
            array($this, 'render_initial_credits_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_credits_section'
        );
        
        add_settings_field(
            'credits_per_package',
            __('Credits per Package', 'ai-virtual-fitting'),
            array($this, 'render_credits_per_package_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_credits_section'
        );
        
        add_settings_field(
            'credits_package_price',
            __('Package Price', 'ai-virtual-fitting'),
            array($this, 'render_package_price_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_credits_section'
        );
        
        // System Settings fields
        add_settings_field(
            'max_image_size',
            __('Maximum Image Size', 'ai-virtual-fitting'),
            array($this, 'render_max_image_size_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_system_section'
        );
        
        add_settings_field(
            'api_retry_attempts',
            __('API Retry Attempts', 'ai-virtual-fitting'),
            array($this, 'render_retry_attempts_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_system_section'
        );
        
        add_settings_field(
            'enable_logging',
            __('Enable Logging', 'ai-virtual-fitting'),
            array($this, 'render_logging_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_system_section'
        );
        
        // Advanced Settings fields
        add_settings_field(
            'temp_file_cleanup_hours',
            __('Temporary File Cleanup', 'ai-virtual-fitting'),
            array($this, 'render_cleanup_hours_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'api_timeout',
            __('API Timeout', 'ai-virtual-fitting'),
            array($this, 'render_api_timeout_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'require_login',
            __('Require User Login', 'ai-virtual-fitting'),
            array($this, 'render_require_login_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'allowed_user_roles',
            __('Allowed User Roles', 'ai-virtual-fitting'),
            array($this, 'render_user_roles_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'enable_analytics',
            __('Enable Analytics', 'ai-virtual-fitting'),
            array($this, 'render_analytics_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'enable_email_notifications',
            __('Email Notifications', 'ai-virtual-fitting'),
            array($this, 'render_email_notifications_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
        
        add_settings_field(
            'admin_email_notifications',
            __('Admin Email Notifications', 'ai-virtual-fitting'),
            array($this, 'render_admin_email_notifications_field'),
            self::PAGE_SLUG,
            'ai_virtual_fitting_advanced_section'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'ai-virtual-fitting-admin',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'admin/js/admin-settings.js',
            array('jquery'),
            AI_VIRTUAL_FITTING_VERSION,
            true
        );
        
        // Enqueue help tooltips JavaScript
        wp_enqueue_script(
            'ai-virtual-fitting-simple-tooltips',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'admin/js/simple-tooltips.js',
            array('jquery'),
            AI_VIRTUAL_FITTING_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ai-virtual-fitting-admin',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'admin/css/admin-settings.css',
            array(),
            AI_VIRTUAL_FITTING_VERSION
        );
        
        // Enqueue help tooltips CSS and dashicons
        wp_enqueue_style(
            'ai-virtual-fitting-help-tooltips',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'admin/css/help-tooltips.css',
            array('dashicons'),
            AI_VIRTUAL_FITTING_VERSION
        );
        
        wp_localize_script('ai-virtual-fitting-admin', 'ai_virtual_fitting_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_virtual_fitting_admin_nonce'),
            'messages' => array(
                'testing_api' => __('Testing API connection...', 'ai-virtual-fitting'),
                'api_success' => __('API connection successful!', 'ai-virtual-fitting'),
                'api_error' => __('API connection failed. Please check your API key.', 'ai-virtual-fitting'),
                'loading_analytics' => __('Loading analytics data...', 'ai-virtual-fitting'),
            )
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-virtual-fitting'));
        }
        
        include AI_VIRTUAL_FITTING_PLUGIN_DIR . 'admin/admin-settings-page.php';
    }
    
    /**
     * Section descriptions
     */
    public function render_api_section_description() {
        echo '<p>' . __('Configure your Google AI Studio API key for virtual fitting processing.', 'ai-virtual-fitting') . '</p>';
    }
    
    public function render_credits_section_description() {
        echo '<p>' . __('Manage credit system settings for virtual fitting usage.', 'ai-virtual-fitting') . '</p>';
    }
    
    public function render_system_section_description() {
        echo '<p>' . __('Configure system-wide settings for the virtual fitting plugin.', 'ai-virtual-fitting') . '</p>';
    }
    
    public function render_monitoring_section_description() {
        echo '<p>' . __('Monitor plugin performance and view usage analytics.', 'ai-virtual-fitting') . '</p>';
    }
    
    public function render_advanced_section_description() {
        echo '<p>' . __('Advanced configuration options for experienced users.', 'ai-virtual-fitting') . '</p>';
    }
    
    public function render_help_section_description() {
        echo '<p>' . __('Documentation, troubleshooting guides, and system requirements.', 'ai-virtual-fitting') . '</p>';
    }
    
    /**
     * Field renderers
     */
    public function render_api_key_field() {
        $value = get_option('ai_virtual_fitting_google_ai_api_key', '');
        $vertex_credentials = get_option('ai_virtual_fitting_vertex_credentials', '');
        $api_provider = get_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
        ?>
        <div class="api-provider-selection">
            <label>
                <input type="radio" name="ai_virtual_fitting_api_provider" value="google_ai_studio" <?php checked($api_provider, 'google_ai_studio'); ?> />
                <?php _e('Google AI Studio (API Key)', 'ai-virtual-fitting'); ?>
            </label>
            <label style="margin-left: 20px;">
                <input type="radio" name="ai_virtual_fitting_api_provider" value="vertex_ai" <?php checked($api_provider, 'vertex_ai'); ?> />
                <?php _e('Google Cloud Vertex AI (Service Account)', 'ai-virtual-fitting'); ?>
            </label>
        </div>
        
        <!-- Google AI Studio API Key -->
        <div id="google-ai-studio-config" style="<?php echo $api_provider === 'vertex_ai' ? 'display: none;' : ''; ?>">
            <input type="password" 
                   id="google_ai_api_key" 
                   name="ai_virtual_fitting_google_ai_api_key" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e('Enter your Google AI Studio API key', 'ai-virtual-fitting'); ?>" />
            <span class="help-tooltip" 
                  title="<?php esc_attr_e('Your Google AI Studio API key is required for AI-powered virtual fitting. Get your free API key from Google AI Studio and paste it here. The key should start with AIza...', 'ai-virtual-fitting'); ?>"
                  style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
            <p class="description">
                <?php _e('Get your API key from Google AI Studio. This key is required for virtual fitting functionality.', 'ai-virtual-fitting'); ?>
                <a href="https://aistudio.google.com/app/apikey" target="_blank"><?php _e('Get API Key', 'ai-virtual-fitting'); ?></a>
            </p>
        </div>
        
        <!-- Google Cloud Vertex AI Service Account -->
        <div id="vertex-ai-config" style="<?php echo $api_provider === 'google_ai_studio' ? 'display: none;' : ''; ?>">
            <div class="vertex-upload-methods">
                <h4><?php _e('Service Account Credentials', 'ai-virtual-fitting'); ?></h4>
                
                <!-- Method 1: JSON File Upload -->
                <div class="upload-method">
                    <h5><?php _e('Method 1: Upload JSON File', 'ai-virtual-fitting'); ?></h5>
                    <input type="file" 
                           id="vertex_credentials_file" 
                           accept=".json" 
                           style="margin-bottom: 10px;" />
                    <button type="button" id="upload-vertex-credentials" class="button button-secondary">
                        <?php _e('Upload & Parse JSON', 'ai-virtual-fitting'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Upload your service account JSON file downloaded from Google Cloud Console.', 'ai-virtual-fitting'); ?>
                    </p>
                </div>
                
                <!-- Method 2: Paste JSON String -->
                <div class="upload-method" style="margin-top: 20px;">
                    <h5><?php _e('Method 2: Paste JSON Content', 'ai-virtual-fitting'); ?></h5>
                    <textarea id="vertex_credentials_json" 
                              name="ai_virtual_fitting_vertex_credentials"
                              rows="8" 
                              cols="80" 
                              class="large-text code"
                              placeholder="<?php esc_attr_e('Paste your service account JSON content here...', 'ai-virtual-fitting'); ?>"><?php echo esc_textarea($vertex_credentials); ?></textarea>
                    <span class="help-tooltip" 
                          title="<?php esc_attr_e('Paste the complete JSON content from your service account key file. This should include type, project_id, private_key_id, private_key, client_email, etc.', 'ai-virtual-fitting'); ?>"
                          style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: top;">?</span>
                    <p class="description">
                        <?php _e('Paste the complete JSON content from your service account key file.', 'ai-virtual-fitting'); ?>
                        <a href="https://cloud.google.com/iam/docs/creating-managing-service-account-keys" target="_blank"><?php _e('Learn how to create service account keys', 'ai-virtual-fitting'); ?></a>
                    </p>
                </div>
                
                <!-- Validation Status -->
                <div id="vertex-validation-status" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <button type="button" id="test-api-key" class="button button-secondary" style="margin-top: 10px;">
            <?php _e('Test Connection', 'ai-virtual-fitting'); ?>
        </button>
        <div id="api-test-result"></div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle between API providers
            $('input[name="ai_virtual_fitting_api_provider"]').change(function() {
                if ($(this).val() === 'google_ai_studio') {
                    $('#google-ai-studio-config').show();
                    $('#vertex-ai-config').hide();
                } else {
                    $('#google-ai-studio-config').hide();
                    $('#vertex-ai-config').show();
                }
            });
            
            // Handle JSON file upload
            $('#upload-vertex-credentials').click(function() {
                var fileInput = document.getElementById('vertex_credentials_file');
                var file = fileInput.files[0];
                
                if (!file) {
                    alert('<?php _e('Please select a JSON file first.', 'ai-virtual-fitting'); ?>');
                    return;
                }
                
                if (!file.name.endsWith('.json')) {
                    alert('<?php _e('Please select a valid JSON file.', 'ai-virtual-fitting'); ?>');
                    return;
                }
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var jsonContent = e.target.result;
                        var parsed = JSON.parse(jsonContent);
                        
                        // Validate required fields
                        var requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
                        var missingFields = [];
                        
                        requiredFields.forEach(function(field) {
                            if (!parsed[field]) {
                                missingFields.push(field);
                            }
                        });
                        
                        if (missingFields.length > 0) {
                            $('#vertex-validation-status').html('<div class="notice notice-error"><p><?php _e('Invalid service account JSON. Missing fields:', 'ai-virtual-fitting'); ?> ' + missingFields.join(', ') + '</p></div>');
                            return;
                        }
                        
                        // Populate textarea with formatted JSON
                        $('#vertex_credentials_json').val(JSON.stringify(parsed, null, 2));
                        $('#vertex-validation-status').html('<div class="notice notice-success"><p><?php _e('✓ Valid service account JSON loaded successfully!', 'ai-virtual-fitting'); ?></p></div>');
                        
                    } catch (error) {
                        $('#vertex-validation-status').html('<div class="notice notice-error"><p><?php _e('Error parsing JSON file:', 'ai-virtual-fitting'); ?> ' + error.message + '</p></div>');
                    }
                };
                reader.readAsText(file);
            });
            
            // Validate JSON on textarea change
            $('#vertex_credentials_json').on('blur', function() {
                var jsonContent = $(this).val().trim();
                if (!jsonContent) {
                    $('#vertex-validation-status').html('');
                    return;
                }
                
                try {
                    var parsed = JSON.parse(jsonContent);
                    var requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
                    var missingFields = [];
                    
                    requiredFields.forEach(function(field) {
                        if (!parsed[field]) {
                            missingFields.push(field);
                        }
                    });
                    
                    if (missingFields.length > 0) {
                        $('#vertex-validation-status').html('<div class="notice notice-error"><p><?php _e('Missing required fields:', 'ai-virtual-fitting'); ?> ' + missingFields.join(', ') + '</p></div>');
                    } else {
                        $('#vertex-validation-status').html('<div class="notice notice-success"><p><?php _e('✓ Valid service account JSON format', 'ai-virtual-fitting'); ?></p></div>');
                    }
                } catch (error) {
                    $('#vertex-validation-status').html('<div class="notice notice-error"><p><?php _e('Invalid JSON format:', 'ai-virtual-fitting'); ?> ' + error.message + '</p></div>');
                }
            });
        });
        </script>
        
        <style>
        .api-provider-selection {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
        }
        .upload-method {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fafafa;
        }
        .upload-method h5 {
            margin-top: 0;
            color: #0073aa;
        }
        #vertex_credentials_json {
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    public function render_ai_prompt_field() {
        $value = get_option('ai_virtual_fitting_ai_prompt_template', $this->get_default_ai_prompt());
        ?>
        <textarea id="ai_prompt_template" 
                  name="ai_virtual_fitting_ai_prompt_template"
                  rows="6" 
                  cols="80" 
                  class="large-text"
                  maxlength="2000"
                  placeholder="<?php esc_attr_e('Enter your custom AI prompt for virtual fitting...', 'ai-virtual-fitting'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <span class="help-tooltip" 
              title="<?php esc_attr_e('This prompt instructs the AI how to generate virtual fitting images. You can customize it to improve results for your specific products. The prompt should be clear and descriptive.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: top;">?</span>
        <p class="description">
            <?php _e('Customize the AI prompt used for virtual fitting generation. Leave empty to use the default prompt.', 'ai-virtual-fitting'); ?>
            <br>
            <span id="prompt-char-count">0</span> / 2000 characters
            <button type="button" id="reset-prompt" class="button button-secondary" style="margin-left: 10px;">
                <?php _e('Reset to Default', 'ai-virtual-fitting'); ?>
            </button>
        </p>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $textarea = $('#ai_prompt_template');
            var $charCount = $('#prompt-char-count');
            
            // Update character count
            function updateCharCount() {
                var length = $textarea.val().length;
                $charCount.text(length);
                
                if (length > 1800) {
                    $charCount.css('color', '#d63638');
                } else if (length > 1500) {
                    $charCount.css('color', '#dba617');
                } else {
                    $charCount.css('color', '#135e96');
                }
            }
            
            // Initial count
            updateCharCount();
            
            // Update on input
            $textarea.on('input', updateCharCount);
            
            // Reset to default
            $('#reset-prompt').click(function() {
                if (confirm('<?php _e('Are you sure you want to reset to the default prompt? This will overwrite your current prompt.', 'ai-virtual-fitting'); ?>')) {
                    $textarea.val('<?php echo esc_js($this->get_default_ai_prompt()); ?>');
                    updateCharCount();
                }
            });
        });
        </script>
        <?php
    }
    
    private function get_default_ai_prompt() {
        return "Please create a realistic virtual try-on image showing the person from the first image wearing the wedding dress from the product images. Show how the dress would look on this person, maintaining their natural pose, body proportions, and lighting. The result should be a seamless, professional-looking virtual fitting that accurately represents how the dress would fit and appear on them.";
    }
    
    public function render_initial_credits_field() {
        $value = get_option('ai_virtual_fitting_initial_credits', 2);
        ?>
        <input type="number" 
               id="initial_credits" 
               name="ai_virtual_fitting_initial_credits" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="10" 
               class="small-text" />
        <span class="help-tooltip" 
              title="<?php esc_attr_e('Free credits help new users try the virtual fitting feature before purchasing. Recommended: 2-3 credits to allow multiple tries.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <p class="description">
            <?php _e('Number of free credits given to new users (0-10).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_credits_per_package_field() {
        $value = get_option('ai_virtual_fitting_credits_per_package', 20);
        ?>
        <input type="number" 
               id="credits_per_package" 
               name="ai_virtual_fitting_credits_per_package" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="100" 
               class="small-text" />
        <span class="help-tooltip" 
              title="<?php esc_attr_e('This determines how many virtual fitting credits customers get when they purchase a credit package. Higher numbers provide better value but lower revenue per credit.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <p class="description">
            <?php _e('Number of credits included in each purchased package (1-100).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_package_price_field() {
        $value = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        ?>
        <input type="number" 
               id="credits_package_price" 
               name="ai_virtual_fitting_credits_package_price" 
               value="<?php echo esc_attr($value); ?>" 
               min="0.01" 
               step="0.01" 
               class="small-text" />
        <span><?php echo get_woocommerce_currency_symbol(); ?></span>
        <span class="help-tooltip" 
              title="<?php esc_attr_e('This is the price customers pay for a credit package. Consider your costs (API usage, server resources) and desired profit margin. The price should provide good value while covering your expenses.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <p class="description">
            <?php _e('Price for each credit package in your store currency.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_max_image_size_field() {
        $value = get_option('ai_virtual_fitting_max_image_size', 10485760);
        $value_mb = round($value / 1048576, 1);
        ?>
        <input type="number" 
               id="max_image_size" 
               name="ai_virtual_fitting_max_image_size" 
               value="<?php echo esc_attr($value); ?>" 
               min="1048576" 
               max="52428800" 
               step="1048576" 
               class="regular-text" />
        <span class="help-tooltip" 
              title="<?php esc_attr_e('Controls the maximum file size for customer photos and product images. Larger files provide better quality but use more server resources and bandwidth. 10MB is recommended for most setups.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <span><?php _e('bytes', 'ai-virtual-fitting'); ?></span>
        <span class="description">(<?php printf(__('Currently: %s MB', 'ai-virtual-fitting'), $value_mb); ?>)</span>
        <p class="description">
            <?php _e('Maximum allowed image file size in bytes (1MB - 50MB).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_retry_attempts_field() {
        $value = get_option('ai_virtual_fitting_api_retry_attempts', 3);
        ?>
        <input type="number" 
               id="api_retry_attempts" 
               name="ai_virtual_fitting_api_retry_attempts" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="10" 
               class="small-text" />
        <span class="help-tooltip" 
              title="<?php esc_attr_e('When the Google AI API fails (due to network issues or rate limits), the system will automatically retry this many times before giving up. Higher values provide better reliability but may cause longer delays.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <p class="description">
            <?php _e('Number of times to retry failed API calls (1-10).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_logging_field() {
        $value = get_option('ai_virtual_fitting_enable_logging', true);
        ?>
        <label>
            <input type="checkbox" 
                   id="enable_logging" 
                   name="ai_virtual_fitting_enable_logging" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php _e('Enable detailed logging for debugging and monitoring', 'ai-virtual-fitting'); ?>
        </label>
        <span class="help-tooltip" 
              title="<?php esc_attr_e('When enabled, the plugin will write detailed logs about API calls, errors, and user activities. This is helpful for troubleshooting issues but may use additional disk space. Logs are written to the WordPress debug log.', 'ai-virtual-fitting'); ?>"
              style="display: inline-block; width: 18px; height: 18px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 18px; font-size: 12px; font-weight: bold; margin-left: 8px; cursor: help; vertical-align: middle;">?</span>
        <p class="description">
            <?php _e('Logs are written to the WordPress debug log when WP_DEBUG_LOG is enabled.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_cleanup_hours_field() {
        $value = get_option('ai_virtual_fitting_temp_file_cleanup_hours', 24);
        ?>
        <input type="number" 
               id="temp_file_cleanup_hours" 
               name="ai_virtual_fitting_temp_file_cleanup_hours" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="168" 
               class="small-text" />
        <span><?php _e('hours', 'ai-virtual-fitting'); ?></span>
        <p class="description">
            <?php _e('How long to keep temporary files before automatic cleanup (1-168 hours).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_api_timeout_field() {
        $value = get_option('ai_virtual_fitting_api_timeout', 60);
        ?>
        <input type="number" 
               id="api_timeout" 
               name="ai_virtual_fitting_api_timeout" 
               value="<?php echo esc_attr($value); ?>" 
               min="10" 
               max="300" 
               class="small-text" />
        <span><?php _e('seconds', 'ai-virtual-fitting'); ?></span>
        <p class="description">
            <?php _e('Maximum time to wait for API responses (10-300 seconds).', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_require_login_field() {
        $value = get_option('ai_virtual_fitting_require_login', true);
        ?>
        <label>
            <input type="checkbox" 
                   id="require_login" 
                   name="ai_virtual_fitting_require_login" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php _e('Require users to be logged in to use virtual fitting', 'ai-virtual-fitting'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, only logged-in users can access virtual fitting features.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_user_roles_field() {
        $selected_roles = get_option('ai_virtual_fitting_allowed_user_roles', array('customer', 'subscriber', 'administrator'));
        $all_roles = wp_roles()->get_names();
        ?>
        <fieldset>
            <?php foreach ($all_roles as $role_key => $role_name): ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" 
                       name="ai_virtual_fitting_allowed_user_roles[]" 
                       value="<?php echo esc_attr($role_key); ?>"
                       <?php checked(in_array($role_key, $selected_roles)); ?> />
                <?php echo esc_html($role_name); ?>
            </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description">
            <?php _e('Select which user roles can access virtual fitting features.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_analytics_field() {
        $value = get_option('ai_virtual_fitting_enable_analytics', true);
        ?>
        <label>
            <input type="checkbox" 
                   id="enable_analytics" 
                   name="ai_virtual_fitting_enable_analytics" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php _e('Enable usage analytics and tracking', 'ai-virtual-fitting'); ?>
        </label>
        <p class="description">
            <?php _e('Collect anonymous usage statistics for plugin improvement.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_email_notifications_field() {
        $value = get_option('ai_virtual_fitting_enable_email_notifications', true);
        ?>
        <label>
            <input type="checkbox" 
                   id="enable_email_notifications" 
                   name="ai_virtual_fitting_enable_email_notifications" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php _e('Send email notifications to customers for credit purchases', 'ai-virtual-fitting'); ?>
        </label>
        <p class="description">
            <?php _e('Customers will receive email confirmations when credits are added to their account.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    public function render_admin_email_notifications_field() {
        $value = get_option('ai_virtual_fitting_admin_email_notifications', false);
        ?>
        <label>
            <input type="checkbox" 
                   id="admin_email_notifications" 
                   name="ai_virtual_fitting_admin_email_notifications" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php _e('Send email notifications to admin for system events', 'ai-virtual-fitting'); ?>
        </label>
        <p class="description">
            <?php _e('Receive notifications for API errors, system issues, and high usage alerts.', 'ai-virtual-fitting'); ?>
        </p>
        <?php
    }
    
    /**
     * Sanitization callbacks
     */
    public function sanitize_api_key($value) {
        return sanitize_text_field(trim($value));
    }
    
    public function sanitize_ai_prompt($value) {
        // Trim and sanitize the prompt
        $value = trim($value);
        
        // If empty, return empty (will use default)
        if (empty($value)) {
            return '';
        }
        
        // Validate length (10-2000 characters)
        if (strlen($value) < 10) {
            add_settings_error(
                'ai_virtual_fitting_ai_prompt_template',
                'prompt_too_short',
                __('AI prompt must be at least 10 characters long.', 'ai-virtual-fitting')
            );
            return get_option('ai_virtual_fitting_ai_prompt_template', '');
        }
        
        if (strlen($value) > 2000) {
            add_settings_error(
                'ai_virtual_fitting_ai_prompt_template',
                'prompt_too_long',
                __('AI prompt must be no more than 2000 characters long.', 'ai-virtual-fitting')
            );
            return get_option('ai_virtual_fitting_ai_prompt_template', '');
        }
        
        // Sanitize for safe storage (allow basic formatting)
        return wp_kses($value, array(
            'br' => array(),
            'p' => array(),
            'strong' => array(),
            'em' => array()
        ));
    }
    
    public function sanitize_api_provider($value) {
        $allowed_providers = array('google_ai_studio', 'vertex_ai');
        return in_array($value, $allowed_providers) ? $value : 'google_ai_studio';
    }
    
    public function sanitize_vertex_credentials($value) {
        $value = trim($value);
        
        // If empty, return empty
        if (empty($value)) {
            return '';
        }
        
        // Validate JSON format
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'ai_virtual_fitting_vertex_credentials',
                'invalid_json',
                __('Invalid JSON format for Vertex AI credentials.', 'ai-virtual-fitting')
            );
            return get_option('ai_virtual_fitting_vertex_credentials', '');
        }
        
        // Validate required fields
        $required_fields = array('type', 'project_id', 'private_key_id', 'private_key', 'client_email');
        foreach ($required_fields as $field) {
            if (!isset($decoded[$field]) || empty($decoded[$field])) {
                add_settings_error(
                    'ai_virtual_fitting_vertex_credentials',
                    'missing_field',
                    sprintf(__('Missing required field "%s" in Vertex AI credentials.', 'ai-virtual-fitting'), $field)
                );
                return get_option('ai_virtual_fitting_vertex_credentials', '');
            }
        }
        
        // Validate service account type
        if ($decoded['type'] !== 'service_account') {
            add_settings_error(
                'ai_virtual_fitting_vertex_credentials',
                'invalid_type',
                __('Vertex AI credentials must be of type "service_account".', 'ai-virtual-fitting')
            );
            return get_option('ai_virtual_fitting_vertex_credentials', '');
        }
        
        return $value;
    }
    
    public function sanitize_positive_integer($value) {
        $value = intval($value);
        return max(0, $value);
    }
    
    public function sanitize_price($value) {
        $value = floatval($value);
        return max(0.01, $value);
    }
    
    public function sanitize_file_size($value) {
        $value = intval($value);
        return max(1048576, min(52428800, $value)); // 1MB - 50MB
    }
    
    public function sanitize_retry_attempts($value) {
        $value = intval($value);
        return max(1, min(10, $value));
    }
    
    public function sanitize_boolean($value) {
        return (bool) $value;
    }
    
    public function sanitize_cleanup_hours($value) {
        $value = intval($value);
        return max(1, min(168, $value)); // 1 hour to 1 week
    }
    
    public function sanitize_timeout($value) {
        $value = intval($value);
        return max(10, min(300, $value)); // 10 seconds to 5 minutes
    }
    
    public function sanitize_user_roles($value) {
        if (!is_array($value)) {
            return array('customer', 'subscriber', 'administrator');
        }
        
        $all_roles = array_keys(wp_roles()->get_names());
        $sanitized = array();
        
        foreach ($value as $role) {
            if (in_array($role, $all_roles)) {
                $sanitized[] = $role;
            }
        }
        
        // Ensure at least administrator role is always included
        if (!in_array('administrator', $sanitized)) {
            $sanitized[] = 'administrator';
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX handlers
     */
    public function test_api_connection() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(__('API key is required.', 'ai-virtual-fitting'));
        }
        
        // Test API connection using Image Processor
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        $test_result = $image_processor->test_api_connection($api_key);
        
        if ($test_result['success']) {
            wp_send_json_success(__('API connection successful!', 'ai-virtual-fitting'));
        } else {
            wp_send_json_error($test_result['message']);
        }
    }
    
    public function get_analytics_data() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $data_type = sanitize_text_field($_POST['data_type'] ?? 'dashboard');
        
        // Use system analytics for admin dashboard (more reliable)
        $data = $this->get_system_analytics();
        
        // If Analytics Manager is available, enhance with additional data
        if (class_exists('AI_Virtual_Fitting_Core')) {
            try {
                $core = AI_Virtual_Fitting_Core::instance();
                if (method_exists($core, 'get_analytics_manager')) {
                    $analytics_manager = $core->get_analytics_manager();
                    if ($analytics_manager) {
                        $enhanced_data = $analytics_manager->get_dashboard_analytics($period);
                        // Merge enhanced analytics data
                        if (isset($enhanced_data['summary'])) {
                            $data = array_merge($data, array(
                                'total_fittings' => $enhanced_data['summary']['total_fittings'],
                                'successful_fittings' => $enhanced_data['summary']['successful_fittings'],
                                'avg_processing_time' => $enhanced_data['summary']['avg_processing_time']
                            ));
                        }
                    }
                }
            } catch (Exception $e) {
                // Fallback to system analytics only
                error_log('AI Virtual Fitting: Analytics Manager error - ' . $e->getMessage());
            }
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get user credit details for admin
     */
    public function get_user_credits() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $user_credits = $this->get_user_credit_list($page, $per_page, $search);
        wp_send_json_success($user_credits);
    }
    
    /**
     * Update user credits (admin action)
     */
    public function update_user_credits() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $credits = intval($_POST['credits'] ?? 0);
        $action = sanitize_text_field($_POST['credit_action'] ?? 'set');
        
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'ai-virtual-fitting'));
        }
        
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        switch ($action) {
            case 'add':
                $result = $credit_manager->add_credits($user_id, $credits);
                $message = sprintf(__('Added %d credits to user.', 'ai-virtual-fitting'), $credits);
                break;
            case 'subtract':
                $current_credits = $credit_manager->get_customer_credits($user_id);
                if ($current_credits >= $credits) {
                    for ($i = 0; $i < $credits; $i++) {
                        $credit_manager->deduct_credit($user_id);
                    }
                    $result = true;
                    $message = sprintf(__('Subtracted %d credits from user.', 'ai-virtual-fitting'), $credits);
                } else {
                    wp_send_json_error(__('User does not have enough credits to subtract.', 'ai-virtual-fitting'));
                }
                break;
            case 'set':
            default:
                // Set credits by calculating difference
                $current_credits = $credit_manager->get_customer_credits($user_id);
                $difference = $credits - $current_credits;
                
                if ($difference > 0) {
                    $result = $credit_manager->add_credits($user_id, $difference);
                } elseif ($difference < 0) {
                    for ($i = 0; $i < abs($difference); $i++) {
                        $credit_manager->deduct_credit($user_id);
                    }
                    $result = true;
                } else {
                    $result = true; // No change needed
                }
                $message = sprintf(__('Set user credits to %d.', 'ai-virtual-fitting'), $credits);
                break;
        }
        
        if ($result) {
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to update user credits.', 'ai-virtual-fitting'));
        }
    }
    
    /**
     * Get system analytics data
     */
    private function get_system_analytics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        
        // Get total users with credits
        $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_name}");
        
        // Get total credits purchased
        $total_credits_purchased = $wpdb->get_var("SELECT SUM(total_credits_purchased) FROM {$table_name}");
        
        // Get total credits remaining
        $total_credits_remaining = $wpdb->get_var("SELECT SUM(credits_remaining) FROM {$table_name}");
        
        // Get recent activity (last 30 days)
        $recent_activity = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE updated_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        // Get WooCommerce credit product sales
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        $credit_sales = 0;
        
        if ($credit_product_id) {
            $credit_sales = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_items oi
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                JOIN {$wpdb->prefix}posts p ON oi.order_id = p.ID
                WHERE oim.meta_key = '_product_id' 
                AND oim.meta_value = %d
                AND p.post_status = 'wc-completed'
            ", $credit_product_id));
        }
        
        return array(
            'total_users' => intval($total_users),
            'total_credits_purchased' => intval($total_credits_purchased),
            'total_credits_remaining' => intval($total_credits_remaining),
            'total_credits_used' => intval($total_credits_purchased) - intval($total_credits_remaining),
            'recent_activity' => intval($recent_activity),
            'credit_sales' => intval($credit_sales),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Get system status information
     */
    public function get_system_status() {
        $status = array();
        
        // Check WordPress version
        $status['wordpress_version'] = array(
            'value' => get_bloginfo('version'),
            'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'good' : 'warning'
        );
        
        // Check WooCommerce
        $status['woocommerce'] = array(
            'value' => class_exists('WooCommerce') ? WC()->version : __('Not installed', 'ai-virtual-fitting'),
            'status' => class_exists('WooCommerce') ? 'good' : 'error'
        );
        
        // Check API key
        $api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
        $status['api_key'] = array(
            'value' => !empty($api_key) ? __('Configured', 'ai-virtual-fitting') : __('Not configured', 'ai-virtual-fitting'),
            'status' => !empty($api_key) ? 'good' : 'warning'
        );
        
        // Check database tables
        global $wpdb;
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        $status['database'] = array(
            'value' => $table_exists ? __('Tables exist', 'ai-virtual-fitting') : __('Tables missing', 'ai-virtual-fitting'),
            'status' => $table_exists ? 'good' : 'error'
        );
        
        // Check credit product
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        $product_exists = $credit_product_id && get_post($credit_product_id);
        
        $status['credit_product'] = array(
            'value' => $product_exists ? __('Product exists', 'ai-virtual-fitting') : __('Product missing', 'ai-virtual-fitting'),
            'status' => $product_exists ? 'good' : 'warning'
        );
        
        return $status;
    }
    
    /**
     * Get paginated list of users with credits
     */
    private function get_user_credit_list($page = 1, $per_page = 20, $search = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        $offset = ($page - 1) * $per_page;
        
        // Build search condition
        $search_condition = '';
        $search_params = array();
        
        if (!empty($search)) {
            $search_condition = "AND (u.user_login LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)";
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $search_params = array($search_like, $search_like, $search_like);
        }
        
        // Get total count for pagination
        $total_query = "
            SELECT COUNT(DISTINCT c.user_id) 
            FROM {$table_name} c
            JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE 1=1 {$search_condition}
        ";
        
        if (!empty($search_params)) {
            $total = $wpdb->get_var($wpdb->prepare($total_query, $search_params));
        } else {
            $total = $wpdb->get_var($total_query);
        }
        
        // Get user credit data with pagination
        $query = "
            SELECT 
                c.user_id,
                c.credits_remaining,
                c.total_credits_purchased,
                c.created_at,
                c.updated_at,
                u.user_login,
                u.display_name,
                u.user_email,
                u.user_registered
            FROM {$table_name} c
            JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE 1=1 {$search_condition}
            ORDER BY c.updated_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params = array_merge($search_params, array($per_page, $offset));
        $results = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Format results
        $users = array();
        foreach ($results as $row) {
            $users[] = array(
                'user_id' => intval($row->user_id),
                'username' => $row->user_login,
                'display_name' => $row->display_name,
                'email' => $row->user_email,
                'credits_remaining' => intval($row->credits_remaining),
                'total_credits_purchased' => intval($row->total_credits_purchased),
                'credits_used' => intval($row->total_credits_purchased) - intval($row->credits_remaining),
                'user_registered' => $row->user_registered,
                'last_activity' => $row->updated_at,
                'profile_url' => admin_url('user-edit.php?user_id=' . $row->user_id)
            );
        }
        
        return array(
            'users' => $users,
            'pagination' => array(
                'total' => intval($total),
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page)
            )
        );
    }
    
    /**
     * Display help documentation
     */
    public function display_help_documentation() {
        // Load help documentation class
        require_once AI_VIRTUAL_FITTING_PLUGIN_DIR . 'admin/help-documentation.php';
        
        $setup_guide = AI_Virtual_Fitting_Help_Documentation::get_setup_guide();
        $troubleshooting = AI_Virtual_Fitting_Help_Documentation::get_troubleshooting_guide();
        $system_requirements = AI_Virtual_Fitting_Help_Documentation::get_system_requirements();
        $faq = AI_Virtual_Fitting_Help_Documentation::get_faq();
        
        ?>
        <div class="ai-virtual-fitting-help-content">
            <!-- Setup Guide -->
            <div class="help-section">
                <h3><?php echo esc_html($setup_guide['title']); ?></h3>
                <ol>
                    <?php foreach ($setup_guide['steps'] as $step): ?>
                    <li>
                        <strong><?php echo esc_html($step['title']); ?></strong>
                        <p><?php echo esc_html($step['description']); ?></p>
                        <?php if (isset($step['link'])): ?>
                        <p><a href="<?php echo esc_url($step['link']); ?>" target="_blank"><?php echo esc_html($step['link_text']); ?></a></p>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            
            <!-- System Requirements -->
            <div class="help-section">
                <h3><?php echo esc_html($system_requirements['title']); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Component', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Minimum', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Recommended', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Current', 'ai-virtual-fitting'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($system_requirements['requirements'] as $req): ?>
                        <tr>
                            <td><?php echo esc_html($req['name']); ?></td>
                            <td><?php echo esc_html($req['minimum']); ?></td>
                            <td><?php echo esc_html($req['recommended']); ?></td>
                            <td><?php echo esc_html($req['current']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- FAQ -->
            <div class="help-section">
                <h3><?php echo esc_html($faq['title']); ?></h3>
                <?php foreach ($faq['questions'] as $item): ?>
                <div class="faq-item">
                    <h4><?php echo esc_html($item['question']); ?></h4>
                    <p><?php echo esc_html($item['answer']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Troubleshooting -->
            <div class="help-section">
                <h3><?php echo esc_html($troubleshooting['title']); ?></h3>
                <?php foreach ($troubleshooting['issues'] as $issue): ?>
                <div class="troubleshooting-item">
                    <h4><?php echo esc_html($issue['problem']); ?></h4>
                    <ul>
                        <?php foreach ($issue['solutions'] as $solution): ?>
                        <li><?php echo esc_html($solution); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Export plugin settings
     */
    public function export_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $settings = array();
        $option_names = array(
            'google_ai_api_key',
            'initial_credits',
            'credits_per_package',
            'credits_package_price',
            'max_image_size',
            'api_retry_attempts',
            'enable_logging',
            'temp_file_cleanup_hours',
            'enable_analytics',
            'require_login',
            'allowed_user_roles',
            'api_timeout',
            'enable_email_notifications',
            'admin_email_notifications'
        );
        
        foreach ($option_names as $option_name) {
            $settings[$option_name] = get_option('ai_virtual_fitting_' . $option_name);
        }
        
        $filename = 'ai-virtual-fitting-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Import plugin settings
     */
    public function import_settings($settings_data) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $imported_count = 0;
        
        foreach ($settings_data as $option_name => $value) {
            $full_option_name = 'ai_virtual_fitting_' . $option_name;
            
            // Validate and sanitize based on option type
            switch ($option_name) {
                case 'google_ai_api_key':
                    $value = $this->sanitize_api_key($value);
                    break;
                case 'initial_credits':
                case 'credits_per_package':
                case 'api_retry_attempts':
                    $value = $this->sanitize_positive_integer($value);
                    break;
                case 'credits_package_price':
                    $value = $this->sanitize_price($value);
                    break;
                case 'max_image_size':
                    $value = $this->sanitize_file_size($value);
                    break;
                case 'temp_file_cleanup_hours':
                    $value = $this->sanitize_cleanup_hours($value);
                    break;
                case 'api_timeout':
                    $value = $this->sanitize_timeout($value);
                    break;
                case 'allowed_user_roles':
                    $value = $this->sanitize_user_roles($value);
                    break;
                default:
                    if (is_bool($value) || in_array($value, array('0', '1', 0, 1))) {
                        $value = $this->sanitize_boolean($value);
                    }
                    break;
            }
            
            if (update_option($full_option_name, $value)) {
                $imported_count++;
            }
        }
        
        return $imported_count;
    }
}