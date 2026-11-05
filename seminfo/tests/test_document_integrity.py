import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'documentacao'))
from document_integrity import source_sha256


class DocumentIntegrityTest(unittest.TestCase):
    def test_checksum_ignores_git_line_endings_but_detects_content_changes(self):
        with tempfile.TemporaryDirectory() as folder:
            source = Path(folder) / 'manual.md'
            source.write_bytes('Questão\nLinha dois\n'.encode())
            expected = source_sha256(source)
            source.write_bytes('Questão\r\nLinha dois\r\n'.encode())
            self.assertEqual(source_sha256(source), expected)
            source.write_bytes('Questão\r\nLinha três\r\n'.encode())
            self.assertNotEqual(source_sha256(source), expected)
