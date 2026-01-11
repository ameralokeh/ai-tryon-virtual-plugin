# AI Virtual Fitting Plugin

A comprehensive WordPress plugin that provides AI-powered virtual try-on experiences for wedding dresses using Google AI Studio's Gemini 2.5 Flash Image model. This plugin seamlessly integrates with WooCommerce to provide a complete e-commerce solution for virtual fitting services.

## 🌟 Features

### Core Functionality
- **AI-Powered Virtual Fitting**: Uses Google AI Studio's Gemini 2.5 Flash Image model for realistic virtual try-on experiences
- **Credit-Based System**: Flexible usage tracking with initial free credits and purchasable credit packages
- **WooCommerce Integration**: Seamless integration with WooCommerce for credit purchases and product management
- **User Authentication**: Secure access control with WordPress user authentication
- **Image Processing**: Advanced image upload, validation, and optimization
- **Download Functionality**: High-quality result image downloads for customers
- **Admin Dashboard**: Comprehensive monitoring and configuration interface

### Advanced Features
- **Performance Optimization**: Asynchronous processing, caching, and queue management
- **Error Handling**: Comprehensive error handling with user-friendly messages
- **Analytics**: Usage tracking and performance metrics
- **Multi-language Support**: Translation-ready with WordPress i18n
- **Security**: File validation, MIME type checking, and secure image processing
- **Responsive Design**: Mobile-friendly interface for all devices

## 📋 Requirements

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **WooCommerce**: 5.0 or higher

### External Services
- **Google AI Studio API Key**: Required for AI processing
- **SSL Certificate**: Recommended for production use

## 🚀 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Upload the plugin files to `/wp-content/plugins/ai-virtual-fitting/`
2. Activate the plugin through the WordPress admin
3. The plugin will automatically create necessary database tables and WooCommerce products

### Post-Installation Setup
1. Go to WordPress Admin → AI Virtual Fitting → Settings
2. Enter your Google AI Studio API key
3. Configure system settings as needed
4. Test the virtual fitting functionality

## ⚙️ Configuration

### Google AI Studio Setup
1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Create an account or sign in
3. Generate an API key for Gemini 2.5 Flash Image
4. Copy the API key to the plugin settings

### Plugin Settings

#### Basic Settings
- **Google AI API Key**: Your Google AI Studio API key (required)
- **Initial Credits**: Free credits for new users (default: 2)
- **Credits per Package**: Credits in each purchasable package (default: 20)
- **Package Price**: Price for credit packages (default: $10.00)

#### Advanced Settings
- **Maximum Image Size**: Maximum upload size in MB (default: 10MB)
- **Allowed Image Types**: Supported formats (JPEG, PNG, WebP)
- **API Retry Attempts**: Number of retry attempts for failed API calls (default: 3)
- **API Timeout**: Timeout for API requests in seconds (default: 60)
- **Enable Logging**: System logging for debugging (default: enabled)
- **Temp File Cleanup**: Hours to keep temporary files (default: 24)

#### Performance Settings
- **Enable Analytics**: Usage tracking and metrics (default: enabled)
- **Enable Email Notifications**: Customer notifications (default: enabled)
- **Admin Email Notifications**: Admin alerts (default: disabled)

## 📖 Usage Guide

### For Customers

#### Getting Started
1. **Account Creation**: Create a WordPress account or log in
2. **Initial Credits**: Receive 2 free virtual fitting credits
3. **Virtual Fitting Page**: Navigate to `/virtual-fitting` on your website

#### Virtual Fitting Process
1. **Select a Dress**: Browse the product slider and select a wedding dress
2. **Upload Photo**: Upload a clear, well-lit photo of yourself
   - Supported formats: JPEG, PNG, WebP
   - Maximum size: 10MB
   - Recommended: 800x600 pixels or larger
3. **Try On**: Click "Try On This Dress" to start AI processing
4. **Wait for Results**: Processing typically takes 30-60 seconds
5. **Download Results**: Save your virtual fitting image

#### Photo Guidelines
- **Lighting**: Use natural, even lighting
- **Background**: Plain background works best
- **Pose**: Stand straight, facing the camera
- **Quality**: High resolution for best results
- **Format**: JPEG, PNG, or WebP formats only

#### Credit Management
- **Initial Credits**: 2 free credits for new users
- **Credit Usage**: 1 credit per virtual fitting
- **Purchase More**: Buy 20 credits for $10 through WooCommerce checkout
- **Credit Balance**: View remaining credits on the virtual fitting page

### For Administrators

#### Dashboard Overview
Access the admin dashboard at WordPress Admin → AI Virtual Fitting

#### Monitoring
- **Usage Statistics**: Track virtual fitting usage
- **Performance Metrics**: Monitor system performance
- **Error Logs**: Review system errors and issues
- **User Activity**: Monitor customer usage patterns

#### System Management
- **API Configuration**: Manage Google AI Studio settings
- **Credit Management**: Monitor and adjust credit settings
- **Performance Tuning**: Optimize system performance
- **Maintenance**: Regular system maintenance tasks

#### Troubleshooting
- **Error Logs**: Check system logs for issues
- **API Status**: Monitor Google AI Studio API connectivity
- **Database Health**: Verify database integrity
- **File Permissions**: Ensure proper file permissions

## 🔧 Technical Documentation

### Database Schema
The plugin creates the following database table:

```sql
CREATE TABLE wp_virtual_fitting_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    credits_remaining INT DEFAULT 0,
    total_credits_purchased INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);
```

### File Structure
```
ai-virtual-fitting/
├── ai-virtual-fitting.php          # Main plugin file
├── README.md                       # Documentation
├── uninstall.php                   # Uninstall cleanup
├── includes/                       # Core classes
│   ├── class-virtual-fitting-core.php
│   ├── class-credit-manager.php
│   ├── class-image-processor.php
│   ├── class-woocommerce-integration.php
│   ├── class-database-manager.php
│   ├── class-performance-manager.php
│   └── class-analytics-manager.php
├── admin/                          # Admin interface
│   ├── class-admin-settings.php
│   ├── admin-settings-page.php
│   ├── help-documentation.php
│   ├── css/admin-settings.css
│   └── js/admin-settings.js
├── public/                         # Frontend interface
│   ├── class-public-interface.php
│   ├── virtual-fitting-page.php
│   ├── css/virtual-fitting.css
│   └── js/virtual-fitting.js
├── tests/                          # Test files
│   └── [various test files]
├── languages/                      # Translations
│   └── ai-virtual-fitting.pot
└── assets/                         # Static assets
    ├── images/
    └── icons/
```

### Hooks and Filters

#### Actions
- `ai_virtual_fitting_activated`: Fired when plugin is activated
- `ai_virtual_fitting_deactivated`: Fired when plugin is deactivated
- `ai_virtual_fitting_credit_added`: Fired when credits are added to user
- `ai_virtual_fitting_credit_deducted`: Fired when credits are deducted
- `ai_virtual_fitting_processing_complete`: Fired when AI processing completes

#### Filters
- `ai_virtual_fitting_initial_credits`: Filter initial credits amount
- `ai_virtual_fitting_max_image_size`: Filter maximum image size
- `ai_virtual_fitting_allowed_types`: Filter allowed image types
- `ai_virtual_fitting_api_timeout`: Filter API timeout duration

### API Integration
The plugin integrates with Google AI Studio's Gemini 2.5 Flash Image model:

- **Endpoint**: Google AI Studio API
- **Model**: Gemini 2.5 Flash Image
- **Input**: Customer photo + 4 product images
- **Output**: Virtual fitting result image
- **Authentication**: API key-based authentication

## 🛠️ Development

### Setting Up Development Environment
1. Clone the repository
2. Set up local WordPress with WooCommerce
3. Install the plugin in development mode
4. Configure Google AI Studio API key
5. Run tests to verify functionality

### Running Tests
```bash
# Run all tests
php tests/wp-test-runner.php

# Run specific test
php tests/wp-test-runner.php test-credit-manager

# Run integration tests
php tests/wp-test-runner.php test-integration-workflow
```

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 🔒 Security

### Data Protection
- **Image Security**: Uploaded images are validated and sanitized
- **File Permissions**: Proper file permissions for uploaded content
- **Database Security**: Prepared statements prevent SQL injection
- **User Authentication**: WordPress authentication system integration

### Privacy
- **Data Retention**: Temporary files are automatically cleaned up
- **User Data**: Minimal user data collection
- **GDPR Compliance**: Compatible with GDPR requirements
- **Data Export**: User data can be exported/deleted

## 🚨 Troubleshooting

### Common Issues

#### Plugin Activation Fails
- **Check Requirements**: Verify WordPress, PHP, and WooCommerce versions
- **Database Permissions**: Ensure database write permissions
- **Memory Limit**: Increase PHP memory limit if needed

#### API Errors
- **Invalid API Key**: Verify Google AI Studio API key
- **Rate Limiting**: Check API usage limits
- **Network Issues**: Verify server connectivity

#### Image Upload Issues
- **File Size**: Check maximum upload size settings
- **File Type**: Verify supported image formats
- **Permissions**: Check upload directory permissions

#### Credit System Issues
- **Database Connection**: Verify database connectivity
- **WooCommerce Integration**: Ensure WooCommerce is active
- **Order Processing**: Check WooCommerce order hooks

### Getting Help
1. **Check Logs**: Review error logs in WordPress admin
2. **Documentation**: Consult this documentation
3. **Support**: Contact plugin support team
4. **Community**: Check WordPress plugin forums

## 📊 Performance

### Optimization Features
- **Asynchronous Processing**: Non-blocking AI processing
- **Image Caching**: Optimized image storage and retrieval
- **Database Optimization**: Efficient database queries
- **Queue Management**: Request queuing for high traffic

### Performance Monitoring
- **Response Times**: Monitor API response times
- **Error Rates**: Track error rates and types
- **Usage Patterns**: Analyze usage patterns
- **System Load**: Monitor system resource usage

## 🔄 Updates and Maintenance

### Automatic Updates
The plugin supports WordPress automatic updates for:
- Security patches
- Bug fixes
- Minor feature updates

### Manual Updates
For major updates:
1. Backup your website
2. Download the latest version
3. Replace plugin files
4. Run any necessary database migrations

### Maintenance Tasks
- **Log Cleanup**: Regular log file cleanup
- **Temp File Cleanup**: Automatic temporary file removal
- **Database Optimization**: Periodic database optimization
- **Performance Monitoring**: Regular performance checks

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🤝 Support

### Documentation
- **Plugin Documentation**: This README file
- **WordPress Codex**: WordPress development documentation
- **WooCommerce Docs**: WooCommerce integration documentation
- **Google AI Studio**: Google AI Studio API documentation

### Support Channels
- **Plugin Support**: Contact plugin developer
- **WordPress Forums**: WordPress community support
- **WooCommerce Support**: WooCommerce-specific issues
- **GitHub Issues**: Bug reports and feature requests

### Professional Services
- **Custom Development**: Plugin customization services
- **Integration Support**: Third-party integration assistance
- **Performance Optimization**: Performance tuning services
- **Training**: User and administrator training

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Compatibility**: WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+