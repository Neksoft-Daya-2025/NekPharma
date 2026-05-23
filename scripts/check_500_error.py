#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

cmds = [
    # Latest errors in Laravel log
    f'tail -100 {APP}/storage/logs/laravel.log | grep -A5 "local\\.ERROR" | tail -40',
    # Storage/cache writability
    f'ls -la {APP}/storage/framework/cache/data/ | head -5',
    f'ls -la {APP}/bootstrap/cache/ | grep ".php"',
    # Test PHP can boot the app
    f'cd {APP} && php -r "require __DIR__.\"/vendor/autoload.php\"; echo \"autoload OK\";" 2>&1',
    # Artisan can run
    f'cd {APP} && php artisan --version 2>&1',
]

for cmd in cmds:
    print("===", cmd[:70])
    stdin, stdout, stderr = client.exec_command(cmd, timeout=30)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    print(out[-3000:] if len(out) > 3000 else out)
    if err:
        print("STDERR:", err[:500])
    print()

client.close()
