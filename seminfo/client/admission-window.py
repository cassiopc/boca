#!/usr/bin/env python3
"""Unprivileged GTK participant window; it never applies firewall rules."""
import json
import threading
import urllib.request
import gi
gi.require_version('Gtk', '3.0')
from gi.repository import Gtk, GLib

OPENER = urllib.request.build_opener(urllib.request.ProxyHandler({}))


class Window(Gtk.Window):
    def __init__(self):
        super().__init__(title='SEMINFO — autorização da banca')
        self.set_default_size(570, 380)
        self.set_border_width(24)
        box = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=14)
        self.add(box)
        self.title = Gtk.Label(label='Acesso ao BOCA do laboratório')
        self.my_ip = Gtk.Label(label='Meu IP: aguardando rede')
        self.state = Gtk.Label(label='Conectando ao controle local…')
        self.code = Gtk.Label(label='')
        for label in (self.title, self.my_ip, self.state, self.code):
            label.set_line_wrap(True)
            box.pack_start(label, False, False, 0)
        box.pack_start(Gtk.Label(label='IP da mesa da banca (informado pela organização)'), False, False, 0)
        self.address = Gtk.Entry()
        self.address.set_placeholder_text('xxx.xxx.xxx.xxx')
        box.pack_start(self.address, False, False, 0)
        row = Gtk.Box(spacing=12)
        row.pack_start(Gtk.Label(label='Número da equipe:'), False, False, 0)
        self.team = Gtk.SpinButton.new_with_range(1, 10, 1)
        row.pack_start(self.team, False, False, 0)
        box.pack_start(row, False, False, 0)
        self.request = Gtk.Button(label='Solicitar acesso à mesa da banca')
        self.request.connect('clicked', self.submit)
        box.pack_start(self.request, False, False, 0)
        self.open = Gtk.Button(label='Abrir BOCA')
        self.open.set_sensitive(False)
        self.open.connect('clicked', lambda *_: self.open_boca())
        box.pack_start(self.open, False, False, 0)
        self.csrf, self.url, self.initial, self.busy, self.was_approved = '', '', True, False, False
        self.connect('destroy', Gtk.main_quit)
        self.poll()
        GLib.timeout_add_seconds(2, self.poll)

    def poll(self):
        if self.busy:
            return True
        self.busy = True
        def worker():
            try:
                with OPENER.open('http://127.0.0.1:17631/status', timeout=2) as response:
                    data = json.load(response)
                GLib.idle_add(self.update, data)
            except Exception:
                GLib.idle_add(self.state.set_text, 'Controle local indisponível; procure a organização')
            finally:
                self.busy = False
        threading.Thread(target=worker, daemon=True).start()
        return True

    def update(self, data):
        self.csrf = data['csrf']
        self.title.set_text(f'Lab {data["lab"]} — {data["profile"].upper()} · Dispositivo {data["device"]}')
        self.my_ip.set_text('Meu IP: '+data['ip'])
        self.state.set_text(data['status'])
        self.code.set_text('Código para conferir com a banca: '+(data['code'] or '—'))
        self.team.set_range(1, data['teams'])
        if self.initial:
            self.address.set_text(data['server_ip'])
            self.initial = False
        self.url = data['url']
        self.open.set_sensitive(data['approved'])
        if data['approved'] and not self.was_approved:
            self.open_boca()
        self.was_approved = data['approved']

    def open_boca(self):
        if self.url:
            import subprocess
            subprocess.Popen(['epiphany', self.url])

    def submit(self, *_):
        payload = json.dumps({'server_ip': self.address.get_text().strip(), 'team': self.team.get_value_as_int()}).encode()
        def worker():
            try:
                req = urllib.request.Request('http://127.0.0.1:17631/request', payload,
                    {'Content-Type': 'application/json', 'X-SEMINFO-CSRF': self.csrf})
                with OPENER.open(req, timeout=3):
                    pass
                GLib.idle_add(self.state.set_text, 'Solicitação enviada; aguarde a banca')
            except Exception:
                GLib.idle_add(self.state.set_text, 'Confira o IPv4 da banca e o número da equipe')
        threading.Thread(target=worker, daemon=True).start()


if __name__ == '__main__':
    window = Window()
    window.show_all()
    Gtk.main()
