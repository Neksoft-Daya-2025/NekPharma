#!/usr/bin/env python3
"""Extended verify: include ExcelImportable + final report."""
import hashlib
import os
import subprocess
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES = [
    "app/Imports/DoctorImport.php",
    "app/Jobs/ImportDoctorJob.php",
    "app/Http/Controllers/DoctorController.php",
    "app/Traits/ImportExcel.php",
    "app/Traits/ExcelImportable.php",
    "routes/web.php",
    "resources/views/doctors/ajax/import.blade.php",
    "resources/views/doctors/ajax/import_mapping.blade.php",
    "resources/views/doctors/ajax/import_progress.blade.php",
    "resources/views/import/process-form.blade.php",
]

PATTERNS = [
    ("DoctorImport.php", "app/Imports/DoctorImport.php", "Always attempt header-based matching"),
    ("ImportDoctorJob.php", "app/Jobs/ImportDoctorJob.php", "Collapse internal whitespace"),
    ("ImportDoctorJob.php", "app/Jobs/ImportDoctorJob.php", "skip_duplicate"),
    ("DoctorController.php", "app/Http/Controllers/DoctorController.php", "importDoctorFileProcess"),
    ("DoctorController.php", "app/Http/Controllers/DoctorController.php", "import_mapping"),
    ("ImportExcel.php", "app/Traits/ImportExcel.php", "importDoctorFileProcess"),
    ("ImportExcel.php", "app/Traits/ImportExcel.php", "readExcelPreserveColumnIndices"),
    ("ExcelImportable.php", "app/Traits/ExcelImportable.php", "columnIndexFor"),
    ("web.php", "routes/web.php", "doctors.import.process"),
    ("import_progress.blade.php", "resources/views/doctors/ajax/import_progress.blade.php", "Already in database"),
    ("process-form.blade.php", "resources/views/import/process-form.blade.php", "Already in database"),
    ("import_mapping.blade.php", "resources/views/doctors/ajax/import_mapping.blade.php", "Step 2"),
]


def local_md5(rel):
    p = os.path.join(ROOT, rel.replace("/", os.sep))
    with open(p, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()


client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)


def run(cmd, timeout=60):
    _, stdout, _ = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip()


r = subprocess.run(["git", "log", "-1", "--oneline"], cwd=ROOT, capture_output=True, text=True)
local_commit = r.stdout.strip()
server_commit = run(f"cd {APP} && git log -1 --oneline")

print("LOCAL :", local_commit)
print("SERVER:", server_commit)
print()

md5_ok = True
for rel in FILES:
    lh = local_md5(rel)
    rh = run(f"md5sum {APP}/{rel} | awk '{{print $1}}'")
    match = lh == rh
    md5_ok = md5_ok and match
    status = "MATCH" if match else "DIFFER"
    print(f"  [{status}] {rel}")

print()
pat_ok = True
for label, rel, pat in PATTERNS:
    # write pattern to temp file on server to avoid shell escaping issues
    remote = f"{APP}/{rel}"
    found = run(f"grep -F '{pat.replace(chr(39), chr(39)+chr(92)+chr(39)+chr(39))}' {remote} | wc -l")
    ok = found and int(found.split()[0] if found.split() else 0) > 0
    pat_ok = pat_ok and ok
    print(f"  [{'OK' if ok else 'FAIL'}] {label}: {pat[:60]}...")

print()
print("=" * 50)
if local_commit.split()[0] == server_commit.split()[0] and md5_ok and pat_ok:
    print("VERIFIED: Import code on server matches local codebase.")
else:
    print("ISSUES FOUND:")
    if local_commit.split()[0] != server_commit.split()[0]:
        print("  - Commit mismatch")
    if not md5_ok:
        print("  - File MD5 mismatch")
    if not pat_ok:
        print("  - Missing code patterns")

client.close()
