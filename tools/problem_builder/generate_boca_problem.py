#!/usr/bin/env python3
import argparse
import os
import re
import shutil
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path


DEFAULT_LANGUAGES = ["c", "cpp", "py3"]
SCRIPT_DIRS = ["compile", "run", "compare", "limits", "tests"]
EXECUTABLE_DIRS = set(SCRIPT_DIRS)


class BuildError(Exception):
    pass


def strip_inline_comment(value):
    in_single = False
    in_double = False
    for index, char in enumerate(value):
        if char == "'" and not in_double:
            in_single = not in_single
        elif char == '"' and not in_single:
            in_double = not in_double
        elif char == "#" and not in_single and not in_double:
            return value[:index].rstrip()
    return value.strip()


def parse_scalar(value):
    value = strip_inline_comment(value).strip()
    if value == "":
        return ""
    if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
        return value[1:-1]
    if value.startswith("[") and value.endswith("]"):
        raw_items = value[1:-1].strip()
        if not raw_items:
            return []
        return [parse_scalar(item.strip()) for item in raw_items.split(",")]
    if value.lower() in {"true", "false"}:
        return value.lower() == "true"
    if re.fullmatch(r"-?\d+", value):
        return int(value)
    return value


def parse_simple_yaml(path):
    data = {}
    current_key = None
    for raw_line in path.read_text(encoding="utf-8").splitlines():
        if not raw_line.strip() or raw_line.lstrip().startswith("#"):
            continue
        if raw_line.startswith((" ", "\t")):
            if current_key is None:
                raise BuildError(f"Linha indentada sem chave anterior em {path}: {raw_line}")
            item = raw_line.strip()
            if not item.startswith("- "):
                raise BuildError(f"Somente listas com '- item' sao suportadas em {path}: {raw_line}")
            data.setdefault(current_key, []).append(parse_scalar(item[2:]))
            continue
        if ":" not in raw_line:
            raise BuildError(f"Linha invalida em {path}: {raw_line}")
        key, value = raw_line.split(":", 1)
        key = key.strip().lstrip("\ufeff")
        if not re.fullmatch(r"[A-Za-z0-9_]+", key):
            raise BuildError(f"Chave invalida em {path}: {key}")
        value = value.strip()
        current_key = key
        data[key] = [] if value == "" else parse_scalar(value)
    return data


def as_list(value):
    if value is None or value == "":
        return []
    if isinstance(value, list):
        return [str(item).strip() for item in value if str(item).strip()]
    return [str(value).strip()]


def safe_name(value, field):
    value = str(value).strip()
    if not value:
        raise BuildError(f"Campo obrigatorio vazio: {field}")
    if not re.fullmatch(r"[A-Za-z0-9_.-]+", value):
        raise BuildError(f"{field} deve usar apenas letras, numeros, '.', '_' ou '-': {value}")
    if value in {".", ".."} or "/" in value or "\\" in value:
        raise BuildError(f"{field} nao pode conter caminho: {value}")
    return value


def read_config(problem_dir):
    config_path = problem_dir / "problem.yml"
    if not config_path.is_file():
        raise BuildError(f"Arquivo nao encontrado: {config_path}")
    config = parse_simple_yaml(config_path)
    basename = safe_name(config.get("basename") or config.get("name"), "basename")
    fullname = str(config.get("fullname") or config.get("title") or basename).strip()
    if not fullname:
        raise BuildError("fullname nao pode ficar vazio")
    languages = as_list(config.get("languages")) or DEFAULT_LANGUAGES
    languages = [safe_name(lang, "languages") for lang in languages]
    test_languages = as_list(config.get("test_languages"))
    if not test_languages:
        test_languages = ["py3"] if "py3" in languages else [languages[0]]
    test_languages = [safe_name(lang, "test_languages") for lang in test_languages]
    return {
        "basename": basename,
        "fullname": fullname,
        "languages": languages,
        "test_languages": test_languages,
        "time_limit": int(config.get("time_limit", 2)),
        "repetitions": int(config.get("repetitions", 1)),
        "memory_limit": int(config.get("memory_limit", 512)),
        "output_limit": int(config.get("output_limit", 1024)),
        "statement": str(config.get("statement", "")).strip(),
        "inputs_dir": str(config.get("inputs_dir", "tests/inputs")).strip(),
        "outputs_dir": str(config.get("outputs_dir", "tests/outputs")).strip(),
    }


def ensure_positive(config, key):
    if config[key] <= 0:
        raise BuildError(f"{key} deve ser maior que zero")


def resolve_child(base, relative):
    candidate = (base / relative).resolve()
    base_resolved = base.resolve()
    if base_resolved != candidate and base_resolved not in candidate.parents:
        raise BuildError(f"Caminho fora da pasta do problema: {relative}")
    return candidate


def find_statement(problem_dir, config, build_dir, compile_pdf):
    candidates = []
    if config["statement"]:
        candidates.append(resolve_child(problem_dir, config["statement"]))
    candidates.extend(problem_dir / name for name in ["statement.pdf", "statement.tex", "statement.md", "statement.txt"])
    for candidate in candidates:
        if candidate.is_file():
            if candidate.suffix.lower() == ".tex" and compile_pdf:
                return compile_latex(candidate, build_dir, config["basename"])
            return candidate
    statement = build_dir / f"{config['basename']}.txt"
    statement.write_text(config["fullname"] + "\n", encoding="utf-8")
    return statement


def compile_latex(tex_path, build_dir, basename):
    out_dir = build_dir / "latex"
    out_dir.mkdir(parents=True, exist_ok=True)
    command = [
        "pdflatex",
        "-interaction=nonstopmode",
        "-halt-on-error",
        f"-output-directory={out_dir}",
        str(tex_path),
    ]
    try:
        subprocess.run(command, check=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
    except FileNotFoundError as exc:
        raise BuildError("pdflatex nao encontrado. Rode sem --compile-pdf ou instale LaTeX.") from exc
    except subprocess.CalledProcessError as exc:
        raise BuildError("Falha ao compilar LaTeX:\n" + exc.stdout[-3000:]) from exc
    generated = out_dir / (tex_path.stem + ".pdf")
    if not generated.is_file():
        raise BuildError("pdflatex executou, mas nao gerou PDF")
    target = build_dir / f"{basename}.pdf"
    shutil.copy2(generated, target)
    return target


def discover_cases(problem_dir, config):
    inputs_dir = resolve_child(problem_dir, config["inputs_dir"])
    outputs_dir = resolve_child(problem_dir, config["outputs_dir"])
    if not inputs_dir.is_dir():
        raise BuildError(f"Pasta de inputs nao encontrada: {inputs_dir}")
    if not outputs_dir.is_dir():
        raise BuildError(f"Pasta de outputs nao encontrada: {outputs_dir}")
    input_files = sorted(path for path in inputs_dir.iterdir() if path.is_file())
    if not input_files:
        raise BuildError(f"Nenhum input encontrado em {inputs_dir}")
    pairs = []
    for input_file in input_files:
        output_file = find_output_for_input(input_file, outputs_dir)
        pairs.append((input_file, output_file))
    return pairs


def find_output_for_input(input_file, outputs_dir):
    candidates = [
        outputs_dir / input_file.name,
        outputs_dir / (input_file.stem + ".out"),
        outputs_dir / (input_file.stem + ".ans"),
        outputs_dir / input_file.stem,
    ]
    if input_file.suffix == ".in":
        candidates.insert(0, outputs_dir / (input_file.stem + ".out"))
    existing = [candidate for candidate in candidates if candidate.is_file()]
    if not existing:
        raise BuildError(f"Output correspondente nao encontrado para {input_file.name}")
    return existing[0]


def copy_cases(stage_dir, pairs):
    input_dir = stage_dir / "input"
    output_dir = stage_dir / "output"
    input_dir.mkdir(parents=True, exist_ok=True)
    output_dir.mkdir(parents=True, exist_ok=True)
    width = max(3, len(str(len(pairs))))
    for index, (input_file, output_file) in enumerate(pairs, start=1):
        case_name = f"{index:0{width}d}"
        shutil.copy2(input_file, input_dir / case_name)
        shutil.copy2(output_file, output_dir / case_name)


def copy_runtime_scripts(stage_dir, template_dir, languages, test_languages, limits):
    for directory in SCRIPT_DIRS:
        (stage_dir / directory).mkdir(parents=True, exist_ok=True)
    required_script_languages = sorted(set(languages) | set(test_languages))
    for language in required_script_languages:
        for directory in ["compile", "run", "compare"]:
            source = template_dir / directory / language
            if not source.is_file():
                raise BuildError(f"Template ausente para {directory}/{language}: {source}")
            target = stage_dir / directory / language
            copy_script(source, target)
        write_limits(stage_dir / "limits" / language, limits)
    copied_tests = []
    for language in test_languages:
        test_target = stage_dir / "tests" / language
        if write_generated_test(test_target, language):
            copied_tests.append(language)
    if not copied_tests:
        fallback = "c"
        for directory in ["compile", "run", "compare"]:
            target = stage_dir / directory / fallback
            if not target.is_file():
                source = template_dir / directory / fallback
                if not source.is_file():
                    raise BuildError(f"Template de autoteste ausente para {directory}/{fallback}: {source}")
                copy_script(source, target)
        write_limits(stage_dir / "limits" / fallback, limits)
        test_target = stage_dir / "tests" / fallback
        write_generated_test(test_target, fallback)


def copy_script(source, target):
    text = source.read_text(encoding="utf-8", errors="replace")
    target.write_text(text.replace("\r\n", "\n"), encoding="utf-8", newline="\n")


def fix_test_script_paths(path):
    text = path.read_text(encoding="utf-8")
    text = text.replace("../../compile/", "../compile/")
    text = text.replace("../../run/", "../run/")
    path.write_text(text, encoding="utf-8", newline="\n")


def write_generated_test(path, language):
    sources = {
        "c": (
            "test.c",
            "test.exe",
            "#include<stdio.h>\nint main(){char s[100];scanf(\"%99s\",s);printf(\"%s\\n\",s);return 0;}\n",
        ),
        "cpp": (
            "test.cpp",
            "test.exe",
            "#include <iostream>\nusing namespace std;\nint main(){string s;cin>>s;cout<<s<<'\\n';return 0;}\n",
        ),
        "py3": (
            "test.py",
            "test.exe",
            "import sys\nprint(sys.stdin.read().strip())\n",
        ),
    }
    if language not in sources:
        return False
    source_name, executable_name, source_code = sources[language]
    content = "\n".join(
        [
            "#!/bin/bash",
            "set -e",
            f"cat > {source_name} <<'EOF'",
            source_code.rstrip(),
            "EOF",
            "cat > test.in <<'EOF'",
            "inputdata",
            "EOF",
            "TL=2",
            "REP=1",
            f"chmod 755 ../compile/{language}",
            f"../compile/{language} {source_name} {executable_name} $TL",
            f"chmod 755 ../run/{language}",
            f"../run/{language} {executable_name} test.in $TL $REP",
            "output=$(cat stdout0 2>/dev/null || true)",
            'if [ "$output" != "inputdata" ]; then',
            '  echo "ERROR"',
            "  exit 1",
            "fi",
            'echo "TEST PASSED"',
            "exit 0",
            "",
        ]
    )
    path.write_text(content, encoding="utf-8", newline="\n")
    return True


def write_limits(path, limits):
    content = "\n".join(
        [
            "#!/bin/bash",
            f"echo {limits['time_limit']}",
            f"echo {limits['repetitions']}",
            f"echo {limits['memory_limit']}",
            f"echo {limits['output_limit']}",
            "exit 0",
            "",
        ]
    )
    path.write_text(content, encoding="utf-8", newline="\n")


def write_description(stage_dir, statement_path, config):
    description_dir = stage_dir / "description"
    description_dir.mkdir(parents=True, exist_ok=True)
    desc_name = safe_name(statement_path.name, "statement")
    shutil.copy2(statement_path, description_dir / desc_name)
    info = "\n".join(
        [
            f"basename={config['basename']}",
            f"fullname={config['fullname']}",
            f"descfile={desc_name}",
            "",
        ]
    )
    (description_dir / "problem.info").write_text(info, encoding="utf-8", newline="\n")


def validate_stage(stage_dir, languages):
    required_dirs = ["description", "input", "output", "limits", "compile", "run", "compare", "tests"]
    for directory in required_dirs:
        if not (stage_dir / directory).is_dir():
            raise BuildError(f"Pasta obrigatoria ausente no pacote: {directory}")
    if not (stage_dir / "description" / "problem.info").is_file():
        raise BuildError("Arquivo obrigatorio ausente: description/problem.info")
    input_names = sorted(path.name for path in (stage_dir / "input").iterdir() if path.is_file())
    output_names = sorted(path.name for path in (stage_dir / "output").iterdir() if path.is_file())
    if input_names != output_names:
        raise BuildError("Arquivos de input e output nao ficaram pareados no pacote")
    test_files = [path for path in (stage_dir / "tests").iterdir() if path.is_file()]
    if not test_files:
        raise BuildError("O pacote precisa de pelo menos um script em tests/")
    for language in languages:
        for directory in ["compile", "run", "compare", "limits"]:
            script = stage_dir / directory / language
            if not script.is_file():
                raise BuildError(f"Script ausente: {directory}/{language}")
    for directory in SCRIPT_DIRS:
        for script in (stage_dir / directory).iterdir():
            if not script.is_file():
                continue
            text = script.read_text(encoding="utf-8", errors="replace")
            if directory == "tests" and ("../../compile/" in text or "../../run/" in text):
                raise BuildError(f"Script de teste contem caminho quebrado: {directory}/{script.name}")


def write_zip(stage_dir, output_path):
    output_path.parent.mkdir(parents=True, exist_ok=True)
    if output_path.exists():
        output_path.unlink()
    with zipfile.ZipFile(output_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for path in sorted(stage_dir.rglob("*")):
            if not path.is_file():
                continue
            arcname = path.relative_to(stage_dir).as_posix()
            if arcname.startswith("../") or "/../" in arcname:
                raise BuildError(f"Caminho invalido no zip: {arcname}")
            info = zipfile.ZipInfo.from_file(path, arcname)
            if arcname.split("/", 1)[0] in EXECUTABLE_DIRS:
                info.external_attr = (0o755 & 0xFFFF) << 16
            else:
                info.external_attr = (0o644 & 0xFFFF) << 16
            with path.open("rb") as source:
                archive.writestr(info, source.read(), compress_type=zipfile.ZIP_DEFLATED)


def build_problem(problem_dir, output_path, template_dir, compile_pdf, keep_stage):
    problem_dir = problem_dir.resolve()
    template_dir = template_dir.resolve()
    if not template_dir.is_dir():
        raise BuildError(f"Pasta de templates nao encontrada: {template_dir}")
    config = read_config(problem_dir)
    for key in ["time_limit", "repetitions", "memory_limit", "output_limit"]:
        ensure_positive(config, key)
    pairs = discover_cases(problem_dir, config)
    with tempfile.TemporaryDirectory(prefix="boca-problem-") as temp:
        build_dir = Path(temp)
        stage_dir = build_dir / "package"
        stage_dir.mkdir()
        statement_path = find_statement(problem_dir, config, build_dir, compile_pdf)
        write_description(stage_dir, statement_path, config)
        copy_cases(stage_dir, pairs)
        copy_runtime_scripts(stage_dir, template_dir, config["languages"], config["test_languages"], config)
        validate_stage(stage_dir, config["languages"])
        write_zip(stage_dir, output_path.resolve())
        if keep_stage:
            keep_dir = output_path.with_suffix("")
            if keep_dir.exists():
                shutil.rmtree(keep_dir)
            shutil.copytree(stage_dir, keep_dir)
    return config, len(pairs)


def main(argv):
    parser = argparse.ArgumentParser(description="Gera ZIP de problema no padrao BOCA a partir de formato simples.")
    parser.add_argument("problem_dir", type=Path, help="Pasta com problem.yml, statement e tests/")
    parser.add_argument("-o", "--output", type=Path, help="Arquivo .zip de saida")
    parser.add_argument(
        "--templates",
        type=Path,
        default=Path(__file__).resolve().parents[2] / "doc" / "problemexamples" / "problemtemplate",
        help="Pasta com scripts BOCA usados como template",
    )
    parser.add_argument("--compile-pdf", action="store_true", help="Compila statement.tex com pdflatex e usa PDF no pacote")
    parser.add_argument("--keep-stage", action="store_true", help="Mantem a pasta gerada ao lado do ZIP para inspecao")
    args = parser.parse_args(argv)
    problem_dir = args.problem_dir
    output = args.output or (Path.cwd() / (problem_dir.name + ".zip"))
    try:
        config, case_count = build_problem(problem_dir, output, args.templates, args.compile_pdf, args.keep_stage)
    except BuildError as exc:
        print(f"erro: {exc}", file=sys.stderr)
        return 1
    print(f"ZIP gerado: {output}")
    print(f"Problema: {config['basename']} - {config['fullname']}")
    print(f"Linguagens: {', '.join(config['languages'])}")
    print(f"Autotestes: {', '.join(config['test_languages'])}")
    print(f"Casos: {case_count}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
