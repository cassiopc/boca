#!/usr/bin/env python3
"""Apply the category's assigned artwork before the desktop starts."""
import json
from pathlib import Path
import shutil
import sys

sys.path.insert(0, '/usr/local/lib/seminfo')
from client_config import wallpaper_for_profile


def apply_wallpaper(config_path=Path('/etc/seminfo/lab.json'),
                    folder=Path('/usr/share/maratona-background')):
    config = json.loads(Path(config_path).read_text(encoding='utf-8-sig'))
    name = wallpaper_for_profile(config.get('profile'))
    if config.get('wallpaper') != name:
        raise ValueError('Wallpaper nao corresponde ao perfil')
    folder = Path(folder)
    source = folder / name
    shutil.copyfile(source, folder/'maratona-common-wallpaper.png')


if __name__ == '__main__':
    apply_wallpaper()
