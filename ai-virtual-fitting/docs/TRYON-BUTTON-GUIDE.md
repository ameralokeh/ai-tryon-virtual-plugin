# Virtual Try-On Button - User Guide

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Admin Setup Guide](#admin-setup-guide)
4. [Customer Experience](#customer-experience)
5. [Configuration Options](#configuration-options)
6. [Common Use Cases](#common-use-cases)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)
9. [FAQ](#faq)

## Overview

The Virtual Try-On Button feature provides a seamless way for customers to access the virtual fitting experience directly from product pages. With a single click, customers can try on wedding dresses virtually without navigating through multiple pages.

### Key Benefits

**For Customers:**
- One-click access to virtual fitting from product pages
- Automatic product pre-selection saves time
- Seamless experience with no manual product search
- Works on desktop and mobile devices

**For Store Owners:**
- Increased engagement with virtual fitting feature
- Higher conversion rates from product pages
- Easy configuration with no coding required
- Analytics tracking for button performance
- Category-based filtering for targeted display

### How It Works

1. Customer browses wedding dress product pages
2. Sees "Try on Virtually" button below "Add to Cart"
3. Clicks button to access virtual fitting page
4. Selected dress is automatically pre-loaded
5. Customer uploads photo and sees result
6. Downloads result and continues shopping

## Quick Start

### 5-Minute Setup

Follow these steps to get the Try-On Button working on your site:

**Step 1: Enable the Button** (30 seconds)
1. Go to **WordPress Admin → AI Virtual Fitting → Settings**
2. Click the **Try-On Button** tab
3. Check **Enable Try-On Button**
4. Click **Save Changes**

**Step 2: Select Target Page** (1 minute)
1. In the same settings page
2. Find **Virtual Fitting Page** dropdown
3. Select your virtual fitting page (usually "Virtual Fitting")
4. Click **Save Changes**

**Step 3: Test the Button** (2 minutes)
1. Visit any wedding dress product page
2. Look for the "Try on Virtually" button
3. Click the button
4. Verify you're redirected to virtual fitting page
5. Confirm the dress is pre-selected

**Step 4: Configure Categories** (Optional - 1 minute)
1. Return to settings page
2. Find **Allowed Categories** section
3. Select categories where button should appear
4. Leave empty to show on all products
5. Click **Save Changes**

**Done!** Your Try-On Button is now live.

## Admin Setup Guide

### Accessing Settings

1. Log in to WordPress admin dashboard
2. Navigate to **AI Virtual Fitting** in the left sidebar
3. Click **Settings**
4. Select the **Try-On Button** tab

### Configuration Steps

#### 1. Enable/Disable Button

**Purpose:** Global toggle to turn button on or off across all products.

**Steps:**
1. Find **Enable Try-On Button** checkbox
2. Check to enable, uncheck to disable
3. Click **Save Changes**

**When to disable:**
- During testing or maintenance
- Temporarily while updating products
- If virtual fitting page is unavailable

#### 2. Select Virtual Fitting Page

**Purpose:** Choose which page the button redirects to.

**Steps:**
1. Find **Virtual Fitting Page** dropdown
2. Select your virtual fitting page from the list
3. Click **Save Changes**

**Requirements:**
- Page must exist and be published
- Page should contain virtual fitting interface
- Typically named "Virtual Fitting" or "Try On Dresses"

**Validation:**
- Plugin validates page exists before saving
- Error message shown if page is invalid
- Previous valid page retained if new selection fails

#### 3. Customize Button Text

**Purpose:** Change the text displayed on the button.

**Steps:**
1. Find **Button Text** field
2. Enter your desired text (e.g., "Virtual Try-On", "See How It Looks")
3. Click **Save Changes**

**Default:** "Try on Virtually"

**Tips:**
- Keep text short and action-oriented
- Use clear, customer-friendly language
- Consider your brand voice
- Test different variations for best results

**Examples:**
- "Try on Virtually"
- "Virtual Try-On"
- "See How It Looks"
- "Try This Dress"
- "Virtual Fitting"

#### 4. Toggle Icon Display

**Purpose:** Show or hide the camera icon on the button.

**Steps:**
1. Find **Show Icon** checkbox
2. Check to show icon, uncheck to hide
3. Click **Save Changes**

**Recommendation:** Keep icon enabled for better visual recognition.

#### 5. Configure Category Filters

**Purpose:** Control which product categories display the button.

**Steps:**
1. Find **Allowed Categories** multi-select field
2. Hold Ctrl/Cmd and click to select multiple categories
3. Leave empty to show on all products
4. Click **Save Changes**

**Use Cases:**
- Show only on "Wedding Dresses" category
- Exclude accessories or non-dress items
- Target specific dress styles (e.g., "Ball Gown", "Mermaid")
- Limit to premium products

**Example Configuration:**
```
Selected Categories:
☑ Wedding Dresses
☑ Evening Gowns
☐ Accessories
☐ Veils
```

#### 6. Authentication Settings

**Purpose:** Control login requirements for virtual fitting.

**Note:** This uses the existing plugin authentication setting.

**Location:** **AI Virtual Fitting → Settings → General → Require Login**

**Options:**
- **Enabled:** Users must log in before virtual fitting
- **Disabled:** Guest users can use virtual fitting

**Behavior with Try-On Button:**
- If enabled and user not logged in: Button redirects to login page
- After login: User returns to virtual fitting with product pre-selected
- If disabled: Button goes directly to virtual fitting page

### Viewing Analytics

#### Accessing Button Statistics

1. Go to **AI Virtual Fitting → Analytics**
2. Click **Try-On Button** tab
3. View metrics and charts

#### Available Metrics

**Button Clicks:**
- Total clicks across all products
- Clicks per product
- Click trends over time
- Click-through rate from product views

**Conversions:**
- Percentage of clicks that result in completed fittings
- Conversion rate by product
- Time from click to completion
- Drop-off points in the flow

**Popular Products:**
- Most clicked products
- Products with highest conversion rates
- Trending dresses
- Category performance

**User Engagement:**
- Average clicks per user
- Repeat usage patterns
- Time spent on virtual fitting
- Download rates

#### Exporting Data

1. Select date range for report
2. Click **Export** button
3. Choose format (CSV or PDF)
4. Download file for analysis

## Customer Experience

### How Customers Use the Button

#### On Product Pages

**What Customers See:**
- Button appears below "Add to Cart" button
- Gradient purple/blue styling (customizable)
- Camera icon (optional)
- Text: "Try on Virtually" (customizable)
- Responsive design on mobile

**Button Appearance:**
```
┌─────────────────────────────────┐
│  [Add to Cart]                  │
│                                 │
│  📷 Try on Virtually            │
│  ↑ Gradient button with icon   │
└─────────────────────────────────┘
```

#### Clicking the Button

**Step 1: Click**
- Customer clicks "Try on Virtually" button
- Analytics event logged
- Redirect initiated

**Step 2: Authentication Check** (if required)
- If not logged in: Redirect to login page
- Login page includes return URL with product ID
- After login: Return to virtual fitting page

**Step 3: Virtual Fitting Page**
- Page loads with selected dress pre-loaded
- Dress is highlighted in product grid
- Dress scrolls into view automatically
- Preview images load in center panel

**Step 4: Upload and Process**
- Customer uploads photo (if not already uploaded)
- Clicks "Try On This Dress"
- AI processing begins
- Result displayed

**Step 5: Download Result**
- Customer views result
- Downloads high-resolution image
- Can try other dresses or return to shopping

### Mobile Experience

**Optimizations:**
- Button stacks properly on small screens
- Touch-friendly size and spacing
- Smooth scrolling on virtual fitting page
- Responsive product grid
- Mobile-optimized upload interface

**Mobile Layout:**
```
┌─────────────────┐
│ Product Image   │
│                 │
│ Product Name    │
│ $999.00         │
│                 │
│ [Add to Cart]   │
│                 │
│ 📷 Try on       │
│    Virtually    │
└─────────────────┘
```

### Accessibility Features

**Keyboard Navigation:**
- Button is keyboard accessible (Tab key)
- Enter/Space to activate
- Focus states clearly visible
- Logical tab order

**Screen Readers:**
- Descriptive ARIA labels
- Product name included in label
- Icon marked as decorative
- Semantic HTML structure

**WCAG 2.1 AA Compliance:**
- Sufficient color contrast (4.5:1 minimum)
- Keyboard accessible
- Screen reader compatible
- Focus indicators visible
- No flashing or moving content

## Configuration Options

### Button Styling

#### Default Styling

The button comes with attractive default styling:
- Gradient background (purple to blue)
- White text
- Rounded corners
- Hover effects (lift and shadow)
- Smooth transitions
- Responsive sizing

#### Custom Styling

**Via Theme CSS:**
```css
/* Add to your theme's style.css */
.ai-virtual-fitting-tryon-button {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    border-radius: 12px;
    padding: 14px 28px;
}

.ai-virtual-fitting-tryon-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
}
```

**Via Plugin Filters:**
```php
// Add to your theme's functions.php
add_filter('ai_virtual_fitting_tryon_button_class', function($classes) {
    $classes[] = 'my-custom-button-class';
    return $classes;
});
```

### Button Position

**Default Position:** After "Add to Cart" button

**Change Position:**
```php
// Remove from default position
remove_action('woocommerce_after_add_to_cart_button', 
    array($tryon_button, 'render_button'));

// Add to new position
add_action('woocommerce_before_add_to_cart_button', 
    array($tryon_button, 'render_button'));
```

**Available WooCommerce Hooks:**
- `woocommerce_before_add_to_cart_button`
- `woocommerce_after_add_to_cart_button` (default)
- `woocommerce_product_meta_start`
- `woocommerce_product_meta_end`
- `woocommerce_single_product_summary`

### Advanced Configuration

#### Per-Product Override

**Enable/Disable for Specific Products:**
```php
add_filter('ai_virtual_fitting_tryon_button_eligible', function($eligible, $product_id) {
    // Disable for product ID 123
    if ($product_id == 123) {
        return false;
    }
    return $eligible;
}, 10, 2);
```

#### Custom Button URL

**Add Custom Parameters:**
```php
add_filter('ai_virtual_fitting_tryon_button_url', function($url, $product_id) {
    // Add source tracking parameter
    return add_query_arg('source', 'product-page', $url);
}, 10, 2);
```

#### Custom Analytics

**Integrate with Google Analytics:**
```php
add_action('ai_virtual_fitting_tryon_button_clicked', function($product_id, $user_id) {
    $product = wc_get_product($product_id);
    ?>
    <script>
    gtag('event', 'virtual_tryon_click', {
        'product_id': <?php echo $product_id; ?>,
        'product_name': '<?php echo esc_js($product->get_name()); ?>'
    });
    </script>
    <?php
}, 10, 2);
```

## Common Use Cases

### Use Case 1: Wedding Dress Store

**Scenario:** Bridal boutique with 200+ wedding dresses

**Configuration:**
- Enable button on all products
- Use default button text: "Try on Virtually"
- Show icon for visual recognition
- Require login for personalized experience
- Track analytics for popular styles

**Results:**
- 35% of product page visitors click button
- 60% conversion rate from click to completed fitting
- Increased engagement with virtual fitting feature
- Better understanding of customer preferences

### Use Case 2: Multi-Category Store

**Scenario:** Store selling dresses, accessories, and veils

**Configuration:**
- Enable button only on "Wedding Dresses" category
- Exclude accessories and veils
- Custom button text: "See How It Looks"
- No login required for easier access
- Track category-specific performance

**Results:**
- Button appears only on relevant products
- Reduced confusion for customers
- Focused analytics on dress products
- Higher quality virtual fitting usage

### Use Case 3: Premium Dress Collection

**Scenario:** High-end boutique with exclusive designs

**Configuration:**
- Enable button only on products over $1000
- Custom button text: "Virtual Fitting"
- Require login for exclusive access
- Premium styling to match brand
- Detailed analytics tracking

**Implementation:**
```php
// Show button only for products over $1000
add_filter('ai_virtual_fitting_tryon_button_eligible', function($eligible, $product_id) {
    if (!$eligible) return false;
    
    $product = wc_get_product($product_id);
    return $product && $product->get_price() >= 1000;
}, 10, 2);
```

**Results:**
- Exclusive feature for premium products
- Enhanced brand perception
- Higher engagement from serious buyers
- Better conversion rates

### Use Case 4: Seasonal Collections

**Scenario:** Store with rotating seasonal collections

**Configuration:**
- Enable button on specific product tags
- Tag products with "virtual-fitting-enabled"
- Easy to add/remove products from feature
- Flexible seasonal management

**Implementation:**
```php
// Show button only for tagged products
add_filter('ai_virtual_fitting_tryon_button_eligible', function($eligible, $product_id) {
    if (!$eligible) return false;
    
    $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'slugs'));
    return in_array('virtual-fitting-enabled', $tags);
}, 10, 2);
```

**Results:**
- Easy seasonal management
- Flexible product selection
- No category restructuring needed
- Quick updates for new collections

## Troubleshooting

### Button Not Appearing

**Problem:** Button doesn't show on product pages

**Possible Causes & Solutions:**

1. **Button Not Enabled**
   - Go to Settings → Try-On Button
   - Check "Enable Try-On Button"
   - Save changes

2. **No Virtual Fitting Page Selected**
   - Go to Settings → Try-On Button
   - Select virtual fitting page from dropdown
   - Ensure page exists and is published
   - Save changes

3. **Product Not in Allowed Categories**
   - Check product's categories
   - Go to Settings → Try-On Button
   - Verify category is selected in "Allowed Categories"
   - Or leave empty to show on all products

4. **Theme Compatibility Issue**
   - Check if theme uses standard WooCommerce hooks
   - Try switching to default WooCommerce theme temporarily
   - Contact support if issue persists

5. **Cache Issue**
   - Clear WordPress cache
   - Clear browser cache
   - Clear CDN cache if applicable
   - Refresh product page

**Diagnostic Steps:**
```
1. Check Settings → Try-On Button → Enable checkbox
2. Verify Virtual Fitting Page is selected
3. Check product categories match allowed categories
4. Clear all caches
5. Test in incognito/private browser window
6. Check browser console for JavaScript errors
```

### Button Redirects to Wrong Page

**Problem:** Button goes to incorrect page

**Solution:**
1. Go to Settings → Try-On Button
2. Verify correct page is selected in dropdown
3. Check page exists and is published
4. Save changes
5. Clear cache and test again

### Product Not Pre-Selected

**Problem:** Product doesn't auto-select on virtual fitting page

**Possible Causes & Solutions:**

1. **JavaScript Error**
   - Open browser console (F12)
   - Look for JavaScript errors
   - Report errors to support

2. **Product Not in Grid**
   - Verify product is published
   - Check product is in WooCommerce catalog
   - Ensure product has images

3. **URL Parameter Missing**
   - Check URL includes `?product_id=123`
   - Verify button URL generation is correct
   - Test button URL directly

**Diagnostic Steps:**
```
1. Click button and check URL in address bar
2. Verify product_id parameter is present
3. Check browser console for errors
4. Verify product exists in virtual fitting grid
5. Test with different product
```

### Button Styling Issues

**Problem:** Button doesn't match theme or looks broken

**Solutions:**

1. **Theme Conflict**
   - Add custom CSS to override theme styles
   - Use theme-specific CSS classes
   - Contact theme developer for compatibility

2. **CSS Not Loading**
   - Check browser console for 404 errors
   - Verify plugin files are uploaded correctly
   - Clear cache and hard refresh (Ctrl+F5)

3. **Mobile Display Issues**
   - Test on actual mobile device
   - Check responsive CSS is loading
   - Verify viewport meta tag is present

**Custom CSS Fix:**
```css
/* Add to Appearance → Customize → Additional CSS */
.ai-virtual-fitting-tryon-button {
    display: inline-block !important;
    width: auto !important;
    margin: 10px 0 !important;
}
```

### Analytics Not Tracking

**Problem:** Button clicks not showing in analytics

**Solutions:**

1. **Analytics Not Enabled**
   - Go to Settings → General
   - Enable analytics tracking
   - Save changes

2. **Database Issue**
   - Check database connection
   - Verify analytics table exists
   - Review error logs

3. **JavaScript Blocked**
   - Check if ad blockers are interfering
   - Verify JavaScript is enabled
   - Test in different browser

### Authentication Issues

**Problem:** Login redirect not working properly

**Solutions:**

1. **Product ID Lost After Login**
   - Check WordPress login URL generation
   - Verify return URL includes product_id
   - Test login flow manually

2. **Infinite Redirect Loop**
   - Check authentication settings
   - Verify user has proper permissions
   - Clear cookies and try again

3. **Login Not Required But Prompting**
   - Go to Settings → General
   - Check "Require Login" setting
   - Ensure setting matches desired behavior

## Best Practices

### Configuration Best Practices

1. **Start Simple**
   - Enable button on all products initially
   - Use default settings
   - Monitor analytics
   - Refine based on data

2. **Category Filtering**
   - Use categories for dress products only
   - Exclude accessories and non-dress items
   - Keep category structure simple
   - Review and update regularly

3. **Button Text**
   - Keep it short and clear
   - Use action-oriented language
   - Match your brand voice
   - A/B test different variations

4. **Authentication**
   - Consider your audience
   - Balance convenience vs. data collection
   - Test both logged-in and guest flows
   - Monitor conversion rates

5. **Analytics**
   - Review metrics weekly
   - Track trends over time
   - Identify popular products
   - Optimize based on data

### User Experience Best Practices

1. **Product Images**
   - Use high-quality product images
   - Ensure consistent image quality
   - Include multiple angles
   - Keep images up-to-date

2. **Product Descriptions**
   - Clear, accurate descriptions
   - Include size and fit information
   - Mention virtual fitting availability
   - Add call-to-action

3. **Mobile Optimization**
   - Test on multiple devices
   - Ensure touch-friendly buttons
   - Optimize page load speed
   - Simplify mobile checkout

4. **Customer Education**
   - Add banner about virtual fitting
   - Include instructions on product pages
   - Create tutorial video
   - Provide photo guidelines

5. **Performance**
   - Optimize images
   - Enable caching
   - Monitor page load times
   - Use CDN if available

### Marketing Best Practices

1. **Promote the Feature**
   - Highlight in product descriptions
   - Add banner on homepage
   - Include in email campaigns
   - Share on social media

2. **Customer Testimonials**
   - Collect feedback
   - Share success stories
   - Display reviews
   - Create case studies

3. **Seasonal Campaigns**
   - Promote during wedding season
   - Create themed collections
   - Offer special promotions
   - Track seasonal performance

4. **Social Proof**
   - Show number of virtual fittings
   - Display popular products
   - Share customer results (with permission)
   - Highlight success rate

## FAQ

### General Questions

**Q: Does the button work on mobile devices?**
A: Yes, the button is fully responsive and works on all devices including smartphones and tablets.

**Q: Can I customize the button appearance?**
A: Yes, you can customize the button text, toggle the icon, and add custom CSS for styling.

**Q: Does the button work with all WooCommerce themes?**
A: The button is designed to work with most WooCommerce themes. It includes compatibility classes for popular themes.

**Q: Can I show the button only on specific products?**
A: Yes, use category filtering or custom code to control which products display the button.

**Q: Does the button affect page load speed?**
A: The button has minimal impact on page load speed. CSS and JavaScript are only loaded on product pages where the button appears.

### Configuration Questions

**Q: How do I change the button text?**
A: Go to Settings → Try-On Button → Button Text field, enter your desired text, and save.

**Q: Can I disable the button temporarily?**
A: Yes, uncheck "Enable Try-On Button" in settings to disable globally without losing your configuration.

**Q: How do I show the button only on wedding dresses?**
A: In settings, select only the "Wedding Dresses" category in the "Allowed Categories" field.

**Q: Can I have different button text for different products?**
A: Yes, use the `ai_virtual_fitting_tryon_button_text` filter in your theme's functions.php file.

**Q: Does the button respect my authentication settings?**
A: Yes, the button uses the existing "Require Login" setting from the main plugin configuration.

### Technical Questions

**Q: What WordPress hooks does the button use?**
A: By default, it uses `woocommerce_after_add_to_cart_button` for display and `wp_enqueue_scripts` for assets.

**Q: Can I move the button to a different position?**
A: Yes, you can remove the default hook and add it to a different WooCommerce hook using custom code.

**Q: Does the button work with page builders?**
A: Yes, the button includes compatibility classes for Elementor, WPBakery, and Beaver Builder.

**Q: Can I track button clicks in Google Analytics?**
A: Yes, use the `ai_virtual_fitting_tryon_button_clicked` action hook to integrate with Google Analytics.

**Q: Is the button accessible for users with disabilities?**
A: Yes, the button is WCAG 2.1 AA compliant with proper ARIA labels, keyboard navigation, and screen reader support.

### Troubleshooting Questions

**Q: Why isn't the button appearing on my product pages?**
A: Check that the button is enabled, a virtual fitting page is selected, and the product is in an allowed category.

**Q: The button appears but doesn't work when clicked. What should I do?**
A: Check browser console for JavaScript errors, verify the virtual fitting page exists, and clear your cache.

**Q: Product isn't pre-selected after clicking the button. How do I fix this?**
A: Verify the URL includes the product_id parameter and check browser console for JavaScript errors.

**Q: Can I get support if I have issues?**
A: Yes, contact plugin support with details about your issue, including WordPress version, theme, and any error messages.

### Analytics Questions

**Q: How do I view button analytics?**
A: Go to AI Virtual Fitting → Analytics → Try-On Button tab to view metrics and charts.

**Q: What metrics are tracked?**
A: Button clicks, conversion rates, popular products, user engagement, and time-based trends.

**Q: Can I export analytics data?**
A: Yes, select a date range and click the Export button to download data in CSV or PDF format.

**Q: How often are analytics updated?**
A: Analytics are updated in real-time as customers interact with the button.

**Q: Can I integrate with third-party analytics tools?**
A: Yes, use the provided action hooks to send data to Google Analytics, Mixpanel, or other tools.

---

## Need More Help?

### Documentation Resources
- [Main Plugin Documentation](../README.md)
- [Admin Guide](ADMIN-GUIDE.md)
- [User Guide](USER-GUIDE.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Developer Documentation](../DEVELOPER.md)

### Support Channels
- **Plugin Support**: Contact the development team
- **WordPress Forums**: Community support
- **WooCommerce Docs**: Integration help
- **Email Support**: support@example.com

### Additional Resources
- Video tutorials (coming soon)
- Webinars and training sessions
- Community forums
- Knowledge base articles

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Plugin Version**: 1.0.7+
