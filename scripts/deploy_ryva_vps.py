#!/usr/bin/env python3
"""Deploy Ryva CRM to Hostinger VPS and show dashboard deploy toast."""

import json
import os
import sys

try:
    import paramiko
except ImportError:
    print("Install paramiko: pip install paramiko")
    sys.exit(1)

HOST = os.environ.get("RYVA_VPS_HOST", "187.127.141.89")
USER = os.environ.get("RYVA_VPS_USER", "rudra")
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP_DIR = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES_TO_UPLOAD = [
    "app/Helper/DeployNotice.php",
    "app/Console/Commands/PublishDeployNoticeCommand.php",
    "app/Traits/ImportExcel.php",
    "app/Http/Controllers/DoctorController.php",
    "app/Http/Controllers/EmployeeController.php",
    "app/Http/Controllers/ProfileController.php",
    "app/Http/Requests/Admin/Employee/UpdateRequest.php",
    "app/Imports/DoctorImport.php",
    "resources/views/layouts/app.blade.php",
    "resources/views/sections/deploy-notice-toast.blade.php",
    "resources/views/doctors/ajax/import.blade.php",
    "resources/views/employees/ajax/edit.blade.php",
]

DEPLOY_MESSAGE = (
    "Ryva CRM updated on server: doctor import column fix, employee password/HQ fixes, "
    "and deploy confirmation toast."
)


def run_ssh(client, cmd, timeout=300):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace")
    err = stderr.read().decode(errors="replace")
    code = stdout.channel.recv_exit_status()
    return code, out, err


def main():
    if not PASSWORD:
        print("Set RYVA_VPS_PASSWORD environment variable (do not commit passwords).")
        sys.exit(1)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PASSWORD, timeout=30)

    sftp = client.open_sftp()

    print("Uploading changed files to /tmp/ryva-deploy/ ...")
    run_ssh(client, "mkdir -p /tmp/ryva-deploy")
    for rel in FILES_TO_UPLOAD:
        local = os.path.join(LOCAL_ROOT, rel.replace("/", os.sep))
        if not os.path.isfile(local):
            print("Skip missing:", rel)
            continue
        remote_tmp = "/tmp/ryva-deploy/" + rel.replace("\\", "/")
        remote_dir = os.path.dirname(remote_tmp).replace("\\", "/")
        run_ssh(client, f"mkdir -p {remote_dir}")
        sftp.put(local, remote_tmp)
        print("  uploaded", rel)

    sftp.close()

    pw_escaped = PASSWORD.replace("'", "'\"'\"'")
    deploy_script = f"""
set -e
APP="{APP_DIR}"
if echo '{pw_escaped}' | sudo -S cp -r /tmp/ryva-deploy/. "$APP/" 2>/dev/null; then
  echo "Files copied with sudo"
else
  cp -r /tmp/ryva-deploy/. "$APP/" 2>/dev/null || true
fi
cd "$APP"
git config --global --add safe.directory "$APP" 2>/dev/null || true
git pull origin main 2>/dev/null || true
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
php artisan migrate --force 2>/dev/null | tail -3
php artisan deploy:notice {json.dumps(DEPLOY_MESSAGE)}
php artisan config:cache 2>/dev/null | tail -1
php artisan route:cache 2>/dev/null | tail -1
php artisan view:cache 2>/dev/null | tail -1
echo DEPLOY_DONE
git log -1 --oneline 2>/dev/null || true
"""

    print("Running deploy on VPS (sudo may be required)...")
    code, out, err = run_ssh(client, deploy_script, timeout=600)
    print(out)
    if err:
        print(err)
    client.close()

    if "DEPLOY_DONE" in out:
        print("\nSuccess. Log in to www.ryvavitabiotics.com — you should see a green toast: System updated")
    else:
        print("\nDeploy may be incomplete. Check output above.")
        sys.exit(1 if code != 0 else 0)


if __name__ == "__main__":
    main()
