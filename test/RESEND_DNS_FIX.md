# Resend DNS Records Fix for conchcapital.co

## Current Status
❌ **SPF Record**: Missing  
⚠️ **DKIM Record**: Incomplete (only has public key, missing header)  
❌ **DMARC Record**: Missing

## What You Need to Do

### Step 1: Get Exact Records from Resend Dashboard

1. Go to: https://resend.com/domains
2. Click on `conchcapital.co`
3. Copy the **exact** DNS records Resend shows you

### Step 2: Add/Update DNS Records

#### 1. SPF Record (REQUIRED - Currently Missing)

**Add this TXT record:**
- **Name/Host**: `@` or `conchcapital.co`
- **Type**: `TXT`
- **Value**: `v=spf1 include:resend.com ~all`
  (Or copy the exact value from Resend dashboard)
- **TTL**: 3600

#### 2. DKIM Record (NEEDS FIX - Currently Incomplete)

**Update the existing DKIM record:**
- **Name/Host**: `resend._domainkey.conchcapital.co`
- **Type**: `TXT`
- **Current Value** (incomplete): `p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDJZOVfg8Ox9cx5dxzL5gCvjAoqGJD1RLG4/l+nvw76Kc4xVjMjiBHa/Lj0BjCIDD3HfUWXvGOvy3ykTh1JsB6VSCT467tOL7EBzW0KnjZtkUe6w3g+iNFvAtQlTwdNF+DKWJiLlUjT1hWHihoXYX/Jdo/nDiiAmCuin3tCQiE8lwIDAQAB`

- **Should be** (get exact from Resend):
  ```
  v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDJZOVfg8Ox9cx5dxzL5gCvjAoqGJD1RLG4/l+nvw76Kc4xVjMjiBHa/Lj0BjCIDD3HfUWXvGOvy3ykTh1JsB6VSCT467tOL7EBzW0KnjZtkUe6w3g+iNFvAtQlTwdNF+DKWJiLlUjT1hWHihoXYX/Jdo/nDiiAmCuin3tCQiE8lwIDAQAB
  ```

**⚠️ IMPORTANT**: Copy the EXACT value from Resend dashboard - it must start with `v=DKIM1; k=rsa; p=`

#### 3. DMARC Record (RECOMMENDED - Currently Missing)

**Add this TXT record:**
- **Name/Host**: `_dmarc`
- **Type**: `TXT`
- **Value**: `v=DMARC1; p=none; rua=mailto:dmarc@conchcapital.co`
- **TTL**: 3600

### Step 3: Verify Records After Adding

Wait 15-30 minutes, then run:

```bash
# Check SPF
dig TXT conchcapital.co +short

# Check DKIM
dig TXT resend._domainkey.conchcapital.co +short

# Check DMARC
dig TXT _dmarc.conchcapital.co +short
```

### Step 4: Re-verify in Resend

1. Go to Resend dashboard
2. Click on your domain
3. Click "Verify Domain" or "Re-verify"
4. Wait for verification to complete

## Common Mistakes to Avoid

1. ❌ **Don't add extra spaces** in DNS record values
2. ❌ **Don't wrap values in quotes** (DNS provider usually handles this)
3. ❌ **Don't use wrong selector** - use exactly what Resend shows
4. ❌ **Don't forget the `v=DKIM1; k=rsa;` prefix** for DKIM

## Where to Add Records

**If your domain is at:**
- **Cloudflare**: DNS → Records → Add Record
- **GoDaddy**: DNS Management → Add Record  
- **Namecheap**: Advanced DNS → Add New Record
- **Other**: Look for "DNS Management" or "DNS Settings"

## Quick Checklist

- [ ] Added SPF TXT record at root (`@` or `conchcapital.co`)
- [ ] Fixed DKIM record to include `v=DKIM1; k=rsa;` prefix
- [ ] Added DMARC TXT record at `_dmarc` subdomain
- [ ] Waited 15-30 minutes for DNS propagation
- [ ] Verified records using `dig` commands
- [ ] Re-verified domain in Resend dashboard

## Still Not Working?

If verification still fails after 24 hours:
1. Double-check you copied values EXACTLY from Resend
2. Make sure no extra characters/spaces
3. Check Resend dashboard for any error messages
4. Contact Resend support with screenshots of your DNS records
