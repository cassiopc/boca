# Roteiro rápido da banca

XIX SEMINFO 2026 | 29/10/2026 | Texto revisado em 28/10/2026. Use com instalação e homologação concluídas.

## 1. Abrir a sala e conferir a prova

![Operação da maratona - captura de 18/10](recursos/telas-2026-10-18/recortes/08-operacao.png)

1. Abra Docker Desktop → Central SEMINFO → aceite a permissão do Windows.
2. Confira **Esta banca gerencia**: SI/Lab 3 ou TII/Lab 4.
3. **Iniciar mesa** → aguarde **Banca funcionando** → **Atualizar diagnóstico**.
4. Confira rede, relógio, início, duração, congelamento, questões e seis ensaios. Iniciar serviços não ativa a prova.
5. O responsável usa **Revisar e ativar prova…** e digita a confirmação da sala somente após a revisão completa.
6. Abra o placar da categoria no projetor em área estendida e use **F11**. Administração fica no monitor.

| Antes de abrir | Preencher / conferir |
|---|---|
| Sala e IP da banca | ______________________________________________ |
| Início / duração / congelamento | ______________________________________________ |
| Responsável da banca / contato | ______________________________________________ |
| Apoio técnico / contato | ______________________________________________ |
| Coordenação / contato | ______________________________________________ |

**Para revisar configuração antes da prova:** Preparação da sala → rede; Prova e questões → tempos, pacotes e referências. Não use Preparar prova numa competição ativa ou já usada.

<div class="page-break"></div>

## 2. Autorizar cada computador

![Janela da equipe - ensaio virtual](recursos/telas-2026-10-18/16-cliente-ensaio-virtual.png)

*Imagem de ensaio virtual. O IP exibido não é o IP da sala.*

1. Confira sala, USB e número da equipe.
2. Na equipe: informe IP da mesa, selecione a equipe e clique **Solicitar acesso à mesa da banca**.
3. Na central: **Abrir solicitações da sala** → **Atualizar solicitações**.
4. Compare equipe, IP e dispositivo com o PC real. Peça o código atual de oito caracteres.
5. Digite no pedido correspondente → **Conferir código e liberar**.
6. Aguarde **Acesso liberado** → **Abrir BOCA**. Entregue somente a conta daquela equipe.

| Categoria | Contas / local dos trabalhos |
|---|---|
| SI / Lab 3 | si01 a si10; equipe 1 usa si01. |
| TII / Lab 4 | tii01 a tii10; equipe 1 usa tii01. |
| Arquivos | Salvar em ~/trabalhos no USB; desligar antes de retirar a mídia. |

**Reinício, troca de PC ou IP:** revogue o registro anterior e confira o novo pedido/código. Código errado, pedido duplicado ou categoria diferente não deve ser aprovado por suposição.

**Conta admin e lista de senhas:** uso exclusivo da banca, fora da projeção. Não entregar o kit privado às equipes.

<div class="page-break"></div>

## 3. Acompanhar, encerrar e recuperar

![Backup da categoria - captura de 18/10](recursos/telas-2026-10-18/recortes/11-backup.png)

| Durante a prova | Fazer |
|---|---|
| Submissões e dúvidas | BOCA: Submissões, Dúvidas e Placar. Confira se o juiz esvazia a fila. |
| Projetor | Somente o placar da categoria. Uma banca pode estar congelada enquanto a outra já terminou. |
| Ocorrência | Anote hora, equipe, falha e decisão em ocorrencias.csv; comunique o responsável. |
| Backup | Combine a breve pausa → Criar backup e verificar restauração → aguarde aprovação → copie a pasta completa para outra mídia protegida. |
| Falha de serviço | Atualizar diagnóstico; conferir Docker/rede. Use Iniciar mesa para retomar os serviços existentes. Chame a TI se persistir. |
| Trocar a mesa | Somente com a TI: kit, identidade e backup correspondentes; restauração em destino vazio. |

**Encerramento:** aguarde todos os julgamentos/rejulgamentos → confira o Placar → **Publicar resultado final…** da categoria → confirme o laboratório → confira o placar projetado → faça backup conferido.

**Animeitor:** a TI usa `python seminfo/manage.py reveal si` ou `tii` após o final validado. Espaço avança; q sai. Em divergência/falha, use o resultado oficial do BOCA.

**Não fazer durante a prova:** alterar tempos/pacotes, preparar uma prova usada, reinstalar para resolver um incidente ou marcar ensaio não executado. Encaminhe decisões excepcionais à coordenação/TI.
