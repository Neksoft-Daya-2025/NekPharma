#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
REL = "resources/views/doctors/ajax/import_progress.blade.php"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

sftp = client.open_sftp()
sftp.put(os.path.join(ROOT, REL.replace("/", os.sep)), f"{APP}/{REL}")
sftp.close()

stdin, stdout, stderr = client.exec_command(
    f"chmod 644 {APP}/{REL} && php {APP}/artisan view:clear && echo DONE"
)
print(stdout.read().decode())
print(stderr.read().decode())

# Verify
stdin, stdout, stderr = client.exec_command(
    f"grep -n 'Already in database' {APP}/{REL}"
)
print("Verify:", stdout.read().decode().strip() or "NOT FOUND - check!")

client.close()
