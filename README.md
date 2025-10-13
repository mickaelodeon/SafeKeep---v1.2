# SafeKeep - School Lost & Found System

> A modern, secure web application for managing lost and found items in educational institutions.

![SafeKeep Logo](assets/images/logo.png)

## 🎯 Overview

SafeKeep is a comprehensive web-based lost and found system designed specifically for schools and educational institutions. It provides a secure platform where students can report lost items, post found items, and safely connect with each other to recover belongings.

### ✨ Key Features

- **🔐 Secure Authentication** - School email domain validation and admin approval workflow
- **📱 Responsive Design** - Mobile-first Bootstrap 5 interface
- **🔍 Advanced Search** - Full-text search with category and date filtering
- **📊 Admin Dashboard** - Complete administrative control panel
- **📧 Contact System** - Secure messaging without exposing personal information
- **🖼️ Image Upload** - Photo support with security validation
- **📢 Announcements** - System-wide notifications and updates
- **🔒 Security First** - CSRF protection, rate limiting, and audit logging

## 🚀 Quick Start

### Prerequisites

- **PHP 8.0+** with extensions: `pdo`, `pdo_mysql`, `gd`, `fileinfo`
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache** web server with mod_rewrite
- **Composer** for dependency management

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/safekeep.git
   cd safekeep
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   copy .env.example .env
   # Edit .env file with your configuration
   ```

4. **Set up database**
   ```bash
   # Create database in MySQL/MariaDB
   mysql -u root -p -e "CREATE DATABASE safekeep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Import schema
   mysql -u root -p safekeep_db < migrations/001_create_tables.sql
   
   # Import sample data (optional)
   mysql -u root -p safekeep_db < migrations/002_sample_data.sql
   ```

5. **Configure web server**
   - Point document root to project directory
   - Ensure `.htaccess` is enabled
   - Set appropriate permissions for `uploads/` directory

6. **Access the application**
   - Open browser to `http://localhost/safekeep-v2`
   - Register with a school email address
   - Use demo accounts (see Development section)

## ⚙️ Configuration

### Environment Variables

Configure your `.env` file with the following settings:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=safekeep_db
DB_USER=root
DB_PASS=your_password

# Email Configuration (PHPMailer)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@school.edu
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@safekeep.school
MAIL_FROM_NAME="SafeKeep - Lost & Found"

# Application Settings
APP_NAME="SafeKeep"
APP_URL=http://localhost/safekeep-v2
APP_ENV=production
APP_DEBUG=false

# Security Settings
CSRF_SECRET=your-random-32-character-secret-here
SESSION_NAME=safekeep_session
SESSION_LIFETIME=3600

# School Configuration
ALLOWED_EMAIL_DOMAIN=@school.edu
AUTO_APPROVE_USERS=false

# File Upload Limits
MAX_FILE_SIZE=5242880
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp
```

### Security Configuration

1. **Generate secure secrets**:
   ```bash
   # Generate CSRF secret
   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
   ```

2. **Configure email domain**: Set `ALLOWED_EMAIL_DOMAIN` to your school's domain

3. **Set user approval**: Configure `AUTO_APPROVE_USERS` based on your needs

4. **File upload security**: Adjust `MAX_FILE_SIZE` and `ALLOWED_EXTENSIONS` as needed

## 🏗️ Architecture

### Technology Stack

- **Backend**: PHP 8+ (PSR-12 compliant)
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5
- **Database**: MySQL/MariaDB
- **Dependencies**: PHPMailer, vlucas/phpdotenv

### Project Structure

```
safekeep/
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── images/            # Images and icons
├── auth/                  # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── forgot-password.php
├── admin/                 # Admin panel
│   ├── dashboard.php
│   ├── users.php
│   ├── posts.php
│   └── announcements.php
├── posts/                 # Post management
│   ├── create.php
│   ├── browse.php
│   ├── view.php
│   └── my-posts.php
├── includes/              # Core files
│   ├── config.php         # Configuration loader
│   ├── db.php            # Database layer
│   ├── functions.php     # Security & utilities
│   ├── header.php        # Common header
│   └── footer.php        # Common footer
├── migrations/            # Database schemas
├── uploads/               # File uploads (secure)
├── vendor/                # Composer dependencies
├── .env.example          # Environment template
├── .htaccess             # Apache configuration
├── composer.json         # PHP dependencies
└── index.php            # Landing page
```

### Database Schema

The application uses a normalized MySQL database with the following core tables:

- **`users`** - User accounts and authentication
- **`posts`** - Lost and found item listings
- **`announcements`** - System announcements
- **`categories`** - Item categorization
- **`contact_logs`** - Message history and rate limiting
- **`audit_logs`** - Administrative action tracking
- **`rate_limits`** - Rate limiting enforcement

See `migrations/001_create_tables.sql` for complete schema.

## 👥 User Roles & Permissions

### Student Users
- Register with school email
- Create lost/found posts (pending approval)
- Search and browse approved posts
- Contact other users via secure forms
- Manage personal posts and profile

### Administrators
- Approve/reject user registrations
- Moderate and approve posts
- Manage system announcements
- View audit logs and statistics
- Full user management capabilities

## 🔒 Security Features

### Authentication & Authorization
- School email domain validation
- Secure password hashing (Argon2ID)
- Session management with regeneration
- CSRF token protection on all forms
- Role-based access control

### Input Validation & Sanitization
- Server-side validation for all inputs
- HTML entity encoding for XSS prevention
- SQL injection protection via PDO prepared statements
- File upload validation (MIME type, size, extension)

### Rate Limiting & Abuse Prevention
- Contact form rate limiting
- Failed login attempt tracking
- File upload restrictions
- Audit logging for administrative actions

### Data Protection
- Secure file upload handling
- Personal information privacy
- Secure contact form (no direct email exposure)
- Regular session cleanup

## 📧 Email Configuration

SafeKeep uses PHPMailer for email functionality. Configure SMTP settings:

### Gmail Configuration
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
```

### Office 365 Configuration
```env
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@school.edu
MAIL_PASSWORD=your-password
```

### Development Testing
Use services like Mailtrap or MailHog for development:
```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

## 🧪 Development

### Demo Accounts

When using sample data, these accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@school.edu | SafeKeep2024! |
| Student | john.smith@school.edu | SafeKeep2024! |
| Student | sarah.johnson@school.edu | SafeKeep2024! |

### Development Tools

```bash
# Install development dependencies
composer install --dev

# Run code style checks
composer cs-check

# Fix code style issues
composer cs-fix

# Run unit tests
composer test
```

### Local Development with XAMPP

1. **Install XAMPP** from https://www.apachefriends.org/
2. **Start Apache and MySQL** services
3. **Clone project** to `xampp/htdocs/safekeep`
4. **Configure database** via phpMyAdmin
5. **Set up .env file** with localhost settings
6. **Access application** at `http://localhost/safekeep`

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate secure secrets and update `.env`
- [ ] Configure SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Configure email settings
- [ ] Set up database backups
- [ ] Configure web server security headers
- [ ] Test all functionality

### Apache Configuration

Ensure your virtual host configuration includes:

```apache
<Directory /path/to/safekeep>
    AllowOverride All
    Require all granted
</Directory>

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
```

### File Permissions

```bash
# Set proper ownership
chown -R www-data:www-data /path/to/safekeep

# Set directory permissions
find /path/to/safekeep -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/safekeep -type f -exec chmod 644 {} \;

# Make uploads directory writable
chmod 755 uploads/
```

## 🔧 API Endpoints

### Authentication Endpoints
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `GET /auth/logout.php` - User logout

### Post Management
- `GET /posts/browse.php` - Browse/search posts
- `POST /posts/create.php` - Create new post
- `GET /posts/view.php?id={id}` - View post details
- `GET /posts/my-posts.php` - User's posts

### Contact System
- `POST /posts/contact.php` - Send contact message

### Admin Endpoints
- `GET /admin/dashboard.php` - Admin dashboard
- `POST /admin/posts.php` - Approve/reject posts
- `GET /admin/users.php` - Manage users

## 🧪 Testing

### Manual Testing Checklist

**Authentication Flow:**
- [ ] User registration with valid school email
- [ ] Registration with invalid email domain
- [ ] Login with correct credentials
- [ ] Login with incorrect credentials
- [ ] Password reset functionality
- [ ] Session timeout handling

**Post Management:**
- [ ] Create lost item post
- [ ] Create found item post with image
- [ ] Search and filter functionality
- [ ] Post approval workflow
- [ ] Contact form between users

**Admin Functions:**
- [ ] User approval/rejection
- [ ] Post moderation
- [ ] Announcement creation
- [ ] Audit log viewing

### Automated Testing

```bash
# Run PHPUnit tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## 📊 Performance Optimization

### Database Optimization
- Full-text indexes on searchable fields
- Proper foreign key relationships
- Query optimization for large datasets
- Regular cleanup of expired sessions and logs

### Caching Strategies
- Browser caching via HTTP headers
- Static asset optimization
- Database query result caching (future enhancement)

### File Upload Optimization
- Image compression and resizing
- Secure file storage outside web root
- CDN integration (future enhancement)

## 🔍 Troubleshooting

### Common Issues

**Database Connection Errors:**
```
Solution: Check DB credentials in .env file, ensure MySQL service is running
```

**File Upload Errors:**
```
Solution: Check uploads/ directory permissions, verify PHP upload_max_filesize setting
```

**Email Not Sending:**
```
Solution: Verify SMTP configuration, check email provider settings, test with Mailtrap
```

**Session Issues:**
```
Solution: Check PHP session configuration, verify session directory permissions
```

### Debug Mode

Enable debug mode for development:
```env
APP_DEBUG=true
APP_ENV=development
```

This will show detailed error messages and query logs.

## 🤝 Contributing

### Git Workflow

We follow the GitFlow branching model:

- **`main`** - Production releases
- **`develop`** - Integration branch
- **`feature/*`** - New features
- **`hotfix/*`** - Critical fixes

### Commit Convention

Use conventional commit format:

```
<type>(scope): description

feat(auth): add password reset functionality
fix(upload): validate file MIME types properly
docs(readme): update installation instructions
```

### Code Style

- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add PHPDoc comments for all functions
- Maintain consistent indentation (4 spaces)

### Pull Request Process

1. Fork the repository
2. Create feature branch from `develop`
3. Implement changes with tests
4. Run code style checks
5. Submit pull request with description
6. Request review from maintainers

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Installation Guide](docs/installation.md)
- [API Reference](docs/api.md)
- [Security Guide](docs/security.md)

### Community
- **Issues**: [GitHub Issues](https://github.com/your-org/safekeep/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-org/safekeep/discussions)
- **Email**: support@safekeep.school

### Professional Support
For professional support, customization, or enterprise deployment assistance, contact our development team.

## 🛣️ Roadmap

### Version 2.0 (Future Enhancements)
- [ ] **Mobile App** - React Native mobile application
- [ ] **Real-time Chat** - WebSocket-based messaging
- [ ] **Image Recognition** - AI-powered item matching
- [ ] **Multi-language Support** - Internationalization
- [ ] **API v2** - RESTful API with authentication
- [ ] **Progressive Web App** - Offline functionality
- [ ] **Advanced Analytics** - Usage statistics and insights
- [ ] **Integration APIs** - Connect with school systems

### Version 1.1 (Next Release)
- [ ] Email notifications for post updates
- [ ] Bulk operations in admin panel
- [ ] Advanced search filters
- [ ] Export functionality for reports
- [ ] Enhanced mobile responsive design

---

**Built with ❤️ by the SafeKeep development team**

*Making lost items find their way home, safely and efficiently.*