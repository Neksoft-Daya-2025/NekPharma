#!/usr/bin/env python3
import os, paramiko
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd):
    _, o, _ = c.exec_command(cmd, timeout=30)
    return o.read().decode(errors="replace").strip()

checks = [
    ("DCR export route", f"grep 'dcr-management.export' {APP}/routes/web.php"),
    ("Tour export route", f"grep 'tours.export' {APP}/routes/web.php"),
    ("DCR export btn", f"grep 'dcr-management.export' {APP}/resources/views/dcr-reports/index.blade.php"),
    ("Tour export btn", f"grep 'tour-export-link' {APP}/resources/views/tours/index.blade.php"),
    ("DcrManagementExport file", f"test -f {APP}/app/Exports/DcrManagementExport.php && echo EXISTS"),
    ("TourPlanExport file", f"test -f {APP}/app/Exports/TourPlanExport.php && echo EXISTS"),
    ("DCR export fn", f"grep 'public function export' {APP}/app/Http/Controllers/DcrReportController.php"),
    ("Tour export fn", f"grep 'public function export' {APP}/app/Http/Controllers/TourController.php"),
]
for label, cmd in checks:
    result = run(cmd) or "NOT FOUND"
    print(f"{label}: {result[:120]}")

c.close()
