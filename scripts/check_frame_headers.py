#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")
APP = "/home/ryvavitabiotics/htdocs/www.ryvavitabiotics.com"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)

cmds = [
    # Follow redirects, get final HTTP code + first 3 lines of response
    'curl -s -o /tmp/resp.html -w "%{http_code}" -L -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login && head -3 /tmp/resp.html',
    # Actual response headers
    'curl -s -I -L -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login 2>&1 | grep -i "x-frame\\|content-security\\|location\\|http/"',
    # Check nginx for X-Frame or CSP headers
    'grep -r "X-Frame\\|frame-ancestors\\|Content-Security" /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null || echo "(not in nginx)"',
    # Check Laravel middleware
    f'grep -r "X-Frame\\|frame-ancestors" {APP}/app/Http/Middleware/ 2>/dev/null | head -5 || echo "(not in middleware)"',
    # PHP version
    'php -v | head -1',
    # Check if 302/301 loops to HTTPS
    'curl -s -o /dev/null -w "%{redirect_url}" -H "Host: www.ryvavitabiotics.com" http://127.0.0.1/login',
]

for cmd in cmds:
    print("---", cmd[:70])
    stdin, stdout, stderr = client.exec_command(cmd, timeout=30)
    print(stdout.read().decode(errors="replace").strip() or "(empty)")
    print()

client.close()
