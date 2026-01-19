# Production FTP Deployment

## CRITICAL RULES

**⚠️ ONLY modify files within:** `/bridesandtailor.com/public_html/wp-content/plugins/ai-virtual-fitting/`

**NEVER touch:** WordPress core, other plugins, themes, wp-config.php, .htaccess, or any files outside the plugin directory.

## FTP Connection

### Credentials
- **Host:** `ftp.bridesandtailor.com`
- **Username:** `aalokeh@bridesandtailor.com`
- **Password:** (prompt securely, NEVER hardcode)
- **Port:** `21`
- **Protocol:** FTP with SSL

### Connection Command
```bash
lftp -u aalokeh@bridesandtailor.com -p 21 ftp.bridesandtailor.com
```

## Plugin Path
```
/bridesandtailor.com/public_html/wp-content/plugins/ai-virtual-fitting/
```

## Deployment Rules

### When User Requests Plugin Update:
1. **Backup first** - Download current production files
2. **Upload only plugin files** - Exclude tests/, *.sh, *.md (except README.md), .git/
3. **Verify upload** - List files after upload
4. **Never deploy without approval** - User must explicitly request deployment

### Allowed Operations:
- ✅ Upload/update files in plugin directory
- ✅ Create subdirectories within plugin folder
- ✅ Download files for backup

### Forbidden Operations:
- ❌ Modify files outside plugin directory
- ❌ Delete entire plugin directory
- ❌ Upload sensitive files (credentials, test files)
- ❌ Change file permissions
- ❌ Access other plugins or WordPress core

## Quick Commands

```bash
# Test connection
./test-siteground-ftp.sh

# List plugin files
./list-plugins.sh
```

## Production Site
- **URL:** https://bridesandtailor.com
- **Admin:** https://bridesandtailor.com/wp-admin
