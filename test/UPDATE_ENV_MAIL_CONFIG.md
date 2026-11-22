# Update .env File for Resend Email Configuration

## Step 1: Access Server and Check Current .env

### Option A: SSH into Server
```bash
ssh deploy@conchcapital.co
# Enter password when prompted
```

### Option B: Use Server Control Panel
- Log into Hostwinds control panel
- Use File Manager or Terminal
- Navigate to: `/var/www/conchcapital/backend/`

### Step 2: View Current Mail Settings
```bash
cd /var/www/conchcapital/backend
cat .env | grep -E 'MAIL_|RESEND'
```

---

## Step 3: Update .env File

### Required Settings for Resend

Add or update these lines in your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@conchcapital.co
MAIL_FROM_NAME="InvestEdu"

# Resend API Key
RESEND_API_KEY=re_xxxxxxxxxxxxx
```

### How to Edit

**Option 1: Using nano (recommended)**
```bash
cd /var/www/conchcapital/backend
nano .env
# Make changes, then press Ctrl+X, then Y, then Enter to save
```

**Option 2: Using vi**
```bash
cd /var/www/conchcapital/backend
vi .env
# Press 'i' to enter insert mode
# Make changes
# Press Esc, then type ':wq' and Enter to save
```

**Option 3: Using File Manager**
- Use Hostwinds File Manager
- Navigate to `/var/www/conchcapital/backend/`
- Edit `.env` file directly

---

## Step 4: Get Your Resend API Key

1. **Log into Resend:** https://resend.com/api-keys
2. **Find your API key** (starts with `re_`)
3. **Copy it** and paste into `.env` file

**If you don't have an API key:**
1. Go to Resend dashboard
2. Click "API Keys"
3. Click "Create API Key"
4. Give it a name (e.g., "Laravel Production")
5. Copy the key (you'll only see it once!)

---

## Step 5: Complete .env Mail Section

Here's what your mail section should look like:

```env
# Mail Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@conchcapital.co
MAIL_FROM_NAME="InvestEdu"

# Resend API Key (get from https://resend.com/api-keys)
RESEND_API_KEY=re_your_actual_api_key_here

# Optional: Other mail settings (can leave as default)
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

---

## Step 6: Clear Laravel Config Cache

After updating `.env`, run these commands:

```bash
cd /var/www/conchcapital/backend
php artisan config:clear
php artisan config:cache
```

This ensures Laravel reads the new `.env` values.

---

## Step 7: Test Email Sending

### Option 1: Test via Laravel Tinker
```bash
cd /var/www/conchcapital/backend
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});
```

### Option 2: Test via User Registration
- Try creating a new user account
- Check if verification email is sent

---

## Current Settings to Check

When you access the `.env` file, check for these lines:

### What to Look For:
- `MAIL_MAILER=` - Should be `resend`
- `MAIL_FROM_ADDRESS=` - Should be `noreply@conchcapital.co` (or your verified domain email)
- `MAIL_FROM_NAME=` - Should be `InvestEdu` or your app name
- `RESEND_API_KEY=` - Should have your Resend API key

### What to Update:
- If `MAIL_MAILER` is `log` or `smtp` → Change to `resend`
- If `MAIL_FROM_ADDRESS` is wrong → Update to `noreply@conchcapital.co`
- If `RESEND_API_KEY` is missing or wrong → Add/update with your API key

---

## Important Notes

1. **Email Address:** Use `noreply@conchcapital.co` (or any email at your verified domain)
2. **API Key:** Keep it secret! Never commit `.env` to git
3. **Domain:** Must match your verified domain in Resend (`conchcapital.co`)
4. **Cache:** Always clear config cache after changing `.env`

---

## Quick Checklist

- [ ] SSH into server or access via control panel
- [ ] Check current `.env` mail settings
- [ ] Get Resend API key from dashboard
- [ ] Update `.env` with correct values
- [ ] Save `.env` file
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan config:cache`
- [ ] Test email sending

---

## Need Help?

If you see errors or need to troubleshoot:
1. Check Resend dashboard for API key
2. Verify domain is verified in Resend
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Verify `.env` file syntax (no extra spaces, correct quotes)
