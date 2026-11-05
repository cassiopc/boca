# Configuração das provas — 18/10/2026

A organização de cada categoria pode decidir e revisar os tempos, criar/importar questões e conferir suas soluções na própria mesa. Use o novo atalho **Configurar Provas SEMINFO**, selecione **SI / Lab 3** ou **TII / Lab 4** e abra **Prova e questões**. O acesso completo **Central SEMINFO** permanece disponível para a operação do evento.

## Horários por categoria

Início, duração e congelamento são editáveis independentemente da configuração de rede e da outra categoria. A tela calcula o término e o instante do congelamento em Brasília. Zero desativa o congelamento. É possível salvar um rascunho e confirmar os tempos posteriormente. A preparação mantém a prova inativa; a ativação continua exigindo a revisão e a homologação.

## Questões e soluções

**Abrir BOCA de preparação** inicia o BOCA somente nesta máquina, sem liberar acesso às equipes e sem exigir IP institucional. Aguarde o diagnóstico ficar pronto. **Criar pacote de questão** abre o criador nativo; **Importar ZIP ou editar questões** permite cadastrar os pacotes da Cristiane ou substituir o pacote de uma questão existente. Os links passam pelo login administrativo da categoria e retornam ao formulário solicitado. As contas estão disponíveis no botão de consulta reservado da central.

A edição das questões permite revisar enunciado, casos de teste, limites, identificação e cores. Os modelos do criador foram ajustados para as quatro linguagens do evento, incluindo C++20 e classes Java com nomes curtos. O kit trata as quebras de linha dos scripts produzidos a partir do Windows.

Na central, selecione uma questão e envie as referências em C, C++, Java e Python 3. O botão **Testar soluções no juiz** executa o pacote real em um BOCA descartável e isolado. Mostra aprovação ou detalhes da falha por linguagem, sem inserir submissões ou alterar o placar da prova real. Um conjunto incompleto fica como parcial. Mudar o pacote ou a versão do kit exige nova validação. As referências e os relatórios ficam privados e entram nos novos backups.

**Questões revisadas** exige referências aprovadas nas quatro linguagens e registra a versão dos pacotes revisados. A organização ainda deve avaliar clareza do enunciado, cobertura dos casos e adequação dos limites. A edição é bloqueada para provas ativas, com submissões ou com a banca de acesso aberta.

## Correção dos links de preparação

As páginas sem saída vistas em `127.0.0.1:17863/open/problems` e `/open/problem-builder` pertenciam a um painel de ensaio sem BOCA conectado. O painel foi encerrado. A preparação real usa a porta local 17864, com sessão independente da central completa. Os links agora ficam disponíveis somente após a confirmação de saúde do BOCA; acessos indisponíveis exibem orientação e retorno à configuração. A entrada de administrador em prova inativa seleciona explicitamente a categoria e conserva o destino escolhido após o login.

O novo atalho de preparação dispensa elevação do Windows e tem permissões limitadas no servidor: não ativa provas, não abre firewall e não aprova dispositivos. Para preparar a outra categoria no mesmo computador, use **Fechar BOCA de preparação** e troque a seleção.

## Verificações desta versão

- **45 testes unitários aprovados**, incluindo tempos independentes, sessões separadas, restrição do modo de preparação, revisões de questões e integridade dos backups das referências.
- **Criação, importação e edição nativas aprovadas**, com referências aceitas nas quatro linguagens, referência incorreta recusada, invalidação após troca do pacote e bloqueio de edição com prova ativa. O mesmo ensaio comprovou backup/restauração das referências e preservação dos bancos reais: `reports/contest-setup.json`.
- **Integração do BOCA aprovada** com dez equipes, 43 submissões, quatro linguagens, AC/WA/CE/TLE, congelamento, paridade do placar e restauração de banco com large objects: `reports/integration.json`.
- Os formulários reais de criação e importação e o login administrativo da prova inativa foram observados no navegador integrado deste Windows.

As questões definitivas e os tempos permanecem para decisão da organização. Nenhum problema sintético dos testes foi cadastrado nas provas reais. As ISOs preservadas e suas identidades continuam válidas; estas alterações são da mesa e dos servidores. Os registros de recuperação anteriores permanecem em `RECUPERACAO-2026-10-17.md`.

Pendrives físicos, projetores, rede institucional, firewall efetivo e início após logon continuam dependendo de homologação presencial. Os testes virtuais anteriores não representam essa homologação.

O pacote atualizado contém credenciais e chaves privadas: guarde-o com acesso restrito e mantenha uma cópia fora deste computador. Consulte também o [guia da organização](guia-organizacao.md).
