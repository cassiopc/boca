"""Regression: commands launched by pythonw must not allocate a console."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage


@unittest.skipUnless(sys.platform=='win32','Windows console allocation regression')
class BackgroundProcessTests(unittest.TestCase):
    def test_pythonw_helpers_have_no_console_and_keep_captured_output(self):
        pythonw=Path(sys.executable).with_name('pythonw.exe')
        if not pythonw.exists():self.skipTest('pythonw.exe unavailable')
        with tempfile.TemporaryDirectory() as folder:
            result=Path(folder)/'result.json'
            subprocess.run([str(pythonw),__file__,'--probe',str(result)],check=True,timeout=30,
                           **manage.hidden_process_options())
            value=json.loads(result.read_text())
            self.assertEqual(value,{'console':0,'stdout':'captured'})


if __name__=='__main__':
    if len(sys.argv)==3 and sys.argv[1]=='--probe':
        python=Path(sys.executable).with_name('python.exe')
        probe='import ctypes,json;print(json.dumps({"console":int(ctypes.windll.kernel32.GetConsoleWindow()),"stdout":"captured"}))'
        Path(sys.argv[2]).write_bytes(manage.run([python,'-c',probe]))
    else:
        unittest.main()
