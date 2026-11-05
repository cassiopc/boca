"""Check document files, pagination, text boundaries, links and secret exclusion."""
import csv
import hashlib
import json
from pathlib import Path
import re
import sys
from document_integrity import source_sha256

from pypdf import PdfReader
import pdfplumber

HERE=Path(__file__).resolve().parent
KIT=HERE.parent
sys.path.insert(0,str(KIT))
import manage as kit

manifest=json.loads((HERE/'manifesto-documentacao.json').read_text(encoding='utf-8'))
report={'passed':False,'documents':[],'errors':[]}
for item in manifest['files']:
    source=HERE/item['source']; target=HERE/item['file']
    if source_sha256(source)!=item['source_sha256']:report['errors'].append('Fonte diverge: '+source.name)
    if hashlib.sha256(target.read_bytes()).hexdigest()!=item['pdf_sha256']:report['errors'].append('PDF diverge: '+target.name)
    pdf=PdfReader(target)
    page_lengths=[len(p.extract_text() or '') for p in pdf.pages]
    if min(page_lengths)<80:report['errors'].append('Página vazia ou ilegível: '+target.name)
    with pdfplumber.open(target) as doc:
        for i,page in enumerate(doc.pages,1):
            # Every glyph should stay inside the physical page. The text frame
            # uses 20 mm margins; allow only a small tolerance for font metrics.
            bad=[c for c in page.chars if c['x0']<45 or c['x1']>page.width-45 or c['top']<15 or c['bottom']>page.height-15]
            if bad:report['errors'].append(f'Texto fora das margens: {target.name} p.{i}')
            if '\u25a0' in (page.extract_text() or ''):report['errors'].append(f'Glifo inesperado: {target.name} p.{i}')
    report['documents'].append({'file':item['file'],'pages':len(pdf.pages),'min_page_characters':min(page_lengths)})

for item in manifest.get('screenshots',[]):
    path=HERE/item['file']
    if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest()!=item['sha256']:
        report['errors'].append('Captura ausente ou divergente: '+item['file'])
report['screenshots_checked']=len(manifest.get('screenshots',[]))

for path in HERE.glob('*.md'):
    for url in re.findall(r'\]\(([^)]+)\)',path.read_text(encoding='utf-8')):
        if '://' not in url and not url.startswith('#') and not (path.parent/url.split('#')[0]).exists():
            report['errors'].append('Link ausente em '+path.name+': '+url)
with (HERE/'inventario-pendrives.csv').open(encoding='utf-8-sig',newline='') as f:
    rows=list(csv.DictReader(f,delimiter=';'))
if len(rows)!=22 or len({r['etiqueta'] for r in rows})!=22:report['errors'].append('Inventário precisa de 22 etiquetas únicas')
if any(r['checksum_conferido'] or r['persistencia_conferida'] for r in rows):report['errors'].append('Inventário contém aprovação pré-preenchida')

secrets=kit.current_secrets()
for profile in ('si','tii'):
    for name in ('admission-control.txt','admission-score.txt'):
        path=KIT/'local'/profile/name
        if path.exists():secrets.append(path.read_text().strip())
for path in HERE.rglob('*'):
    if path.is_file() and path.suffix in ('.md','.html','.json','.csv','.pdf'):
        text='\n'.join(p.extract_text() or '' for p in PdfReader(path).pages) if path.suffix=='.pdf' else path.read_text(encoding='utf-8-sig')
        if any(value in text for value in secrets if len(value)>=12):report['errors'].append('Credencial detectada; bloquear entrega: '+path.name)
lock=json.loads((KIT/'dist/server.lock.json').read_text())
if kit.source_hash()!=lock['source_sha256']:report['errors'].append('Código de operação diverge do manifesto do servidor')
report['runtime_source_unchanged']=kit.source_hash()==lock['source_sha256']
report['passed']=not report['errors']
output=KIT/'reports/documentacao-validacao.json'
output.write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(report,ensure_ascii=True,indent=2))
if not report['passed']:sys.exit(1)
