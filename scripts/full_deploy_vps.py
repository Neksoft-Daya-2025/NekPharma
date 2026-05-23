#!/usr/bin/env python3
"""Full git-pull deploy to Ryva VPS."""
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=300):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    return out, err

print("=== Step 1: fetch and reset to latest main ===")
out, err = run(f"""
cd {APP}
git config --global --add safe.directory {APP} 2>/dev/null || true
git fetch origin main
git reset --hard origin/main
""")
print(out)
if err:
    print("WARN:", err[:300])

FPM_USER = "ryvavitabiotics"

print("\n=== Step 2: fix permissions ===")
out, err = run(f"""
mkdir -p {APP}/storage/framework/cache/data {APP}/storage/framework/sessions {APP}/storage/framework/views {APP}/bootstrap/cache
find {APP}/storage/framework/cache {APP}/bootstrap/cache -user root -delete 2>/dev/null || true
chown -R {FPM_USER}:{FPM_USER} {APP}/storage {APP}/bootstrap/cache
chmod -R 775 {APP}/storage {APP}/bootstrap/cache
chmod -R 777 {APP}/storage/framework/cache {APP}/storage/framework/sessions {APP}/storage/framework/views
echo PERMS_DONE
""")
print(out)

print("\n=== Step 3: run migrations ===")
out, err = run(f"su -s /bin/bash {FPM_USER} -c 'cd {APP} && php artisan migrate --force' 2>&1 | tail -5")
print(out)

print("\n=== Step 4: clear & rebuild caches (as FPM user) ===")
out, err = run(f"""
su -s /bin/bash {FPM_USER} -c 'cd {APP} && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache' 2>&1 | tail -5
echo CACHE_DONE
""")
print(out)

print("\n=== Step 5: verify latest commit on server ===")
out, err = run(f"cd {APP} && git log --oneline -3")
print(out)

print("\n=== DEPLOY COMPLETE ===")
client.close()
