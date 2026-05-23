#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=60):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip()

# 1. Check headquarters
print("=== Headquarters in DB ===")
out = run(r"""php """ + APP + r"""/artisan tinker --no-interaction << 'PHP'
$hqs = \App\Models\PharmaHeadquarter::select('id','name')->get();
foreach ($hqs as $h) echo "HQ|{$h->id}|{$h->name}\n";
PHP""")
for line in out.splitlines():
    if line.startswith("HQ|"):
        print(" ", line)

# 2. Last 100 lines of laravel log - look for doctor import entries
print("\n=== Recent import log entries ===")
log_out = run(f"tail -200 {APP}/storage/logs/laravel.log | grep -i 'doctor import\\|import.*doctor\\|Skipped\\|skip_dup' | tail -30")
print(log_out or "(no recent doctor import log lines)")

client.close()
