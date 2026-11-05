"""QEMU monitor helper for the disposable virtual USB rehearsal."""
import argparse
import socket
import time

p=argparse.ArgumentParser()
p.add_argument('socket')
p.add_argument('--type')
p.add_argument('--keys')
p.add_argument('--screenshot')
p.add_argument('--reset',action='store_true')
args=p.parse_args()
s=socket.socket(socket.AF_UNIX,socket.SOCK_STREAM);s.connect(args.socket);s.settimeout(.1)
def command(text):
    s.sendall((text+'\n').encode())
    time.sleep(.8)
    try:
        while s.recv(65536): pass
    except TimeoutError: pass
if args.keys: command('sendkey '+args.keys+' 400')
if args.type:
    symbols={' ':'spc','\n':'ret','-':'minus','/':'slash','.':'dot',':':'shift-semicolon',
        ';':'semicolon','~':'shift-grave','>':'shift-dot','_':'shift-minus','=':'equal',
        '(':'shift-9',')':'shift-0','"':'shift-apostrophe',"'":'apostrophe',
        '|':'shift-backslash','\\':'backslash','*':'shift-8','[':'bracket_left',']':'bracket_right'}
    for char in args.type:
        key=symbols.get(char,'shift-'+char.lower() if char.isupper() else char)
        command('sendkey '+key+' 400')
if args.screenshot: command('screendump '+args.screenshot)
if args.reset: command('system_reset')
s.close()
