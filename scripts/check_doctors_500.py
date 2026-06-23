#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=60):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip()

print("=== Latest Laravel errors (doctors related) ===")
print(run(f"tail -150 {APP}/storage/logs/laravel.log | grep -A 8 'DoctorController\\|doctors\\|ERROR' | tail -80"))

print("\n=== Last 30 lines of laravel.log ===")
print(run(f"tail -30 {APP}/storage/logs/laravel.log"))

print("\n=== PHP syntax check DoctorController ===")
print(run(f"php -l {APP}/app/Http/Controllers/DoctorController.php 2>&1"))

print("\n=== Storage permissions ===")
print(run(f"ls -la {APP}/storage/framework/cache/data 2>&1 | head -5"))
print(run(f"stat -c '%a %U:%G' {APP}/storage/framework/cache/data 2>&1"))

client.close()
