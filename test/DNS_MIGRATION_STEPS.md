# DNS Migration Steps: Hostwinds → Namesilo

## ⚠️ IMPORTANT: Do NOT Delete Records Yet!

**Why?** If you delete records from Hostwinds before adding them to Namesilo, you'll have a gap where DNS records don't exist anywhere, causing:
- ❌ Website downtime
- ❌ Email failures
- ❌ Service interruptions

---

## ✅ Correct Migration Order

### Step 1: Change Nameservers FIRST
1. Log into **Namesilo**
2. Change nameservers to Namesilo defaults
3. **DO NOT delete anything from Hostwinds yet**

### Step 2: Add ALL DNS Records in Namesilo
1. Add existing records (A records for website)
2. Add Resend records (MX, SPF, DKIM, DMARC)
3. **DO NOT delete from Hostwinds yet**

### Step 3: Wait for Propagation (24-48 hours)
- Nameservers need time to propagate globally
- During this time, some users might still hit Hostwinds DNS
- That's why you keep records in both places temporarily

### Step 4: Verify Everything Works
- Check website loads
- Check Resend verification passes
- Test email sending

### Step 5: THEN You Can Delete from Hostwinds (Optional)
- After 48+ hours and everything is verified
- Hostwinds records are no longer being used
- You can delete them for cleanup (optional - doesn't hurt to leave them)

---

## 📋 Step-by-Step Checklist

### Phase 1: Preparation (NOW)
- [ ] Document all current DNS records from Hostwinds
- [ ] Get exact Resend DNS record values from Resend dashboard
- [ ] **DO NOT delete anything from Hostwinds**

### Phase 2: Change Nameservers
- [ ] Log into Namesilo
- [ ] Change nameservers to Namesilo defaults
- [ ] Save changes
- [ ] **Keep all records in Hostwinds for now**

### Phase 3: Add Records in Namesilo
- [ ] Add A record: `conchcapital.co` → `23.254.129.29`
- [ ] Add A record: `www` → `23.254.129.29`
- [ ] Add DKIM TXT: `resend._domainkey` (copy from Resend)
- [ ] Add SPF TXT: `send` (copy from Resend)
- [ ] Add MX (sending): `send` (copy from Resend)
- [ ] Add MX (receiving): `@` (copy from Resend)
- [ ] Add DMARC TXT: `_dmarc` (copy from Resend)

### Phase 4: Wait & Verify
- [ ] Wait 24-48 hours for nameserver propagation
- [ ] Verify website still works
- [ ] Verify Resend domain verification passes
- [ ] Test email functionality

### Phase 5: Cleanup (Optional, After 48+ Hours)
- [ ] Everything verified and working
- [ ] **NOW you can optionally delete records from Hostwinds**
- [ ] Or just leave them (they're not being used anyway)

---

## 🎯 Key Points

1. **Nameservers determine which DNS records are used**
   - When nameservers point to Namesilo → Namesilo DNS records are used
   - When nameservers point to Hostwinds → Hostwinds DNS records are used

2. **During transition period:**
   - Some DNS servers globally still point to Hostwinds
   - Some DNS servers globally point to Namesilo
   - Having records in both places prevents downtime

3. **After full propagation:**
   - All DNS servers globally point to Namesilo
   - Hostwinds records are ignored (but harmless to leave)

---

## ❌ What NOT to Do

- ❌ **Don't delete Hostwinds records before changing nameservers**
- ❌ **Don't delete Hostwinds records before adding to Namesilo**
- ❌ **Don't delete Hostwinds records during propagation period**

## ✅ What TO Do

- ✅ **Change nameservers first**
- ✅ **Add all records in Namesilo**
- ✅ **Wait for propagation**
- ✅ **Verify everything works**
- ✅ **Then optionally delete from Hostwinds (or leave them)**

---

## Quick Answer

**NO - Don't delete from Hostwinds yet!**

**Correct order:**
1. Change nameservers to Namesilo
2. Add all records in Namesilo
3. Wait 24-48 hours
4. Verify everything works
5. **THEN** optionally delete from Hostwinds (or just leave them)

**The Hostwinds records become irrelevant once nameservers change, but keeping them during transition prevents any downtime.**
