# 🚀 Deployment Guide - ryvavitabiotics.com

**Target Domain:** https://ryvavitabiotics.com  
**Hosting:** Hostinger Shared Hosting  
**Estimated Time:** 30-45 minutes  

---

## 📦 Step 1: Create Deployment Package

### Run the Package Creator

1. Open Command Prompt
2. Navigate to your project:
   ```cmd
   cd "C:\Users\ASUS\Desktop\Pharma Crm RYVA"
   ```
3. Run the deployment package creator:
   ```cmd
   create_deployment_package.bat
   ```

This will create a folder called: **`DEPLOYMENT_RYVAVITABIOTICS`**

### What Gets Created:

```
DEPLOYMENT_RYVAVITABIOTICS/
├── app/                              ✓ All application code
├── bootstrap/                        ✓ Laravel bootstrap
├── config/                           ✓ Configuration files
├── database/                         ✓ Migrations & seeders
├── Modules/                          ✓ Pharma modules
├── public/                           ✓ Web assets
├── resources/                        ✓ Views & language files
├── routes/                           ✓ Route definitions
├── storage/                          ✓ Storage (cleaned)
├── tests/                            ✓ Test files
├── .env                              ✓ Production config (EDIT THIS!)
├── .htaccess                         ✓ Apache rules
├── artisan                           ✓ Laravel CLI
├── composer.json                     ✓ Dependencies
├── package.json                      ✓ Frontend dependencies
├── README.txt                        ✓ Package info
├── DEPLOYMENT_CHECKLIST.txt          ✓ Upload checklist
├── DEPLOYMENT_INSTRUCTIONS.md        ✓ Full guide
├── DATABASE_EXPORT_REMINDER.txt      ✓ DB export guide
└── setup_on_server.sh                ✓ Server setup script
```

---

## 📝 Step 2: Configure for Your Server

### A. Edit .env File

1. Open `DEPLOYMENT_RYVAVITABIOTICS\.env` in a text editor
2. Update these lines with your Hostinger database credentials:

```env
DB_HOST=localhost
DB_DATABASE=your_hostinger_database_name
DB_USERNAME=your_hostinger_username
DB_PASSWORD=your_hostinger_password
```

**Where to find these:**
- Login to Hostinger
- Go to: **Databases** section
- Select your database
- Copy the credentials

### B. Update Email Settings (Optional)

```env
MAIL_FROM_ADDRESS="noreply@ryvavitabiotics.com"
MAIL_FROM_NAME="Pharma CRM"
```

---

## 💾 Step 3: Export Your Database

### Export from Local

1. Open: http://localhost/phpmyadmin
2. Select database: **`pharma_crm`**
3. Click **"Export"** tab
4. Method: **Quick**
5. Format: **SQL**
6. Click **"Go"**
7. Save as: **`pharma_crm_ryvavitabiotics.sql`**

**Keep this file safe - you'll upload it to Hostinger!**

---

## 🔄 Step 4: Backup Current Hostinger (Important!)

### Backup Files

1. Login to Hostinger Control Panel
2. Go to: **Backups**
3. Click **"Create Backup"**
4. Wait for completion

### Backup Database

1. Go to: **Databases** → **phpMyAdmin**
2. Select your current database
3. Click **Export** → Quick → SQL → Go
4. Save as: `hostinger_backup_YYYY-MM-DD.sql`

**Keep these backups safe in case you need to restore!**

---

## 🗑️ Step 5: Clean Hostinger Server

### Delete Everything in public_html

1. Hostinger → **File Manager**
2. Navigate to: **`public_html/`**
3. **Select ALL** files and folders
4. Click **Delete**
5. Confirm deletion

⚠️ **Make sure you created backups first!**

---

## 📤 Step 6: Upload Deployment Package

### A. Create ZIP File

1. Right-click on `DEPLOYMENT_RYVAVITABIOTICS` folder
2. Select: **Send to → Compressed (zipped) folder**
3. Name it: `pharma_crm_deploy.zip`

### B. Upload to Hostinger

1. Hostinger → **File Manager**
2. Navigate to: **`public_html/`**
3. Click **Upload** (top right)
4. Select: `pharma_crm_deploy.zip`
5. Wait for upload (may take 5-10 minutes for large files)

### C. Extract Files

1. In File Manager, right-click: `pharma_crm_deploy.zip`
2. Select: **Extract**
3. Extract to: **Current directory**
4. Wait for extraction
5. **Delete** `pharma_crm_deploy.zip` after extraction

Your `public_html` should now have all the Laravel files.

---

## 🔧 Step 7: Install Composer Dependencies

### Via SSH Terminal (Recommended)

1. Hostinger → **Advanced** → **SSH Access**
2. Enable SSH if not already enabled
3. Copy the SSH command
4. Open terminal and connect

Run these commands:

```bash
cd public_html

# Install dependencies
composer install --no-dev --optimize-autoloader

# Should see vendor/ folder created
ls -la | grep vendor
```

### Alternative: Manual Upload (If No SSH)

1. On your local machine:
   ```cmd
   cd "C:\Users\ASUS\Desktop\Pharma Crm RYVA"
   composer install --no-dev --optimize-autoloader
   ```

2. Compress `vendor` folder
3. Upload `vendor.zip` to `public_html/`
4. Extract on server
5. Delete `vendor.zip`

---

## 🔑 Step 8: Generate Application Key

### Via SSH:

```bash
cd public_html
php artisan key:generate
```

### Without SSH:

1. On local machine:
   ```cmd
   php artisan key:generate
   ```
2. Copy the generated key from local `.env`
3. Paste into production `.env` on Hostinger (edit via File Manager)

---

## 🔐 Step 9: Set File Permissions

### Via SSH:

```bash
cd public_html

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make artisan executable
chmod +x artisan

# CRITICAL: Make storage writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Via File Manager:

1. Right-click **`storage`** folder
2. Select **Permissions**
3. Set to: **775**
4. Check: **Apply to subdirectories**
5. Click OK

Repeat for **`bootstrap/cache`** folder

---

## 💾 Step 10: Import Database

### Via phpMyAdmin:

1. Hostinger → **Databases** → **phpMyAdmin**
2. Select your database (left sidebar)
3. Click **Import** tab
4. Choose File: `pharma_crm_ryvavitabiotics.sql`
5. Scroll down → Click **"Go"**
6. Wait for import to complete

⏱️ **May take a few minutes for large databases**

---

## 🌐 Step 11: Configure Web Root

### Option A: Move Public Contents (Recommended)

**Via SSH:**

```bash
cd public_html

# Move public contents to root
mv public/* .
mv public/.htaccess .
rmdir public

# Edit index.php
nano index.php
```

**Change these lines in index.php:**

```php
// OLD:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// NEW:
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

Save: `Ctrl+X`, then `Y`, then `Enter`

### Option B: Point Domain to Public (Alternative)

1. Hostinger → **Domains**
2. Select: **ryvavitabiotics.com**
3. **Document Root** → Change to: `/public_html/public`
4. Save

---

## 🧹 Step 12: Clear & Cache

### Via SSH:

```bash
cd public_html

# Clear old caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Build production caches (speeds up site)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink
php artisan storage:link
```

---

## ✅ Step 13: Test Your Deployment

### Test Homepage

Visit: **https://ryvavitabiotics.com**

Should see your Pharma CRM homepage

### Test Login

1. Go to: https://ryvavitabiotics.com/login
2. Enter admin credentials
3. Should login successfully

### Test Pharma Features

- [ ] **Doctor** module loads and works
- [ ] **Chemist** module loads and works
- [ ] **Stockist** module loads and works
- [ ] **Tour Plan** creation works
- [ ] **DCR** submission works

### Security Tests

Try accessing these (should show 404 or 403):

```
❌ https://ryvavitabiotics.com/vendor/autoload.php
❌ https://ryvavitabiotics.com/.env
❌ https://ryvavitabiotics.com/storage/
```

**If you can see code/content = SECURITY ISSUE!**

---

## 🔍 Step 14: Check for Errors

### Via SSH:

```bash
cd public_html
tail -50 storage/logs/laravel.log
```

### Via File Manager:

1. Navigate to: `storage/logs/`
2. Download latest log file
3. Open and check for errors

**Should see no critical errors**

---

## 🚨 TROUBLESHOOTING

### Problem: White Screen or 500 Error

**Check:**
```bash
# View error logs
tail -100 storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear

# Check permissions
ls -la storage
ls -la bootstrap/cache
```

**Fix:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Problem: CSS/JS Not Loading

**Solution:**
```bash
php artisan view:clear
# Check if mix-manifest.json exists
cat public/mix-manifest.json
```

### Problem: Database Connection Failed

**Check:**
1. Open `.env` file
2. Verify DB credentials match Hostinger
3. Test connection:
   ```bash
   php artisan db
   ```

### Problem: "No input file specified"

**Fix .htaccess in web root:**

Make sure you have `.htaccess` file with proper Laravel rewrite rules.

---

## 📋 POST-DEPLOYMENT CHECKLIST

### Functionality
- [ ] Homepage loads
- [ ] Login works
- [ ] Doctor module works
- [ ] Chemist module works
- [ ] Stockist module works
- [ ] Tour plans can be created
- [ ] DCR can be submitted
- [ ] File uploads work (if applicable)

### Security
- [ ] APP_DEBUG=false in .env
- [ ] APP_ENV=production in .env
- [ ] vendor/ not accessible from browser
- [ ] .env not accessible from browser
- [ ] storage/ not accessible from browser
- [ ] No critical errors in logs

### Performance
- [ ] Site loads quickly
- [ ] Caches are built (config, routes, views)
- [ ] No console errors in browser

### Configuration
- [ ] Domain points to correct directory
- [ ] SSL certificate active (https://)
- [ ] Email settings configured
- [ ] Storage symlink created

---

## 🎉 SUCCESS!

Your Pharma CRM is now live at:
### **https://ryvavitabiotics.com**

---

## 📱 Quick Commands Reference

```bash
# Navigate to project
cd public_html

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check logs
tail -50 storage/logs/laravel.log

# Test database
php artisan db

# List routes
php artisan route:list

# Check permissions
ls -la storage
ls -la bootstrap/cache
```

---

## 🆘 Need Help?

If you encounter issues:

1. **Check Laravel logs:** `storage/logs/laravel.log`
2. **Check Hostinger error logs:** Advanced → Error Logs
3. **Verify database connection** in `.env`
4. **Check file permissions** on storage and cache
5. **Clear all caches** and try again

---

**Deployment Date:** _________________  
**Domain:** https://ryvavitabiotics.com  
**Status:** Ready to Deploy ✓

