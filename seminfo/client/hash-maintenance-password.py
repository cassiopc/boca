#!/usr/bin/env python3
"""Hash the maintenance password, accepting Windows or Unix line endings."""
from pathlib import Path
import subprocess
import sys


def password_hash(path):
    password = Path(path).read_text(encoding='utf-8').rstrip('\r\n')
    if not password or '\r' in password or '\n' in password:
        raise ValueError('Senha de manutencao deve ocupar uma unica linha')
    # openssl strips LF from stdin but retains CR. Normalize before hashing;
    # never place the password in command arguments or diagnostic output.
    return subprocess.check_output(['openssl', 'passwd', '-6', '-stdin'],
                                   input=password.encode('utf-8') + b'\n').decode().strip()


if __name__ == '__main__':
    if len(sys.argv) != 2:
        raise SystemExit('Uso: hash-maintenance-password.py maintenance-password.txt')
    print(password_hash(sys.argv[1]))
