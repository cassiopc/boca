<?php
function bpw_banner_name() {
    return getenv('SEMINFO_PROFILE') === 'tii' ? 'caramel-claro.png' : 'caramel-espresso.png';
}

function bpw_allowed_languages() {
	return array(
		'c' => 'C',
		'cpp' => 'C++20',
		'java' => 'Java',
		'py3' => 'Python3'
	);
}

function bpw_sanitize_filename($name, $fallback) {
	$name = basename(trim($name));
	$name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
	$name = trim($name, '._-');
	if($name == '') $name = $fallback;
	return $name;
}

function bpw_normalize_newlines($text) {
	return str_replace(array("\r\n", "\r"), "\n", $text);
}

function bpw_latex_escape($text) {
	$text = bpw_normalize_newlines($text);
	$map = array(
		'\\' => '\\textbackslash{}',
		'{' => '\\{',
		'}' => '\\}',
		'$' => '\\$',
		'&' => '\\&',
		'%' => '\\%',
		'#' => '\\#',
		'_' => '\\_',
		'^' => '\\textasciicircum{}',
		'~' => '\\textasciitilde{}'
	);
	return strtr($text, $map);
}

function bpw_latex_paragraphs($text) {
	$text = trim(bpw_latex_escape($text));
	if($text == '') return '';
	$paragraphs = preg_split("/\n\s*\n/", $text);
	return implode("\n\n", $paragraphs) . "\n";
}

function bpw_latex_section($title, $text) {
	$text = trim($text);
	if($text == '') return '';
	return "\\ccsection{" . bpw_latex_escape($title) . "}\n" .
		"{\\color{cctextgray}\n" .
		bpw_latex_paragraphs($text) .
		"}\n\n";
}

function bpw_latex_code_lines($text) {
	$text = rtrim(bpw_normalize_newlines($text));
	$lines = explode("\n", $text);
	if(count($lines) == 0 || (count($lines) == 1 && $lines[0] == ''))
		$lines = array('');
	$out = array();
	foreach($lines as $line) {
		if($line == '') $out[] = "\\mbox{}";
		else $out[] = str_replace(' ', '~', bpw_latex_escape($line));
	}
	return implode("\\\\\n", $out);
}

function bpw_latex_code_box($text) {
	return "\\begingroup\n" .
		"\\setlength{\\fboxsep}{7pt}\n" .
		"\\fcolorbox{ccboxborder}{ccboxbg}{\\begin{minipage}{0.92\\linewidth}\n" .
		"{\\ttfamily\\small\n" .
		bpw_latex_code_lines($text) . "\n" .
		"}\n" .
		"\\end{minipage}}\n" .
		"\\endgroup\n";
}

function bpw_latex_examples($examples) {
	if(!is_array($examples) || count($examples) == 0) return '';
	$out = "\\ccsection{Exemplos}\n";
	$count = 1;
	foreach($examples as $example) {
		$input = isset($example['input']) ? $example['input'] : '';
		$output = isset($example['output']) ? $example['output'] : '';
		$out .= "\\subsection*{Caso " . $count . "}\n" .
			"\\noindent\\begingroup\n" .
			"\\setlength{\\fboxsep}{8pt}\n" .
			"\\fcolorbox{ifline}{white}{\\begin{minipage}{0.94\\linewidth}\n" .
			"\\begin{minipage}[t]{0.47\\linewidth}\n" .
			"{\\bfseries\\color{ifdark}Entrada}\\\\[0.35em]\n" .
			bpw_latex_code_box($input) .
			"\\end{minipage}\\hfill\n" .
			"\\begin{minipage}[t]{0.47\\linewidth}\n" .
			"{\\bfseries\\color{ifdark}Saída}\\\\[0.35em]\n" .
			bpw_latex_code_box($output) .
			"\\end{minipage}\n\n" .
			"\\end{minipage}}\n" .
			"\\endgroup\n\n" .
			"\\vspace{0.35em}\n\n";
		$count++;
	}
	return $out;
}

function bpw_latex_statement($fields) {
	$title = trim($fields['title']) == '' ? 'Questão' : trim($fields['title']);
	$examples = isset($fields['examples']) ? $fields['examples'] : array();
	return "\\documentclass[11pt,a4paper]{article}\n" .
		"\\usepackage[utf8]{inputenc}\n" .
		"\\usepackage[T1]{fontenc}\n" .
		"\\usepackage[brazilian]{babel}\n" .
		"\\IfFileExists{lmodern.sty}{\\usepackage{lmodern}}{}\n" .
		"\\usepackage{graphicx}\n" .
		"\\usepackage{geometry}\n" .
		"\\usepackage{xcolor}\n" .
		"\\geometry{top=1.7cm,bottom=1.7cm,left=2.3cm,right=2.3cm}\n" .
		"\\definecolor{ifgreen}{HTML}{70472D}\n" .
		"\\definecolor{ifdark}{HTML}{36251D}\n" .
		"\\definecolor{ifline}{HTML}{E5D3BF}\n" .
		"\\definecolor{ifgold}{HTML}{E6B867}\n" .
		"\\definecolor{cctextgray}{HTML}{444444}\n" .
		"\\definecolor{ccboxbg}{HTML}{F7F0D8}\n" .
		"\\definecolor{ccboxborder}{HTML}{B8A978}\n" .
		"\\setlength{\\parindent}{0pt}\n" .
		"\\setlength{\\parskip}{0.35em}\n" .
		"\\newcommand{\\ccsection}[1]{\\vspace{0.40em}\\noindent{\\Large\\bfseries\\color{ifgreen}#1}\\par\\vspace{0.10em}{\\color{ifgold}\\rule{0.18\\linewidth}{1pt}}\\par\\vspace{0.20em}}\n" .
		"\\begin{document}\n" .
		"\\begin{center}\n" .
		"\\includegraphics[width=\\linewidth]{banner-maratona.png}\\\\[0.25cm]\n" .
		"{\\fontsize{20}{24}\\selectfont\\bfseries\\color{ifdark} " . bpw_latex_escape($title) . "}\\\\[0.10cm]\n" .
		"{\\color{ifgreen}\\rule{0.48\\textwidth}{1pt}}\n" .
		"\\end{center}\n\n" .
		bpw_latex_section('Descrição', $fields['description']) .
		bpw_latex_section('Entrada', $fields['input']) .
		bpw_latex_section('Saída', $fields['output']) .
		bpw_latex_examples($examples) .
		bpw_latex_section('Observações', $fields['notes']) .
		"\\vfill\n" .
		"\\begin{center}{\\small\\color{cctextgray}IF Goiano - Campus Uruta\\'{i} | Caramel Coders BOCA}\\end{center}\n" .
		"\\end{document}\n";
}

function bpw_pdflatex_path() {
	$output = array();
	$return = 1;
	@exec('command -v pdflatex 2>/dev/null', $output, $return);
	if($return != 0 || count($output) == 0 || trim($output[0]) == '')
		throw new Exception('pdflatex não encontrado no container BOCA.');
	return trim($output[0]);
}

function bpw_tail($lines, $count=12) {
	if(!is_array($lines)) return '';
	$tail = array_slice($lines, -$count);
	return implode(' ', $tail);
}

function bpw_compile_latex_statement($descriptionDir, $statementName) {
	if(strtolower(pathinfo($statementName, PATHINFO_EXTENSION)) != 'tex')
		return $statementName;
	$pdflatex = bpw_pdflatex_path();
	$current = getcwd();
	$output = array();
	$return = 1;
	@chdir($descriptionDir);
	$cmd = escapeshellarg($pdflatex) . ' -no-shell-escape -interaction=nonstopmode -halt-on-error -output-directory ' . escapeshellarg($descriptionDir) . ' ' . escapeshellarg($statementName) . ' 2>&1';
	@exec($cmd, $output, $return);
	if($current !== false) @chdir($current);
	$pdfName = preg_replace('/\.tex$/i', '.pdf', $statementName);
	$pdfPath = $descriptionDir . DIRECTORY_SEPARATOR . $pdfName;
	@unlink($descriptionDir . DIRECTORY_SEPARATOR . preg_replace('/\.tex$/i', '.aux', $statementName));
	@unlink($descriptionDir . DIRECTORY_SEPARATOR . preg_replace('/\.tex$/i', '.log', $statementName));
	if($return != 0 || !is_readable($pdfPath))
		throw new Exception('Falha ao compilar LaTeX: ' . bpw_tail($output));
	@chmod($pdfPath, 0640);
	return $pdfName;
}

function bpw_mkdir($dir) {
	if(!is_dir($dir) && !@mkdir($dir, 0770, true))
		throw new Exception('Não foi possível criar a pasta temporária do pacote.');
}

function bpw_write_file($path, $content, $mode=0640) {
	if(file_put_contents($path, $content) === false)
		throw new Exception('Não foi possível gravar arquivo temporário do pacote.');
	@chmod($path, $mode);
}

function bpw_copy_file($source, $target, $mode=0755) {
	if(!is_readable($source))
		throw new Exception('Template BOCA ausente: ' . $source);
	if(!@copy($source, $target))
		throw new Exception('Não foi possível copiar template BOCA.');
	@chmod($target, $mode);
}

function bpw_remove_dir($dir) {
	if(!is_dir($dir)) return;
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach($items as $item) {
		if($item->isDir()) @rmdir($item->getPathname());
		else @unlink($item->getPathname());
	}
	@rmdir($dir);
}

function bpw_language_list($languages) {
	$allowed = bpw_allowed_languages();
	$out = array();
	foreach($languages as $language) {
		$language = trim($language);
		if(isset($allowed[$language]) && !in_array($language, $out))
			$out[] = $language;
	}
	if(count($out) == 0) $out[] = 'py3';
	return $out;
}

function bpw_test_script() {
	return "#!/bin/bash\n" .
		"set -e\n" .
		"SCRIPT_DIR=\$(CDPATH= cd -- \"\$(dirname -- \"\$0\")\" && pwd)\n" .
		"PACKAGE_DIR=\$(CDPATH= cd -- \"\$SCRIPT_DIR/..\" && pwd)\n" .
		"cd \"\$SCRIPT_DIR\"\n" .
		"cat > test.py <<'EOF'\n" .
		"import sys\n" .
		"print(sys.stdin.read().strip())\n" .
		"EOF\n" .
		"cat > test.in <<'EOF'\n" .
		"inputdata\n" .
		"EOF\n" .
		"TL=2\n" .
		"REP=1\n" .
		"chmod 755 \"\$PACKAGE_DIR/compile/py3\"\n" .
		"\"\$PACKAGE_DIR/compile/py3\" test.py test.exe \$TL\n" .
		"chmod 755 \"\$PACKAGE_DIR/run/py3\"\n" .
		"\"\$PACKAGE_DIR/run/py3\" test.exe test.in \$TL \$REP\n" .
		"output=\$(cat stdout0 2>/dev/null || true)\n" .
		"if [ \"\$output\" != \"inputdata\" ]; then\n" .
		"  echo \"ERROR\"\n" .
		"  exit 1\n" .
		"fi\n" .
		"echo \"TEST PASSED\"\n" .
		"exit 0\n";
}

function bpw_limit_script($time, $repetitions, $memory, $output) {
	return "#!/bin/bash\n" .
		"echo " . intval($time) . "\n" .
		"echo " . intval($repetitions) . "\n" .
		"echo " . intval($memory) . "\n" .
		"echo " . intval($output) . "\n" .
		"exit 0\n";
}

function bpw_add_to_zip($zip, $stage, $path) {
	$relative = str_replace('\\', '/', substr($path, strlen($stage) + 1));
	if($relative == '' || strpos($relative, '..') !== false)
		throw new Exception('Caminho inválido no pacote.');
	if(is_dir($path)) return;
	if(!$zip->addFile($path, $relative))
		throw new Exception('Não foi possível adicionar arquivo ao ZIP.');
	if(method_exists($zip, 'setExternalAttributesName')) {
		$first = explode('/', $relative);
		$exec = in_array($first[0], array('compile', 'run', 'compare', 'limits', 'tests'));
		$mode = $exec ? 0100755 : 0100644;
		@$zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, $mode << 16);
	}
}

function bpw_zip_dir($stage, $zipPath) {
	if(!class_exists('ZipArchive'))
		throw new Exception('Extensão ZipArchive não está disponível no PHP.');
	$zip = new ZipArchive();
	if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
		throw new Exception('Não foi possível criar o ZIP.');
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach($items as $item)
		bpw_add_to_zip($zip, $stage, $item->getPathname());
	$zip->close();
	if(!is_readable($zipPath))
		throw new Exception('ZIP final não foi gerado.');
}

function bpw_create_package($repoRoot, $spec, $zipPath) {
	$ds = DIRECTORY_SEPARATOR;
	$template = $repoRoot . $ds . 'doc' . $ds . 'problemexamples' . $ds . 'problemtemplate';
	if(!is_dir($template))
		throw new Exception('Template de problemas BOCA não encontrado.');
	$stage = $zipPath . '_stage';
	bpw_remove_dir($stage);
	$dirs = array('description', 'input', 'output', 'limits', 'compile', 'run', 'compare', 'tests');
	foreach($dirs as $dir)
		bpw_mkdir($stage . $ds . $dir);
	$languages = bpw_language_list($spec['languages']);
	$scriptLanguages = $languages;
	if(!in_array('py3', $scriptLanguages))
		$scriptLanguages[] = 'py3';
	foreach($scriptLanguages as $language) {
		foreach(array('compile', 'run', 'compare') as $folder)
			bpw_copy_file($template . $ds . $folder . $ds . $language, $stage . $ds . $folder . $ds . $language);
		bpw_write_file($stage . $ds . 'limits' . $ds . $language, bpw_limit_script($spec['time'], $spec['repetitions'], $spec['memory'], $spec['output']), 0755);
	}
	bpw_write_file($stage . $ds . 'tests' . $ds . 'py3', bpw_test_script(), 0755);
	$statementName = bpw_sanitize_filename($spec['statement_name'], 'statement.md');
	bpw_write_file($stage . $ds . 'description' . $ds . $statementName, $spec['statement_content'], 0640);
	$logo = $repoRoot . $ds . 'src' . $ds . 'images' . $ds . bpw_banner_name();
	if(is_readable($logo))
		bpw_copy_file($logo, $stage . $ds . 'description' . $ds . 'banner-maratona.png', 0644);
	if(isset($spec['compile_pdf']) && $spec['compile_pdf']) {
		$sourceName = $statementName;
		$statementName = bpw_compile_latex_statement($stage . $ds . 'description', $statementName);
		// The PDF embeds the banner. Avoid duplicating it in the uploaded package.
		@unlink($stage . $ds . 'description' . $ds . 'banner-maratona.png');
		@unlink($stage . $ds . 'description' . $ds . $sourceName);
	}
	$info = "basename=" . $spec['basename'] . "\n" .
		"fullname=" . $spec['fullname'] . "\n" .
		"descfile=" . $statementName . "\n";
	bpw_write_file($stage . $ds . 'description' . $ds . 'problem.info', $info, 0640);
	bpw_write_file($stage . $ds . 'description' . $ds . 'caramel-coders.txt', "Caramel Coders BOCA\n", 0640);
	$count = 1;
	foreach($spec['cases'] as $case) {
		$name = sprintf('%03d', $count);
		bpw_write_file($stage . $ds . 'input' . $ds . $name, bpw_normalize_newlines($case['input']), 0640);
		bpw_write_file($stage . $ds . 'output' . $ds . $name, bpw_normalize_newlines($case['output']), 0640);
		$count++;
	}
	try {
		bpw_zip_dir($stage, $zipPath);
	} catch(Exception $e) {
		bpw_remove_dir($stage);
		throw $e;
	}
	bpw_remove_dir($stage);
	return array(
		'zip' => $zipPath,
		'languages' => $languages,
		'cases' => count($spec['cases'])
	);
}
?>
