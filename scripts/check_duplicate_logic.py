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


print("=== Duplicate detection logic in ImportDoctorJob")
print(run(f"grep -n 'skip_duplicate\\|existingDoctor\\|Duplicate\\|skippedDetails\\|skipped_details' {APP}/app/Jobs/ImportDoctorJob.php | head -20"))

print("\n=== Lines around the duplicate check")
print(run(f"sed -n '295,315p' {APP}/app/Jobs/ImportDoctorJob.php"))

print("\n=== Skip reason message for duplicates")
print(run(f"grep -n 'Duplicate\\|already existed\\|skip_duplicate' {APP}/app/Jobs/ImportDoctorJob.php"))

client.close()
