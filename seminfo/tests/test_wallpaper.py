import importlib.util
import json
import os
from pathlib import Path
import runpy
import sys
import tempfile
import unittest
from unittest.mock import patch

HERE = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(HERE))
from client_config import generate, normalize_wallpaper, wallpaper_for_profile

spec = importlib.util.spec_from_file_location('seminfo_wallpaper', HERE/'client/wallpaper.py')
wallpaper = importlib.util.module_from_spec(spec)
spec.loader.exec_module(wallpaper)


class WallpaperTests(unittest.TestCase):
    def test_category_assignment_and_invalid_profile(self):
        self.assertEqual(wallpaper_for_profile('tii'), 'caramel-claro.png')
        self.assertEqual(wallpaper_for_profile('si'), 'caramel-espresso.png')
        for profile in (None, '', 'outro'):
            with self.subTest(profile=profile), self.assertRaises(ValueError):
                wallpaper_for_profile(profile)

    def test_legacy_and_current_names_are_scoped_to_their_category(self):
        for profile, legacy, current in (('tii', 'lab4-tii.png', 'caramel-claro.png'),
                                         ('si', 'lab3-si.png', 'caramel-espresso.png')):
            for name in (legacy, current):
                self.assertEqual(normalize_wallpaper(profile, name), current)
            for name in ('lab3-si.png', 'lab4-tii.png', 'caramel-claro.png', 'caramel-espresso.png', None):
                if name not in (legacy, current):
                    with self.subTest(profile=profile, name=name), self.assertRaises(ValueError):
                        normalize_wallpaper(profile, name)

    def test_boot_restores_the_assigned_art_for_each_category(self):
        with tempfile.TemporaryDirectory() as tmp:
            folder = Path(tmp)
            (folder/'caramel-claro.png').write_bytes(b'junior artwork')
            (folder/'caramel-espresso.png').write_bytes(b'pleno artwork')
            config_path = folder/'lab.json'
            alias = folder/'maratona-common-wallpaper.png'
            for profile, expected in (('tii', b'junior artwork'), ('si', b'pleno artwork')):
                config_path.write_text(json.dumps({'profile': profile, 'wallpaper': wallpaper_for_profile(profile)}))
                for _ in range(2):
                    alias.write_bytes(b'previous session artwork')
                    wallpaper.apply_wallpaper(config_path, folder)
                    self.assertEqual(alias.read_bytes(), expected)

    def test_invalid_or_mismatched_config_does_not_replace_alias(self):
        with tempfile.TemporaryDirectory() as tmp:
            folder = Path(tmp)
            config_path = folder/'lab.json'
            alias = folder/'maratona-common-wallpaper.png'
            alias.write_bytes(b'preserved')
            for config in ({'profile': 'invalid'}, {'profile': 'tii', 'wallpaper': 'caramel-espresso.png'}):
                config_path.write_text(json.dumps(config))
                with self.assertRaises(ValueError):
                    wallpaper.apply_wallpaper(config_path, folder)
                self.assertEqual(alias.read_bytes(), b'preserved')

    def test_overlay_alias_and_public_config_match_the_category(self):
        for profile, name in (('tii', 'caramel-claro.png'), ('si', 'caramel-espresso.png')):
            config = json.loads((HERE/'labs'/f'{profile}.json').read_text(encoding='utf-8'))
            for supplied in (config['wallpaper'], name):
                with self.subTest(profile=profile, supplied=supplied), tempfile.TemporaryDirectory() as tmp:
                    original = config | {'wallpaper': supplied}
                    with patch('admission_setup.ensure_keys', return_value='a'*64):
                        overlay = generate(original, Path(tmp), allow_incomplete=True)
                    self.assertEqual(original['wallpaper'], supplied)
                    public = json.loads((overlay/'etc/seminfo/lab.json').read_text())
                    self.assertEqual(public['wallpaper'], name)
                    self.assertEqual((overlay/'usr/share/maratona-background/maratona-common-wallpaper.png').read_bytes(),
                                     (HERE/'banners'/name).read_bytes())

    def test_overlay_rejects_another_categorys_alias(self):
        config = json.loads((HERE/'labs/tii.json').read_text(encoding='utf-8'))
        for name in ('lab3-si.png', 'caramel-espresso.png'):
            with self.subTest(name=name), tempfile.TemporaryDirectory() as tmp, self.assertRaises(ValueError):
                generate(config | {'wallpaper': name}, Path(tmp), allow_incomplete=True)

    def test_administrative_import_and_restore_normalize_old_names(self):
        script = HERE/'client/configure-client.py'
        path_type = Path
        for profile, lab, legacy, current in (('tii', 4, 'lab4-tii.png', 'caramel-claro.png'),
                                               ('si', 3, 'lab3-si.png', 'caramel-espresso.png')):
            for restore in (False, True):
                for supplied in (legacy, current):
                    with self.subTest(profile=profile, restore=restore, supplied=supplied), tempfile.TemporaryDirectory() as tmp:
                        root = path_type(tmp)
                        config = {'profile': profile, 'lab': lab, 'server_ip': '192.0.2.2',
                                  'subnet': '192.0.2.0/24', 'wallpaper': supplied, 'admission': True}
                        def rooted(value):
                            return root / str(value).lstrip('/').replace('/', os.sep)
                        for name in ('etc/seminfo', 'etc/xdg/autostart', 'etc/dconf/db/local.d', 'home'):
                            rooted('/'+name).mkdir(parents=True, exist_ok=True)
                        rooted('/etc/seminfo/lab.json').write_text(json.dumps(config))
                        rooted('/etc/dconf/db/local.d/99-seminfo').write_text("homepage-url='about:blank'\n")
                        rooted('/input.json').write_text(json.dumps(config))
                        argv = [str(script), *(['--restore'] if restore else []), '/input.json']
                        with patch('pathlib.Path', side_effect=rooted), patch.object(sys, 'argv', argv), \
                             patch.object(os, 'geteuid', return_value=0, create=True), \
                             patch('subprocess.run'), patch('builtins.print'):
                            if restore:
                                with patch.object(type(root), 'stat', return_value=type('Stat', (), {'st_uid': 0, 'st_mode': 0o600})()):
                                    runpy.run_path(str(script), run_name='__main__')
                            else:
                                runpy.run_path(str(script), run_name='__main__')
                        updated = json.loads(rooted('/etc/seminfo/lab.json').read_text())
                        self.assertEqual(updated['wallpaper'], current)
                        if not restore:
                            saved = json.loads(rooted('/home/.seminfo-lab.json').read_text())
                            self.assertEqual(saved['wallpaper'], current)


if __name__ == '__main__':
    unittest.main()
