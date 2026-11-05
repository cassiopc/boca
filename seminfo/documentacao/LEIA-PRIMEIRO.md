# Documentação visual SEMINFO - 18/10/2026

- **output/pdf/manual-if.pdf:** guia de configuração e operação, 15 páginas. Um assunto por página; imagens e tabelas de campo/botão.
- **output/pdf/roteiro-banca.pdf:** rotina da banca, 3 páginas, para deixar junto à mesa.
- **output/pdf/ficha-homologacao.pdf:** formulário de impressão por sala, 2 páginas. Nenhum ensaio vem aprovado.

Os textos do manual e do roteiro rápido foram atualizados em 28/10 para os rótulos atuais do Builder e do BOCA. As capturas remanescentes são históricas, de 18/10, e podem diferir da interface.

As fontes editáveis são `manual-if.md`, `roteiro-banca.md` e `ficha-homologacao.md`. Os arquivos HTML permitem consultar as mesmas instruções no navegador com as imagens locais.

## Capturas

`recursos/telas-2026-10-18` contém os prints capturados pelo Computer Use do navegador integrado em 18/10 e os recortes usados no PDF. Os JSONs guardam apenas posição e título dos elementos para enquadramento. Nenhuma senha ou chave privada foi incluída.

As capturas mostram a situação real de preparação: rede e tempos pendentes, prova inativa e nenhuma questão importada. As confirmações foram abertas para fotografia e canceladas; não ativaram prova, não revelaram senhas e não registraram homologações.

As imagens com **ensaio-virtual** e **ensaio-sintetico** no nome são registros anteriores de testes, identificados como tal. A imagem de cliente Linux usada no manual não comprova homologação em USB físico. Somente as imagens citadas nas fontes entram nos PDFs; os prints completos ficam disponíveis para consulta.

## Atualizar os documentos

Edite o Markdown. Preserve os caminhos relativos das imagens. No Python usado para gerar os documentos, instale `reportlab`, `Markdown`, `beautifulsoup4`, `Pillow`, `pypdf` e `pdfplumber`.

Na raiz do projeto:

```powershell
python seminfo/documentacao/gerar_documentos.py
python seminfo/documentacao/validar_documentacao.py
```

A geração atualiza os PDFs, os HTMLs e o manifesto de hashes. A validação confere arquivos, imagens, margens, links e ausência de credenciais conhecidas. Para conferir alterações visuais, renderize os PDFs com Poppler e examine as páginas.

Esta atualização é apenas de documentação. As ISOs, as identidades, as configurações de prova e o código de operação foram preservados. A versão anterior dos documentos foi guardada localmente em `output/anteriores`.
