# Caramel Coders BOCA - SEMINFO 2026

Kit das duas maratonas de programação de **29/10/2026**, com servidores BOCA independentes e Linux personalizado para os computadores das equipes.

| Categoria | Laboratório | Perfil | Contas das equipes |
|---|---|---|---|
| Sistemas de Informação | Lab 3 | `si` | `si01` a `si10` |
| Médio/Técnico em Informática para a Internet | Lab 4 | `tii` | `tii01` a `tii10` |

Cada sala usa sua própria mesa Windows com Docker Desktop. Questões, horários, autorizações e placares são separados. Os participantes iniciam o Linux pelo pendrive da categoria correspondente.

## Siga nesta ordem

Comece pelo **[guia visual com prints (PDF, 15 páginas)](seminfo/documentacao/output/pdf/manual-if.pdf)**. A tabela abaixo indica a ordem de execução e as páginas a consultar.

| Ordem | O que fazer | Instruções |
|---|---|---|
| 1 | **TI: preparar uma mesa por laboratório.** Copiar o kit e a identidade correta, conferir Python/Docker, carregar as imagens offline e criar os atalhos. | Guia visual, **p. 13**. |
| 2 | **Configurar cada categoria.** Informar a rede, decidir início/duração/congelamento, criar ou importar as questões e testar as referências. | Guia visual, **p. 1-6**. |
| 3 | **Baixar e gravar os pendrives.** Escolher a ISO SI ou TII, conferir o SHA256 e gravar um piloto por variante com persistência. | [Downloads abaixo](#isos-personalizadas---download) e guia visual, **p. 14**. |
| 4 | **Homologar os laboratórios.** Testar USB físico, persistência, rede, juiz, projetor e questões; registrar os seis ensaios na central. | Guia visual, **p. 7**, e [ficha de homologação (PDF, 2 páginas)](seminfo/documentacao/output/pdf/ficha-homologacao.pdf). |
| 5 | **Abrir a maratona.** Iniciar a mesa, conferir a categoria, aprovar os computadores e ativar após concluir as verificações. | Guia visual, **p. 8-9**, e [roteiro rápido da banca (PDF, 3 páginas)](seminfo/documentacao/output/pdf/roteiro-banca.pdf). |
| 6 | **Acompanhar e projetar.** Usar o placar da categoria; acompanhar submissões e dúvidas pelo BOCA. | Guia visual, **p. 10 e 12**. |
| 7 | **Encerrar e preservar.** Concluir julgamentos, conferir/publicar o resultado e criar backup com restauração verificada; guardar outra cópia. | Guia visual, **p. 11 e 15**. |

Os horários e os pacotes reais são decididos pela organização na interface. As provas permanecem inativas até a revisão. A liberação do evento exige a homologação presencial de cada laboratório.

## Qual atalho abrir

| Atalho | Quando usar |
|---|---|
| **Configurar Provas SEMINFO** | Preparar horários e questões localmente, inclusive antes de confirmar os IPs. Não exige elevação do Windows. |
| **Central SEMINFO** | Operar a sala, instalar firewall/início no logon, aprovar equipes, ativar, projetar e fazer backup. Usa a conta da organização com a permissão do Windows. |

No topo, confira sempre **Esta banca gerencia**. Na mesma máquina de preparação, feche o BOCA de uma categoria antes de abrir o da outra. No evento, cada categoria funciona em sua própria mesa.

**Abas da central:** Preparação da sala → rede e homologação; Prova e questões → tempos, pacotes e referências; Operação da maratona → equipes e placares; Backup e recuperação → cópia e restauração.

Na Central, selecione a categoria em **Esta banca gerencia** antes de editar questões. Em **Prova e questões**, inicie o BOCA de preparação, entre como `admin` e use **Criar pacote de questão** ou **Importar ZIP ou editar questões**. O Builder gera o enunciado em PDF com o banner da categoria e um ZIP; importe esse ZIP na lista de questões da mesma banca. Na tela inicial, **Preparar questões** leva à mesma aba. Se o kit foi atualizado, reconstrua as imagens com `python seminfo/manage.py build-server` antes de abrir o BOCA de preparação.

## ISOs personalizadas - download

**Imagens atualizadas em 28/10:** as ISOs locais foram reconstruídas e aprovadas na inspeção estática, incluindo initramfs e identidade da banca. A validação de boot, persistência e rede em pendrive físico deve ser feita no IF. Elas não estão incluídas no Git e ainda não foram publicadas para download. Os caminhos e hashes são:

| Categoria | ISO atualizada local | SHA-256 |
|---|---|---|
| SI / Lab 3 | `seminfo/dist/clients-refresh-20261028/si/ml-seminfo-lab3-si-atualizada.iso` | `5493a6843f8f29bbefd1b662ad578ba30889ee3bc7e7a2a619be7d484ca89126` |
| TII / Lab 4 | `seminfo/dist/clients-refresh-20261028/tii/ml-seminfo-lab4-tii-atualizada.iso` | `998863a82665dc52a7613a80a528a61c049f692d5efc1a5d3c6f711c376332f0` |

O link e os hashes na seção seguinte identificam o **pacote anterior**, de 17/10, que não contém as correções do PR #1. Não o confunda com as imagens atualizadas.


**[Pacote anterior das ISOs SI e TII pelo SwissTransfer — não usar como versão atual](https://www.swisstransfer.com/dl/01a151eb-2850-70bb-8a64-f23753f527b1)**

Se o pacote estiver em ZIP, extraia antes de conferir o SHA256 e gravar o pendrive. Escolha a ISO correspondente ao laboratório:

| Usar em | Arquivo |
|---|---|
| **Lab 3 / SI** | `ml-seminfo-lab3-si.iso` |
| **Lab 4 / TII** | `ml-seminfo-lab4-tii.iso` |

O link do SwissTransfer é temporário. Se estiver expirado, solicite um novo link à organização.

Cada ISO tem **6.814.892.032 bytes, aproximadamente 6,81 GB**. O pacote anterior inclui os fundos Claro e Espresso e ferramentas da maratona; a associação fixa de TII ao Claro e SI ao Espresso exige as imagens atualizadas. Cada variante reconhece somente a identidade da banca correspondente.

### Onde estão os arquivos no kit local

- **SI:** `seminfo/dist/clients/si/ml-seminfo-lab3-si.iso`
- **TII:** `seminfo/dist/clients/tii/ml-seminfo-lab4-tii.iso`

Ao distribuir as imagens, inclua também o arquivo **`.iso.sha256`** que fica ao lado de cada ISO. Os hashes para conferência também estão abaixo. Os arquivos grandes em `seminfo/dist` são gerados localmente e não são versionados no Git.

### Conferir o download

No PowerShell, execute na pasta onde baixou os arquivos:

```powershell
Get-FileHash .\ml-seminfo-lab3-si.iso -Algorithm SHA256
Get-FileHash .\ml-seminfo-lab4-tii.iso -Algorithm SHA256
```

O resultado deve ser exatamente o da variante correspondente:

```text
SI / ml-seminfo-lab3-si.iso
279f7e7241d2b4e9d3001fd71c52f817fd3b9bd648ea1e79cf3f5acf417da93c

TII / ml-seminfo-lab4-tii.iso
b71deb3f4fe6e19af416cd8299c860ec4e796af223f12fd6e4173b17c88bcff7
```

**Gravação:** use o procedimento da **página 14 do guia** e o script `seminfo/client/write-usb.sh`, em Linux, para criar também a persistência. Primeiro homologue um pendrive físico por variante; depois prepare dez mídias e uma reserva por sala. A ISO pode iniciar bloqueada enquanto o IP da banca não for informado na janela de autorização.

## Instruções complementares

| Documento | Usar para |
|---|---|
| [Índice dos documentos e fontes editáveis](seminfo/documentacao/LEIA-PRIMEIRO.md) | Encontrar PDFs, HTMLs, prints e como atualizar os manuais. |
| [Guia da organização](seminfo/guia-organizacao.md) | Consultar o fluxo da central em texto. |
| [Configuração de provas e questões](seminfo/CONFIGURACAO-PROVAS-2026-10-18.md) | Entender tempos por categoria, importação/edição e validação isolada de referências. |
| [Roteiro técnico do evento](seminfo/runbook.md) | Consultar a operação e os comandos técnicos. |
| [Construção e gravação do Linux](seminfo/imagem-maratona.md) | Reconstruir a distro, gravar os USBs e conferir persistência. |
| [Autorização e placares](seminfo/admissao-e-placares.md) | Consultar o funcionamento da rede, aprovação e projeção. |
| [Recuperação após formatação](seminfo/RECUPERAR-APOS-FORMATACAO.md) | Recuperar o kit, as identidades e os serviços. |

Para consultar os manuais no navegador com as imagens, abra [manual visual em HTML](seminfo/documentacao/manual-if.html), [roteiro em HTML](seminfo/documentacao/roteiro-banca.html) ou [ficha em HTML](seminfo/documentacao/ficha-homologacao.html) na cópia local do projeto.

## Contas, acesso e material privado

- Consulte as contas pelo botão **Consultar contas e senhas…**, fora da projeção. Equipe **1** usa `si01` ou `tii01`; o identificador interno de usuário do BOCA é outro.
- As equipes usam `http://IP-DA-MESA:8000/boca/`, após aprovação. O endereço administrativo `http://127.0.0.1:18000/boca/` funciona somente na mesa.
- Para os downloads da equipe, envie somente as ISOs e seus checksums. **Não publique** `seminfo/local`, backups privados, chaves, senhas ou o ZIP completo de recuperação.
- Preserve a identidade privada de cada banca: os pendrives precisam reconhecer o certificado correspondente.
- Os scripts antigos `boca_deploy.ps1`, `boca_deploy.sh` e `setup_boca_profiles.ps1` pertencem ao deploy anterior. Para a SEMINFO, siga o guia e o kit `seminfo/manage.py`; não misture os dois procedimentos.
