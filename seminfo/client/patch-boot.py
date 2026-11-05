"""Ubuntu 22.04 kernel lines omit boot=casper; insert persistent args explicitly."""
import json
from pathlib import Path
import re
import sys

root = Path(sys.argv[1])
cfg = json.loads(Path(sys.argv[2]).read_text())
title = f'SEMINFO 2026 - Lab {cfg["lab"]} {cfg["profile"].upper()}'
count = 0
for path in root.rglob('*.cfg'):
    if 'grub' not in str(path) and 'isolinux' not in str(path):
        continue
    text = path.read_text(errors='replace')
    text = re.sub(r'menuentry "OEM install[^\n]*\{.*?\n\}', '', text, flags=re.S)
    text = re.sub(r'^set timeout=\d+', 'set timeout=5', text, flags=re.M)
    text = text.replace('Try or Install Ubuntu', title).replace('Ubuntu (safe graphics)', title+' (safe graphics)')
    def kernel(match):
        global count
        count += 1
        args = [arg for arg in match[2].split() if arg not in
                ('---', 'boot=casper', 'persistent', 'noprompt', 'maybe-ubiquity', 'only-ubiquity', 'oem-config/enable=true')
                and not arg.startswith('username=')]
        return match[1]+' '+' '.join(args+['boot=casper','username=icpc','persistent','noprompt','---'])
    text = re.sub(r'^(\s*(?:linux|linuxefi)\s+/casper/vmlinuz)([^\n]*)$', kernel, text, flags=re.M)
    path.write_text(text, newline='\n')
if not count:
    raise SystemExit('Nenhuma entrada de boot Casper encontrada; imagem recusada')
