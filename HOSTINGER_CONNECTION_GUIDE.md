# 🔌 How to Connect to Hostinger

## Method 1: Hostinger File Manager (EASIEST - No Software Needed)

### Steps:
1. **Login to Hostinger:**
   - Go to: https://hpanel.hostinger.com
   - Login with your credentials

2. **Open File Manager:**
   - Click on **"Files"** in the left menu
   - Click **"File Manager"**
   - Navigate to: `public_html` (or `domains/ryvavitabiotics.com/public_html`)

3. **Upload & Extract:**
   - Click **"Upload"** button
   - Select `HOSTINGERCODE_UPDATE.zip`
   - Wait for upload to complete
   - Right-click on ZIP → **"Extract"**
   - Files will extract automatically

4. **Access Terminal (for migrations):**
   - In Hostinger panel, look for **"Terminal"** or **"SSH"**
   - Or use **"Advanced"** → **"Terminal"**
   - Run: `php artisan migrate`

---

## Method 2: FTP Client (FileZilla - Recommended)

### Step 1: Get FTP Credentials
1. Login to Hostinger hPanel
2. Go to **"Files"** → **"FTP Accounts"**
3. Note down:
   - **FTP Host:** (usually `ftp.ryvavitabiotics.com` or IP address)
   - **FTP Username:** (your FTP username)
   - **FTP Password:** (your FTP password)
   - **Port:** 21 (or 22 for SFTP)

### Step 2: Download FileZilla
- Download: https://filezilla-project.org/download.php?type=client
- Install FileZilla

### Step 3: Connect
1. Open FileZilla
2. Enter credentials:
   - **Host:** `ftp.ryvavitabiotics.com` (or your FTP host)
   - **Username:** (your FTP username)
   - **Password:** (your FTP password)
   - **Port:** 21 (FTP) or 22 (SFTP)
3. Click **"Quickconnect"**

### Step 4: Navigate & Upload
- **Left side:** Your local computer
- **Right side:** Hostinger server
- Navigate to: `public_html` on right side
- Drag & drop `HOSTINGERCODE_UPDATE.zip` to upload
- Right-click ZIP → **"Extract"** (if FileZilla supports it, or use File Manager)

---

## Method 3: SFTP Client (WinSCP - More Secure)

### Step 1: Download WinSCP
- Download: https://winscp.net/eng/download.php
- Install WinSCP

### Step 2: Get SFTP Credentials
1. Login to Hostinger hPanel
2. Go to **"Advanced"** → **"SSH Access"**
3. Enable SSH if not already enabled
4. Note:
   - **Host:** `ryvavitabiotics.com` or IP
   - **Port:** 22
   - **Username:** (your SSH username - usually same as hosting account)
   - **Password:** (your SSH password)

### Step 3: Connect
1. Open WinSCP
2. Select **"SFTP"** protocol
3. Enter:
   - **Host name:** `ryvavitabiotics.com`
   - **Port:** 22
   - **User name:** (your username)
   - **Password:** (your password)
4. Click **"Login"**

### Step 4: Upload Files
- **Left side:** Local files
- **Right side:** Server files
- Navigate to: `public_html`
- Upload ZIP file
- Right-click → **"Extract"** or use File Manager

---

## Method 4: SSH Terminal (For Advanced Users)

### Step 1: Enable SSH
1. Login to Hostinger hPanel
2. Go to **"Advanced"** → **"SSH Access"**
3. Enable SSH access
4. Note your credentials

### Step 2: Connect via SSH
**Windows (PowerShell or PuTTY):**
```bash
ssh username@ryvavitabiotics.com
# Enter password when prompted
```

**Mac/Linux (Terminal):**
```bash
ssh username@ryvavitabiotics.com
# Enter password when prompted
```

### Step 3: Navigate & Upload
```bash
# Navigate to project directory
cd ~/domains/ryvavitabiotics.com/public_html

# Upload ZIP via SCP (from your local computer):
# scp HOSTINGERCODE_UPDATE.zip username@ryvavitabiotics.com:~/domains/ryvavitabiotics.com/public_html/

# Or use File Manager to upload, then:
# Extract ZIP
unzip HOSTINGERCODE_UPDATE.zip

# Run migrations
php artisan migrate

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Method 5: Hostinger Terminal (Built-in)

1. Login to Hostinger hPanel
2. Go to **"Advanced"** → **"Terminal"**
3. Navigate to your project:
   ```bash
   cd ~/domains/ryvavitabiotics.com/public_html
   ```
4. Run commands:
   ```bash
   php artisan migrate
   php artisan config:cache
   ```

---

## 🔍 Finding Your Credentials

### FTP Credentials:
- **Location:** hPanel → **"Files"** → **"FTP Accounts"**
- **Host:** Usually `ftp.yourdomain.com` or IP address
- **Port:** 21 (FTP) or 22 (SFTP)

### SSH Credentials:
- **Location:** hPanel → **"Advanced"** → **"SSH Access"**
- **Host:** Your domain or IP
- **Port:** 22
- **Username:** Usually your hosting account username
- **Password:** Your hosting account password

### Database Credentials (if needed):
- **Location:** hPanel → **"Databases"** → **"MySQL Databases"**
- **Host:** Usually `localhost`
- **Database Name:** Your database name
- **Username:** Database username
- **Password:** Database password

---

## ✅ Recommended Method

**For beginners:** Use **Hostinger File Manager** (Method 1)
- No software to install
- Easy to use
- Built into Hostinger panel

**For regular use:** Use **FileZilla** (Method 2)
- Fast file transfers
- Easy drag & drop
- Free and reliable

**For advanced users:** Use **SSH** (Method 4)
- Full control
- Can run commands directly
- Most powerful

---

## 🆘 Troubleshooting

### Can't connect via FTP:
- Check FTP credentials are correct
- Verify FTP is enabled in Hostinger
- Try port 21 (FTP) or 22 (SFTP)
- Check firewall settings

### Can't connect via SSH:
- Enable SSH in Hostinger panel first
- Verify SSH credentials
- Some Hostinger plans require SSH to be enabled manually

### File Manager not showing:
- Make sure you're logged into hPanel
- Check you have file access permissions
- Try refreshing the page

---

## 📞 Need Help?

- **Hostinger Support:** https://www.hostinger.com/contact
- **Live Chat:** Available in hPanel
- **Documentation:** https://support.hostinger.com/


