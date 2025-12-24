# 🚀 DEPLOYMENT INSTRUCTIONS - How to Update Hostingercode

## ⚠️ IMPORTANT: The update-settings page is NOT for code deployment!

The page at `https://ryvavitabiotics.com/public/account/update-settings` is for **user account settings**, not for uploading code updates.

---

## ✅ CORRECT DEPLOYMENT METHOD

You need to upload files directly to your server using one of these methods:

### Option 1: Using FTP/SFTP Client (Recommended)
1. **Connect to your server** using FileZilla, WinSCP, or similar FTP client
2. **Navigate to:** `public_html` or `domains/ryvavitabiotics.com/public_html`
3. **Extract the ZIP** on your local computer first
4. **Upload files** maintaining directory structure:
   - `app/Observers/InvoiceObserver.php` → Replace existing
   - `app/DataTables/CFADistributorInvoicesDataTable.php` → Upload
   - `app/Models/CFADistributorStock.php` → Upload
   - `app/Http/Controllers/InvoiceController_ADDITIONS.txt` → Use to update InvoiceController.php
   - `resources/views/invoices/cfa-distributor/` → Upload entire folder
   - `database/migrations/*.php` → Upload all 16 migration files

### Option 2: Using Hostinger File Manager
1. **Login to Hostinger** control panel
2. **Go to File Manager**
3. **Navigate to:** `public_html` folder
4. **Upload** `HOSTINGERCODE_UPDATE.zip`
5. **Extract** the ZIP file in File Manager
6. **Copy files** to correct locations maintaining structure

### Option 3: Using SSH (If you have access)
```bash
# Connect via SSH
ssh your-username@ryvavitabiotics.com

# Navigate to project directory
cd ~/domains/ryvavitabiotics.com/public_html

# Upload ZIP file (use SCP or upload via File Manager first)
# Extract ZIP
unzip HOSTINGERCODE_UPDATE.zip -d temp_extract/

# Copy files
cp -r temp_extract/app/Observers/InvoiceObserver.php app/Observers/
cp -r temp_extract/app/DataTables/* app/DataTables/
cp -r temp_extract/app/Models/* app/Models/
cp -r temp_extract/resources/views/invoices/cfa-distributor resources/views/invoices/
cp temp_extract/database/migrations/*.php database/migrations/

# Clean up
rm -rf temp_extract/
```

---

## 📝 STEP-BY-STEP DEPLOYMENT

### Step 1: Extract ZIP Locally
Extract `HOSTINGERCODE_UPDATE.zip` on your computer to see all files.

### Step 2: Update InvoiceController.php
1. **Open:** `Hostingercode/app/Http/Controllers/InvoiceController.php` on server
2. **Add imports** at the top (around line 38):
   ```php
   use App\DataTables\CFADistributorInvoicesDataTable;
   use App\Models\ProductPurchaseDetail;
   use App\Models\CFADistributorStock;
   ```
3. **Open:** `InvoiceController_ADDITIONS.txt` from extracted ZIP
4. **Copy ALL methods** from the file
5. **Paste** them BEFORE the closing brace `}` at the end of InvoiceController.php

### Step 3: Upload Files
Upload these files/folders to server maintaining directory structure:
- ✅ `app/Observers/InvoiceObserver.php` → Replace existing
- ✅ `app/DataTables/CFADistributorInvoicesDataTable.php` → New file
- ✅ `app/Models/CFADistributorStock.php` → New file
- ✅ `resources/views/invoices/cfa-distributor/` → Entire folder (10 files)
- ✅ `database/migrations/` → All 16 migration files

### Step 4: Run Migrations
**Via SSH:**
```bash
cd ~/domains/ryvavitabiotics.com/public_html
php artisan migrate
```

**Via Hostinger Terminal** (if available):
```bash
php artisan migrate
```

### Step 5: Clear Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ VERIFICATION

After deployment, verify:
1. ✅ Visit: `https://ryvavitabiotics.com/account/cfa-distributor-invoices`
2. ✅ Should see invoice list (not error)
3. ✅ Can create new invoice
4. ✅ No duplicate items appear

---

## 🔧 TROUBLESHOOTING

### If routes don't work:
- Clear route cache: `php artisan route:clear && php artisan route:cache`

### If migrations fail:
- Check database connection
- Verify migrations table exists
- Check file permissions

### If you get "Class not found" errors:
- Run: `composer dump-autoload`
- Clear config cache: `php artisan config:clear`

---

## 📞 NEED HELP?

If you're not sure how to access your server:
1. **Check Hostinger control panel** for FTP credentials
2. **Use File Manager** in Hostinger (easiest option)
3. **Contact Hostinger support** if you need SSH access

**Remember:** The update-settings page is for user settings, NOT code deployment!
