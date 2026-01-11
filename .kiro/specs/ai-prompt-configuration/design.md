# Design Document

## Overview

This design implements a configurable AI prompt system for the AI Virtual Fitting plugin, allowing administrators to customize the text instruction sent to Google AI Studio's Gemini API instead of using a hardcoded prompt.

## Architecture

### Component Integration

The prompt configuration system integrates with existing components:

1. **Admin Settings Page** - Adds new prompt configuration field
2. **Image Processor** - Retrieves and uses configurable prompt
3. **Database Manager** - Stores prompt settings and history
4. **Core Plugin** - Manages default values and validation

### Data Flow

```
Admin Input → Validation → Database Storage → Image Processor → AI API
     ↓
  Preview/Test → Sample API Call → Result Display
```

## Components and Interfaces

### Admin Settings Enhancement

**New Settings Field:**
```php
// Add to existing settings fields
add_settings_field(
    'ai_prompt_template',
    __('AI Prompt Template', 'ai-virtual-fitting'),
    array($this, 'render_ai_prompt_field'),
    'ai_virtual_fitting_settings',
    'ai_virtual_fitting_advanced_section'
);
```

**Field Properties:**
- Field Type: Textarea (multiline)
- Character Limit: 10-2000 characters
- Default Value: Current hardcoded prompt
- Validation: Required, length check, sanitization
- Help Text: Guidance on writing effective prompts

### Database Schema

**New Option:**
- `ai_virtual_fitting_ai_prompt` - Stores current prompt
- `ai_virtual_fitting_prompt_history` - JSON array of recent changes

**History Entry Format:**
```json
{
  "timestamp": "2026-01-11T01:00:00Z",
  "user_id": 1,
  "user_name": "admin",
  "prompt": "Custom prompt text...",
  "action": "updated"
}
```

### Image Processor Modification

**Current Implementation:**
```php
// Hardcoded prompt
$prompt = "Please create a realistic virtual try-on image...";
```

**New Implementation:**
```php
// Configurable prompt
$prompt = $this->get_ai_prompt_template();
```

**New Method:**
```php
private function get_ai_prompt_template() {
    $custom_prompt = AI_Virtual_Fitting_Core::get_option('ai_prompt_template');
    
    if (!empty($custom_prompt)) {
        return $custom_prompt;
    }
    
    // Fallback to default
    return $this->get_default_prompt();
}
```

## Data Models

### Prompt Configuration Model

```php
class AI_Virtual_Fitting_Prompt_Manager {
    
    /**
     * Get current prompt template
     */
    public function get_prompt_template();
    
    /**
     * Update prompt template with validation
     */
    public function update_prompt_template($prompt, $user_id);
    
    /**
     * Get default prompt
     */
    public function get_default_prompt();
    
    /**
     * Validate prompt content
     */
    public function validate_prompt($prompt);
    
    /**
     * Get prompt history
     */
    public function get_prompt_history($limit = 5);
    
    /**
     * Test prompt with sample data
     */
    public function test_prompt($prompt, $sample_images);
}
```

### Admin Interface Model

```php
class AI_Virtual_Fitting_Admin_Prompt {
    
    /**
     * Render prompt configuration field
     */
    public function render_ai_prompt_field();
    
    /**
     * Handle AJAX prompt preview
     */
    public function handle_prompt_preview();
    
    /**
     * Handle AJAX prompt test
     */
    public function handle_prompt_test();
    
    /**
     * Validate and sanitize prompt input
     */
    public function validate_prompt_input($input);
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Prompt Persistence
*For any* valid prompt saved by an administrator, retrieving the prompt should return the exact same content that was saved.
**Validates: Requirements 1.2, 1.4**

### Property 2: Default Fallback
*For any* system state where no custom prompt is configured, the system should use the predefined default prompt for AI generation.
**Validates: Requirements 3.1, 3.2**

### Property 3: Validation Consistency
*For any* prompt input, the validation rules should consistently accept valid prompts (10-2000 characters, non-empty) and reject invalid ones.
**Validates: Requirements 2.1, 2.2**

### Property 4: History Integrity
*For any* prompt change operation, the system should maintain accurate history records with correct timestamps and user information.
**Validates: Requirements 5.1, 5.4**

### Property 5: Integration Compatibility
*For any* existing plugin setting operation, adding prompt configuration should not interfere with other settings save/load operations.
**Validates: Requirements 6.2, 6.3**

## Error Handling

### Validation Errors
- **Empty Prompt**: Display error, prevent save, suggest using default
- **Too Long/Short**: Show character count, highlight limits
- **Invalid Characters**: Sanitize input, warn about changes
- **Save Failure**: Retry mechanism, fallback to previous value

### Runtime Errors
- **Database Unavailable**: Use cached prompt or default
- **Prompt Retrieval Failure**: Log error, use default prompt
- **API Integration Issues**: Validate prompt format before sending

### User Experience
- **Progressive Enhancement**: Core functionality works without JavaScript
- **Real-time Feedback**: Character counter, validation messages
- **Graceful Degradation**: System continues working with default prompt

## Testing Strategy

### Unit Tests
- Prompt validation logic
- Database save/retrieve operations
- Default prompt fallback behavior
- History tracking accuracy

### Property Tests
- Prompt persistence round-trip testing
- Validation boundary testing with random inputs
- History integrity across multiple operations
- Integration compatibility with existing settings

### Integration Tests
- Admin interface save/load workflow
- Image processor prompt retrieval
- AJAX preview and test functionality
- Database migration and upgrade scenarios

### User Acceptance Tests
- Administrator can successfully customize prompt
- Custom prompts are used in AI generation
- Default prompt works when no customization exists
- Settings integrate seamlessly with existing interface