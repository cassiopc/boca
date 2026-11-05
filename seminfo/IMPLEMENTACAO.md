# Situação da implementação — 16/10/2026

> Registro histórico anterior à formatação. Em 17/10/2026, a recuperação começou a partir do Git, sem o ZIP privado: as ISOs, bancos, certificados e relatórios descritos abaixo não estavam disponíveis. Os resultados desta seção não atestam o ambiente reconstruído. Acompanhar a nova execução em [RECUPERACAO-2026-10-17.md](RECUPERACAO-2026-10-17.md).

A implementação de autorização e placares está no projeto e nas duas ISOs. **Ainda não é homologação dos laboratórios.** Para quem operará o evento, o roteiro atual é [guia-organizacao.md](guia-organizacao.md). Procedimentos técnicos: [admissao-e-placares.md](admissao-e-placares.md), [runbook.md](runbook.md) e [VALIDACAO.md](VALIDACAO.md).

## Entregue

- Central SEMINFO local com atalho na área de trabalho/menu Iniciar: operação da maratona, preparação guiada e backup/recuperação. O uso diário dispensa terminal e edição de JSON; a instalação técnica do kit continua necessária uma vez.
- Botões para iniciar/parar, abrir aprovações e BOCA, parear salas por arquivo, projetar placares, conferir senhas com ocultação automática, verificar, registrar ensaios reais, ativar e publicar. Confirmações incluem o laboratório; uma tarefa por vez, resultado e orientação em caso de falha, registro privado e detecção de operação interrompida.
- Edição bloqueada com banca aberta, prova ativa, submissões ou banco sem estado confirmado. Restauração recusa destino ocupado. Backup pela interface inclui teste real de recuperação isolada. Revogação de acesso exige segunda confirmação; estados e instruções estão em português.
- Linux das equipes inicia bloqueado, com janela mostrando laboratório, IP, dispositivo, equipe e solicitação à banca.
- Banca aprova/revoga mediante código de conferência. A autorização vincula IP observado, dispositivo, solicitação e BOCA da sala; expira em até 60 s sem renovação. O próprio kernel expira a regra se o agente morrer.
- Canal TLS 8443 com certificado fixado por laboratório, utilizável somente pelo serviço root do Linux. O navegador do participante só ganha BOCA TCP 8000 após aprovação. Internet, pares, outro BOCA e IPv6 externo continuam bloqueados.
- Mesa com whitelist dinâmica, gate público em 0.0.0.0:8000 e backend Docker restrito a 127.0.0.1:18000. Instalador Windows inclui firewall e tarefa de início no logon; parada deliberada interrompe a supervisão.
- Duas bancas gerenciadoras: Graduação/SI e Médio-Técnico/TII, com questões e placares próprios. A central da banca aprova somente sua categoria; o placar da outra é consulta opcional. Descritores exportados pela interface são somente leitura.
- Ranking único retirado (rota antiga responde 410). Publicação final exige fim da própria prova e ausência de pendências, além de paridade com o BOCA. Não aguarda a outra categoria. Migração de catálogos remove regras de ranking antigo e tokens administrativos remotos.
- Fundos atuais: Claro e Espresso fornecidos pelo usuário, 2560×1440, preservados byte a byte. Sorteia uma imagem por inicialização e mantém durante a sessão, sem carrossel. Os banners anteriores ficaram como histórico.

## ISOs geradas e inspecionadas

- `C:\Users\vitor\Desktop\bocaCaramel\seminfo\dist\clients\si\ml-seminfo-lab3-si.iso` — 4,969,660,416 bytes; SHA256 `d040432798e85c859bb19a7d59e3a975f3b5c94d602069072c3fd1e47c4b77c5`.
- `C:\Users\vitor\Desktop\bocaCaramel\seminfo\dist\clients\tii\ml-seminfo-lab4-tii.iso` — 4,969,660,416 bytes; SHA256 `472ebefbd97d027192959fe22851809a9fbb774430cb1976ce9336b62bfaf33b`.

Cada pasta contém checksum, manifesto de pacotes, overlay e image.json. Os hashes Linux/Windows e da receita conferem. A inspeção abriu o sistema comprimido de cada ISO e conferiu cliente, bloqueio inicial, certificado, permissões root e as duas artes.

As ISOs já trazem a identidade pública das bancas. Levar também a **pasta privada correspondente** de local/si ou local/tii para a mesa final; gerar outra chave no destino faria a imagem recusar a banca. Sem IP pré-configurado, a equipe informa na janela o IP indicado pela organização. A própria mesa ainda exige IP/sub-rede confirmados.

## Verificado nesta máquina

- 37 testes automatizados, incluindo migração do catálogo, banca gerenciadora por categoria, bloqueio de aprovação remota, questões/tempos/fases independentes, remoção do ranking único, sessões HTTP/CSRF/origem/Host, proteção de configuração/restauração e tarefas. Nenhuma regra real do host foi alterada pelo ensaio.
- BOCA e juiz isolados: dez equipes, quatro linguagens, 43 submissões com AC/WA/CE/TLE, logins, webcast HTTP fechado, placar público igual ao BOCA, freeze sem vazamento, rejeição de publicação antes do fim e durante rejulgamento. Backup restaurado com igualdade de tabelas e large objects.
- Rede/TLS/nftables reais em containers isolados: pendente bloqueado, autorização correta, GET/POST no BOCA, outro laboratório/backend bloqueados, revogação, certificado errado e expiração após SIGKILL. Usuário sem privilégios não usa o canal 8443 nem lê a identidade root.
- Ciclo real do processo da banca em container: login por cookie, detecção de serviço próprio, início idempotente, reinício após mudança de versão e parada idempotente. Isso não substitui testar Agendador/Firewall no Windows físico.
- Consulta de duas bancas com questões/tempos diferentes, placares e fases independentes; token de projetor e banca da outra categoria impedidos de aprovar.
- Boot SI por ISO/UEFI virtual com 8 GB: GNOME entrou em icpc, abriu a janela com Meu IP e botão BOCA bloqueado; sorteou Espresso. Captura em reports/vm-admission-si-final.png. Este ensaio usou DVD virtual somente leitura, não um USB físico. Ensaio anterior de USB virtual não homologa a nova versão.
- As duas construções compilaram/executaram C, C++20, Java 17 e Python 3 na conta sem sudo. Verificações de servidor SI/TII aprovadas. Pacote offline de servidor regenerado com manifesto e checksum.

Evidências em reports/delivery-admission.json, integration.json, admission-network.json, network.json, iso-admission-inspection.json e virtual-admission-si.json. Dados de execução e segredos ficam fora do Git.

A central também tem ensaio HTTP com tarefas reais de verificação e backup/restauração em reports/organizer-http.json. A inspeção visual automatizada não pôde ser concluída: o navegador integrado falhou ao iniciar e a política do SENTRA bloqueou localhost. Apresentação, cliques, UAC, atalhos e firewall real continuam na homologação presencial; nenhum desses itens foi declarado aprovado automaticamente.

## Falta para o evento

1. Confirmar IP/sub-rede de cada mesa e, se houver roteamento entre sub-redes, control_ips das mesas centrais/projetores. IPs das equipes podem vir do DHCP e serão aprovados dinamicamente.
2. Confirmar início proposto às 13h, duração e freeze de cada categoria. Os tempos continuam sem confirmação automática; provas inativas.
3. Importar os pacotes reais da Cristiane destinados à Graduação no SI e os de Médio/Técnico no TII e conferir as quatro linguagens. Nenhum problema real está nas ISOs.
4. Instalar nas mesas finais e testar firewall real, tarefa de logon, solicitação, aprovação/revogação, queda e recuperação.
5. Homologar um USB de cada variante em hardware real, incluindo persistência de trabalhos após desligar, isolamento e ausência de internet. Testar projetores, os dois placares de categoria, freeze, revelação e carga real.
6. Após aprovação dos pilotos, gravar/conferir dez pendrives e uma reserva por sala. Concluir a homologação presencial até 28/10 conforme acesso aos laboratórios.

Na máquina de preparação, SI está saudável somente em 127.0.0.1:18000/boca/, pois os IPs institucionais não foram fornecidos; TII permanece preparado sem segunda publicação web. A banca/central de produção e o firewall real não foram ativados aqui. Há outro serviço em 127.0.0.1:8000; ele foi preservado. Nenhuma validação presencial foi registrada automaticamente.
