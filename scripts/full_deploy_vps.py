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

print("=== Step 1: git pull latest code ===")
out, err = run(f"""
cd {APP}
git config --global --add safe.directory {APP} 2>/dev/null || true
git pull origin main
""")
print(out)
if err:
    print("WARN:", err[:300])

print("\n=== Step 2: fix permissions ===")
out, err = run(f"""
chown -R ryvavitabiotics:ryvavitabiotics {APP}/storage {APP}/bootstrap/cache 2>/dev/null || true
chmod -R 775 {APP}/storage {APP}/bootstrap/cache
chmod -R 644 {APP}/app {APP}/resources {APP}/routes {APP}/database 2>/dev/null || true
find {APP}/app {APP}/resources {APP}/routes {APP}/database -type d -exec chmod 755 {{}} \\; 2>/dev/null || true
echo PERMS_DONE
""")
print(out)

print("\n=== Step 3: run migrations ===")
out, err = run(f"php {APP}/artisan migrate --force 2>&1 | tail -5")
print(out)

print("\n=== Step 4: clear & rebuild caches ===")
out, err = run(f"""
php {APP}/artisan cache:clear 2>&1 | tail -1
php {APP}/artisan config:cache 2>&1 | tail -1
php {APP}/artisan route:cache 2>&1 | tail -1
php {APP}/artisan view:cache 2>&1 | tail -1
echo CACHE_DONE
""")
print(out)

print("\n=== Step 5: verify latest commit on server ===")
out, err = run(f"cd {APP} && git log --oneline -3")
print(out)

print("\n=== DEPLOY COMPLETE ===")
client.close()
