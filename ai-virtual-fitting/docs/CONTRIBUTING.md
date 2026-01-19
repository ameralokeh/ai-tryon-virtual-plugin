# Contributing to Documentation

Thank you for contributing to the AI Virtual Fitting Plugin documentation!

## Quick Start

1. **Choose a template** from the available templates:
   - `.doc-template.md` - Standard document
   - `.code-example-template.md` - Code examples
   - `.screenshot-template.md` - Screenshot guidelines

2. **Follow the style guide** (`.style-guide.md`)
   - Use active voice and present tense
   - Write clear, concise sentences
   - Use consistent terminology

3. **Use markdown linting** (`.markdownlint.json`)
   - ATX-style headers (`#`)
   - Dash-style lists (`-`)
   - Fenced code blocks

4. **Add screenshots** to appropriate directory:
   - `images/installation/` - Installation screenshots
   - `images/configuration/` - Settings screenshots
   - `images/user-interface/` - UI screenshots
   - `images/workflows/` - Workflow diagrams

## Documentation Workflow

### Creating New Documentation

1. Copy appropriate template
2. Rename to document name
3. Update metadata (title, audience, version, date)
4. Write content following style guide
5. Add code examples and screenshots
6. Test all procedures and code
7. Run markdown linter
8. Submit for review

### Updating Existing Documentation

1. Update version and last_updated date
2. Make necessary changes
3. Update related docs if needed
4. Verify all links work
5. Test modified procedures
6. Submit for review

## Review Process

All documentation goes through:

1. **Technical Review** - Verify accuracy, test procedures
2. **Editorial Review** - Check grammar, formatting, clarity
3. **User Testing** - Test with target audience (when applicable)
4. **Final Approval** - Project lead approval

## Style Guidelines Summary

### Writing Style
- Professional but friendly tone
- Active voice, present tense, second person
- Clear and concise
- Action-oriented

### Formatting
- ATX-style headers (`#`)
- Dash-style lists (`-`)
- Fenced code blocks with language identifiers
- 120 character line length (excluding code)

### Code Examples
- Include comments
- Show usage examples
- Provide expected output
- Test before documenting

### Screenshots
- PNG format for UI
- 1920x1080 minimum resolution
- Descriptive alt text
- Clear captions

## Common Tasks

### Adding a Code Example

```markdown
### Example: Feature Name

**Description:** What this demonstrates.

**Code:**
```php
function example() {
    return true;
}
```

**Usage:**
```php
$result = example();
```
```

### Adding a Screenshot

```markdown
![Admin settings page](images/configuration/admin-settings.png)

**Figure 1:** The admin settings page showing API configuration.
```

### Adding a Procedure

```markdown
### Installing the Plugin

1. Navigate to WordPress admin dashboard
2. Go to Plugins → Add New
3. Click "Upload Plugin"
4. Select the plugin ZIP file
5. Click "Install Now"
6. Click "Activate Plugin"

**Result:** The plugin is now active and ready to configure.
```

### Cross-Referencing

```markdown
For installation instructions, see [Installation Guide](INSTALLATION.md).

See the [Configuration](#configuration) section below.
```

## Quality Checklist

Before submitting:

- [ ] Spell check completed
- [ ] Grammar check completed
- [ ] All links verified
- [ ] All code tested
- [ ] Screenshots current
- [ ] Markdown linting passed
- [ ] Consistent terminology
- [ ] Appropriate audience level
- [ ] Version and date updated

## Getting Help

- Review existing documentation for examples
- Check templates for formatting guidance
- Follow the style guide
- Ask questions in project tracker

## File Naming

### Documents
- Use kebab-case: `user-guide.md`, `api-reference.md`
- Use uppercase for major docs: `README.md`, `FAQ.md`

### Screenshots
- Format: `{component}-{action}-{number}.png`
- Examples: `admin-settings-api.png`, `upload-step-1.png`

### Code Examples
- Include in document, don't create separate files
- Use fenced code blocks with language identifiers

## Markdown Linting

Run markdown linter before submitting:

```bash
# If markdownlint-cli is installed
markdownlint docs/**/*.md

# Or use VS Code extension
# Install: Markdown Lint (David Anson)
```

## Resources

- [Markdown Guide](https://www.markdownguide.org/)
- [WordPress Documentation Standards](https://make.wordpress.org/docs/style-guide/)
- [Style Guide](.style-guide.md)
- [Templates](.doc-template.md)

---

**Last Updated:** 2025-01-15  
**Version:** 1.0.0
