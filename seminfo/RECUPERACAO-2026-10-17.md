# Reconstrução SEMINFO após formatação — 17/10/2026

## Origem e decisões

- Fonte recuperada do Git: commit `eca509e`; bancos, identidades, ISOs e relatórios privados anteriores foram perdidos.
- Mesas Windows e BOCAs independentes: SI no Lab 3 e TII no Lab 4, até dez equipes por sala.
- Duas variantes da mesma distro Ubuntu 22.04.5/Maratona Linux, cada uma reconhecendo somente a identidade da própria banca.
- Usar os dois anexos fornecidos em 17/10 byte a byte, com sorteio de uma arte por inicialização e sem carrossel. Não acrescentar textos às artes.
- Não reutilizar os hashes das ISOs de 16/10 como critérios das novas construções.

## Fundos recuperados

| Arquivo no kit | SHA-256 do anexo original |
| --- | --- |
| `banners/caramel-claro.png` | `068fe7ca2cae3ba3a8607267d5ae8d5fda70cd0713bb23be12cc84c8460547d7` |
| `banners/caramel-espresso.png` | `86623b8651c53a8675373230d4586da9fcddd1cfadb48ee1a2f0a8fa546b54c8` |

As duas artes têm 2560×1440. O Espresso escolhido não contém o logo do IF. As imagens anteriores do Git não eram idênticas aos anexos atuais.

## Reconstrução executada

- Fundos substituídos pelos anexos originais.
- Windows com Python 3.12 e aproximadamente 32 GB de RAM; virtualização detectada.
- WSL recuperado pelo MSI oficial 3.0.3.0, assinatura Microsoft válida, instalação com código 0. O reparo inicial não resolveu e a reinstalação MSIX havia retornado `0x80070002`.
- Docker Desktop 4.95.0 instalado pelo instalador oficial, SHA-256 do manifesto Microsoft/winget e assinatura Docker Inc conferidos; instalação com código 0.
- A Plataforma de Máquina Virtual foi habilitada; após o reinício feito pelo usuário, o motor Linux Docker 29.8.2 ficou operacional. Uma interrupção posterior do backend WSL durante o intervalo dos ensaios foi resolvida reiniciando o WSL e o aplicativo Docker, sem reset de fábrica ou perda dos artefatos. Os ensaios virtuais interrompidos foram repetidos.
- Base Ubuntu 22.04.5 oficial concluída e verificada: SHA-256 `bfd1cee02bc4f35db939e69b934ba49a39a378797ce9aee20f6e3e3e728fefbf`.
- Novos certificados SI/TII gerados com OpenSSL do Git for Windows, RSA 3072, validade de 3650 dias e `CA:FALSE`, seguindo os parâmetros da receita. Credenciais distintas criadas pelo inicializador do kit e diretórios privados com ACL restrita.
- 37 testes unitários aprovados, incluindo os três testes de overlay/certificados antes pendentes. Corrigido um reset HTTP no Windows ao rejeitar POSTs com corpo ainda não consumido; a leitura continua limitada e a interpretação/execução depende da autorização. O teste existente cobre a regressão.
- Animeitor instalado no commit fixado pelo kit; ensaio de renderização, freeze e resultado final aprovado. Fonte e wheel Pygame Windows/Python 3.12 preservadas no pacote offline.
- Servidores e as duas ISOs reconstruídos. A inspeção dos sistemas comprimidos das ISOs confirmou bloqueio inicial, certificado público da própria banca, fundos idênticos aos anexos e executáveis de sistema pertencentes a root. Credenciais/chaves privadas das bancas não entram nas ISOs.
- Atalho Central SEMINFO recriado na Área de Trabalho e menu Iniciar do usuário.
- Roteiro `retomar-recuperacao.ps1` criado, validado com `-WhatIf` e executado por completo com sucesso. Preserva credenciais e identidades existentes. O log privado é `reports/rebuild-console-after-http-fix.log`.

Os bancos novos têm dez contas de equipe por categoria e continuam inativos, sem questões ou submissões reais. As credenciais e identidades antigas perdidas não foram recuperadas.

## Artefatos novos

| Perfil | ISO em `dist/clients/` | Tamanho | SHA-256 |
| --- | --- | --- | --- |
| SI / Lab 3 | `si/ml-seminfo-lab3-si.iso` | 6.814.892.032 bytes | `279f7e7241d2b4e9d3001fd71c52f817fd3b9bd648ea1e79cf3f5acf417da93c` |
| TII / Lab 4 | `tii/ml-seminfo-lab4-tii.iso` | 6.814.892.032 bytes | `b71deb3f4fe6e19af416cd8299c860ec4e796af223f12fd6e4173b17c88bcff7` |

Cada variante tem checksum, manifesto de pacotes e metadados da construção. A conferência dos hashes foi feita no Linux e no Windows, inclusive nas cópias para entrega. As variantes foram construídas com configuração de rede incompleta: começam bloqueadas e permitem informar o IP da banca na janela.

Pacote offline dos servidores: `dist/seminfo-server-images.tar`, 1.705.682.944 bytes, SHA-256 `cf2976d490575bd92e758c5b21b585ec7fcec1239c0b7d25812ae8468a318b07`. Fontes do Animeitor e wheel Pygame Windows/Python 3.12 também foram preservadas.

Identidades públicas novas: SI `da72f853599dceea7ff841c202f1c196d429a5b3801ba41626114071af8f15dc`; TII `eb65712c8e8c5200f36ab3a3e7e41b32f608202b812e7b5679a96769375983a1`.

## Verificações de software e restauração

- `reports/integration.json`: 43 submissões, dez equipes, quatro linguagens, julgamento AC/WA/CE/TLE, logins HTTP, paridade de placar, freeze sem vazamento e bloqueio de rejulgamento final. Backup e restauração com comparação de tabelas e large objects.
- `reports/network.json`: regras nftables reais; bloqueio por padrão, outro BOCA, vizinhos e saída IPv6; configuração inválida mantém navegação fechada.
- `reports/admission-network.json`: aprovação com certificado fixado, revogação, expiração no kernel mesmo após encerrar o agente, ausência de envio de credenciais a certificado incorreto e separação de questões/tempos/placares entre categorias.
- `reports/organizer-http.json`: tarefas reais de verificação e backup/restauração pela central HTTP, prova/configuração preservadas e recusa de ativação incompleta. Não comprova UAC, firewall Windows ou operação gráfica presencial.
- `reports/iso-admission-inspection.json`: inspeção de ambas as ISOs comprimidas, identidades corretas, cliente fiel ao código e fundos byte a byte.
- Backups novos `backups/si-20261017T232602234253Z` e `backups/tii-20261017T232616990459Z`: restauração isolada aprovada, todas as tabelas e large objects conferidos; certificados e chaves correspondem às bancas atuais e aos certificados das ISOs.

Os relatórios são privados e são preservados no pacote completo. O snapshot de preparação anterior não contém os bancos/ISOs completos e não deve ser confundido com o ZIP final recuperado. Os hashes e ensaios registrados em 16/10 permanecem somente como histórico.

## Boot e persistência em máquina virtual

As duas variantes passaram por dois boots de teste UEFI em QEMU/TCG, a partir de arquivos regulares simulando USBs de 32 GB com partição ext4 `home-rw`. Nenhum dispositivo físico foi gravado. Um script de ensaio foi colocado somente na pasta pessoal dessas mídias descartáveis; as ISOs entregues permaneceram inalteradas.

Em cada sessão real, o usuário `icpc` (UID 1000) compilou e executou C, C++20, Java 17 e Python 3. As tentativas `sudo -n true` e `pkexec --disable-internal-agent /usr/bin/id` foram recusadas. O cliente local informou a categoria correta, sem IP de banca configurado e sem aprovação. O arquivo criado pelo participante no primeiro boot permaneceu legível no segundo, após desligamento normal; os dois ciclos terminaram com código 0.

O boot gráfico também foi observado: Lab 3/SI e Lab 4/TII com autorização correta e botão BOCA desabilitado. Claro e Espresso foram vistos em boots distintos; os hashes das duas artes já haviam sido conferidos dentro de cada ISO. A receita mantém um único sorteio no serviço de inicialização, sem temporizador durante a sessão.

Evidências privadas: `reports/virtual-recovery-si.json`, `reports/virtual-recovery-tii.json`, capturas de tela e o script de ensaio em `reports/virtual-recovery-harness.py`. O ensaio com USB virtual não substitui a verificação de firmware, persistência e rede nos pendrives e computadores dos laboratórios. As flags de homologação física continuam falsas.

Após a interrupção do WSL, os bancos persistentes também foram comparados novamente com os backups verificados: todas as tabelas e large objects coincidiram (`reports/post-wsl-recovery-databases.json`).

## Pendências institucionais e físicas

- Confirmar IP reservado e sub-rede de cada mesa e endereços de controle/projetores quando necessários.
- Programação fornecida: 29/10 às 13h, SI/Lab 3 e TII/Lab 4. Duração e freeze continuam sem definição; manter `timing_confirmed` falso até validação dos tempos completos.
- Receber e revisar os pacotes distintos da Cristiane; problemas não devem entrar nas ISOs dos participantes.
- Instalar nas mesas definitivas e verificar firewall, logon, solicitação, aprovação/revogação e recuperação.
- Homologar USB físico, persistência após desligar, isolamento, projetores e carga de dez equipes por laboratório antes de replicar dez mídias e uma reserva por sala.
- Guardar uma cópia do backup privado completo fora deste computador, com acesso restrito. A restauração dos bancos novos já foi comprovada; a cópia externa depende de um destino da organização.

O número informado na autorização é de 1 a 10 e corresponde a `si01`–`si10` ou `tii01`–`tii10`. Não é o número interno do usuário no banco BOCA.
