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


# Last errors in laravel log
print("=== Recent Laravel errors")
print(run(f"tail -150 {APP}/storage/logs/laravel.log 2>/dev/null | grep -A 15 'local\\.ERROR' | tail -60"))

# Check the import views exist on VPS
print("\n=== Import view files on VPS")
print(run(f"ls -la {APP}/resources/views/doctors/ajax/import*.blade.php 2>/dev/null"))
print(run(f"ls -la {APP}/resources/views/import/process-form.blade.php 2>/dev/null"))

# Check routes
print("\n=== Doctor import routes cached")
print(run(f"php {APP}/artisan route:list --path=doctors/import 2>/dev/null | grep -v Deprecated | head -10"))

# Check controller has importProcess method
print("\n=== importProcess method in DoctorController")
print(run(f"grep -n 'importProcess\\|importStore\\|importDoctorFile' {APP}/app/Http/Controllers/DoctorController.php | head -10"))

# Check ImportProcessRequest exists
print("\n=== ImportProcessRequest usage in DoctorController")
print(run(f"grep -n 'ImportProcessRequest' {APP}/app/Http/Controllers/DoctorController.php"))

client.close()
