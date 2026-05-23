#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)


def run(cmd, timeout=60):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    return out, err


# Check ports nginx is listening on
print("=== Nginx listening ports")
out, _ = run("ss -tlnp | grep nginx; netstat -tlnp 2>/dev/null | grep nginx || true")
print(out)

# Check nginx vhost for ryvavitabiotics
print("\n=== Nginx site config")
out, _ = run("cat /etc/nginx/sites-enabled/ryvavitabiotics.com 2>/dev/null || "
             "cat /etc/nginx/conf.d/ryvavitabiotics.com.conf 2>/dev/null || "
             "ls /etc/nginx/sites-enabled/ && ls /etc/nginx/conf.d/")
print(out[:3000])

# Step: fix permissions and rebuild caches - proper single-command execution
print("\n=== Fixing permissions and rebuilding caches")
out, err = run(
    f"rm -rf {APP}/storage/framework/cache/data/* {APP}/storage/framework/views/* "
    f"{APP}/bootstrap/cache/config.php {APP}/bootstrap/cache/routes-v7.php 2>/dev/null; "
    f"mkdir -p {APP}/storage/framework/cache/data {APP}/storage/framework/sessions "
    f"{APP}/storage/framework/views {APP}/storage/logs {APP}/bootstrap/cache; "
    f"chown -R www-data:www-data {APP}/storage {APP}/bootstrap/cache; "
    f"chmod -R 775 {APP}/storage {APP}/bootstrap/cache; "
    f"chmod -R 777 {APP}/storage/framework; "
    f"sudo -u www-data php {APP}/artisan config:cache 2>&1; "
    f"sudo -u www-data php {APP}/artisan route:cache 2>&1; "
    f"sudo -u www-data php {APP}/artisan view:cache 2>&1; "
    "echo PERMISSIONS_FIXED",
    timeout=120,
)
print(out[-2000:])
if err:
    print("ERR:", err[:500])

# Test HTTP (the actual port nginx uses)
print("\n=== HTTP response check")
out, _ = run('curl -s -o /dev/null -w "HTTP %{http_code}" -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login')
print(out)
out, _ = run('curl -s -w "\\nHTTP %{http_code}" -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login 2>&1 | tail -3')
print(out)

# Check laravel log after request
print("\n=== Laravel log after request")
out, _ = run(f"tail -30 {APP}/storage/logs/laravel.log 2>/dev/null | grep -A10 'ERROR' | head -30")
print(out or "(no errors in laravel.log)")

client.close()
