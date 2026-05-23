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


print("=== Last Laravel errors")
out = run(f"tail -200 {APP}/storage/logs/laravel.log 2>/dev/null")
# Show last error block
if "local.ERROR" in out or "ERROR" in out:
    lines = out.split("\n")
    result = []
    capture = False
    for line in lines:
        if "ERROR" in line:
            capture = True
            result = []
        if capture:
            result.append(line)
    print("\n".join(result[-60:]))
else:
    print("(no errors - log may be empty)")
    print(out[-500:])

print("\n=== importProcess in DoctorController (lines 644-700)")
print(run(f"sed -n '644,710p' {APP}/app/Http/Controllers/DoctorController.php"))

print("\n=== ImportExcel trait - importJobProcessDirect signature")
print(run(f"grep -n 'importJobProcessDirect\\|readExcelPreserve' {APP}/app/Traits/ImportExcel.php | head -10"))

print("\n=== ImportProcessRequest rules")
print(run(f"cat {APP}/app/Http/Requests/Admin/Employee/ImportProcessRequest.php"))

client.close()
