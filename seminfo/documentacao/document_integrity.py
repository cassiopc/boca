"""Portable document source checksums across Git line-ending settings."""
import hashlib


def source_sha256(path):
    content = path.read_bytes().replace(b'\r\n', b'\n')
    return hashlib.sha256(content).hexdigest()
