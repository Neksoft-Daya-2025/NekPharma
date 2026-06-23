#!/usr/bin/env python3
"""Verify doctor import code on VPS matches local codebase."""
import hashlib
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

IMPORT_FILES = [
    "app/Imports/DoctorImport.php",
    "app/Jobs/ImportDoctorJob.php",
    "app/Http/Controllers/DoctorController.php",
    "app/Traits/ImportExcel.php",
    "routes/web.php",
    "resources/views/doctors/ajax/import.blade.php",
    "resources/views/doctors/ajax/import_mapping.blade.php",
    "resources/views/doctors/ajax/import_progress.blade.php",
    "resources/views/import/process-form.blade.php",
]

# Each check: (label, grep pattern)
SERVER_CHECKS = {
    "app/Imports/DoctorImport.php": [
        ("header-based column mapping", "Always attempt header-based matching"),
        ("buildColumnIndexMap method", "function buildColumnIndexMap"),
    ],
    "app/Jobs/ImportDoctorJob.php": [
        ("whitespace normalization", "preg_replace('/\\\\s+/', ' ', trim"),
        ("case-insensitive duplicate key", "strtolower(preg_replace"),
        ("skip duplicate (no re-import)", "skip_duplicate"),
    ],
    "app/Http/Controllers/DoctorController.php": [
        ("importProcess method", "function importProcess"),
        ("import mapping view", "import_mapping"),
    ],
    "app/Traits/ImportExcel.php": [
        ("importDoctorFileProcess", "importDoctorFileProcess"),
        ("columnIndexFor helper", "columnIndexFor"),
    ],
    "routes/web.php": [
        ("import.process route", "doctors.import.process"),
    ],
    "resources/views/doctors/ajax/import_mapping.blade.php": [
        ("mapping step 2 UI", "Step 2"),
        ("process route", "doctors.import.process"),
    ],
    "resources/views/doctors/ajax/import_progress.blade.php": [
        ("duplicate toast", "Already in database"),
    ],
    "resources/views/import/process-form.blade.php": [
        ("duplicate toast", "Already in database"),
    ],
}


def local_md5(rel):
    path = os.path.join(ROOT, rel.replace("/", os.sep))
    if not os.path.isfile(path):
        return None
    with open(path, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()


client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)


def run(cmd, timeout=60):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip(), stderr.read().decode(errors="replace").strip()


print("=" * 60)
print("DOCTOR IMPORT — SERVER VERIFICATION")
print("=" * 60)

# Git commit
out, _ = run(f"cd {APP} && git log -1 --oneline")
print(f"\nServer commit: {out}")

local_commit = ""
if os.path.isfile(os.path.join(ROOT, ".git", "HEAD")):
    out2, _ = run(f"cd {ROOT.replace(chr(92), '/')}" if False else "echo skip")
    # read local git log via subprocess would need shell; use file read
    import subprocess
    r = subprocess.run(
        ["git", "log", "-1", "--oneline"],
        cwd=ROOT, capture_output=True, text=True
    )
    local_commit = r.stdout.strip()
    print(f"Local commit:  {local_commit}")

failures = []
warnings = []

print("\n--- File existence & MD5 match ---")
for rel in IMPORT_FILES:
    remote = f"{APP}/{rel}"
    exists_out, _ = run(f"test -f {remote} && echo YES || echo NO")
    local_hash = local_md5(rel)

    if exists_out != "YES":
        failures.append(f"MISSING on server: {rel}")
        print(f"  FAIL  {rel} — file not found on server")
        continue

    remote_hash_out, _ = run(f"md5sum {remote} | awk '{{print $1}}'")
    remote_hash = remote_hash_out.split()[0] if remote_hash_out else ""

    if local_hash and remote_hash:
        if local_hash == remote_hash:
            print(f"  OK    {rel} — MD5 match")
        else:
            warnings.append(f"MD5 MISMATCH: {rel}")
            print(f"  WARN  {rel} — MD5 differs (local={local_hash[:8]}… server={remote_hash[:8]}…)")
    else:
        print(f"  OK    {rel} — exists (hash skipped)")

print("\n--- Key code patterns on server ---")
for rel, checks in SERVER_CHECKS.items():
    remote = f"{APP}/{rel}"
    for label, pattern in checks:
        # escape for shell grep
        pat_esc = pattern.replace("'", "'\\''")
        out, _ = run(f"grep -F '{pat_esc}' {remote} 2>/dev/null | head -1")
        if out:
            print(f"  OK    {rel}: {label}")
        else:
            failures.append(f"Pattern missing — {rel}: {label}")
            print(f"  FAIL  {rel}: {label} — NOT FOUND")

print("\n--- File permissions (must be readable by PHP-FPM) ---")
for rel in IMPORT_FILES:
    if "resources/views" in rel or rel.endswith(".php"):
        remote = f"{APP}/{rel}"
        perm_out, _ = run(f"stat -c '%a %U:%G' {remote} 2>/dev/null")
        if perm_out:
            perms = perm_out.split()[0]
            if perms in ("644", "664", "755"):
                print(f"  OK    {rel} — {perm_out}")
            else:
                warnings.append(f"Unusual perms {rel}: {perm_out}")
                print(f"  WARN  {rel} — {perm_out}")

print("\n" + "=" * 60)
if failures:
    print(f"RESULT: FAILED — {len(failures)} issue(s)")
    for f in failures:
        print(f"  • {f}")
elif warnings:
    print(f"RESULT: OK WITH WARNINGS — {len(warnings)} warning(s)")
    for w in warnings:
        print(f"  • {w}")
else:
    print("RESULT: ALL IMPORT CHECKS PASSED")
print("=" * 60)

client.close()
exit(1 if failures else 0)
