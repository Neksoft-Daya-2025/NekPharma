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


print("=== import_progress.blade.php — duplicate toast")
print(run(f"grep -n 'Already in database\\|already exist\\|dupCount' {APP}/resources/views/doctors/ajax/import_progress.blade.php | head -10"))

print("\n=== process-form.blade.php — duplicate toast")
print(run(f"grep -n 'Already in database\\|already exist\\|dupCount' {APP}/resources/views/import/process-form.blade.php | head -10"))

print("\n=== ImportDoctorJob — case-insensitive duplicate check")
print(run(f"grep -n 'strtolower.*fullname\\|strtolower.*trim' {APP}/app/Jobs/ImportDoctorJob.php"))

print("\n=== File timestamps (should be recent)")
print(run(f"ls -la {APP}/resources/views/doctors/ajax/import_progress.blade.php"))
print(run(f"ls -la {APP}/resources/views/import/process-form.blade.php"))
print(run(f"ls -la {APP}/app/Jobs/ImportDoctorJob.php"))

client.close()
