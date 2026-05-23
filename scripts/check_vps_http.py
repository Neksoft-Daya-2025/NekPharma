#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

checks = [
    'curl -s -o /dev/null -w "login: %{http_code}" -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login',
    'curl -s -o /dev/null -w "dashboard: %{http_code}" -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/account/dashboard',
    f'ls {APP}/storage/framework/cache/data 2>&1 | head -3',
    f'grep "\\[ERROR\\]\\|local\\.ERROR" {APP}/storage/logs/laravel.log 2>/dev/null | tail -5',
    f'ls -la {APP}/bootstrap/cache/ | head -6',
]

for cmd in checks:
    print("---", cmd[:70])
    stdin, stdout, stderr = client.exec_command(cmd, timeout=30)
    print(stdout.read().decode(errors="replace").strip() or "(empty)")
    print()

client.close()
