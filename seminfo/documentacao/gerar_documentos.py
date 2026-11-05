"""Generate the printable institutional manuals from their Markdown sources.

Requirements: reportlab, Markdown, beautifulsoup4. Uses Arial/Consolas on Windows
and DejaVu on Linux, with standard PDF fonts as a final fallback.
"""
from __future__ import annotations
import csv
import hashlib
import html
import json
from pathlib import Path
import re
import sys
from document_integrity import source_sha256

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent/'reports/documentation-tools'))
import markdown
from bs4 import BeautifulSoup
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import BaseDocTemplate, Frame, HRFlowable, Image, KeepTogether, PageBreak, PageTemplate, Paragraph, Spacer, Table, TableStyle
from reportlab.platypus.tableofcontents import TableOfContents

OUT = HERE/'output/pdf'
INK, MUTED, GREEN, PALE = '#202b25', '#506156', '#2f663e', '#f0f5ef'
WIDTH = A4[0]-36*mm
FONT, BOLD, ITALIC, MONO = 'Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Courier'
for paths in [('C:/Windows/Fonts/arial.ttf','C:/Windows/Fonts/arialbd.ttf','C:/Windows/Fonts/ariali.ttf','C:/Windows/Fonts/consola.ttf'),
              ('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf','/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf','/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf','/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf')]:
    if all(Path(p).exists() for p in paths):
        for name, path in zip(('Body','BodyBold','BodyItalic','Code'), paths):pdfmetrics.registerFont(TTFont(name,path))
        FONT,BOLD,ITALIC,MONO = 'Body','BodyBold','BodyItalic','Code'
        pdfmetrics.registerFontFamily(FONT,normal=FONT,bold=BOLD,italic=ITALIC,boldItalic=BOLD)
        break
styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='Text',fontName=FONT,fontSize=9.6,leading=13.5,textColor=colors.HexColor(INK),spaceAfter=6,splitLongWords=True))
styles.add(ParagraphStyle(name='TitleIF',parent=styles['Text'],fontName=BOLD,fontSize=25,leading=29,spaceAfter=12))
styles.add(ParagraphStyle(name='ChapterIF',parent=styles['Text'],fontName=BOLD,fontSize=17,leading=21,textColor=colors.HexColor(GREEN),spaceBefore=7,spaceAfter=9,keepWithNext=True))
styles.add(ParagraphStyle(name='SubIF',parent=styles['Text'],fontName=BOLD,fontSize=12,leading=17,spaceBefore=14,spaceAfter=8,keepWithNext=True))
styles.add(ParagraphStyle(name='CellIF',parent=styles['Text'],fontSize=9.1,leading=12,spaceAfter=0))
styles.add(ParagraphStyle(name='CellHeadIF',parent=styles['CellIF'],fontName=BOLD,textColor=colors.HexColor(GREEN)))
styles.add(ParagraphStyle(name='CodeIF',parent=styles['Text'],fontName=MONO,fontSize=8,leading=12,backColor=colors.HexColor('#f1f2ef'),borderPadding=9,spaceBefore=7,spaceAfter=12,wordWrap='CJK'))
styles.add(ParagraphStyle(name='SmallIF',parent=styles['Text'],fontSize=9,leading=13,textColor=colors.HexColor(MUTED)))
styles.add(ParagraphStyle(name='TOCIF',parent=styles['Text'],fontSize=10,leading=18,leftIndent=0,firstLineIndent=0,spaceBefore=5))
styles.add(ParagraphStyle(name='ListIF',parent=styles['Text'],leftIndent=19,firstLineIndent=0,bulletIndent=0,spaceAfter=7))


def inline(element):
    if isinstance(element,str):return html.escape(element)
    contents=''.join(inline(child) for child in element.children)
    if element.name in ('strong','b'):return '<b>'+contents+'</b>'
    if element.name in ('em','i'):return '<i>'+contents+'</i>'
    if element.name=='code':return '<font name="'+MONO+'" size="8.3">'+contents+'</font>'
    if element.name=='br':return '<br/>'
    # Relative links remain readable as titles in the PDF; HTML keeps navigation.
    if element.name=='a' and str(element.get('href','')).startswith(('https://','http://')):
        return '<a color="'+GREEN+'" href="'+html.escape(element['href'],quote=True)+'">'+contents+'</a>'
    return contents


class ManualDoc(BaseDocTemplate):
    def __init__(self,filename,title,chapter_pages):
        super().__init__(str(filename),pagesize=A4,leftMargin=18*mm,rightMargin=18*mm,topMargin=23*mm,bottomMargin=19*mm,title=title,author='IF Goiano - Núcleo de Informática / Organização SEMINFO')
        self.book_title=title;self.chapter_pages=chapter_pages
        self.footer_note='Guia visual | revisão 28/10/2026' if chapter_pages or title=='Roteiro rápido da banca' else 'Guia visual | edição 18/10/2026'
        self.addPageTemplates(PageTemplate(id='main',frames=[Frame(self.leftMargin,self.bottomMargin,self.width,self.height,id='body',leftPadding=0,rightPadding=0,topPadding=0,bottomPadding=0)],onPage=self.decorate))

    def decorate(self,canvas,doc):
        canvas.saveState()
        canvas.setStrokeColor(colors.HexColor('#d2ded1'));canvas.setLineWidth(.6)
        canvas.line(20*mm,A4[1]-16*mm,A4[0]-20*mm,A4[1]-16*mm)
        canvas.setFillColor(colors.HexColor(GREEN));canvas.setFont(BOLD,8)
        canvas.drawString(20*mm,A4[1]-12*mm,'XIX SEMINFO 2026 | IF GOIANO - CAMPUS URUTAÍ')
        canvas.setFont(FONT,8);canvas.setFillColor(colors.HexColor(MUTED))
        canvas.drawString(20*mm,12*mm,self.footer_note)
        canvas.drawRightString(A4[0]-20*mm,12*mm,f'Página {doc.page}')
        canvas.restoreState()

    def afterFlowable(self,flowable):
        if isinstance(flowable,Paragraph) and flowable.style.name=='ChapterIF' and self.chapter_pages:
            text=flowable.getPlainText();key='chapter-'+hashlib.sha256(text.encode()).hexdigest()[:12]
            self.canv.bookmarkPage(key)
            self.canv.addOutlineEntry(text,key,level=0,closed=False)
            self.notify('TOCEntry',(0,text,self.page,key))


def table_flow(element,compact=False):
    rows=[]
    for row in element.find_all('tr'):
        cells=row.find_all(['th','td'],recursive=False)
        rows.append([Paragraph(inline(cell),styles['CellHeadIF' if cell.name=='th' else 'CellIF']) for cell in cells])
    count=max(len(row) for row in rows)
    if count==2:
        first=element.find('tr').get_text(' ',strip=True).lower()
        ratio=.36 if 'comando' not in first else .31
        widths=[WIDTH*ratio,WIDTH*(1-ratio)]
    elif count==3:widths=[WIDTH*.27,WIDTH*.43,WIDTH*.30]
    else:widths=[WIDTH/count]*count
    table=Table(rows,colWidths=widths,repeatRows=1,hAlign='LEFT',spaceBefore=7,spaceAfter=13)
    padding=5 if compact else 6
    table.setStyle(TableStyle([('VALIGN',(0,0),(-1,-1),'TOP'),('BACKGROUND',(0,0),(-1,0),colors.HexColor(PALE)),('ROWBACKGROUNDS',(0,1),(-1,-1),[colors.white,colors.HexColor('#fafbf9')]),('LINEBELOW',(0,0),(-1,0),.6,colors.HexColor('#afc6b0')),('LINEBELOW',(0,1),(-1,-1),.3,colors.HexColor('#dce4d9')),('LEFTPADDING',(0,0),(-1,-1),8),('RIGHTPADDING',(0,0),(-1,-1),8),('TOPPADDING',(0,0),(-1,-1),padding),('BOTTOMPADDING',(0,0),(-1,-1),padding)]))
    return table


HTML_CSS='''body{font:17px/1.7 system-ui,sans-serif;color:#202b25;background:#f4f6f1;margin:0}main{max-width:1050px;margin:24px auto;background:white;padding:44px 56px;border:1px solid #dbe4d7}header{border-bottom:2px solid #397449;padding-bottom:22px;margin-bottom:30px}header img{width:290px;max-width:100%}h1{font-size:36px;line-height:1.25}h2{color:#2f663e;border-top:1px solid #d9e3d5;padding-top:26px;margin-top:38px;line-height:1.4}h3{font-size:21px;margin-top:30px}a{color:#225e35;text-underline-offset:3px}table{width:100%;border-collapse:collapse;font-size:15px;margin:20px 0}th,td{padding:12px;border:1px solid #d7e1d3;text-align:left;vertical-align:top;overflow-wrap:anywhere}th{background:#eff5eb}tr:nth-child(even){background:#fafcf8}pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#f0f3ed;padding:18px;border-left:3px solid #537a4d;font-size:14px}code{font-family:Consolas,monospace;font-size:.9em;overflow-wrap:anywhere}li{margin:9px 0}nav{padding:18px;background:#eef4e9}nav ul{padding-left:22px}footer{border-top:1px solid #d9e3d5;margin-top:35px;padding-top:20px;font-size:14px;color:#506156}@media(max-width:650px){main{padding:22px;margin:0;border:0}h1{font-size:29px}table{font-size:12px}td,th{padding:8px}}@media print{body{background:white}main{margin:0;border:0;padding:0;max-width:none}nav{display:none}h2{break-before:page}pre,table{font-size:10px}tr{break-inside:avoid}a{color:inherit}}'''


def generate(name,title,full=False):
    source=(HERE/(name+'.md')).read_text(encoding='utf-8')
    rendered=markdown.markdown(source,extensions=['tables','fenced_code','sane_lists'])
    soup=BeautifulSoup(rendered,'html.parser')
    navigation=[]
    for index,heading in enumerate(soup.find_all('h2'),1):
        heading['id']='capitulo-'+str(index)
        navigation.append('<li><a href="#'+heading['id']+'">'+html.escape(heading.get_text())+'</a></li>')
    footer_note=('Texto revisado em 28/10/2026. Capturas históricas de 18/10; valores pendentes continuam para decisão da organização.' if name in ('manual-if','roteiro-banca') else 'Edição de 18/10/2026. Capturas reais da mesa; registros virtuais identificados nas legendas. Campos pendentes continuam para decisão da organização.')
    page='<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>'+html.escape(title)+'</title><style>'+HTML_CSS+'main img{max-width:100%;height:auto}.page-break{break-before:page}</style><main><header><img src="recursos/if-goiano-urutai.png" alt="Instituto Federal Goiano - Campus Urutaí"><p>XIX SEMINFO 2026 | Documentação da organização</p></header><nav aria-label="Índice"><strong>Neste documento</strong><ul>'+''.join(navigation)+'</ul></nav>'+str(soup)+'<footer>'+footer_note+'</footer></main></html>'
    (HERE/(name+'.html')).write_text(page,encoding='utf-8')
    story=[]
    chapter_seen=False
    for element in soup.children:
        if not getattr(element,'name',None):continue
        tag=element.name
        if tag=='h1':
            story.append(Paragraph(inline(element),styles['TitleIF']))
        elif tag=='h2':
            if full and chapter_seen:story.append(PageBreak())
            story.append(Paragraph(inline(element),styles['ChapterIF']));chapter_seen=True
        elif tag=='h3':story.append(Paragraph(inline(element),styles['SubIF']))
        elif tag=='p':
            picture=element.find('img')
            if picture:
                asset=(HERE/picture['src']).resolve()
                if not asset.is_relative_to(HERE.resolve()):raise ValueError('Imagem fora da documentação')
                from PIL import Image as PILImage
                with PILImage.open(asset) as bitmap:w,h=bitmap.size
                scale=min(WIDTH/w,96*mm/h)
                story.append(Image(str(asset),width=w*scale,height=h*scale,hAlign='CENTER'))
                caption=picture.get('alt','').strip()
                if caption:story.append(Paragraph(html.escape(caption),styles['SmallIF']))
                story.append(Spacer(1,2*mm))
            else:story.append(Paragraph(inline(element),styles['Text']))
        elif tag in ('ol','ul'):
            for i,item in enumerate(element.find_all('li',recursive=False),int(element.get('start',1))):
                story.append(Paragraph(inline(item),styles['ListIF'],bulletText=str(i)+'.' if tag=='ol' else '-'))
        elif tag=='table':story.append(table_flow(element,compact=name=='ficha-homologacao'))
        elif tag=='pre':
            code=html.escape(element.get_text().rstrip()).replace('\n','<br/>')
            story.append(Paragraph(code,styles['CodeIF']))
        elif tag=='div' and 'page-break' in element.get('class',[]):story.append(PageBreak())
        elif tag=='hr':story.append(HRFlowable(width='100%',thickness=.4,color=colors.HexColor('#9caaa0'),spaceBefore=6*mm,spaceAfter=4*mm))
    OUT.mkdir(parents=True,exist_ok=True)
    target=OUT/(name+'.pdf');doc=ManualDoc(target,title,full)
    doc.multiBuild(story)
    return {'file':target.relative_to(HERE).as_posix(),'source':name+'.md','source_sha256':source_sha256(HERE/(name+'.md')),'pdf_sha256':hashlib.sha256(target.read_bytes()).hexdigest()}


if __name__=='__main__':
    files=[generate('manual-if','Guia visual de configuração e operação',True),generate('roteiro-banca','Roteiro rápido da banca'),generate('ficha-homologacao','Ficha de homologação por laboratório')]
    screenshots=[{'file':p.relative_to(HERE).as_posix(),'sha256':hashlib.sha256(p.read_bytes()).hexdigest()} for p in sorted((HERE/'recursos/telas-2026-10-18').rglob('*')) if p.is_file() and p.suffix in ('.jpg','.png')]
    (HERE/'manifesto-documentacao.json').write_text(json.dumps({'edition':'2026-10-18','event':'2026-10-29','interface':'Central SEMINFO completa e preparação; BOCA nativo','contains_real_credentials':False,'screenshots':screenshots,'files':files},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print('Gerados três PDFs e três HTMLs a partir das fontes Markdown.')
