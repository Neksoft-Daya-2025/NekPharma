#!/usr/bin/env python3
import os
import sys

import paramiko

HOST = os.environ.get("RYVA_VPS_HOST", "187.127.141.89")
UPLOAD_USER = os.environ.get("RYVA_VPS_USER", "rudra")
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES = [
    "app/Imports/DoctorImport.php",
    "app/Http/Controllers/DoctorController.php",
    "app/Traits/ImportExcel.php",
    "app/Jobs/ImportDoctorJob.php",
    "resources/views/doctors/ajax/import.blade.php",
    "resources/views/doctors/ajax/import_mapping.blade.php",
    "resources/views/import/process-form.blade.php",
    "routes/web.php",
]


def main():
    if not PASSWORD:
        print("Set RYVA_VPS_PASSWORD")
        sys.exit(1)

    rudra = paramiko.SSHClient()
    rudra.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    rudra.connect(HOST, username=UPLOAD_USER, password=PASSWORD, timeout=30)
    rudra.exec_command("mkdir -p /tmp/ryva-doctor-import")
    sftp = rudra.open_sftp()
    for rel in FILES:
        local = os.path.join(ROOT, rel.replace("/", os.sep))
        remote = f"/tmp/ryva-doctor-import/{rel}"
        parts = remote.rsplit("/", 1)[0]
        rudra.exec_command(f"mkdir -p {parts}")
        sftp.put(local, remote)
        print("uploaded", rel)
    sftp.close()
    rudra.close()

    root = paramiko.SSHClient()
    root.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    root.connect(HOST, username="root", password=PASSWORD, timeout=30)
    copy = " && ".join(f"cp /tmp/ryva-doctor-import/{rel} {APP}/{rel}" for rel in FILES)
    # Fix permissions on all deployed files so web server can read them
    chmod_files = " && ".join(f"chmod 644 {APP}/{rel}" for rel in FILES)
    cmd = (
        f"set -e; {copy}; {chmod_files}; cd {APP}; "
        "mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache; "
        "find resources/views -name '*.blade.php' ! -perm /o=r -exec chmod 644 {} \\; ; "
        "find app -name '*.php' ! -perm /o=r -exec chmod 644 {} \\; ; "
        "php artisan view:clear; php artisan config:cache; php artisan route:cache; "
        "echo DOCTOR_IMPORT_DEPLOY_OK"
    )
    stdin, stdout, stderr = root.exec_command(cmd, timeout=120)
    out = stdout.read().decode()
    err = stderr.read().decode()
    print(out)
    if err:
        print(err, file=sys.stderr)
    code = stdout.channel.recv_exit_status()
    root.close()
    if "DOCTOR_IMPORT_DEPLOY_OK" not in out:
        sys.exit(1 if code != 0 else 0)


if __name__ == "__main__":
    main()
