#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)


def run(cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip()


# Full tail of log - newest entries
print("=== Last 50 lines of laravel.log")
print(run(f"tail -50 {APP}/storage/logs/laravel.log 2>/dev/null"))

# Fix ownership: everything in storage should be ryvavitabiotics (the FPM user)
print("\n=== Fixing ownership to ryvavitabiotics (the FPM user)")
print(run(
    f"chown -R ryvavitabiotics:ryvavitabiotics {APP}/storage && "
    f"chmod -R 775 {APP}/storage && "
    f"chmod -R 777 {APP}/storage/framework/cache && "
    f"chmod -R 777 {APP}/storage/logs && "
    f"echo 'ownership fixed'"
))

# Also fix bootstrap/cache - keep rw for ryvavitabiotics
print(run(
    f"chown -R ryvavitabiotics:ryvavitabiotics {APP}/bootstrap/cache && "
    f"chmod -R 775 {APP}/bootstrap/cache && "
    f"echo 'bootstrap/cache fixed'"
))

# Verify
print("\n=== Verify storage owner")
print(run(f"ls -la {APP}/storage/ | head -5"))
print(run(f"ls -la {APP}/storage/framework/cache/data/ | head -5"))

client.close()
