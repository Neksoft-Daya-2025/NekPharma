#!/usr/bin/env python3
"""Deploy deploy-toast files on VPS using root SSH (rudra uploads, root copies)."""

import json
import os
import sys

import paramiko

HOST = os.environ.get("RYVA_VPS_HOST", "187.127.141.89")
UPLOAD_USER = os.environ.get("RYVA_VPS_USER", "rudra")
ROOT_USER = "root"
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES = [
    "app/Helper/DeployNotice.php",
    "app/Console/Commands/PublishDeployNoticeCommand.php",
    "resources/views/layouts/app.blade.php",
    "resources/views/sections/deploy-notice-toast.blade.php",
]

NOTICE = (
    "Ryva CRM was updated on the server. "
    "Click Close when you have read this message."
)


def ssh_run(client, cmd, timeout=300):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace")
    err = stderr.read().decode(errors="replace")
    code = stdout.channel.recv_exit_status()
    return code, out, err


def main():
    if not PASSWORD:
        print("Set RYVA_VPS_PASSWORD")
        sys.exit(1)

    rudra = paramiko.SSHClient()
    rudra.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    rudra.connect(HOST, username=UPLOAD_USER, password=PASSWORD, timeout=30)

    ssh_run(rudra, "mkdir -p /tmp/ryva-toast-deploy")
    sftp = rudra.open_sftp()
    for rel in FILES:
        local = os.path.join(ROOT, rel.replace("/", os.sep))
        remote = f"/tmp/ryva-toast-deploy/{rel}"
        ssh_run(rudra, f"mkdir -p $(dirname {remote})")
        sftp.put(local, remote)
        print("uploaded", rel)
    sftp.close()
    rudra.close()

    root = paramiko.SSHClient()
    root.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    root.connect(HOST, username=ROOT_USER, password=PASSWORD, timeout=30)

    copy_cmds = " && ".join(
        f"cp /tmp/ryva-toast-deploy/{rel} {APP}/{rel}"
        for rel in FILES
    )
    chmod_cmds = " && ".join(f"chmod 644 {APP}/{rel}" for rel in FILES)
    msg = json.dumps(NOTICE)
    script = f"""
set -e
{copy_cmds}
{chmod_cmds}
cd {APP}
grep -q deploy-notice-toast resources/views/layouts/app.blade.php
php artisan deploy:notice {msg}
php artisan view:clear
php artisan view:cache
echo TOAST_DEPLOY_OK
grep deploy-notice resources/views/layouts/app.blade.php
head -5 storage/app/deploy_notice.json
"""
    code, out, err = ssh_run(root, script, timeout=120)
    print(out)
    if err:
        print(err, file=sys.stderr)
    root.close()

    if "TOAST_DEPLOY_OK" not in out:
        print("Deploy failed")
        sys.exit(1)
    print("\nDone. Hard refresh dashboard (Ctrl+F5). Clear localStorage if needed:")
    print("  localStorage.removeItem('ryva_deploy_notice_seen')")


if __name__ == "__main__":
    main()
