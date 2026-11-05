# Gerador simples de problemas BOCA

Esta ferramenta cria um ZIP no padrao BOCA a partir de uma pasta mais simples.

O objetivo e evitar editar manualmente `compile/`, `run/`, `compare/`, `limits/`, `tests/` e `problem.info` para cada problema.

## Estrutura esperada

```text
meu-problema/
  problem.yml
  statement.md
  tests/
    inputs/
      1.in
      2.in
    outputs/
      1.out
      2.out
```

## `problem.yml`

```yaml
basename: A
fullname: Soma Simples
statement: statement.md
languages: [c, cpp, py3]
test_languages: [py3]
time_limit: 2
repetitions: 1
memory_limit: 512
output_limit: 1024
```

Campos:

- `basename`: nome curto usado pelo BOCA, como `A`, `B`, `soma`.
- `fullname`: nome completo exibido no BOCA.
- `statement`: arquivo do enunciado. Pode ser `.md`, `.txt`, `.tex` ou `.pdf`.
- `languages`: linguagens que entrarao no pacote. Os scripts precisam existir em `doc/problemexamples/problemtemplate`.
- `test_languages`: linguagens usadas para o autoteste interno do pacote. Se omitido, usa `py3` quando estiver em `languages`.
- `time_limit`: limite de tempo em segundos.
- `repetitions`: quantidade de repeticoes usada pelo script de execucao.
- `memory_limit`: memoria em MB.
- `output_limit`: tamanho maximo de saida em KB.

## Gerar ZIP

No Windows:

```powershell
python .\tools\problem_builder\generate_boca_problem.py .\tools\problem_builder\example -o .\build\soma.zip
```

No Linux/macOS:

```bash
python3 ./tools/problem_builder/generate_boca_problem.py ./tools/problem_builder/example -o ./build/soma.zip
```

O ZIP gerado pode ser importado no BOCA como pacote de problema.

## Enunciado em LaTeX

Se o arquivo for `statement.tex`, a ferramenta pode tentar gerar PDF com `pdflatex`:

```bash
python3 ./tools/problem_builder/generate_boca_problem.py ./meu-problema -o ./build/A.zip --compile-pdf
```

Se `pdflatex` nao estiver instalado, gere sem `--compile-pdf` ou use um `statement.pdf` pronto.

## Validacoes

A ferramenta valida:

- Existe `problem.yml`.
- Existe pelo menos um input.
- Cada input tem output correspondente.
- Os arquivos de input/output sao normalizados para pares com o mesmo nome.
- Os scripts BOCA existem para cada linguagem.
- Os scripts `tests/*` sao gerados com `../compile` e `../run`, sem o caminho quebrado `../../compile` ou `../../run`.
- O ZIP final nao contem caminhos com `..`.

## Sobre `test_languages`

O BOCA executa todos os scripts dentro de `tests/` antes de julgar a submissao.

Se `tests/c` falhar, a submissao inteira falha antes mesmo de testar Java, Python ou outra linguagem. Em ambientes onde o chroot do `boca-jail` nao tem `gcc`/`g++`, usar `tests/c` ou `tests/cpp` pode quebrar o pacote.

Por isso o exemplo usa `test_languages: [py3]`.

## Observacao

Esta ferramenta nao altera o BOCA. Ela apenas gera o ZIP no formato que o BOCA ja entende.
