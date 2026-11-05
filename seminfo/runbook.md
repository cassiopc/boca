# XIX SEMINFO 2026 — dois laboratórios independentes

**Roteiro institucional consolidado:** [documentacao/manual-if.md](documentacao/manual-if.md). Inclui a instalação offline e o procedimento para editar configuração antes da prova após uma parada. Este arquivo permanece como referência técnica complementar.

Provas em **29/10/2026**, início proposto **13h (America/Sao_Paulo)**. SI no Lab 3, TII no Lab 4. Cada sala tem mesa Windows, BOCA, banco, projetor e até dez equipes de três participantes. Cada equipe usa uma conta e um PC. Cristiane elaborará as questões; o organizador importará os pacotes reais.

**Atualização de 16/10:** questões diferentes e duas bancas gerenciadoras independentes. SI/Graduação no Lab 3 e TII/Médio-Técnico no Lab 4 têm seus próprios problemas, tempos e placares, sem ranking único. A rede agora usa solicitação/aprovação da banca por dispositivo/IP. Seguir [admissao-e-placares.md](admissao-e-placares.md) para instalação, pareamento da central, projetores e publicação final.

## Preparar as mesas

Instalar Python 3.12 e Docker Desktop com containers Linux. Na raiz do projeto, usar `si` no Lab 3 e `tii` no Lab 4. Ambas as mesas usam a porta 8000; não iniciar as duas stacks na mesma máquina. Para preparar o segundo banco nesta máquina sem publicar outra web, usar `python seminfo/manage.py stage tii`; na mesa final, executar `start tii`.

```powershell
python seminfo/manage.py init si
python seminfo/manage.py build-server
python seminfo/manage.py migrate si
python seminfo/manage.py prepare si
python seminfo/manage.py verify si
```

`init` cria `seminfo/local/si/lab.json`, credenciais, senha de manutenção da distro e identidade TLS da banca. Repeti-lo preserva os valores. Levar a pasta privada do perfil para a mesa final: o certificado precisa ser o mesmo reconhecido pelas ISOs. Perfis possuem segredos distintos, fora do Git, com ACL restrita. Guardar uma cópia protegida com a organização.

`build-server` incorpora o código local e registra hashes em `seminfo/dist/server.lock.json`. `start` exige essas imagens locais; não baixa nem reconstrói. Para transporte offline, executar `bundle`, copiar o kit e `seminfo/dist` e carregar `docker load -i seminfo/dist/seminfo-server-images.tar` na outra mesa. Inicializar o perfil daquela mesa. O bundle inclui o código fixado do Animeitor e a wheel Pygame para Windows/Python 3.12; nesse destino, `animeitor` instala a partir desses arquivos sem internet. Preparar também o Python antes de retirar a internet.

`start` também confere a resposta HTTP. Outro processo pode ocupar especificamente `127.0.0.1:8000` enquanto o Docker publica `0.0.0.0:8000`; nesse caso o navegador local recebe o serviço errado. Conferir `Get-NetTCPConnection -LocalPort 8000 -State Listen` e o IP real da mesa. Resolver o conflito na mesa antes do evento; a ativação exige que a URL configurada responda com o login BOCA.

`migrate` faz backup da instalação antiga, restaura em PostgreSQL isolado e compara o conteúdo de todas as tabelas e large objects antes de trocar o volume anônimo por nomeado. Preserva backup e volume original. Uma instalação nova apenas inicia. Não usar limpeza geral do Docker ou `down -v` no evento.

`prepare` mantém a prova inativa e cria `admin`, `placar` e `si01`–`si10` ou `tii01`–`tii10`. As senhas estão no arquivo privado `credentials.json`: `BOCA_PASSWORD`, `SCORE_PASSWORD`, `TEAM_01` etc. O usuário `system` do contest 0 usa `SYSTEM_PASSWORD`. O comando recusa contest ativo ou que já recebeu submissões.

## Configuração obrigatória

Editar o `lab.json` local da respectiva mesa:

- `server_ip` e `subnet`: endereços reais e reservados com a TI.
- `shared_subnet`: registrar a topologia real. `team_ips` pode ficar vazio com aprovação dinâmica; a banca observa o IPv4 atribuído pelo DHCP. O BOCA nunca fica liberado para o prefixo inteiro.
- `control_ips`: lista opcional dos IPs reais de mesas centrais/projetores em outras sub-redes, para acesso autenticado TLS 8443.
- `start`: confirmar 29/10/2026 às 13h com offset `-03:00`.
- `duration_minutes` e `freeze_minutes_before_end`: preencher após a organização decidir. Freeze é a quantidade de minutos **antes do fim**.
- `stop_answering_minutes_before_end`: inicialmente `0`, respostas até o fim. Penalidade inicial: 20 minutos.
- Definir `timing_confirmed: true` somente depois de confirmar os tempos. Os valores antigos 300/285/240 não são adotados automaticamente.

```powershell
python seminfo/manage.py check si --network --timing
python seminfo/manage.py prepare si
```

Antes da ativação, acessar `http://localhost:8000/boca/?contest=1` como `admin` para importar os ZIPs reais. Na preparação sem IP configurado, o gate fica parado; usar somente na própria mesa `http://127.0.0.1:18000/boca/?contest=1`. A seleção explícita de contest aceita somente administradores, sem liberar as equipes. Conferir scripts `compile`, `run`, `compare` e `limits` para `c`, `cpp`, `java`, `py3`, entradas e saídas. Linguagens: C, C++20, Java 17 e Python 3. Usar `javac --release 17`. Com os scripts BOCA deste projeto, a classe Java acompanha o basename do problema: problema A usa arquivo `A.java` e `public class A`. Os pacotes reais não entram nas imagens das equipes.

## Rede e pendrives

Na mesa do laboratório, primeiro simular; depois aplicar em PowerShell elevado:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/firewall-mesa.ps1 -Profile si -WhatIf
powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/firewall-mesa.ps1 -Profile si
```

A mesa aceita TCP 8000 somente dos IPs aprovados, preservando o acesso local. TLS 8443 recebe solicitações da sub-rede e das mesas centrais configuradas. O BOCA Docker fica em `127.0.0.1:18000`, atrás do gate. Bloqueia o complemento IPv4 e IPv6, inclusive contra permissões amplas. Grafana permanece em `127.0.0.1:3001`. A reversão `-Maintenance` restaura somente as regras SEMINFO anteriores. Instalar o autoinício com `install-admission.ps1` em PowerShell elevado, conforme o roteiro de admissão.

Antes da aprovação, as equipes só podem sair para TLS 8443 da banca, além de loopback e DHCP. Após aprovação, o serviço root libera seu BOCA TCP 8000 por autorização renovável de até 60 s. A janela mostra IP, equipe e código. Certificado errado ou falha de rede mantém/retoma o bloqueio. A sessão `icpc` não tem sudo. `seminfo-admin` é o administrador de manutenção; sua senha fica em `maintenance-password.txt` e não deve ser entregue à equipe.

O procedimento atual de mídia é [imagem-maratona.md](imagem-maratona.md). A receita Debian anterior é histórica. Os banners atuais do Lab 3/SI e Lab 4/TII foram aprovados.

```powershell
python seminfo/manage.py build-client si
python seminfo/manage.py build-client tii
```

Sem os endereços da TI, usar `--allow-incomplete` gera imagem bloqueada com janela de autorização: informar o IP da banca no laboratório e solicitar acesso. Ela ainda exige ensaio presencial. Testar um USB por sala antes de replicar os dez; reservar um sobressalente por laboratório. Nas novas construções, o fundo é fixo por categoria: TII usa claro/JUNIOR e SI usa espresso/PLENO, inclusive após reiniciar.

## Homologação e ativação

Executar os testes e o checklist [VALIDACAO.md](VALIDACAO.md). Registrar somente verificações efetivamente realizadas:

```powershell
python seminfo/manage.py verify si
python seminfo/manage.py attest si --check client_boot --operator "Responsavel" --note "PC, USB e resultado observados"
```

Repetir para `client_boot`, `client_persistence`, `network_isolation`, `projector`, `judge_load` e `problems_reviewed`, com evidências reais. Mudanças na configuração, no código do servidor ou na receita/banner dos participantes invalidam os registros. A instituição deve disponibilizar uma janela até 28/10; o roteiro anterior não constitui reserva.

```powershell
python seminfo/manage.py activate si
```

O comando exige configuração de rede e tempos, usuários, linguagens, compiladores, problemas importados e registros atuais. A ativação pela interface web do evento é bloqueada. O início programado controla a abertura das submissões. Ensaios automatizados usam dados sintéticos e containers separados.

## Prova, projetor e recuperação

Usar área de trabalho **estendida**: administração no monitor e placar da própria categoria em tela cheia no projetor, pelas páginas somente leitura (`127.0.0.1:17861/projector/si` ou `tii`). A rota antiga `general` foi retirada e responde HTTP 410, sem dados de ranking. O placar BOCA com usuário `placar` continua como alternativa. Não projetar a sessão administrativa. Todos respeitam o freeze. Depois de conferir o resultado final, usar `publish-score` em cada mesa para liberar os placares finais.

Comandos durante a operação: `status`, `logs`, `start`, `stop` e `backup`, sempre seguidos de `si` ou `tii`. O backup consistente pausa brevemente web/juiz e os religa; combinar essa interrupção com a organização. Após queda de energia, iniciar Docker e executar `start`; não preparar nem reinstalar a prova.

Instalar antecipadamente o Animeitor: `python seminfo/manage.py animeitor`. Python/Pygame e commit do Animeitor ficam fixados; fotos são opcionais e não interrompem a apresentação.

Ao encerrar e concluir o julgamento:

```powershell
python seminfo/manage.py export si
python seminfo/manage.py reveal si
```

A exportação usa Docker CLI na mesa; HTTP de webcast está fechado. Uma pasta nova guarda resultado oficial, `contest/runs/time` e metadados. A animação oculta os resultados a partir do freeze, preservando o resultado oficial. Aceita `X` sem penalidade e aplica o desempate do BOCA. A revelação exige prova encerrada, juiz sem pendências e igualdade de pontuação/classificação. Se divergir, projetar o resultado oficial web. Espaço avança; `q` sai.

```powershell
python seminfo/manage.py backup si
python seminfo/manage.py restore-check --backup "C:\caminho\backup"
```

`restore-check` restaura em PostgreSQL temporário sem rede, compara dados e remove somente seus recursos temporários. O backup guarda banco/large objects, diretório privado e manifesto. Para recuperar outra mesa, restaurar somente em destino vazio; `restore_into` recusa um banco existente. Preservar a cópia protegida e os volumes originais da migração.

Em uma mesa substituta vazia, depois de carregar as imagens: `python seminfo/manage.py restore si --backup "C:\caminho\backup"`. Um backup do kit restaura também as credenciais originais; revisar o IP reservado da mesa substituta antes de retomar.
# Acesso dos organizadores

O roteiro do dia a dia está em [guia-organizacao.md](guia-organizacao.md). Use o atalho **Central SEMINFO** para iniciar serviços, aprovar computadores, abrir placares e fazer backup. Este documento mantém os procedimentos técnicos de preparação, instalação e suporte.

