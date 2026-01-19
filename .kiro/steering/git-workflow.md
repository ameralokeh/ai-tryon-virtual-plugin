# Git Workflow Guidelines

## Commit Policy

**CRITICAL: Do not commit changes until explicitly instructed by the user.**

### When to Commit
- Only when the user explicitly says "commit", "commit these changes", or similar direct instruction
- When the user asks to "save to git" or "push changes"
- After completing a feature and user confirms they want it committed

### When NOT to Commit
- After making code changes (even if tests pass)
- After completing a task or feature
- After fixing bugs or issues
- When the user says "looks good" or "that works" (this is NOT a commit instruction)
- Automatically at any point in the workflow

## Git Commands Available

### Checking Status
```bash
# View current changes
git status

# View diff of changes
git diff

# View staged changes
git diff --cached
```

### Staging Changes
```bash
# Stage specific files
git add path/to/file.php

# Stage all changes in a directory
git add ai-virtual-fitting/

# Stage all changes (use with caution)
git add .
```

### Committing (Only When Instructed)
```bash
# Commit with message
git commit -m "Description of changes"

# Commit with detailed message
git commit -m "Title" -m "Detailed description"
```

### Viewing History
```bash
# View commit log
git log --oneline -10

# View specific file history
git log --oneline path/to/file.php
```

## Best Practices

### Before Committing (When Instructed)
1. **Review changes**: Show user what will be committed
2. **Run tests**: Ensure all tests pass
3. **Check for sensitive data**: No API keys, passwords, or credentials
4. **Verify file list**: Confirm all intended files are included
5. **Write clear message**: Descriptive commit message

### Commit Message Format
```
[Component] Brief description

- Detailed change 1
- Detailed change 2
- Detailed change 3
```

**Examples:**
```
[Credit System] Add credit purchase validation

- Validate minimum purchase amount
- Add error handling for failed transactions
- Update credit balance display
```

```
[Image Processor] Improve upload validation

- Add MIME type verification
- Increase max file size to 10MB
- Add dimension validation
```

### What to Include in Commits
- Source code changes
- Test files
- Documentation updates
- Configuration changes (non-sensitive)

### What NOT to Include in Commits
- API keys or credentials
- Temporary files
- Log files
- Database dumps
- Large binary files
- node_modules or vendor directories
- IDE-specific files (unless intentional)

## Workflow Example

### User Request
"Add a new feature to validate credit purchases"

### Agent Actions
1. ✅ Implement the feature
2. ✅ Write tests
3. ✅ Run tests to verify
4. ✅ Update documentation
5. ❌ **DO NOT commit automatically**
6. ✅ Inform user: "Feature implemented and tested. Ready to commit when you're ready."

### User Says "Commit it"
1. ✅ Review changes with `git status`
2. ✅ Stage appropriate files
3. ✅ Create commit with descriptive message
4. ✅ Confirm commit completed

## Common Scenarios

### Scenario 1: Feature Complete
**User**: "Add credit validation"
**Agent**: *implements feature*
**Agent**: "Credit validation implemented and tested. Let me know when you'd like to commit."
**User**: "Looks good"
**Agent**: *does NOT commit* "Great! The changes are ready. Would you like me to commit them?"

### Scenario 2: Multiple Changes
**User**: "Fix the image upload bug and update docs"
**Agent**: *fixes bug and updates docs*
**Agent**: "Bug fixed and documentation updated. Ready to commit when you are."
**User**: "Commit the bug fix only"
**Agent**: *commits only the bug fix files*

### Scenario 3: Explicit Instruction
**User**: "Add logging and commit it"
**Agent**: *adds logging*
**Agent**: *commits the changes*
**Agent**: "Logging added and committed."

## Git Configuration

### Check Current Config
```bash
# View user name and email
git config user.name
git config user.email

# View all config
git config --list
```

### Branch Management
```bash
# View current branch
git branch

# Create new branch
git checkout -b feature/new-feature

# Switch branches
git checkout main
```

## Summary

**Remember: The default action is to NOT commit. Only commit when the user explicitly instructs you to do so.**

This ensures the user maintains full control over their git history and can review changes before they're committed.
