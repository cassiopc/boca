# Autorização da banca e placares

Para o passo a passo da instituição, usar o [manual completo](documentacao/manual-if.md) e o [roteiro da banca](documentacao/roteiro-banca.md). A sequência abaixo é a referência técnica de autorização e placares.

Decisões confirmadas: imagens Linux das equipes com firewall já ativo; solicitação mostrando IP; aprovação humana por equipe/dispositivo/IP/código; BOCA de cada laboratório independente; questões diferentes em SI/Graduação e TII/Médio-Técnico; duas bancas gerenciadoras e classificações independentes, sem ranking único. Windows é o sistema das mesas com Docker, não o sistema dos participantes.

## Portas e responsabilidades

| Serviço | Endereço | Acesso |
|---|---|---|
| BOCA público / whitelist | `0.0.0.0:8000` | Gate nativo; somente IP aprovado, além da própria mesa |
| Solicitação e renovação | `0.0.0.0:8443` | TLS com certificado fixado na imagem; sub-rede da sala e `control_ips` confirmados |
| BOCA dentro do Docker | `127.0.0.1:18000` | Somente mesa; nunca publicar na LAN |
| Aprovação local | `127.0.0.1:17860` | Sessão privada da banca; formulário com proteção CSRF |
| Central / projetores | `127.0.0.1:17861` | Painel privado; páginas de projeção somente leitura |
| Central dos organizadores | `127.0.0.1:17862` | Abrir pelo atalho Central SEMINFO; sessão privada |
| Grafana | `127.0.0.1:3001` | Somente mesa |
| Ponte Linux da equipe | `127.0.0.1:17631` | Janela GTK sem privilégios conversa com serviço root |

`0.0.0.0` é a escuta em todas as interfaces IPv4, não localhost e não autorização irrestrita. O gate verifica a whitelist em cada requisição, inclusive quando uma regra ampla de Windows/Docker existiria. O backend não pode ficar exposto em outra publicação Docker.

## Instalar em cada mesa Windows

Preparar Docker Desktop com containers Linux e Python 3.12. Levar a pasta do kit, imagens offline e a pasta **privada do perfil correspondente** de `seminfo/local/PERFIL`: ela contém credenciais e, principalmente, a chave/certificado da banca que as ISOs já reconhecem. Não inicializar uma identidade nova na mesa final usando pendrives preparados para outra chave. Um backup atual também preserva essas chaves. Arquivos privados não devem ser colocados nas ISOs nem no Git.

Confirmar `server_ip` e `subnet`. `team_ips` agora é opcional: o endereço é observado pela banca e aprovado dinamicamente, permitindo DHCP. Reservar o IP da mesa continua necessário. Se a central estiver em outra sub-rede, preencher `control_ips` com os IPs reais das mesas autorizadas a administrá-la/consultar placares; isso só abre TLS 8443, não aprova equipes nem abre BOCA.

Em PowerShell elevado:

```powershell
python seminfo/manage.py check si --network
python seminfo/manage.py install si
```

Para uma instalação já preparada, sem reinstalar bancos/imagens:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/install-admission.ps1 -Profile si -WhatIf
powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/install-admission.ps1 -Profile si
python seminfo/manage.py admission-panel si
```

Substituir por `tii` na outra mesa. O instalador aplica firewall e agenda início automático no logon do organizador, com privilégios elevados, aguardando Docker Desktop. Usa a conta interativa do organizador, porque Docker Desktop pertence a ela. A sessão do organizador/Docker precisa estar disponível; não é um serviço que funciona antes do logon. A supervisão tenta reiniciar em caso de falha. Nenhuma tarefa da mesa é instalada nesta máquina de preparação enquanto os IPs reais estiverem ausentes.

Na reinicialização da banca, as aprovações são descartadas. Os dados BOCA e os trabalhos USB continuam preservados. Para retirar somente o autoinício: `install-admission.ps1 -Profile si -Uninstall`; parar a banca com `manage.py stop si`. Para manutenção das regras da mesa: `firewall-mesa.ps1 -Profile si -Maintenance`.

## Participante e aprovação

1. No boot, Linux bloqueia internet, outras equipes, outro BOCA e saída IPv6. Só loopback, DHCP IPv4 e TLS 8443 para o IP informado da própria banca são permitidos antes da aprovação. TLS 8443 é restrito ao serviço root; o participante/navegador não pode usar esse canal para navegar, mesmo informando outro IP.
2. A janela mostra laboratório, **Meu IP**, identificação do dispositivo, número de equipe e campo para IP da banca. A organização informa esse IP se não estiver preenchido na imagem.
3. A equipe clica **Solicitar acesso à mesa da banca**. O serviço root envia uma solicitação por TLS, depois de verificar o certificado da banca. A banca usa o IP de origem da conexão; ignora endereços informados por cliente ou cabeçalhos de proxy.
4. No painel local ou na central, selecionar a sala, conferir a equipe/dispositivo/IP e digitar o código de oito caracteres mostrado no computador. A aprovação vale **somente para aquele BOCA**. Uma equipe/IP já aprovado exige revogar a autorização anterior antes de trocar de dispositivo.
5. O serviço root valida laboratório, banca, IP, dispositivo e solicitação, aplica nftables e a janela abre o BOCA. O login BOCA continua separado; aprovação de rede não ativa a prova nem autentica a equipe no BOCA.
6. A autorização dura até 60 s e é renovada a cada consulta. O próprio kernel encerra a permissão quando o prazo expira, mesmo se o agente morrer. Resposta inválida, certificado errado ou perda de conexão fecham o acesso. Reinício do cliente, mudança de IP ou reinício da banca exigem nova aprovação. Revogação fecha o gate imediatamente e o Linux na próxima consulta.

Não há segredo de administrador nos clientes. Cada USB cria sua identidade própria no primeiro boot, em arquivo root 0600 na home persistente. Replicar a imagem antes de aprovar/inicializar os USBs; não clonar a home de um piloto que já possui identidade. TLS autentica a resposta da banca; aprovações antigas não são reaproveitadas do disco. O código público é para conferência presencial, não é uma senha administrativa.

A tela pode ser reaberta por **Autorizacao BOCA SEMINFO** no menu de aplicativos. O serviço root continua responsável pelo firewall quando a janela é fechada.

## Central com várias salas

Em cada mesa gerar os descritores:

```powershell
python seminfo/manage.py admission-export si
```

`admission-desk.json` permite consultar placar e aprovar/revogar. `projector-desk.json` permite **somente consultar placar**. Transferir o descritor apropriado de cada banca para a mesa que exibirá a central ou o projetor. Ambos são arquivos privados; a chave TLS da banca não é transferida por esses descritores.

Na mesa central:

```powershell
python seminfo/manage.py hub-import si --desk "C:\transferencia\si\admission-desk.json"
python seminfo/manage.py hub-import si --desk "C:\transferencia\tii\projector-desk.json"
python seminfo/manage.py hub
```

O catálogo registra `manager_profile` (`si` ou `tii`) e `mode: independent_categories`. Uma banca aprova somente sua categoria. O descritor da outra é reduzido a consulta de placar; tokens administrativos remotos são removidos. Catálogos antigos sem banca gerenciadora ficam somente leitura até adicionar a própria banca pela interface. Não reutilizar identificadores de bancos diferentes.

Para projetores em mesas diferentes, importar os descritores `projector-desk.json` de todas as salas em cada mesa de projeção e executar `hub`. Assim o navegador usa somente a central local, sem levar poderes de aprovação ao projetor. O IP dessa mesa precisa ter acesso a TLS 8443 das bancas; em sub-redes diferentes, usar `control_ips`.

Janelas de projeção, sem controles administrativos:

- Sala SI: `http://127.0.0.1:17861/projector/si`
- Sala TII: `http://127.0.0.1:17861/projector/tii`

A rota antiga `/projector/general` responde 410 e orienta usar os placares separados; nunca reúne equipes em um ranking.

Usar área estendida e tela cheia no projetor. A central consulta as bancas a cada 10 s, por TLS autenticado, sem depender de internet. Dados com mais de 30 s ou uma banca indisponível bloqueiam somente o placar daquela categoria. Os BOCA locais continuam funcionando se a central parar. `hub-stop` encerra a central; após importar ou editar o catálogo, reiniciá-la.

## Classificação, freeze e resultado final

Cada placar vem de `DBScoreSite` do respectivo BOCA com freeze ativado, não da exportação completa. As questões e os tempos pertencem à categoria; nenhum hash, horário ou fase é comparado entre SI e TII. Os resultados não são reunidos em um ranking único.

Cada placar preserva a ordem oficial do BOCA da categoria, com problemas resolvidos, penalidade e seus desempates. Não há ordenação entre equipes de níveis diferentes. Graduação pode estar com resultado final enquanto Médio/Técnico está congelado, cada qual em sua página.

Ao final, após encerrar a prova, terminar julgamentos e conferir paridade com BOCA, publicar separadamente em **cada mesa**:

```powershell
python seminfo/manage.py publish-score si
```

O comando gera exportação local conferida e só libera o resultado final se ela for final. O BOCA volta a fechar a versão pública final se houver julgamento pendente. A publicação final é independente por categoria e não aguarda a outra banca. Não altera submissões ou resultados oficiais. Animeitor continua disponível para revelação local; exportação completa de webcast continua inacessível por HTTP.

## Planos de fundo

As imagens fornecidas pelo usuário são `banners/caramel-claro.png` e `banners/caramel-espresso.png`, preservadas byte a byte na ISO. A associação é fixa: **TII usa claro/JUNIOR; SI usa espresso/PLENO**. Antes da área de trabalho, um serviço oneshot lê o perfil em `/etc/seminfo/lab.json` e copia a arte correspondente para `maratona-common-wallpaper.png`, o caminho mantido na configuração do GNOME. Perfil inválido ou wallpaper incompatível causa falha explícita, sem escolher outra arte. A escolha permanece igual após reiniciar, sem sorteio, carrossel, temporizador ou alternância de tema. O enquadramento `scaled` mostra a arte inteira também em proporções diferentes. Os banners anteriores são preservados como histórico.

## Homologação ainda necessária

Os testes em containers não substituem Windows Firewall/Agendador reais, USB em hardware, persistência após desligar, projetor e conectividade institucional entre mesas. Testar pedido pendente, aprovação/código incorreto, troca de IP, revogação, queda da banca, reinício, acesso ao outro laboratório e expiração. Conferir os três placares e freeze/final com submissões reais de ensaio. Nenhuma prova deve ser ativada nem os USBs replicados antes dos pilotos.
# Operação pela interface

Para os organizadores, abra **Central SEMINFO** na área de trabalho ou `Abrir Central SEMINFO.vbs` no kit. O [guia da organização](guia-organizacao.md) descreve o fluxo por botões, pareamento por seletor de arquivo e recuperação. Os comandos abaixo continuam disponíveis para preparação e suporte técnico.

