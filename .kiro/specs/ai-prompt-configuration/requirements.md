# Requirements Document

## Introduction

This specification defines the requirements for making the AI virtual fitting prompt configurable through the WordPress admin dashboard, replacing the current hardcoded prompt with a user-editable field.

## Glossary

- **AI_Prompt**: The text instruction sent to Google AI Studio's Gemini API for virtual fitting image generation
- **Admin_Dashboard**: WordPress admin interface where plugin settings are configured
- **Default_Prompt**: The fallback prompt used when no custom prompt is configured
- **Virtual_Fitting_System**: The AI-powered system that generates try-on images

## Requirements

### Requirement 1: Admin Prompt Configuration

**User Story:** As a website administrator, I want to customize the AI prompt used for virtual fitting, so that I can control how the AI generates try-on images and optimize results for my specific products.

#### Acceptance Criteria

1. WHEN an administrator accesses the AI Virtual Fitting settings page, THE Admin_Dashboard SHALL display a configurable AI prompt field
2. WHEN an administrator enters a custom prompt, THE Virtual_Fitting_System SHALL use the custom prompt for all AI image generation requests
3. WHEN the prompt field is empty, THE Virtual_Fitting_System SHALL use a sensible default prompt
4. WHEN an administrator saves the settings, THE Admin_Dashboard SHALL validate the prompt is not empty and store it in the database
5. THE Admin_Dashboard SHALL provide helpful guidance text explaining how to write effective AI prompts

### Requirement 2: Prompt Validation and Safety

**User Story:** As a system administrator, I want the AI prompt to be validated for safety and effectiveness, so that the virtual fitting system continues to work reliably.

#### Acceptance Criteria

1. WHEN an administrator enters a prompt, THE Admin_Dashboard SHALL validate the prompt length is between 10 and 2000 characters
2. WHEN an administrator saves an invalid prompt, THE Admin_Dashboard SHALL display an error message and prevent saving
3. THE Admin_Dashboard SHALL sanitize the prompt input to prevent XSS attacks
4. WHEN the prompt contains potentially harmful content, THE Admin_Dashboard SHALL warn the administrator

### Requirement 3: Default Prompt Management

**User Story:** As a plugin developer, I want to provide a high-quality default prompt, so that the system works well out of the box without configuration.

#### Acceptance Criteria

1. WHEN the plugin is first installed, THE Virtual_Fitting_System SHALL use a predefined default prompt
2. WHEN an administrator resets the prompt, THE Admin_Dashboard SHALL restore the original default prompt
3. THE default prompt SHALL be optimized for wedding dress virtual fitting scenarios
4. THE default prompt SHALL include instructions for maintaining natural pose, body proportions, and lighting

### Requirement 4: Prompt Preview and Testing

**User Story:** As a website administrator, I want to preview how my custom prompt will affect AI generation, so that I can optimize the prompt before using it with customers.

#### Acceptance Criteria

1. WHEN an administrator enters a custom prompt, THE Admin_Dashboard SHALL display a preview of the formatted prompt that will be sent to the AI
2. WHEN an administrator clicks a test button, THE Admin_Dashboard SHALL allow testing the prompt with sample images (if API is configured)
3. THE Admin_Dashboard SHALL show the character count and provide guidance on optimal prompt length
4. WHEN testing fails, THE Admin_Dashboard SHALL display helpful error messages and suggestions

### Requirement 5: Prompt History and Backup

**User Story:** As a website administrator, I want to track changes to the AI prompt, so that I can revert to previous versions if needed.

#### Acceptance Criteria

1. WHEN an administrator changes the prompt, THE Virtual_Fitting_System SHALL log the change with timestamp and user information
2. THE Admin_Dashboard SHALL display the last modified date and user for the current prompt
3. WHEN an administrator requests it, THE Admin_Dashboard SHALL show a history of recent prompt changes
4. THE Virtual_Fitting_System SHALL maintain at least the last 5 prompt versions for recovery purposes

### Requirement 6: Integration with Existing Settings

**User Story:** As a website administrator, I want the prompt configuration to integrate seamlessly with existing plugin settings, so that I have a unified configuration experience.

#### Acceptance Criteria

1. THE prompt configuration field SHALL be added to the existing AI Virtual Fitting admin settings page
2. THE prompt field SHALL follow the same styling and layout patterns as other settings fields
3. WHEN an administrator saves settings, THE prompt SHALL be saved along with other plugin options
4. THE prompt field SHALL include the same help tooltip system as other advanced settings