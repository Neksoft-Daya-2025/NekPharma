#!/usr/bin/env python3
"""Fix storage/cache permissions for ryvavitabiotics FPM user."""
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
USER = "ryvavitabiotics"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=120):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    return out, err

script = f"""
set -e
APP="{APP}"
USER="{USER}"

# Ensure dirs exist
mkdir -p "$APP/storage/framework/cache/data" "$APP/storage/framework/sessions" \\
         "$APP/storage/framework/views" "$APP/storage/logs" "$APP/bootstrap/cache"

# Remove root-owned cache files that block FPM writes
find "$APP/storage/framework/cache" -user root -delete 2>/dev/null || true
find "$APP/bootstrap/cache" -user root -delete 2>/dev/null || true

# Full ownership for FPM user
chown -R "$USER:$USER" "$APP/storage" "$APP/bootstrap/cache"
chmod -R 775 "$APP/storage" "$APP/bootstrap/cache"
chmod -R 777 "$APP/storage/framework/cache" "$APP/storage/framework/sessions" "$APP/storage/framework/views"

# Run artisan as FPM user so new cache files get correct owner
su -s /bin/bash "$USER" -c "cd $APP && php artisan cache:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache" 2>&1 | tail -6

echo FIX_OK
"""

print("Fixing storage permissions...")
out, err = run(script)
print(out)
if err:
    print("STDERR:", err[:500])

# Verify ownership of cache data dir
print("\n=== Cache dir ownership sample ===")
print(run(f"ls -la {APP}/storage/framework/cache/data | head -8")[0])

# Check for root-owned files remaining
root_files, _ = run(f"find {APP}/storage {APP}/bootstrap/cache -user root 2>/dev/null | wc -l")
print(f"Root-owned files remaining: {root_files}")

# HTTP smoke test
print("\n=== HTTP smoke tests ===")
for path in ["/login", "/account/doctors"]:
    code, _ = run(f"curl -s -o /dev/null -w '%{{http_code}}' -L 'http://127.0.0.1{path}'")
    print(f"  {path}: HTTP {code}")

client.close()
