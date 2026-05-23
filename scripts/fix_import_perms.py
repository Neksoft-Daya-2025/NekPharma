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


# Fix all blade files with wrong permissions (should be 644 minimum)
print(run(
    f"find {APP}/resources/views -name '*.blade.php' ! -perm /o=r -exec chmod 664 {{}} \\; && "
    f"ls -la {APP}/resources/views/doctors/ajax/import_mapping.blade.php"
))

# Fix any PHP files too
print(run(
    f"find {APP}/app -name '*.php' ! -perm /o=r -exec chmod 644 {{}} \\; && "
    f"echo 'php perms fixed'"
))

# Rebuild view cache
print(run(f"cd {APP} && php artisan view:clear && php artisan view:cache && echo VIEW_CACHE_OK"))

# Quick test of the import endpoint
print("\n=== Test import GET endpoint")
print(run(
    'curl -s -o /dev/null -w "%{http_code}" '
    '--resolve "www.ryvavitabiotics.com:443:127.0.0.1" '
    'https://www.ryvavitabiotics.com/account/doctors/import -k'
))

client.close()
