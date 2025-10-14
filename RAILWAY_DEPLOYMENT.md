# SafeKeep Railway Deployment

This project is configured for deployment on Railway.app with the following setup:

## Deployment Files

- `railway.toml` - Railway service configuration
- `.env.production` - Production environment variables template
- `railway-setup.php` - Database initialization script
- `package.json` - Project metadata and scripts

## Environment Variables Required

Set these in your Railway dashboard:

### Database (Auto-injected by Railway MySQL)
- `DATABASE_HOST`
- `DATABASE_PORT` 
- `DATABASE_NAME`
- `DATABASE_USER`
- `DATABASE_PASSWORD`

### Email Configuration
- `GMAIL_APP_PASSWORD` - Your Gmail app-specific password

### Application
- `PORT` - Auto-injected by Railway
- `RAILWAY_PUBLIC_DOMAIN` - Auto-injected by Railway

## Deployment Steps

1. **Connect Repository**: Link your GitHub repository to Railway
2. **Add MySQL Database**: Add Railway MySQL service to your project
3. **Set Environment Variables**: Configure Gmail app password in Railway dashboard
4. **Deploy**: Railway will automatically build and deploy your application

## Database Setup

The `railway-setup.php` script will automatically:
- Create all required tables (users, categories, posts, contact_logs)
- Insert default categories
- Create admin user: `admin@safekeep.com` / `admin123`
- Create demo user: `demo@student.edu` / `demo123`

## Post-Deployment

After successful deployment:
1. Visit your Railway app URL
2. Access `/railway-setup.php` to initialize the database
3. Login with admin credentials to verify functionality
4. Test email functionality with forgot password feature

## Features Included

- ✅ User Authentication & Registration
- ✅ Lost & Found Post Management
- ✅ Email Notifications (Gmail SMTP)
- ✅ Contact Form with Email Alerts
- ✅ Password Reset via Email
- ✅ Admin Dashboard
- ✅ Responsive Bootstrap UI
- ✅ File Upload for Item Images
- ✅ CSRF Protection
- ✅ Audit Logging

## Technical Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0
- **Email**: PHPMailer with Gmail SMTP
- **Frontend**: Bootstrap 5, jQuery
- **Security**: CSRF tokens, password hashing
- **Hosting**: Railway.app with Nixpacks