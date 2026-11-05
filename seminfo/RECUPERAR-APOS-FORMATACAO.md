# Recuperar o projeto após formatar o computador

O ZIP completo de preservação é privado: contém senhas, certificados e chaves das bancas. Guardar no Drive com acesso restrito à organização. O GitHub contém o código e a documentação; não contém os bancos, as ISOs nem as credenciais.

## Quando o ZIP privado também foi perdido

O código permite reconstruir o ambiente, mas não recuperar bancos, senhas ou certificados antigos. Não usar `restore` sem um backup real. Criar identidades novas para SI e TII e reconstruir as duas ISOs para que reconheçam essas novas identidades.

Preparar WSL2 e Docker Desktop com containers Linux, executar `init si` e `init tii` por `seminfo/manage.py`, construir os servidores com `build-server` e gerar cada variante com `build-client si --allow-incomplete` e `build-client tii --allow-incomplete` enquanto os endereços institucionais estiverem pendentes. Os procedimentos completos estão em [imagem-maratona.md](imagem-maratona.md) e [runbook.md](runbook.md).

Manter os contests inativos; não inventar IPs, duração, freeze ou aprovações presenciais para liberar a ativação. A nova construção terá novos hashes e deverá repetir os testes. O registro de recuperação iniciado em 17/10 está em [RECUPERACAO-2026-10-17.md](RECUPERACAO-2026-10-17.md).

Para o ambiente recuperado em 17/10, depois de reiniciar o Windows e iniciar o Docker Desktop em modo Linux, executar na raiz do projeto `powershell -NoProfile -ExecutionPolicy Bypass -File seminfo/retomar-recuperacao.ps1`. O roteiro preserva identidades existentes, prepara os bancos sem publicar portas web conflitantes, executa os testes, constrói as duas variantes bloqueadas, inspeciona seus sistemas comprimidos e confere a restauração dos backups. Uma ISO existente só é reaproveitada se checksum, receita, configuração e identidade coincidirem. `-WhatIf` mostra as etapas sem executar a reconstrução. O roteiro não grava USBs, não ativa provas e não registra homologações presenciais.

## Antes de formatar

1. Enviar o ZIP completo para o Drive e aguardar a conclusão do upload.
2. Conferir o tamanho e, idealmente, baixar uma cópia para outro dispositivo e verificar o SHA-256 informado na entrega. Manter o arquivo original até conferir essa cópia.
3. O ZIP inclui `LEIA-PRIMEIRO-BACKUP.txt`, `MANIFESTO-BACKUP.json` e a pasta `bocaCaramel`, com o projeto, o histórico Git, os artefatos e os backups dos bancos. O manifesto identifica os backups novos e verificados e os hashes dos arquivos.
4. A formatação só deve ocorrer depois de confirmar que a cópia fora deste computador está íntegra.

## Depois de formatar

1. Instalar Git, Python 3.10 ou superior e Docker Desktop com suporte a containers Linux/WSL2. Iniciar o Docker e aguardar ficar pronto. O pacote preserva as imagens dos containers; não inclui os instaladores dessas ferramentas nem a distribuição WSL do computador antigo.
2. Extrair o ZIP completo com uma ferramenta com suporte a ZIP64, como 7-Zip. Preservar as pastas ocultas, inclusive `.git`. Abrir o PowerShell dentro da pasta extraída `bocaCaramel`.
3. Conferir `git status` e `git log -1`. O histórico local também está preservado no ZIP.
4. Carregar as imagens Docker preservadas:

```powershell
docker load --input .\seminfo\dist\seminfo-server-images.tar
```

5. Consultar `MANIFESTO-BACKUP.json` na raiz do ZIP e localizar a pasta de backup verificado de cada perfil. As pastas estão em `seminfo\backups`. Testar a restauração com o nome exato indicado no manifesto:

```powershell
python .\seminfo\manage.py restore-check --backup .\seminfo\backups\NOME-EXATO-DO-BACKUP
```

6. Restaurar cada perfil em sua respectiva mesa, em uma instalação Docker vazia. Usar `si` na mesa da Graduação e `tii` na mesa do Médio/Técnico. Exemplo para SI:

```powershell
python .\seminfo\manage.py restore si --backup .\seminfo\backups\NOME-EXATO-DO-BACKUP-SI
```

Na outra mesa, trocar `si` por `tii` e informar o backup TII. A restauração recusa bancos existentes e compara tabelas e objetos com o backup. Não executar os dois serviços web simultaneamente na mesma mesa: eles usam as mesmas portas.

7. Conferir IP, sub-rede e horários reais em `seminfo\local\si\lab.json` ou `seminfo\local\tii\lab.json`. Preservar as credenciais e os certificados restaurados; as ISOs existentes dependem da identidade da banca.
8. Reinstalar os atalhos e a integração Windows seguindo o manual em `seminfo\documentacao\manual-if.md`. Atalhos, tarefas agendadas, firewall do Windows e ambientes virtuais Python precisam ser recriados no Windows novo. O ambiente virtual Animeitor não é portátil e foi excluído do ZIP recuperado; suas fontes e o wheel offline estão em `seminfo\dist`.
9. Repetir as verificações locais e a homologação presencial descritas no manual antes de ativar qualquer prova. As ISOs estão em `seminfo\dist\clients\si` e `seminfo\dist\clients\tii`; conferir seus checksums antes de gravar os pendrives.

Este backup é do projeto BOCA/SEMINFO. Não cobre outros projetos, arquivos pessoais, instalações do Windows ou volumes Docker de aplicações alheias ao projeto.
