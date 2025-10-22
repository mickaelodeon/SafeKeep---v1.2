# Railway Environment Variables Setup Script (PowerShell)
# Run this after installing Railway CLI: npm i -g @railway/cli

Write-Host "🚀 SafeKeep Railway Environment Setup" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Check if Railway CLI is installed
if (!(Get-Command railway -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Railway CLI not found!" -ForegroundColor Red
    Write-Host "Install it first: npm i -g @railway/cli" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Railway CLI found" -ForegroundColor Green
Write-Host ""

# Login instructions
Write-Host "📝 Setup Steps:" -ForegroundColor Yellow
Write-Host "1. Login to Railway: railway login" -ForegroundColor White
Write-Host "2. Link your project: railway link" -ForegroundColor White
Write-Host ""
$continue = Read-Host "Press Enter when ready to continue (or Ctrl+C to cancel)"

Write-Host ""
Write-Host "🔧 Setting environment variables..." -ForegroundColor Cyan
Write-Host ""

# Application Configuration
Write-Host "Setting APP_NAME..." -ForegroundColor Gray
railway variables set APP_NAME="SafeKeep"

Write-Host "Setting APP_ENV..." -ForegroundColor Gray
railway variables set APP_ENV="production"

Write-Host "Setting APP_DEBUG..." -ForegroundColor Gray
railway variables set APP_DEBUG="false"

Write-Host "Setting APP_URL..." -ForegroundColor Gray
railway variables set APP_URL="https://safekeep-v12-production.up.railway.app"

# SendGrid Email Configuration
Write-Host "Setting MAIL_ENABLED..." -ForegroundColor Gray
railway variables set MAIL_ENABLED="true"

Write-Host "Setting MAIL_HOST..." -ForegroundColor Gray
railway variables set MAIL_HOST="smtp.sendgrid.net"

Write-Host "Setting MAIL_PORT..." -ForegroundColor Gray
railway variables set MAIL_PORT="587"

Write-Host "Setting MAIL_ENCRYPTION..." -ForegroundColor Gray
railway variables set MAIL_ENCRYPTION="tls"

Write-Host "Setting MAIL_USERNAME..." -ForegroundColor Gray
railway variables set MAIL_USERNAME="apikey"

Write-Host "Setting MAIL_PASSWORD..." -ForegroundColor Gray
$sendgridKey = Read-Host "Enter your SendGrid API Key (starts with SG.)"
railway variables set MAIL_PASSWORD="$sendgridKey"

Write-Host "Setting MAIL_FROM_EMAIL..." -ForegroundColor Gray
railway variables set MAIL_FROM_EMAIL="johnmichaeleborda79@gmail.com"

Write-Host "Setting MAIL_FROM_NAME..." -ForegroundColor Gray
railway variables set MAIL_FROM_NAME="SafeKeep - Lost & Found"

Write-Host "Setting MAIL_REPLY_TO..." -ForegroundColor Gray
railway variables set MAIL_REPLY_TO="johnmichaeleborda79@gmail.com"

# Security Configuration
Write-Host "Setting CSRF_SECRET..." -ForegroundColor Gray
railway variables set CSRF_SECRET="safekeep-production-secret-2025-secure-key"

Write-Host "Setting SESSION_NAME..." -ForegroundColor Gray
railway variables set SESSION_NAME="safekeep_session"

Write-Host "Setting SESSION_LIFETIME..." -ForegroundColor Gray
railway variables set SESSION_LIFETIME="3600"

# School Configuration
Write-Host "Setting ALLOWED_EMAIL_DOMAINS..." -ForegroundColor Gray
railway variables set ALLOWED_EMAIL_DOMAINS="@school.edu,@gmail.com"

Write-Host "Setting AUTO_APPROVE_USERS..." -ForegroundColor Gray
railway variables set AUTO_APPROVE_USERS="false"

# File Upload Configuration
Write-Host "Setting MAX_FILE_SIZE..." -ForegroundColor Gray
railway variables set MAX_FILE_SIZE="5242880"

Write-Host "Setting ALLOWED_EXTENSIONS..." -ForegroundColor Gray
railway variables set ALLOWED_EXTENSIONS="jpg,jpeg,png,gif,webp"

# Rate Limiting
Write-Host "Setting CONTACT_RATE_LIMIT..." -ForegroundColor Gray
railway variables set CONTACT_RATE_LIMIT="5"

Write-Host "Setting CONTACT_RATE_WINDOW..." -ForegroundColor Gray
railway variables set CONTACT_RATE_WINDOW="3600"

Write-Host ""
Write-Host "✅ All variables set!" -ForegroundColor Green
Write-Host ""

# Ask about deployment
$deploy = Read-Host "Would you like to trigger a deployment now? (y/n)"
if ($deploy -eq "y" -or $deploy -eq "Y") {
    Write-Host "🚀 Triggering deployment..." -ForegroundColor Cyan
    railway up
}

Write-Host ""
Write-Host "✅ Setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Yellow
Write-Host "1. Wait for deployment to complete (3-5 minutes)" -ForegroundColor White
Write-Host "2. Test email: https://safekeep-v12-production.up.railway.app/test-email-quick.php" -ForegroundColor White
Write-Host "3. Test contact form on any post" -ForegroundColor White
Write-Host "4. Check deployment logs in Railway dashboard" -ForegroundColor White
Write-Host ""
