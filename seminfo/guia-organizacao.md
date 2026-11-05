# Guia da organização — XIX SEMINFO 2026

**Documentação consolidada para o IF:** [manual completo](documentacao/manual-if.md), [roteiro rápido da banca](documentacao/roteiro-banca.md) e [ficha de homologação](documentacao/ficha-homologacao.md). Comece por esses documentos para seguir a sequência completa de implantação e operação.

Este é o roteiro para as pessoas que operarão a maratona. O uso diário fica na **Central SEMINFO**, sem editar arquivos JSON ou executar comandos no terminal.

## Abrir a central

Para decidir horários e preparar questões antes do evento, abra **Configurar Provas SEMINFO**, na área de trabalho ou no menu Iniciar. Esse acesso abre somente a preparação local, sem pedir elevação do Windows e sem liberar acesso às equipes. Escolha a categoria no topo e use **Prova e questões**. A operação da maratona e o firewall continuam no acesso completo abaixo.

Na área de trabalho da mesa, abra **Central SEMINFO**. O mesmo acesso está no menu Iniciar. Se o atalho ainda não existir, abra `Abrir Central SEMINFO.vbs` na pasta `seminfo` do kit.

O Windows pede permissão porque a central gerencia serviços e firewall da mesa. Use a conta do organizador preparada pela TI, a mesma do Docker Desktop. A central não coleta a senha do Windows. Se a conta não tiver a permissão necessária, solicite à TI a preparação da mesa; não use credenciais de outra conta para improvisar acesso ao Docker.

No topo da central, confira **Esta banca gerencia**:

| Mesa | Seleção |
|---|---|
| Superior / Sistemas de Informação | Lab 3 / SI |
| Médio e Técnico em Informática para a Internet | Lab 4 / TII |

A troca dessa seleção não inicia nem para serviços. Cada BOCA funciona na própria mesa; os botões sempre operam a sala selecionada. A central de salas permite acompanhar e aprovar computadores das duas bancas.

## Antes de receber as equipes

1. Abra o Docker Desktop e aguarde sua inicialização.
2. Na aba **Operação da maratona**, clique em **Iniciar mesa**.
3. Aguarde a tarefa terminar e o diagnóstico mostrar **Banca funcionando**. Iniciar serviços não ativa uma prova que estava inativa.
4. Clique em **Abrir solicitações da sala**.
5. Na máquina da equipe, peça que informe o IP da banca, selecione sua equipe e solicite acesso.
6. Confira sala, equipe e IP. Peça o código que aparece no computador e digite-o para liberar.

Aprovar no Lab 3 libera somente o BOCA do Lab 3. Revogar exige uma segunda confirmação e interrompe o acesso daquele computador. Se ele reiniciar, mudar de IP ou perder a autorização, será necessária nova aprovação.

Use **Atualizar solicitações** para consultar novos pedidos. A lista prioriza os pedidos aguardando aprovação e mostra estados em português. Se não houver solicitações, a própria tela explica o próximo passo.

## Projetores

Depois de adicionar sua banca, clique em **Preparar central de salas**. A outra banca é opcional e somente para consulta. Use os links **Graduação / SI · Lab 3** ou **Médio/Técnico / TII · Lab 4** e mova a janela para o projetor. Pressione **F11** para tela cheia.

Projete somente a página de placar. Mantenha administração, solicitações e senhas no monitor da mesa.

As questões são diferentes e as classificações são separadas. Se uma banca ficar indisponível, somente seu placar fica pendente; o da outra continua independente. Use o placar oficial do BOCA correspondente se houver divergência.

## Contas e senhas

**Consultar contas e senhas** pede confirmação de ambiente reservado. A janela mostra administrador, placar e contas das equipes; oculta os dados ao fechar, ao trocar de aba do navegador ou após um minuto. Entregue a cada equipe somente sua conta. Não projete nem fotografe a lista completa.

## Preparação da sala — antes da prova

Os tempos e as questões são decididos pela organização na aba **Prova e questões**, para a categoria escolhida em **Esta banca gerencia**. Eles podem ser cadastrados e revisados na própria mesa durante a preparação; não precisam estar definidos para construir os pendrives.

### Prova e questões

1. Escolha **Lab 3 / SI** ou **Lab 4 / TII** no topo da central.
2. Em **Decidir os horários**, edite início, duração em minutos e quantos minutos antes do fim o placar congela. **Zero** significa sem congelamento. Confira a prévia de término e congelamento em Brasília. Salve sem marcar horários definitivos enquanto ainda estiver decidindo; marque a confirmação quando a organização concluir a escolha. A rede pode permanecer pendente.
3. Use **Preparar prova** para aplicar os tempos e as contas no BOCA, mantendo a prova inativa.
4. Use **Abrir BOCA de preparação** para trabalhar localmente antes de informar os IPs institucionais. Aguarde o diagnóstico confirmar que o BOCA está pronto; os links de criação e importação ficam disponíveis nessa condição. Eles abrem a entrada administrativa da prova inativa e retornam ao editor escolhido após o login como **admin**. Use **Consultar contas e senhas** se necessário. O serviço de preparação fica somente nesta mesa, sem liberar acesso às equipes. Se estiver preparando as duas categorias no mesmo computador, use **Fechar BOCA de preparação** antes de trocar; na implantação cada sala usa seu próprio computador.
5. **Criar pacote de questão** abre o criador do BOCA: Fullname (título), Basename (classe Java, como `A` ou `Main`), Description (enunciado PDF/TXT), Input (casos de entrada), Correct output (saída esperada) e Timelimit (segundos inteiros). Ele baixa um ZIP. Para vários casos separados ou pacotes já elaborados pela Cristiane, use o ZIP BOCA correspondente.
6. Em **Importar ZIP ou editar questões**, importe o pacote, escolha número/letra e mantenha o AutoJudge configurado para os resultados que deseja automatizar. Para revisar enunciado, casos ou limites, substitua o ZIP da mesma questão. A lista também permite editar identificação e cores.
7. De volta à central, atualize o diagnóstico. Em **Conferir soluções de referência**, escolha uma questão e selecione uma solução correta por linguagem: `.c`, `.cpp`, `.java` e `.py`/`.py3`, em UTF-8, até 128 KiB cada. Para Java, respeite o Basename/classe do pacote.
8. **Testar soluções no juiz** cria um BOCA descartável, sem acesso externo, com o pacote e as referências. Aguarde o resultado por linguagem. Submissões e placar da prova real não recebem esses testes. Um envio com menos de quatro linguagens é parcial; envie o conjunto completo para concluir a validação. Referências reprovadas mostram os detalhes do juiz.
9. Alterar o pacote ou a versão do kit invalida a validação anterior. Após todas as questões passarem nas quatro linguagens, revise também o enunciado, a qualidade/cobertura dos casos e os limites, e registre **Questões revisadas** na homologação. Executar a referência não comprova sozinho a qualidade da questão.

Os códigos e relatórios das referências ficam privados na pasta da banca e são incluídos nos novos backups. Não entram nos pendrives dos participantes. A edição dos tempos e das questões é bloqueada com banca de acesso aberta, prova ativa ou submissões existentes; o editor nativo também recusa alterações numa prova ativa ou usada.

Na aba **Preparação da sala**, siga a ordem numerada:

1. **Conferir pasta privada**: confirma o kit trazido pela TI. As chaves precisam corresponder às imagens dos pendrives; não se gera outra identidade por este botão.
2. **Configurar a rede**: preencha IP e sub-rede fornecidos pela TI. Os horários ficam na aba **Prova e questões**.
3. **Configurar prova e questões**: abra a aba correspondente e siga o fluxo acima. Execute também a verificação automática. O início automático/firewall pode ser instalado antes do evento.
4. **Conectar as salas**: clique em **Adicionar esta sala à central**. Na outra mesa, exporte seu pareamento e adicione o arquivo aqui pelo seletor de arquivos. Não é necessário abrir nem editar o JSON. O arquivo exportado pela interface é privado e permite somente consultar o placar. A aprovação continua exclusiva da banca da categoria.
5. **Homologar os testes presenciais**: execute cada ensaio, informe o responsável e registre o computador usado e o resultado. Não marque um teste apenas por existir uma implementação. Mudanças de configuração ou versão tornam os registros anteriores desatualizados.

Pare a banca para editar rede ou horários. A edição é bloqueada se a prova estiver ativa, tiver submissões ou se não for possível conferir o banco. Não se considera um banco indisponível como vazio.

**Revisar e ativar prova** exige os seis ensaios atuais, os tempos confirmados, as questões importadas com referências aprovadas nas quatro linguagens e os serviços/compiladores aprovados. A revisão das questões fica vinculada à versão atual dos pacotes. É necessário digitar a confirmação com o laboratório correto. A prova segue o início configurado no BOCA.

## Backup e recuperação

Na aba **Backup e recuperação**, **Criar backup e verificar restauração** realiza a cópia e testa sua recuperação em um banco separado. BOCA e juiz pausam durante a cópia consistente e retomam depois dela; o teste de restauração não substitui a prova em uso. A tela informa se a tarefa concluiu ou falhou.

Guarde outra cópia em mídia protegida. A pasta `seminfo/backups` contém material privado, incluindo credenciais e submissões.

Os backups desta sala aparecem na lista com data e situação da conferência. **Restaurar em destino vazio** exige confirmação e recusa uma instalação já ocupada. Para levar a recuperação a outra mesa, solicite apoio técnico e leve o kit, a pasta privada e o backup correspondentes. A interface não apaga a prova para abrir espaço a uma restauração.

## Fim da prova

Depois do encerramento e de todos os julgamentos/rejulgamentos, use **Publicar resultado final** em cada sala. A exportação é conferida contra o BOCA antes da publicação. Cada banca publica sua categoria sem aguardar a outra.

Se houver divergência ou falha na revelação, apresente o resultado oficial do BOCA. Não corrija manualmente dados oficiais para fazer a animação coincidir.

## Quando uma tarefa falhar

Leia a mensagem e abra **Diagnóstico dos serviços**. Confira energia, rede da mesa e Docker Desktop; atualize o diagnóstico. Não inicie outra tarefa enquanto uma estiver em andamento. A central registra a operação e bloqueia tarefas simultâneas.

Se a central for encerrada durante uma tarefa, ao reabrir ela informa que o resultado precisa ser conferido; não apresenta a operação interrompida como concluída. Peça apoio técnico antes de repetir ativação, publicação ou restauração em situação incerta.

O botão **Parar mesa** interrompe o acesso das equipes e a supervisão automática. Ele pede confirmação com o laboratório. Para retomar os serviços existentes, use **Iniciar mesa**.

## Preparação única pela TI

A central pressupõe Windows com Python 3.10 ou superior, Docker Desktop funcionando na conta do organizador, kit completo, imagens de servidor carregadas e banco preparado. Ela não instala esses programas nem baixa imagens ao iniciar o evento. A imagem Linux fica nos computadores das equipes; a mesa do plano adotado continua Windows/Docker.

O instalador da banca cria os atalhos. Para criá-los separadamente, a TI pode executar `install-organizer-shortcut.ps1`. Os procedimentos técnicos completos permanecem em `runbook.md` e `admissao-e-placares.md`.

O acesso administrativo da central é somente local (`127.0.0.1:17862`; preparação restrita em `127.0.0.1:17864`), com sessões independentes, proteção de formulários e sem serviços externos para seus arquivos de interface. A banca continua em TCP 8000 e a solicitação em TLS 8443; essas portas não expõem a central administrativa.

Em 18/10/2026, a preparação local, a entrada administrativa de uma prova inativa e os formulários reais de criação e importação foram conferidos no navegador integrado deste Windows. Os testes de criação, importação, edição e julgamento das referências estão em `reports/contest-setup.json`. A apresentação nos projetores, a elevação do Windows, o início no logon, o firewall efetivo e os pendrives físicos ainda exigem homologação nas mesas reais. Os testes HTTP da central são registrados separadamente em `reports/organizer-http.json`.
