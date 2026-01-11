# Google Cloud Vertex AI Setup Guide

## Overview

The AI Virtual Fitting plugin now supports both Google AI Studio and Google Cloud Vertex AI. This guide shows you how to set up Vertex AI service account credentials following Google Cloud security best practices.

## Why Use Vertex AI vs Google AI Studio?

| Feature | Google AI Studio | Google Cloud Vertex AI |
|---------|------------------|-------------------------|
| **Authentication** | API Key | Service Account (more secure) |
| **Rate Limits** | Lower limits | Higher enterprise limits |
| **Security** | API key in code | Service account with IAM |
| **Cost** | Free tier | Pay-per-use with better pricing |
| **Enterprise Features** | Limited | Full enterprise features |

## Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing project
3. Enable the **Vertex AI API**:
   - Go to APIs & Services > Library
   - Search for "Vertex AI API"
   - Click Enable

## Step 2: Create Service Account

1. Go to **IAM & Admin > Service Accounts**
2. Click **Create Service Account**
3. Fill in details:
   - **Name**: `ai-virtual-fitting-service`
   - **Description**: `Service account for AI Virtual Fitting plugin`
4. Click **Create and Continue**

## Step 3: Assign Permissions

Assign these roles to your service account:
- **Vertex AI User** (`roles/aiplatform.user`)
- **Storage Object Viewer** (`roles/storage.objectViewer`) - if using Cloud Storage

## Step 4: Create and Download Service Account Key

⚠️ **Security Warning**: Service account keys are sensitive credentials. Follow these best practices:

### Creating the Key
1. In Service Accounts, click on your service account
2. Go to **Keys** tab
3. Click **Add Key > Create New Key**
4. Select **JSON** format
5. Click **Create**
6. The JSON file will download automatically

### Security Best Practices
- **Never commit keys to version control**
- **Store keys securely** (encrypted storage, password managers)
- **Rotate keys regularly** (every 90 days recommended)
- **Use least privilege** (only necessary permissions)
- **Monitor key usage** in Cloud Console

## Step 5: Upload to WordPress Plugin

### Method 1: Upload JSON File (Recommended)
1. Go to WordPress Admin > Settings > AI Virtual Fitting
2. Select **Google Cloud Vertex AI** as API provider
3. In "Method 1: Upload JSON File" section:
   - Click **Choose File**
   - Select your downloaded JSON file
   - Click **Upload & Parse JSON**
4. Verify the green checkmark appears
5. Click **Save Changes**

### Method 2: Paste JSON Content
1. Open your downloaded JSON file in a text editor
2. Copy the entire JSON content
3. In WordPress admin, select **Google Cloud Vertex AI**
4. In "Method 2: Paste JSON Content" section:
   - Paste the JSON into the textarea
   - Verify validation shows green checkmark
5. Click **Save Changes**

## Step 6: Test Connection

1. Click **Test Connection** button
2. Verify successful connection to Vertex AI
3. Check that virtual fitting functionality works

## JSON File Structure

Your service account JSON should look like this:

```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "key-id",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "ai-virtual-fitting-service@your-project.iam.gserviceaccount.com",
  "client_id": "client-id",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/..."
}
```

## Security Considerations

### What the Plugin Does
- Validates JSON format and required fields
- Stores credentials encrypted in WordPress database
- Uses credentials only for Vertex AI API calls
- Never exposes credentials in frontend code

### What You Should Do
- **Delete the downloaded JSON file** after uploading to WordPress
- **Don't share credentials** via email, chat, or unsecured channels
- **Monitor usage** in Google Cloud Console
- **Rotate keys regularly** (create new key, update plugin, delete old key)
- **Use environment variables** in production (see advanced setup)

## Advanced: Environment Variables (Production)

For production environments, consider using environment variables instead of storing in database:

1. Set environment variable:
   ```bash
   export GOOGLE_APPLICATION_CREDENTIALS="/path/to/service-account.json"
   ```

2. Or set JSON content directly:
   ```bash
   export VERTEX_AI_CREDENTIALS='{"type":"service_account",...}'
   ```

3. Plugin will automatically detect and use environment variables if available

## Troubleshooting

### Common Issues

**"Invalid JSON format"**
- Ensure you copied the complete JSON content
- Check for missing quotes or brackets
- Validate JSON using online JSON validator

**"Missing required field"**
- Ensure you downloaded the complete service account key
- Re-download the key from Google Cloud Console

**"Authentication failed"**
- Verify service account has Vertex AI User role
- Check that Vertex AI API is enabled in your project
- Ensure project ID matches the one in JSON

**"Permission denied"**
- Add `roles/aiplatform.user` role to service account
- Wait a few minutes for permissions to propagate

### Getting Help

1. Check WordPress error logs
2. Enable plugin logging in settings
3. Verify Google Cloud Console for API usage
4. Test with Google Cloud CLI: `gcloud auth activate-service-account --key-file=key.json`

## Cost Estimation

Vertex AI pricing is pay-per-use:
- **Text generation**: ~$0.001 per 1K characters
- **Image processing**: ~$0.002 per image
- **Monthly costs**: Typically $5-50 for small to medium sites

Monitor usage in Google Cloud Console > Billing.

## Migration from Google AI Studio

If you're currently using Google AI Studio:

1. Set up Vertex AI following this guide
2. Test Vertex AI configuration
3. Switch API provider in plugin settings
4. Remove old Google AI Studio API key
5. Monitor for any issues

Your existing credits and user data will be preserved during the switch.