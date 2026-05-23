#!/usr/bin/env python3
import hashlib, os, paramiko
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

FILES = [
    "app/Exports/DcrManagementExport.php",
    "app/Exports/TourPlanExport.php",
    "app/Http/Controllers/DcrReportController.php",
    "app/Http/Controllers/TourController.php",
    "resources/views/dcr-reports/index.blade.php",
    "resources/views/tours/index.blade.php",
    "routes/web.php",
]

def md5(path):
    with open(path, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd):
    _, o, _ = c.exec_command(cmd, timeout=30)
    return o.read().decode(errors="replace").strip()

print("Server commit:", run(f"cd {APP} && git log -1 --oneline"))
print()
for rel in FILES:
    lh = md5(os.path.join(ROOT, rel.replace("/", os.sep)))
    rh = run(f"md5sum {APP}/{rel} | awk '{{print $1}}'")
    ok = lh == rh
    print(f"  [{'OK' if ok else 'DIFF'}] {rel}")

print()
for label, pat, f in [
    ("DCR export route", "dcr-management.export", "routes/web.php"),
    ("Tour export route", "tours.export", "routes/web.php"),
    ("DCR export button", "dcr-management.export", "resources/views/dcr-reports/index.blade.php"),
    ("Tour export button", "tours.export", "resources/views/tours/index.blade.php"),
    ("DCR export method", "function export", "app/Http/Controllers/DcrReportController.php"),
    ("Tour export method", "function export", "app/Http/Controllers/TourController.php"),
]:
    found = run(f"grep -F '{pat}' {APP}/{f} | wc -l")
    print(f"  [{'OK' if int(found)>0 else 'MISSING'}] {label}")

c.close()
