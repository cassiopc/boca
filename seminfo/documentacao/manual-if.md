# Guia visual SEMINFO 2026

Caramel Contest | IF Goiano - Campus Urutaí | Evento: 29/10/2026 | Texto revisado em 28/10/2026.

## 1. Comece aqui: atalhos, categoria e abas

![Tela 01 - mapa da central](recursos/telas-2026-10-18/recortes/01-mapa.png)

**1.** Abra Docker Desktop. **2.** Abra o atalho adequado. **3.** Confira a categoria no topo antes de qualquer ação.

| Controle | Para que serve / o que fazer |
|---|---|
| Configurar Provas SEMINFO | Preparar horários e questões localmente. Não pede elevação do Windows. |
| Central SEMINFO | Operação completa, firewall, aprovação, ativação e backup. Aceite a permissão do Windows na conta da organização. |
| Esta banca gerencia | SI / Lab 3 ou TII / Lab 4. Cada mesa administra sua categoria. Trocar a seleção não inicia nem para serviços. |
| Preparação da sala | Rede, verificação, pareamento e seis homologações. Veja páginas 2 e 7. |
| Prova e questões | Horários, criação/importação e referências. Veja páginas 3 a 6. |
| Operação da maratona | Abrir a sala, receber equipes e acompanhar. Veja páginas 8 a 10. |
| Backup e recuperação | Copiar e verificar a recuperação. Veja página 11. |

**Mapa:** BOCA: p. 12. Instalação pela TI: p. 13. Pendrives: p. 14. Falhas: p. 15.

*Capturas de 18/10; textos revisados em 28/10. A tela do cliente é de ensaio virtual. As imagens não contêm senhas.*

## 2. Preparação da sala: kit e rede

![Tela 02 - dados de rede](recursos/telas-2026-10-18/recortes/02-rede.png)

**Caminho:** Preparação da sala. Use somente os endereços confirmados pela TI.

| Campo ou botão | Preencher / executar |
|---|---|
| Conferir pasta privada | Confere o kit e preserva as chaves/senhas que os pendrives reconhecem. |
| IP da mesa da banca | IPv4 reservado do computador do professor. Não preencher com 0.0.0.0. |
| Sub-rede da sala | Rede com prefixo CIDR fornecida pela TI. Não copiar um exemplo como endereço real. |
| IPs de outras mesas centrais ou projetores | Somente acessos que venham de outra sub-rede; separar por vírgulas. Se não houver, deixar vazio. |
| Salvar rede | Salva os campos desta categoria. Exige banca parada e prova inativa/sem submissões. |
| Executar verificação automática | Confere contas, linguagens e executáveis do juiz. Aguarde a mensagem de aprovação. |
| Instalar início automático e firewall nesta mesa… | A TI instala após confirmar a rede; depois testa o logon e o firewall reais. |

**Se algo ficar pendente:** salve apenas dados conhecidos. Você ainda pode preparar horários e questões; receber equipes e ativar continuam bloqueados.

## 3. Prova e questões: decidir os horários

![Tela 03 - formulário dos horários](recursos/telas-2026-10-18/recortes/03-horarios.png)

| Campo ou botão | O que informar / resultado |
|---|---|
| Início em 29/10/2026 | Data e hora escolhidas para esta categoria, no horário de Brasília. |
| Duração em minutos | Duração total da prova; inteiro maior que zero. Não há duração presumida. |
| Congelar quantos minutos antes do fim? | Quantos minutos finais terão placar congelado. Zero = sem congelamento. Deve ser menor que a duração. |
| Prévia | Confira o horário calculado de término e de congelamento antes de salvar. |
| Estes são os horários definitivos… | Marque apenas quando a organização confirmar os valores. Desmarcado permite rascunho. |
| Salvar horários | Guarda os tempos desta categoria sem alterar a rede nem a outra categoria. |
| Preparar prova… | Aplica tempos e contas no BOCA; mantém a prova inativa. Leia e digite a confirmação solicitada. |

**Exemplo de cálculo, não configuração do evento:** início 13h, duração 180 e congelamento 30 = término 16h e placar congelado a partir de 15h30.

## 4. Criar um pacote de questão

**Caminho:** Prova e questões → Abrir BOCA de preparação → aguardar “BOCA pronto” → Criar pacote de questão. Se aparecer login, entre como admin; o editor será aberto.

| Campo ou botão | O que informar / fazer |
|---|---|
| Nome completo | Título da questão. |
| Basename / arquivo esperado | Nome da classe Java; por exemplo, A para A.java. |
| Tempo / Repetições | Limite em segundos e número de execuções. Confira os limites com a banca. |
| Memória e saída / Linguagens | Informe os limites e selecione as linguagens permitidas. |
| Enunciado | Escolha o template LaTeX, um arquivo pronto ou texto manual. No template, preencha título, descrição, entrada, saída e observações. |
| Casos de teste | Informe entrada e saída esperada para cada caso. Os públicos aparecem no PDF; os opacos ficam apenas no pacote de julgamento. Use Adicionar caso para incluir outros. |
| Pré-visualizar PDF / Gerar ZIP | Confira o enunciado e gere o pacote. Limpar restaura o formulário. |

**Depois:** guarde o ZIP e importe-o conforme a próxima página. Gerar o ZIP não cadastra a questão automaticamente na prova.

## 5. Importar ou editar as questões

**Caminho:** Prova e questões → Importar ZIP ou editar questões. Use os pacotes SI no SI e os pacotes TII no TII.

| Campo ou botão | O que informar / fazer |
|---|---|
| Número | Número da questão: 1, 2, 3… Mantenha a sequência. O registro 0 é interno e não é uma questão da prova. |
| Nome curto | Letra sem espaços: A, B, C… |
| Pacote da questão (ZIP) | ZIP recebido da organização ou gerado no Builder. |
| Nome da cor / Cor | Nome e código RGB hexadecimal. |
| Julgamento automático | Selecione Todas as respostas, Personalizado ou Nenhuma, tanto ao cadastrar quanto ao editar uma questão. |
| Enviar / Limpar | Enviar importa o pacote; Limpar restaura os campos. |
| Linha da questão | Confira título, nome do arquivo, enunciado, pacote e cor. Para trocar enunciado, casos ou limites, reimporte o ZIP usando o mesmo número. |

**Atenção ao clique no número da questão:** ele inicia a exclusão; não serve para abrir o editor. Não altere uma prova em andamento. Depois de importar, volte à central e atualize o diagnóstico.

## 6. Testar as soluções de referência

![Tela 06 - referências por questão](recursos/telas-2026-10-18/recortes/06-referencias.png)

**1.** Importe a questão. **2.** Atualize o diagnóstico. **3.** Selecione a questão. **4.** Escolha as quatro referências e clique em **Testar soluções no juiz**.

| Campo / resultado | Como usar |
|---|---|
| Questão desta categoria | Selecione o pacote que acabou de revisar. Sem questões importadas, os controles ficam desabilitados. |
| Soluções em C, C++20, Java e Python 3 | Uma por linguagem: .c, .cpp, .java e .py/.py3. UTF-8, até 128 KiB por arquivo. |
| Arquivo Java | O nome da classe/arquivo deve corresponder ao Basename do pacote. |
| Testar soluções no juiz | Aguarde: o kit usa o pacote real num BOCA descartável. Não cria submissões nem pontuação na prova oficial. |
| Aprovado / parcial / reprovado | Quatro linguagens aceitas concluem a validação. Menos linguagens = parcial. Em falha, abra os detalhes, corrija e envie novamente. |
| Validação desatualizada | Revalide se trocar o pacote ou a versão do kit. As referências e relatórios entram no backup privado. |

**Antes de registrar Questões revisadas:** todas as questões precisam das quatro referências aceitas. Confira também enunciado, cobertura dos casos e limites; um AC sozinho não garante a qualidade da questão.

## 7. Registrar as seis homologações

**Caminho:** Preparação da sala → Homologar os testes presenciais → Registrar ensaio executado…

![Tela 07 - registro do ensaio](recursos/telas-2026-10-18/recortes/07-homologacao.png)

| Ensaio | O que comprovar |
|---|---|
| Pendrive e linguagens | USB no PC real, fundo estável, sessão sem sudo; C, C++20, Java 17 e Python 3. |
| Trabalhos preservados | Arquivo em ~/trabalhos continua após desligar e iniciar pelo mesmo USB. |
| Rede e autorização | Pedido, código, aprovação/revogação, expiração, bloqueio de internet/outro BOCA/vizinhos; IPv4/IPv6, firewall e logon reais. |
| Projetores e placares | Categoria correta, área estendida, congelamento/revelação, empates e igualdade com BOCA; independência da outra sala. |
| Juiz e dez equipes | AC/WA/CE/TLE, quatro linguagens e fila de dez equipes num ensaio separado da prova oficial. |
| Questões revisadas | Pacotes reais da categoria, referências aprovadas e revisão de enunciados, casos e limites. |

**No formulário:** informe responsável, computador e resultado observado. Marque “Executei…” somente após aprovação real e salve. A ficha impressa complementa o registro. Mudanças de configuração/versão exigem nova conferência.

## 8. Abrir a sala e ativar a prova

![Tela 08 - ações da operação](recursos/telas-2026-10-18/recortes/08-operacao.png)

| Controle | Como usar |
|---|---|
| Atualizar diagnóstico | Reconsulta Docker, serviços, horários e prova. “Equipes sem acesso” com IP pendente ainda não é sala pronta. |
| Iniciar mesa | Inicia BOCA, juiz e autorização. Aguarde “Banca funcionando”. Iniciar serviços não ativa a prova. |
| Parar mesa… | Interrompe o acesso; pede confirmação do laboratório. Use somente quando a parada estiver autorizada. |
| Abrir solicitações da sala | Abre a aprovação dos computadores desta categoria; veja página 9. |
| Consultar contas e senhas… | Com projeção desligada, confirme MOSTRAR SENHAS. A janela se oculta em um minuto; entregue só a conta daquela equipe. |
| Revisar e ativar prova… | Confira sala, rede, horários definitivos, questões, verificação e seis ensaios. Digite ATIVAR LAB 3 ou ATIVAR LAB 4 conforme a tela. |

**Se bloquear:** corrija a pendência exibida. Não ative diretamente pelo BOCA para contornar a revisão. A abertura segue os horários configurados. Mantenha a sessão da organização e o Docker ligados.

**Contas:** equipe 1 = si01 ou tii01; o número interno de usuário no BOCA é outro. A conta admin é da banca e a conta placar é de consulta.

## 9. Receber e autorizar cada equipe

![Tela 09 - cliente Linux em ensaio virtual](recursos/telas-2026-10-18/16-cliente-ensaio-virtual.png)

*Tela do cliente em ensaio virtual anterior. O IP 10.0.2.15 mostrado é do ensaio; use o IP real da sala. Não comprova homologação física.*

| Na máquina da equipe | Na mesa da banca |
|---|---|
| Inicie pelo USB correto: SI ou TII. | Confira sala, equipe, mídia e computador. |
| Confira Meu IP; informe o IP da mesa e Número da equipe. | Abra Abrir solicitações da sala → Atualizar solicitações. |
| Clique em Solicitar acesso à mesa da banca. | Compare equipe, IP observado e dispositivo com o computador presencial. |
| Mostre à banca o código de oito caracteres. | Digite o código no pedido correspondente → Conferir código e liberar. |
| Aguarde Acesso liberado → Abrir BOCA. | Entregue a conta si01…si10 ou tii01…tii10 da equipe. |
| Salve os trabalhos em ~/trabalhos. | Em troca de PC/IP ou reinício, Revogar acesso… antigo e confira o novo pedido/código. |

**Estados:** pendente aguarda conferência; liberado permite só o BOCA da sala; revogado/expirado exige novo pedido. Não retire o USB com o Linux ligado. A outra banca aprova suas próprias equipes.

## 10. Parear, projetar e publicar o placar

![Tela 10 - pareamento das salas](recursos/telas-2026-10-18/recortes/10-pareamento.png)

| Controle | O que fazer |
|---|---|
| Adicionar esta sala à central | Em Preparação da sala, adiciona a banca da categoria escolhida. |
| Exportar pareamento desta sala… | Gera o arquivo privado de consulta do placar. Entregue apenas à organização. |
| Adicionar arquivo de outra sala | Importa o descritor da outra banca; concede consulta do placar, não aprovação de equipes. |
| Preparar central de salas / Abrir central de salas | Em Operação, prepare o serviço e depois abra o painel. |
| Graduação / SI · Lab 3 ou Médio/Técnico / TII · Lab 4 | Abra o placar correto, mova para o projetor em área estendida e use F11. Mantenha administração no monitor. |
| Publicar resultado final… | Após término e fila concluída, confira o resultado e digite PUBLICAR LAB 3 ou PUBLICAR LAB 4. Cada categoria publica independentemente. |

**Congelamento:** oculta o progresso final no placar público; a banca acompanha pelo BOCA. Não existe classificação única entre os níveis. O placar oficial do BOCA resolve divergências.

**Animeitor - TI:** preparar uma vez com `python seminfo/manage.py animeitor`. Após o final conferido, `python seminfo/manage.py reveal si` (ou `tii`). Espaço avança; q sai. Ensaie antes de projetar.

## 11. Criar backup e recuperar

![Tela 11 - backups da categoria](recursos/telas-2026-10-18/recortes/11-backup.png)

| Campo ou botão | O que fazer / conferir |
|---|---|
| Criar backup e verificar restauração | Combine a breve pausa de web/juiz. Aguarde a cópia e o teste de restauração num banco separado. |
| Backups desta sala | Confira a categoria e a data. “Restauração isolada já conferida” indica que o teste foi aprovado. |
| Verificar novamente | Repete a conferência da cópia em banco isolado; não substitui a prova. |
| Restaurar em destino vazio… | Com apoio da TI, use instalação sem prova. Confirme RESTAURAR LAB 3 ou LAB 4 apenas no destino correto. A central recusa sobrescrever uma prova existente. |

**Guardar:** copie a pasta inteira de `seminfo/backups`, incluindo manifesto, referências, credenciais e identidade, para outra mídia protegida. Não copie somente database.dump. Preserve também o kit e a pasta privada correspondente à ISO.

**Após recuperar:** confira IP, relógio, BOCA, juiz, aprovação e placar antes de retomar. Avise a coordenação e registre a ocorrência. A outra categoria segue independente.

## 12. BOCA: para que serve cada seção

| Seção | Uso na rotina |
|---|---|
| Submissões | Confira número, equipe, tempo, questão, linguagem, estado, juiz e resposta. Clique no cabeçalho para ordenar. |
| Placar / Dúvidas | Consulte a classificação e responda às dúvidas conforme as regras da coordenação. |
| Usuários | Gerencie contas e dados das equipes. Entregue senhas pela central reservada. |
| Questões / Criar questão / Linguagens | Importe ou crie pacotes e confira as linguagens antes da prova. |
| Respostas | Tipos de resultado usados pelo juiz; não altere durante a prova. |
| Ajuda e impressão / Local | Pedidos de ajuda e impressão e configuração da sede, para a banca e TI. |
| Competição / Diversos | Parâmetros e ações administrativas. Defina tempos e ative pela Central SEMINFO. |
| Registros / Relatórios / Backups | Auditoria e arquivos do BOCA. Para recuperação completa, use Backup e recuperação da central. |
| Opções / Sair | Preferências e saída da conta. Não feche a sessão Windows da organização durante a prova. |

**Resultados:** YES/AC = aceito; WA = resposta errada; CE = erro de compilação; RE = erro de execução; TLE = tempo excedido; PE = apresentação; NMI = nome/classe divergente; CS = contatar a banca. A opção de julgamento automático na tela de questões determina quais resultados são tratados automaticamente.

## 13. TI: instalar uma mesa a partir do kit

**Uma mesa Windows por categoria.** Use Python 3.12, Docker Desktop com containers Linux e a conta da organização. No Lab 4, troque `si` por `tii`. Execute os comandos na raiz do projeto.

| Passo | Fazer / resultado esperado |
|---|---|
| 1. Copiar | Kit completo para pasta estável; preservar `seminfo/local/si` ou `tii`, com identidade correspondente aos USBs. |
| 2. Conferir | Compare SHA256 do TAR com `seminfo/dist/server-bundle.json`. Não carregue pacote divergente. |
| 3. Carregar e conferir | Execute o bloco abaixo. init deve preservar a identidade recebida; pare se a pasta privada estiver incompleta. |
| 4. Abrir os atalhos | Configure rede (p. 2), tempos (p. 3), questões/referências (p. 4-6) e homologue (p. 7). |
| 5. Preparar banco | Em instalação nova, use stage abaixo. Deploy antigo exige migrate com backup/restauração conferidos. |
| 6. Firewall/logon | Na central completa, Instalar início automático e firewall nesta mesa…; teste na mesa real. Inicia no logon do organizador e aguarda Docker. |

```powershell
python --version
docker version
docker compose version
Get-FileHash seminfo/dist/seminfo-server-images.tar -Algorithm SHA256
docker load -i seminfo/dist/seminfo-server-images.tar
python seminfo/manage.py init si
powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/install-organizer-shortcut.ps1
python seminfo/manage.py stage si
```

**Portas:** equipes `http://IP-DA-MESA:8000/boca/`; autorização TLS 8443; BOCA local 18000; aprovações locais 17860; placares locais 17861; central completa 17862; preparação local 17864. Acessos administrativos são somente na mesa.

**Não reconstruir na instalação:** `build-server` é para uma nova versão. Nunca gere outra identidade e continue usando USBs que reconhecem a anterior.

## 14. TI: gravar, testar e distribuir USBs

**Necessário:** PC amd64, 8 GB de RAM ou mais, boot USB e modo Secure Boot homologado pela TI; pendrive de 32 GB ou mais. Separe dez mídias de equipe e uma reserva por sala.

| Sala | ISO atualizada em seminfo/dist/clients-refresh-20261028 |
|---|---|
| SI / Lab 3 | si/ml-seminfo-lab3-si-atualizada.iso |
| TII / Lab 4 | tii/ml-seminfo-lab4-tii-atualizada.iso |

Confira o .iso.sha256 correspondente. As imagens estão no kit local, não no Git; o link antigo é de outra versão. O teste de boot físico ainda está pendente.

**Em Linux, pela TI:** identifique modelo, série e dispositivo físico antes de gravar. A gravação apaga o USB escolhido; `/dev/sdX` é apenas um marcador a substituir pelo dispositivo conferido.

```bash
lsblk -d -o NAME,MODEL,SERIAL,SIZE,TRAN
lsblk -o NAME,SIZE,FSTYPE,LABEL,MOUNTPOINTS
sudo bash seminfo/client/write-usb.sh --device /dev/sdX /caminho/ISO-DA-SALA.iso
```

1. Desmonte as partições do USB. Confira modelo/série e digite o caminho exato solicitado pelo script.
2. Aguarde SHA256, gravação, comparação e criação de `home-rw`. Uma gravação comum de ISO pode não criar a persistência.
3. Teste um piloto por variante no PC real. Execute `seminfo-verify --offline`; depois da aprovação de rede, execute `seminfo-verify` completo.
4. Crie um arquivo em `~/trabalhos`, desligue e inicie pelo mesmo USB. Confira arquivo, compiladores, aprovação e bloqueios.
5. Só então grave as demais mídias a partir da ISO limpa. Não clone a /home de um USB já usado: cada dispositivo precisa de identidade própria.
6. Etiquete SI-01…SI-10, SI-RESERVA, TII-01…TII-10 e TII-RESERVA; preencha `inventario-pendrives.csv`.

**Fundo:** a ISO atualizada usa Espresso para SI e Claro para TII, de forma fixa. Os trabalhos persistem em /home; não há persistência geral do sistema.

## 15. Resolver falhas e encerrar com segurança

![Tela 13 - diagnóstico dos serviços](recursos/telas-2026-10-18/recortes/14-diagnostico.png)

| Situação | Próxima ação |
|---|---|
| Central não abre / sessão encerrada | Abra novamente o atalho correto. Aguarde Docker. Leia a mensagem e não abra tarefas repetidas. |
| Criador/importação desabilitado | Prova e questões → Abrir BOCA de preparação; aguarde BOCA pronto. Na mesma máquina, feche uma categoria antes de abrir a outra. |
| Iniciar mesa desabilitado | Confirme a rede com a TI. Horários/questões ainda podem ser preparados localmente. |
| Juiz não instalado/parado | No modo de preparação pode estar parado. Para receber equipes, a TI deve verificar a instalação e o início completo da mesa. |
| Código recusado ou equipe sem acesso | Confira sala, IP, dispositivo e código atual. Reabra o pedido após reinício/troca. Não libere internet para contornar. |
| Referência reprovada | Abra detalhes; revise código, classe Java, saídas, limites e pacote; reenvie o conjunto completo. |
| USB não preserva arquivo | Não distribua. Refazer gravação/persistência e repetir o desligamento no hardware real. |
| Placar divergente/indisponível | Use o Placar do BOCA da categoria. A outra sala não deve depender desta falha. |
| Falha durante cópia/restauração | Chame a TI com a mensagem e a categoria. Não trate operação interrompida como concluída nem restaure sobre prova ocupada. |

**No fim:** concluir julgamentos/rejulgamentos → conferir o Placar → publicar por categoria → revelar, se planejado → criar backup conferido → copiar para outra mídia → registrar ocorrências.

**Arquivos de apoio:** `dados-evento.csv`, `ocorrencias.csv`, `inventario-pendrives.csv` e a ficha de homologação. IPs, tempos, questões e ensaios presenciais ainda pendentes devem ser concluídos pela organização. Este manual não concede aprovação.
