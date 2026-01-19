# Public URL Tunneling for Local Development

## Quick Reference: Expose localhost:8080 to the internet

### Option 1: Cloudflare Tunnel (Recommended - No Account Required)

```bash
# Install
brew install cloudflare/cloudflare/cloudflared

# Start tunnel
cloudflared tunnel --url http://localhost:8080

# Update WordPress URLs
docker exec wordpress_site wp option update home 'https://YOUR_CF_URL' --allow-root
docker exec wordpress_site wp option update siteurl 'https://YOUR_CF_URL' --allow-root
```

**Pros:** Free, no account, reliable, fast  
**Cons:** URL changes each time

### Option 2: localtunnel

```bash
# Install
npm install -g localtunnel

# Start tunnel
lt --port 8080

# Update WordPress URLs
docker exec wordpress_site wp option update home 'https://YOUR_LT_URL' --allow-root
docker exec wordpress_site wp option update siteurl 'https://YOUR_LT_URL' --allow-root
```

**Pros:** Simple, no account  
**Cons:** Less reliable, may show warning page

### Option 3: Serveo (SSH-based)

```bash
# No installation needed - uses SSH
ssh -R 80:localhost:8080 serveo.net

# Update WordPress URLs with provided URL
docker exec wordpress_site wp option update home 'https://YOUR_SERVEO_URL' --allow-root
docker exec wordpress_site wp option update siteurl 'https://YOUR_SERVEO_URL' --allow-root
```

**Pros:** No installation, no account  
**Cons:** Requires SSH, less stable

### Option 4: Tailscale Funnel (For Team Access)

```bash
# Install
brew install tailscale

# Setup and start funnel
tailscale funnel 8080

# Share URL with team
```

**Pros:** Secure, great for team sharing  
**Cons:** Requires account, team setup

## Restore Local URLs

```bash
docker exec wordpress_site wp option update home 'http://localhost:8080' --allow-root
docker exec wordpress_site wp option update siteurl 'http://localhost:8080' --allow-root
```

## Stripe Webhook URL Format

Once tunnel is running, configure Stripe webhook:
```
https://YOUR_PUBLIC_URL/wc-api/wc_stripe
```

## Use Cases

- **Stripe Payment Testing:** Requires public URL for webhooks
- **Mobile Device Testing:** Access from phone/tablet
- **Client Demos:** Share with stakeholders
- **3D Secure Testing:** Requires public HTTPS URL
