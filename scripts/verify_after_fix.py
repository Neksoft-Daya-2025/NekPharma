#!/usr/bin/env python3
import os, paramiko
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=30):
    _, o, _ = c.exec_command(cmd, timeout=timeout)
    return o.read().decode(errors="replace").strip()

print("Cache test:", run(f"su -s /bin/bash ryvavitabiotics -c 'cd {APP} && php artisan cache:clear && echo OK' 2>&1 | tail -2"))
for url in ["https://www.ryvavitabiotics.com/login", "https://www.ryvavitabiotics.com/account/doctors"]:
    print(f"{url}: HTTP {run(f'curl -s -o /dev/null -w %{{http_code}} -L {url}')}")
print("\nLatest errors:")
print(run(f"grep -i 'Permission denied\\|ERROR' {APP}/storage/logs/laravel.log | tail -3"))
c.close()
