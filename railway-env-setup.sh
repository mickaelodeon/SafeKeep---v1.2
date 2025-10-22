#!/bin/bash
# Railway Environment Variables Setup Script
# Run this after installing Railway CLI: npm i -g @railway/cli

echo "🚀 SafeKeep Railway Environment Setup"
echo "======================================"
echo ""

# Check if Railway CLI is installed
if ! command -v railway &> /dev/null
then
    echo "❌ Railway CLI not found!"
    echo "Install it first: npm i -g @railway/cli"
    exit 1
fi

echo "✅ Railway CLI found"
echo ""

# Login check
echo "📝 Make sure you're logged in to Railway..."
echo "Run: railway login"
echo ""
read -p "Press Enter when you're logged in and have linked your project..."

echo ""
echo "🔧 Setting environment variables..."
echo ""

# Application Configuration
railway variables set APP_NAME="SafeKeep"
railway variables set APP_ENV="production"
railway variables set APP_DEBUG="false"
railway variables set APP_URL="https://safekeep-v12-production.up.railway.app"

# SendGrid Email Configuration
railway variables set MAIL_ENABLED="true"
railway variables set MAIL_HOST="smtp.sendgrid.net"
railway variables set MAIL_PORT="587"
railway variables set MAIL_ENCRYPTION="tls"
railway variables set MAIL_USERNAME="apikey"

# Get SendGrid API key securely
echo "Enter your SendGrid API Key (starts with SG.):"
read -s SENDGRID_KEY
railway variables set MAIL_PASSWORD="$SENDGRID_KEY"

railway variables set MAIL_FROM_EMAIL="johnmichaeleborda79@gmail.com"
railway variables set MAIL_FROM_NAME="SafeKeep - Lost & Found"
railway variables set MAIL_REPLY_TO="johnmichaeleborda79@gmail.com"

# Security Configuration
railway variables set CSRF_SECRET="safekeep-production-secret-2025-secure-key"
railway variables set SESSION_NAME="safekeep_session"
railway variables set SESSION_LIFETIME="3600"

# School Configuration
railway variables set ALLOWED_EMAIL_DOMAINS="@school.edu,@gmail.com"
railway variables set AUTO_APPROVE_USERS="false"

# File Upload Configuration
railway variables set MAX_FILE_SIZE="5242880"
railway variables set ALLOWED_EXTENSIONS="jpg,jpeg,png,gif,webp"

# Rate Limiting
railway variables set CONTACT_RATE_LIMIT="5"
railway variables set CONTACT_RATE_WINDOW="3600"

echo ""
echo "✅ All variables set!"
echo ""
echo "🚀 Triggering deployment..."
railway up

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Wait for deployment to complete (3-5 minutes)"
echo "2. Test email: https://safekeep-v12-production.up.railway.app/test-email-quick.php"
echo "3. Test contact form on any post"
echo ""
