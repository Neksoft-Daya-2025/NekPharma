#!/usr/bin/env python3
import os
import sys

import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"


def main():
    if not PASSWORD:
        print("Set RYVA_VPS_PASSWORD")
        sys.exit(1)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

    script = f"""
cd {APP}
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chmod -R 777 storage/framework/cache
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
echo STORAGE_FIX_OK
"""
    stdin, stdout, stderr = client.exec_command(script, timeout=120)
    print(stdout.read().decode())
    err = stderr.read().decode()
    if err:
        print(err, file=sys.stderr)
    stdin, stdout, stderr = client.exec_command(
        f"curl -s -o /dev/null -w '%{{http_code}}' http://127.0.0.1/login", timeout=30
    )
    code = stdout.read().decode().strip()
    print("HTTP /login:", code)
    client.close()


if __name__ == "__main__":
    main()
