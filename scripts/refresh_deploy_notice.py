#!/usr/bin/env python3
import os
import sys

import paramiko

HOST = os.environ.get("RYVA_VPS_HOST", "187.127.141.89")
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"


def main():
    if not PASSWORD:
        print("Set RYVA_VPS_PASSWORD")
        sys.exit(1)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username="root", password=PASSWORD, timeout=30)
    cmd = (
        f"cd {APP} && "
        "php artisan deploy:notice 'Ryva CRM was updated. Click Close when done reading.' && "
        "php artisan view:cache && "
        "cat storage/app/deploy_notice.json"
    )
    stdin, stdout, stderr = client.exec_command(cmd, timeout=60)
    print(stdout.read().decode())
    client.close()


if __name__ == "__main__":
    main()
