# Resend Domain Verification Guide

## Step 1: Get Your DNS Records from Resend

1. Log into your Resend dashboard: https://resend.com/domains
2. Click on your domain (likely `conchcapital.co`)
3. You'll see the DNS records you need to add:
   - **SPF Record** (TXT record)
   - **DKIM Record** (TXT record with selector, e.g., `resend._domainkey`)
   - **DMARC Record** (TXT record at `_dmarc` subdomain)

## Step 2: Add DNS Records to Your Domain

### Where to Add DNS Records

**If your domain is managed by:**
- **Cloudflare**: DNS → Records → Add Record
- **GoDaddy**: DNS Management → Add Record
- **Namecheap**: Advanced DNS → Add New Record
- **AWS Route 53**: Hosted Zones → Create Record
- **Google Domains**: DNS → Custom Records

### Record Types to Add

#### 1. SPF Record (TXT)
- **Name/Host**: `@` or `conchcapital.co` (root domain)
- **Type**: `TXT`
- **Value**: Copy from Resend (usually something like `v=spf1 include:resend.com ~all`)
- **TTL**: 3600 (or default)

#### 2. DKIM Record (TXT)
- **Name/Host**: `resend._domainkey` (or the selector Resend provides)
- **Type**: `TXT`
- **Value**: Copy the full DKIM public key from Resend (long string starting with `v=DKIM1; k=rsa; p=...`)
- **TTL**: 3600 (or default)

#### 3. DMARC Record (TXT) - Recommended
- **Name/Host**: `_dmarc`
- **Type**: `TXT`
- **Value**: `v=DMARC1; p=none; rua=mailto:dmarc@conchcapital.co`
- **TTL**: 3600 (or default)

## Step 3: Verify DNS Records Are Added

### Check Using Command Line (Terminal)

```bash
# Check SPF record
dig TXT conchcapital.co +short

# Check DKIM record (replace 'resend' with your selector if different)
dig TXT resend._domainkey.conchcapital.co +short

# Check DMARC record
dig TXT _dmarc.conchcapital.co +short
```

### Check Using Online Tools

1. **MXToolbox**: https://mxtoolbox.com/spf.aspx
2. **DNS Checker**: https://dnschecker.org/
3. Enter your domain and check if records are visible globally

## Step 4: Wait for DNS Propagation

- DNS changes can take **15 minutes to 48 hours** to propagate
- Most changes are visible within **1-2 hours**
- Use DNS checker tools to verify propagation globally

## Step 5: Re-verify in Resend

1. Go back to Resend dashboard: https://resend.com/domains
2. Click on your domain
3. Click **"Verify Domain"** or **"Re-verify"**
4. Resend will check all DNS records

## Common Issues & Solutions

### Issue 1: "SPF Record Not Found"
**Solution:**
- Make sure SPF record is at root domain (`@` or `conchcapital.co`)
- Check the record value matches exactly what Resend provided
- Wait for DNS propagation (can take up to 48 hours)

### Issue 2: "DKIM Record Not Found"
**Solution:**
- Verify the selector name is correct (e.g., `resend._domainkey`)
- Make sure the full DKIM public key is copied (it's very long)
- Check there are no extra spaces or line breaks in the value

### Issue 3: "Verification Failed"
**Solution:**
- Double-check all record values match Resend exactly
- Ensure TTL is set (not 0)
- Wait longer for DNS propagation
- Try using a different DNS checker to verify records are live

### Issue 4: "Domain Already Verified" but Still Not Working
**Solution:**
- Check your Laravel `.env` file has:
  ```
  MAIL_MAILER=resend
  RESEND_API_KEY=re_xxxxxxxxxxxxx
  MAIL_FROM_ADDRESS=noreply@conchcapital.co
  MAIL_FROM_NAME="InvestEdu"
  ```
- Clear Laravel config cache:
  ```bash
  php artisan config:clear
  php artisan config:cache
  ```

## Quick Checklist

- [ ] Added SPF TXT record at root domain
- [ ] Added DKIM TXT record with correct selector
- [ ] Added DMARC TXT record (recommended)
- [ ] Waited at least 15 minutes for DNS propagation
- [ ] Verified records using `dig` or online tools
- [ ] Clicked "Verify" in Resend dashboard
- [ ] Updated Laravel `.env` with correct mail settings
- [ ] Cleared Laravel config cache

## Need Help?

If verification still fails after 48 hours:
1. Take a screenshot of your DNS records from your domain provider
2. Take a screenshot of what Resend is asking for
3. Compare them side-by-side to find discrepancies
4. Check Resend's status page: https://status.resend.com/
