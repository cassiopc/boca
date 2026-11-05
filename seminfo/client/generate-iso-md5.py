#!/usr/bin/env python3
"""Generate ISO checksums while excluding boot files rewritten by xorriso.

Usage: generate-iso-md5.py ISO_TREE SOURCE.iso [--output /work/md5sum.txt]
The source ISO is read-only. No squashfs is decompressed or recompressed here.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shlex
import struct
import subprocess
import tempfile


def require(condition, message):
    if not condition:
        raise ValueError(message)


def report_path(text):
    value = text.strip()
    if value.startswith(('"', "'")):
        parts = shlex.split(value)
        require(len(parts) == 1, 'Caminho de boot ambiguo no relatorio xorriso')
        value = parts[0]
    path = PurePosixPath(value)
    require(path.is_absolute() and '..' not in path.parts, 'Caminho de boot invalido no relatorio xorriso')
    return path.relative_to('/').as_posix()


def boot_exclusions(report):
    """Return filesystem paths that replay may modify, with public reasons."""
    exclusions = {}
    for value in re.findall(r'^\s*El Torito cat path\s*:\s*(.+?)\s*$', report, flags=re.M):
        exclusions[report_path(value)] = 'El Torito catalog regenerated during replay'
    images = {number: value for number, value in re.findall(
        r'^\s*El Torito img path\s*:\s*(\d+)\s+(.+?)\s*$', report, flags=re.M)}
    options = {number: set(value.split()) for number, value in re.findall(
        r'^\s*El Torito img opts\s*:\s*(\d+)\s+(.+?)\s*$', report, flags=re.M)}
    require(re.search(r'^\s*El Torito boot img\s*:', report, flags=re.M),
            'Relatorio xorriso sem imagens El Torito; recusado')
    for number, flags in options.items():
        patched = flags.intersection({'boot-info-table', 'grub2-boot-info'})
        if patched:
            require(number in images, 'Imagem de boot reescrita sem caminho no relatorio xorriso')
            exclusions[report_path(images[number])] = 'xorriso rewrites ' + ', '.join(sorted(patched))
    return exclusions


def xorriso_report(source):
    result = subprocess.run(['xorriso', '-indev', str(source), '-report_el_torito', 'plain'],
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=True)
    return (result.stdout + result.stderr).decode(errors='replace')


def is_el_torito_catalog(path):
    # El Torito 1.0, figures 2/3: validation header 01, zero reserved word,
    # checksum of its 16 little-endian words == 0, keys 55/AA; followed by
    # an Initial/Default Entry. osirrox may materialize a hidden catalog as
    # boot.catalog even when -report_el_torito has no "cat path" line.
    # https://pdos.csail.mit.edu/6.828/2014/readings/boot-cdrom.pdf
    with path.open('rb') as stream:
        header = stream.read(64)
    return (len(header) == 64 and header[0] == 0x01 and header[1] in (0, 1, 2, 0xef)
            and header[2:4] == b'\x00\x00' and header[30:32] == b'\x55\xaa'
            and sum(struct.unpack('<16H', header[:32])) & 0xffff == 0
            and header[32] in (0x00, 0x88) and header[33] in range(5))


def checksum_line(digest, name):
    # GNU md5sum uses an initial backslash to signal escaped filenames.
    if '\\' in name or '\n' in name:
        return '\\' + digest + '  ' + name.replace('\\', '\\\\').replace('\n', '\\n') + '\n'
    return digest + '  ' + name + '\n'


def generate(tree, source, *, output=None, report_output=None):
    tree = Path(tree).resolve(strict=True)
    source = Path(source).resolve(strict=True)
    require(tree.is_dir() and source.is_file(), 'Arvore ISO/dados de origem invalidos')
    output = Path(output).resolve() if output is not None else tree / 'md5sum.txt'
    require(output != source, 'A saida nao pode sobrescrever a ISO de origem')
    require(not output.is_relative_to(tree) or output == tree / 'md5sum.txt',
            'Saida dentro da arvore deve ser o proprio md5sum.txt')
    if report_output is not None:
        report_output = Path(report_output).resolve()
        require(report_output not in {source, output}, 'Relatorio nao pode sobrescrever a ISO ou manifesto')
        require(not report_output.is_relative_to(tree), 'Relatorio deve ficar fora da arvore ISO')
    exclusions = boot_exclusions(xorriso_report(source))
    lines = []
    for path in sorted(tree.rglob('*'), key=lambda p: os.fsencode(p.relative_to(tree))):
        if path.is_symlink() or not path.is_file():
            continue
        relative = path.relative_to(tree).as_posix()
        if path.name.lower() in {'boot.catalog', 'boot.cat'} and is_el_torito_catalog(path):
            exclusions.setdefault(relative, 'Materialized El Torito catalog: validated header and checksum')
        if relative == 'md5sum.txt' or relative in exclusions:
            continue
        digest = hashlib.md5()
        with path.open('rb') as stream:
            while data := stream.read(8 * 1024 * 1024):
                digest.update(data)
        lines.append(checksum_line(digest.hexdigest(), './' + relative))
    require(lines, 'Arvore ISO sem arquivos para conferir')
    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = None
    try:
        with tempfile.NamedTemporaryFile(mode='w', encoding='utf-8', errors='surrogateescape', newline='\n',
                                         dir=output.parent, prefix='.iso-md5-', delete=False) as stream:
            temporary = Path(stream.name)
            stream.writelines(lines)
        temporary.chmod(0o644)
        os.replace(temporary, output)
    finally:
        if temporary is not None and temporary.exists():
            temporary.unlink()
    result = {'source_iso': str(source), 'tree': str(tree), 'manifest': str(output),
              'files': len(lines), 'excluded_boot_files': exclusions}
    if report_output is not None:
        report_output.parent.mkdir(parents=True, exist_ok=True)
        report_output.write_text(json.dumps(result, indent=2), encoding='utf-8')
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('tree', type=Path)
    parser.add_argument('source_iso', type=Path)
    parser.add_argument('--output', type=Path, help='Padrao: ISO_TREE/md5sum.txt')
    parser.add_argument('--report', type=Path, help='JSON de exclusoes; deve ficar fora da arvore ISO')
    args = parser.parse_args()
    result = generate(args.tree, args.source_iso, output=args.output, report_output=args.report)
    print(f'Manifesto MD5 gerado: {result["files"]} arquivos; boot reescrito excluido: ' +
          ', '.join('/' + path for path in result['excluded_boot_files']))


if __name__ == '__main__':
    main()
