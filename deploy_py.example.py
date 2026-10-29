# Deployment Script Example Template
import os
import sys
import time
from ftplib import FTP

HOST = 'your_ftp_host'
USER = 'your_ftp_user'
PASS = 'your_ftp_password'

def get_ftp():
    ftp = FTP()
    ftp.connect(HOST, 21, timeout=60)
    ftp.login(USER, PASS)
    ftp.set_pasv(True)
    return ftp

def ensure_dir(ftp, remote_dir):
    parts = [p for p in remote_dir.split('/') if p]
    ftp.cwd('/')
    for part in parts:
        try:
            ftp.cwd(part)
        except Exception:
            ftp.mkd(part)
            ftp.cwd(part)

def upload_file(ftp, local_path, remote_path):
    print(f"Uploading {local_path} -> {remote_path}")
    remote_dir = os.path.dirname(remote_path).replace('\\', '/')
    ensure_dir(ftp, remote_dir)
    filename = os.path.basename(remote_path)
    with open(local_path, 'rb') as fp:
        ftp.storbinary(f'STOR {filename}', fp)

def upload_tree(ftp, local_dir, remote_base):
    for root, dirs, files in os.walk(local_dir):
        rel = os.path.relpath(root, local_dir)
        if rel == '.':
            target_dir = remote_base
        else:
            target_dir = os.path.join(remote_base, rel).replace('\\', '/')
        for f in files:
            loc = os.path.join(root, f)
            rem = os.path.join(target_dir, f).replace('\\', '/')
            upload_file(ftp, loc, rem)

def main():
    print("Connecting to FTP...")
    ftp = get_ftp()
    print("FTP connected.")

    base_dir = os.path.dirname(os.path.abspath(__file__))
    dist_dir = os.path.join(base_dir, 'dist')
    api_dir = os.path.join(base_dir, 'api')

    print("Uploading dist...")
    upload_tree(ftp, dist_dir, '/public_html')

    print("Uploading api...")
    upload_tree(ftp, api_dir, '/public_html/api')

    ftp.quit()
    print("Deployment completed successfully!")

if __name__ == '__main__':
    for attempt in range(3):
        try:
            main()
            break
        except Exception as e:
            print(f"Deploy error (attempt {attempt + 1}): {e}")
            time.sleep(5)
