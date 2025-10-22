# Railway Environment Variables Setup Guide

## 🚀 How to Configure Environment Variables on Railway

### Step 1: Access Railway Dashboard
1. Go to https://railway.app
2. Login to your account
3. Select your **SafeKeep** project
4. Click on your **web service** (SafeKeep deployment)
5. Go to the **Variables** tab

---

## 📋 Required Environment Variables

### **Option A: Using SendGrid (RECOMMENDED for Production)**

SendGrid is more reliable on Railway and has better deliverability.

#### Email Configuration (SendGrid SMTP)
```
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=apikey
MAIL_PASSWORD=<YOUR_SENDGRID_API_KEY_HERE>
MAIL_FROM_EMAIL=johnmichaeleborda79@gmail.com
MAIL_FROM_NAME=SafeKeep - Lost & Found
MAIL_REPLY_TO=johnmichaeleborda79@gmail.com
MAIL_ENABLED=true
```

**📌 Note:** 
- Username is literally the word `apikey` (not your SendGrid username)
- Password is your SendGrid API key (starts with `SG.`)
- Replace `<YOUR_SENDGRID_API_KEY_HERE>` with your actual SendGrid API key

---

### **Option B: Using Gmail SMTP (Backup)**

If you prefer Gmail (less reliable on cloud platforms):

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=johnmichaeleborda79@gmail.com
MAIL_PASSWORD=gyws dssq prnh lkna
MAIL_FROM_EMAIL=johnmichaeleborda79@gmail.com
MAIL_FROM_NAME=SafeKeep - Lost & Found
MAIL_REPLY_TO=johnmichaeleborda79@gmail.com
MAIL_ENABLED=true
```

**⚠️ Warning:** Gmail SMTP often gets blocked on cloud platforms like Railway.

---

### Application Configuration

```
APP_NAME=SafeKeep
APP_ENV=production
APP_DEBUG=false
APP_URL=https://safekeep-v12-production.up.railway.app
```

---

### Security Configuration

```
CSRF_SECRET=safekeep-production-secret-2025-secure-key
SESSION_NAME=safekeep_session
SESSION_LIFETIME=3600
```

---

### School Configuration

```
ALLOWED_EMAIL_DOMAINS=@school.edu,@gmail.com
AUTO_APPROVE_USERS=false
```

---

### File Upload Configuration

```
MAX_FILE_SIZE=5242880
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp
```

---

### Rate Limiting

```
CONTACT_RATE_LIMIT=5
CONTACT_RATE_WINDOW=3600
```

---

## ✅ Step-by-Step: Adding Variables to Railway

### Method 1: Via Railway Dashboard UI

1. **Open Variables Tab**
   - Railway Dashboard → Your Service → Variables

2. **Click "New Variable"**

3. **Add each variable one by one:**
   - Variable Name: `MAIL_HOST`
   - Value: `smtp.sendgrid.net`
   - Click "Add"

4. **Repeat for all variables above**

5. **Click "Deploy"** (top right) to apply changes

---

### Method 2: Bulk Add via Railway CLI

```bash
# Install Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link to your project
railway link

# Set variables
railway variables set MAIL_HOST=smtp.sendgrid.net
railway variables set MAIL_PORT=587
railway variables set MAIL_USERNAME=apikey
railway variables set MAIL_PASSWORD="<YOUR_SENDGRID_API_KEY_HERE>"
railway variables set MAIL_FROM_EMAIL=johnmichaeleborda79@gmail.com
railway variables set MAIL_FROM_NAME="SafeKeep - Lost & Found"
railway variables set MAIL_ENABLED=true
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false

# Redeploy
railway up
```

---

## 🔍 Verify Configuration

After setting variables:

1. **Check Deployment Logs**
   - Railway Dashboard → Deployments → View Logs
   - Look for successful build

2. **Test Email Configuration**
   ```
   https://safekeep-v12-production.up.railway.app/test-email-quick.php
   ```

3. **Test Contact Form**
   - Go to any post
   - Click "Contact Owner"
   - Send a test message
   - Should complete in <15 seconds

---

## 📧 SendGrid API Key Setup

If you don't have a SendGrid API key yet:

1. **Sign up for SendGrid**
   - Go to https://sendgrid.com
   - Create free account (100 emails/day)

2. **Create API Key**
   - SendGrid Dashboard → Settings → API Keys
   - Click "Create API Key"
   - Name: `SafeKeep Production`
   - Permissions: "Full Access" or "Mail Send"
   - Copy the API key (starts with `SG.`)

3. **Verify Sender Email**
   - Settings → Sender Authentication
   - Verify your email: `johnmichaeleborda79@gmail.com`
   - Check your Gmail for verification link

4. **Add to Railway**
   - Copy the API key
   - Add as `MAIL_PASSWORD` variable in Railway

---

## ⚡ Quick Copy-Paste for Railway

**SendGrid Configuration (Recommended):**
```
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=apikey
MAIL_PASSWORD=<YOUR_SENDGRID_API_KEY_HERE>
MAIL_FROM_EMAIL=johnmichaeleborda79@gmail.com
MAIL_FROM_NAME=SafeKeep - Lost & Found
MAIL_ENABLED=true
APP_ENV=production
APP_DEBUG=false
ALLOWED_EMAIL_DOMAINS=@school.edu,@gmail.com
```

---

## 🐛 Troubleshooting

### Email not sending?

1. **Check Railway Logs:**
   ```
   Railway Dashboard → Deployments → Logs
   ```
   Look for "Email error" or "PHPMailer" messages

2. **Verify SendGrid API key is valid:**
   - Login to SendGrid dashboard
   - Check API key status

3. **Test email manually:**
   ```
   https://your-app.up.railway.app/test-sendgrid.php
   ```

4. **Check sender email is verified in SendGrid**

### Still timing out?

- The new timeout fixes should prevent this
- Check that deployment completed successfully
- Try refreshing Railway variables (delete and re-add)

---

## 📝 Notes

- **Database variables** are auto-injected by Railway MySQL service
- **PORT variable** is auto-injected by Railway
- **APP_URL** can use `${RAILWAY_PUBLIC_DOMAIN}` if set, or hardcode it
- Changes to variables trigger automatic redeployment
- Check logs after deployment to verify configuration loaded

---

## ✅ Verification Checklist

- [ ] All MAIL_* variables set in Railway
- [ ] APP_ENV=production
- [ ] MAIL_ENABLED=true
- [ ] SendGrid API key valid and verified
- [ ] Sender email verified in SendGrid
- [ ] Deployment completed successfully
- [ ] Test page shows correct configuration
- [ ] Contact form works without timeout

---

**Last Updated:** October 22, 2025  
**Railway Project:** SafeKeep v1.2  
**Domain:** https://safekeep-v12-production.up.railway.app
