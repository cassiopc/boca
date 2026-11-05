> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Projetor — placar ao vivo

Durante a prova, o projetor do lab mostra o placar web do BOCA **deste** lab. A tela fica na mesa do professor ou num PC ligado no projetor, em tela cheia, no Microsoft Edge ou no Google Chrome.

Não é o PC de uma equipe. Não é o Animeitor. O Animeitor é só a revelação, no fim. Até lá a sala olha para `/boca/score/`.

Cada lab tem a própria mesa e o próprio placar. O Lab 3 não abre o IP do Lab 4. O Lab 4 não abre o IP do Lab 3.

## Página inicial

`http://IP-DA-MESA:8000/boca/`

Troque `IP-DA-MESA` pelo IP da mesa **deste** lab, o mesmo que as equipes desta sala usam. Essa URL é a tela de login (BOCA Login).

Depois do login com o usuário tipo score, o placar fica em:

`http://IP-DA-MESA:8000/boca/score/`

| Lab | Prova neste BOCA |
| --- | --- |
| 3 | Sistemas de Informação (`si`) |
| 4 | Informática para a Internet (`tii`) |

## Abrir o Edge ou o Chrome em tela cheia

1. Use a mesa do professor ou o PC ligado no projetor desta sala. Confira o número do lab (3 ou 4) antes de digitar qualquer endereço.
2. Abra o PowerShell nesta pasta e rode, com o IP desta mesa:

```powershell
.\abrir-placar.ps1 -BocaUrl 'http://IP-DA-MESA:8000/boca/'
```

Se a política de execução bloquear o arquivo:

```powershell
powershell -ExecutionPolicy Bypass -File .\abrir-placar.ps1 -BocaUrl 'http://IP-DA-MESA:8000/boca/'
```

O script só abre o navegador padrão nessa URL. Não pede senha, não grava senha e não faz login. Se o navegador que abrir não for o Edge nem o Chrome, feche e abra um dos dois na mesma URL.

No lugar do script, abra o Microsoft Edge ou o Google Chrome e cole `http://IP-DA-MESA:8000/boca/` na barra de endereços.

3. Na tela BOCA Login, entre à mão com o usuário tipo score **deste** lab (o cadastro da mesa; o exemplo do runbook é `placar`). A senha não entra no script, em arquivo nem em bilhete. Se o Edge ou o Chrome oferecer salvar a senha, recuse.
4. Espere o endereço ir para `/boca/score/`. Se a sessão entrar como time, juiz ou admin, use Logout e volte ao passo 3. O que se projeta é só o usuário score.
5. Leia o topo antes de esconder a barra. A frase de conferência é: "o cabeçalho do contest tem de ser o deste lab". Na barra do placar, `Username` é o score desta mesa e `site` é o site deste lab. A tabela é a das equipes desta sala: Lab 3, Sistemas de Informação; Lab 4, Informática para a Internet. Se o usuário, o site ou as equipes forem do outro lab, feche a aba. Não corrija apontando para o IP da outra sala.
6. Com o placar certo, aperte **F11**. No Edge e no Chrome, F11 é tela cheia: somem a barra do Windows e a moldura do navegador. F11 de novo sai da tela cheia. Deixe essa janela na frente. Não minimize e não cubra com outra aba.
7. Se o placar parecer parado, aperte F5 na mesma URL. Não mude o endereço. Se o F5 abrir o BOCA Login, a sessão caiu: siga a seção abaixo.

## Se a sessão cair

A sessão caiu quando o placar some e volta a tela BOCA Login, ou quando o endereço deixa `/boca/score/` e volta para `/boca/`.

1. Fique neste PC e neste IP. Não vá ao outro lab e não use o computador de uma equipe.
2. Se a tela cheia atrapalhar o login, aperte F11. Depois que o placar voltar, aperte F11 outra vez.
3. Entre de novo, à mão, com o usuário score deste lab. Não cole a senha no script. Recuse salvar senha no Edge ou no Chrome.
4. Confira de novo `/boca/score/` e repita a frase: "o cabeçalho do contest tem de ser o deste lab".
5. Se a página nem abrir, o endereço continua `http://IP-DA-MESA:8000/boca/` da mesa desta sala, porta 8000. Não teste o IP do outro lab. Quem sobe o BOCA é a mesa deste lab; este passo não mexe em container.
