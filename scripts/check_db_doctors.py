#!/usr/bin/env python3
"""Check what doctor records exist in the live DB vs the import file."""
import os
import re
import openpyxl
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

# ── read excel ─────────────────────────────────────────────────────────────
XLSX = r"c:\Users\ASUS\Desktop\dutch\doctors-sample-import (1).xlsx"
wb = openpyxl.load_workbook(XLSX)
ws = wb.active
file_names = set()
for row in ws.iter_rows(min_row=2, values_only=True):
    if row[0]:
        norm = re.sub(r'\s+', ' ', str(row[0]).strip()).lower()
        file_names.add(norm)

print(f"Excel: {len(file_names)} unique doctors (after whitespace normalization)")

# ── query live DB via artisan tinker ──────────────────────────────────────
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

def run(cmd, timeout=30):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode(errors="replace").strip()

tinker_cmd = r"""php """ + APP + r"""/artisan tinker --no-interaction << 'PHP'
$docs = \App\Models\Doctor::select('id','fullname','headquarter_id')
    ->orderBy('id')
    ->limit(500)
    ->get();
echo "TOTAL:" . $docs->count() . "\n";
foreach ($docs as $d) {
    echo "ROW|" . $d->id . "|" . $d->fullname . "|" . $d->headquarter_id . "\n";
}
PHP"""

print("\nQuerying live DB …")
out = run(tinker_cmd, timeout=60)

db_rows = []
total_line = ""
for line in out.splitlines():
    if line.startswith("TOTAL:"):
        total_line = line
    elif line.startswith("ROW|"):
        parts = line.split("|", 3)
        if len(parts) == 4:
            db_rows.append({"id": parts[1], "name": parts[2], "hq": parts[3]})

print(f"DB {total_line} rows")

# categorise
empty_name  = [r for r in db_rows if r["name"].strip() == ""]
double_space = [r for r in db_rows if re.search(r'  ', r["name"])]
norm_matches = []
for r in db_rows:
    norm = re.sub(r'\s+', ' ', r["name"].strip()).lower()
    if norm in file_names:
        norm_matches.append(r)

print(f"\n  Empty names in DB : {len(empty_name)}")
print(f"  Double-space names: {len(double_space)}")
print(f"  Names matching file (normalised): {len(norm_matches)}")

if empty_name:
    print("\n--- Empty-name records (first 10) ---")
    for r in empty_name[:10]:
        print(f"  id={r['id']} hq={r['hq']}")

if double_space:
    print("\n--- Double-space records (first 10) ---")
    for r in double_space[:10]:
        print(f"  id={r['id']} name={repr(r['name'])} hq={r['hq']}")

client.close()
