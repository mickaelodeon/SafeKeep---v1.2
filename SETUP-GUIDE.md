# SafeKeep Setup Guide - Step by Step

## 📋 Prerequisites Check
- Windows 10/11
- At least 2GB free disk space
- Administrative privileges (for XAMPP installation)

---

## 🚀 Step 1: Install XAMPP

### Download XAMPP
1. Go to https://www.apachefriends.org/download.html
2. Download **XAMPP for Windows** (PHP 8.0 or higher)
3. Run the installer as Administrator

### Install XAMPP
1. **Run the installer** → Click "Next"
2. **Select Components** → Make sure these are checked:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
3. **Installation Directory** → Keep default: `C:\xampp`
4. **Complete installation** → Click "Finish"

### Start XAMPP Services
1. **Launch XAMPP Control Panel** (run as Administrator)
2. **Start Apache** → Click "Start" button next to Apache
3. **Start MySQL** → Click "Start" button next to MySQL
4. **Verify** → Both should show green "Running" status

**✅ Test:** Open browser and go to `http://localhost` - you should see XAMPP welcome page

---

## 📁 Step 2: Set Up Project Directory

### Copy Project Files
1. **Navigate to XAMPP directory:** `C:\xampp\htdocs\`
2. **Create new folder:** `safekeep-v2`
3. **Copy all files** from `SAFEKEEP v2.0` to `C:\xampp\htdocs\safekeep-v2\`

**Your structure should look like:**
```
C:\xampp\htdocs\safekeep-v2\
├── auth\
├── posts\
├── admin\
├── includes\
├── assets\
├── composer.json
├── .env.example
└── index.php
```

---

## 🔧 Step 3: Install Composer & Dependencies

### Install Composer (if not installed)
1. **Download:** Go to https://getcomposer.org/download/
2. **Run:** `Composer-Setup.exe`
3. **Follow installer** → Use default settings
4. **Restart** your computer after installation

### Install Project Dependencies
1. **Open Command Prompt as Administrator**
2. **Navigate to project:**
   ```cmd
   cd C:\xampp\htdocs\safekeep-v2
   ```
3. **Install dependencies:**
   ```cmd
   composer install
   ```

**✅ Expected output:** You should see packages being downloaded and a `vendor` folder created.

---

## ⚙️ Step 4: Configure Environment

### Create Environment File
1. **Copy configuration file:**
   ```cmd
   copy .env.example .env
   ```
2. **Edit `.env` file** with Notepad or VS Code:
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_NAME=safekeep_db
   DB_USER=root
   DB_PASS=

   # Application Configuration
   APP_NAME="SafeKeep"
   APP_URL=http://localhost/safekeep-v2
   APP_ENV=development
   APP_DEBUG=true
   ```

### Generate Security Secret
1. **Open Command Prompt in project directory**
2. **Generate random secret:**
   ```cmd
   php -r "echo 'CSRF_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;"
   ```
3. **Copy the output** and replace `CSRF_SECRET=your-random-32-character-secret-here` in `.env`

---

## 🗄️ Step 5: Set Up Database

### Create Database
1. **Open phpMyAdmin:** Go to `http://localhost/phpmyadmin`
2. **Click "New"** in left sidebar
3. **Database name:** `safekeep_db`
4. **Collation:** `utf8mb4_unicode_ci`
5. **Click "Create"**

### Import Database Schema
1. **Select your database:** Click on `safekeep_db`
2. **Click "Import" tab**
3. **Choose file:** Click "Choose File"
4. **Navigate to:** `C:\xampp\htdocs\safekeep-v2\migrations\001_create_tables.sql`
5. **Click "Import"**

### Import Sample Data (Optional)
1. **Still in Import tab**
2. **Choose file:** `002_sample_data.sql`
3. **Click "Import"**

**✅ Verify:** You should see tables like `users`, `posts`, `announcements` in the database.

---

## 🌐 Step 6: Access Your Application

### Open SafeKeep
1. **Open browser**
2. **Go to:** `http://localhost/safekeep-v2`
3. **You should see** the SafeKeep homepage!

### Test Demo Accounts (if you imported sample data)
- **Admin Login:**
  - Email: `admin@school.edu`
  - Password: `SafeKeep2024!`
  
- **Student Login:**
  - Email: `john.smith@school.edu`
  - Password: `SafeKeep2024!`

---

## 🔧 Troubleshooting

### Common Issues & Solutions

#### "Page not found" or 404 Error
```
Problem: Cannot access http://localhost/safekeep-v2
Solution: 
1. Check XAMPP Apache is running (green in control panel)
2. Verify files are in C:\xampp\htdocs\safekeep-v2\
3. Try: http://127.0.0.1/safekeep-v2
```

#### Database Connection Error
```
Problem: "Database connection failed"
Solution:
1. Check MySQL is running in XAMPP
2. Verify .env database settings match phpMyAdmin
3. Ensure database 'safekeep_db' exists
```

#### Composer Command Not Found
```
Problem: 'composer' is not recognized as internal command
Solution:
1. Restart Command Prompt after Composer installation
2. Or download composer.phar and use: php composer.phar install
```

#### Permission Errors
```
Problem: Cannot write to uploads directory
Solution:
1. Right-click uploads folder → Properties → Security
2. Give "Users" full control permissions
3. Or run XAMPP as Administrator
```

#### PHP Version Issues
```
Problem: PHP version compatibility
Solution:
1. Check PHP version: php -v (should be 8.0+)
2. If older, update XAMPP to latest version
```

### Getting Help
- **Check XAMPP logs:** `C:\xampp\apache\logs\error.log`
- **Enable debug mode:** Set `APP_DEBUG=true` in `.env`
- **Check PHP errors:** Look at browser developer console

---

## ✅ Success Checklist

- [ ] XAMPP installed and running (Apache + MySQL green)
- [ ] Project files in `C:\xampp\htdocs\safekeep-v2\`
- [ ] Composer dependencies installed (`vendor` folder exists)
- [ ] `.env` file configured with database settings
- [ ] Database `safekeep_db` created in phpMyAdmin
- [ ] Database tables imported successfully
- [ ] Can access `http://localhost/safekeep-v2`
- [ ] Can register new account or login with demo accounts

---

## 🎯 Next Steps After Setup

1. **Create Admin Account:** Register with your school email and approve via phpMyAdmin
2. **Customize Settings:** Update school domain in `.env` file
3. **Test Features:** Try creating posts, uploading images, using search
4. **Configure Email:** Set up SMTP settings for email notifications (optional)

**🎉 Congratulations!** Your SafeKeep application is now running at: `http://localhost/safekeep-v2`