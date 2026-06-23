#!/usr/bin/env python3
import os, paramiko
PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"
products = ["ATLOCK-SP TAB.", "AVEDINE M CREAM", "AVEFEM B6 TAB."]
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=60):
    _, o, _ = c.exec_command(cmd, timeout=timeout)
    return o.read().decode(errors="replace").strip()

print("=== Product name lookup ===")
for p in products:
    esc = p.replace("'", "''")
    cmd = f"php {APP}/artisan tinker --execute=\"echo \\App\\Models\\Product::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim('{esc}'))])->value('name') ?? 'NOT_FOUND';\""
    out = run(cmd)
    print(f"  {p!r} -> {out.splitlines()[-1] if out else 'ERROR'}")

print("\n=== Similar product names (ATLOCK, AVEDINE, AVEFEM) ===")
for term in ["ATLOCK", "AVEDINE", "AVEFEM"]:
    cmd = f"php {APP}/artisan tinker --execute=\"\\App\\Models\\Product::where('name','like','%{term}%')->limit(5)->pluck('name')->each(fn(\\$n)=>print(\\$n.PHP_EOL));\""
    print(f"  {term}:")
    for line in run(cmd).splitlines():
        if line.strip():
            print(f"    {line}")

c.close()
