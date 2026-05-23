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
    return stdout.read().decode(errors="replace").strip()


# All errors after 18:36 (after our fix)
print("=== Laravel errors after 18:36 today")
print(run(
    f"grep '2026-05-21 1[89]:' {APP}/storage/logs/laravel.log 2>/dev/null | grep 'ERROR' | tail -10"
))
print(run(
    f"tail -300 {APP}/storage/logs/laravel.log 2>/dev/null | grep -A5 '18:[3-9][0-9]:' | tail -40"
))

# Check cache/data directory permissions now
print("\n=== cache/data permissions now")
print(run(f"ls -la {APP}/storage/framework/cache/data/ | head -10"))
print(run(f"stat {APP}/storage/framework/cache/data/"))

# Who does PHP-FPM run as for this site?
print("\n=== ryvavitabiotics FPM pool user")
print(run("grep -E 'user|listen' /etc/php/8.3/fpm/pool.d/www.ryvavitabiotics.com.conf 2>/dev/null | grep -v ';'"))
print(run("grep -E 'user|listen' /etc/php/8.4/fpm/pool.d/www.ryvavitabiotics.com.conf 2>/dev/null | grep -v ';'"))

# Can the FPM user write to cache/data?
print("\n=== Test write as ryvavitabiotics user")
print(run(
    f"sudo -u ryvavitabiotics mkdir -p {APP}/storage/framework/cache/data/test123 && "
    f"echo 'write OK' && rmdir {APP}/storage/framework/cache/data/test123 2>/dev/null"
))

# Permanent fix: make cache dir writable by everyone
print("\n=== Applying permanent cache dir fix")
print(run(
    f"chmod -R 777 {APP}/storage/framework/cache/data && "
    f"chmod -R 777 {APP}/storage/framework/sessions && "
    f"chmod -R 777 {APP}/storage/framework/views && "
    f"chmod -R 777 {APP}/storage/logs && "
    f"echo 'chmod 777 done' && "
    f"ls -la {APP}/storage/framework/"
))

client.close()
