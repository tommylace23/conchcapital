# Check Server .env and Fix API Error

## The Error

You're getting an API error during registration. This is likely because:
1. `.env` file doesn't have `RESEND_API_KEY` set
2. `MAIL_MAILER` is not set to `resend`
3. Resend API key is invalid or missing

---

## Step 1: Check Current .env Settings

SSH into server and check:

```bash
ssh deploy@conchcapital.co
cd /var/www/conchcapital/backend
cat .env | grep -E 'MAIL_|RESEND'
```

**What you should see:**
```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@conchcapital.co
MAIL_FROM_NAME="InvestEdu"
RESEND_API_KEY=re_xxxxxxxxxxxxx
```

**If you see:**
- `MAIL_MAILER=log` → Change to `resend`
- `RESEND_API_KEY=` (empty) → Add your API key
- Missing `RESEND_API_KEY` line → Add it

---

## Step 2: Check Laravel Logs for Exact Error

```bash
cd /var/www/conchcapital/backend
tail -n 50 storage/logs/laravel.log
```

**Look for errors like:**
- "Resend API key is required"
- "Invalid API key"
- "Mailer [resend] is not defined"
- Any Resend-related errors

---

## Step 3: Get Your Resend API Key

1. **Go to:** https://resend.com/api-keys
2. **Find your API key** (starts with `re_`)
3. **If you don't have one:**
   - Click "Create API Key"
   - Name it (e.g., "Laravel Production")
   - Copy the key (you'll only see it once!)

---

## Step 4: Update .env File

```bash
cd /var/www/conchcapital/backend
nano .env
```

### Add or Update These Lines:

```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@conchcapital.co
MAIL_FROM_NAME="InvestEdu"
RESEND_API_KEY=re_your_actual_api_key_here
```

**Important:**
- Replace `re_your_actual_api_key_here` with your actual API key from Resend
- Make sure there are **no spaces** around the `=` sign
- Make sure the API key is on **one line** (no line breaks)

### Save the file:
- Press `Ctrl+X`
- Press `Y` to confirm
- Press `Enter` to save

---

## Step 5: Clear Laravel Cache

**This is CRITICAL - Laravel caches config!**

```bash
cd /var/www/conchcapital/backend
php artisan config:clear
php artisan config:cache
```

**Why:** Laravel caches configuration. If you don't clear cache, it will still use old settings!

---

## Step 6: Test Email Sending

### Option 1: Check Logs Again
```bash
tail -f storage/logs/laravel.log
```
Then try registering again and watch for errors.

### Option 2: Test via Tinker
```bash
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});
```

---

## Common Issues & Fixes

### Issue 1: "Resend API key is required"
**Fix:** Add `RESEND_API_KEY=re_xxxxx` to `.env`

### Issue 2: "Invalid API key"
**Fix:** 
- Check API key is correct (copy from Resend dashboard)
- Make sure no extra spaces or quotes
- Regenerate API key in Resend if needed

### Issue 3: "Mailer [resend] is not defined"
**Fix:**
- Make sure `MAIL_MAILER=resend` (not `log` or `smtp`)
- Run `php artisan config:clear` and `php artisan config:cache`

### Issue 4: Still using old settings
**Fix:**
- **ALWAYS** run `php artisan config:clear` after changing `.env`
- Then run `php artisan config:cache`

---

## Quick Fix Checklist

- [ ] SSH into server
- [ ] Check current `.env` mail settings
- [ ] Get Resend API key from dashboard
- [ ] Update `.env` with correct values
- [ ] **Run `php artisan config:clear`** ⚠️ IMPORTANT
- [ ] **Run `php artisan config:cache`** ⚠️ IMPORTANT
- [ ] Check Laravel logs for errors
- [ ] Try registration again

---

## What to Share

If it still doesn't work, share:
1. Output of: `cat .env | grep -E 'MAIL_|RESEND'`
2. Last 20 lines of: `tail -n 20 storage/logs/laravel.log`
3. Exact error message you see in browser

This will help diagnose the exact issue!
