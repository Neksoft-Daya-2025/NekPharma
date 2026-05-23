#!/usr/bin/env python3
"""Deploy doctor import column-mapping fix and whitespace normalization."""
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES = [
    "app/Imports/DoctorImport.php",
    "app/Jobs/ImportDoctorJob.php",
]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

sftp = client.open_sftp()
for rel in FILES:
    local = os.path.join(ROOT, rel.replace("/", os.sep))
    remote = f"{APP}/{rel}"
    sftp.put(local, remote)
    print(f"  uploaded: {rel}")
sftp.close()

def run(cmd, timeout=60):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    return out, err

# Fix permissions
for rel in FILES:
    run(f"chmod 644 {APP}/{rel}")

# Clear caches
out, err = run(f"php {APP}/artisan config:clear && php {APP}/artisan view:clear && php {APP}/artisan cache:clear && echo DONE")
print("Cache clear:", out.splitlines()[-1] if out else "")
if err:
    print("Warnings:", err[:200])

# Verify fixes
print("\n=== Verify DoctorImport.php ===")
out, _ = run(f"grep -n 'matchesSampleFileHeaders\\|Always attempt\\|header matching' {APP}/app/Imports/DoctorImport.php | head -5")
print(out)

print("\n=== Verify ImportDoctorJob.php ===")
out, _ = run(f"grep -n 'preg_replace.*\\\\\\\\s+.*fullname\\|Collapse internal' {APP}/app/Jobs/ImportDoctorJob.php | head -5")
print(out)

print("\nDEPLOY_IMPORT_FIX_OK")
client.close()
