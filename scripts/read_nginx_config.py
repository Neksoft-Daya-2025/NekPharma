#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get("RYVA_VPS_PASSWORD", "")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("187.127.141.89", username="root", password=PASSWORD, timeout=30)


def run(cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode(errors="replace").strip()
    err = stderr.read().decode(errors="replace").strip()
    return out, err


# Read the nginx vhost config
print("=== /etc/nginx/conf.d/www.ryvavitabiotics.com.conf")
out, _ = run("cat /etc/nginx/conf.d/www.ryvavitabiotics.com.conf")
print(out[:5000])

# What PHP-FPM socket/pool is used
print("\n=== PHP-FPM pools")
out, _ = run("ls /etc/php/8.4/fpm/pool.d/ 2>/dev/null; ls /etc/php/8.3/fpm/pool.d/ 2>/dev/null; ls /etc/php/8.2/fpm/pool.d/ 2>/dev/null; ls /etc/php/8.1/fpm/pool.d/ 2>/dev/null")
print(out)

out, _ = run("grep -r 'user\\|listen' /etc/php/8.4/fpm/pool.d/ 2>/dev/null | grep -v ';' | head -20")
print(out)

# Check home dir permissions
print("\n=== /home/ryvavitabiotics permissions")
out, _ = run("ls -la /home/ | grep ryvavitabiotics; ls -la /home/ryvavitabiotics/ 2>/dev/null | head -5")
print(out)

# Actual 443 HTTPS test with SSL
print("\n=== HTTPS test")
out, _ = run('curl -s -o /dev/null -w "HTTPS %{http_code}" --resolve "www.ryvavitabiotics.com:443:127.0.0.1" https://www.ryvavitabiotics.com/login -k')
print(out)

# See what error HTTPS returns
out, _ = run('curl -s -w "\\nHTTP %{http_code}" --resolve "www.ryvavitabiotics.com:443:127.0.0.1" https://www.ryvavitabiotics.com/login -k 2>&1 | tail -5')
print(out)

client.close()
