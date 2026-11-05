<?php
$generating = isset($_POST['SubmitWizard']) && $_POST['SubmitWizard'] == 'Gerar ZIP';
$previewing = isset($_POST['SubmitPreview']) && $_POST['SubmitPreview'] == 'Pré-visualizar PDF';
if($generating || $previewing) $_POST['noflush'] = 'true';
require('header.php');
require_once('../private/problem_builder_web.php');
if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

function bp_html($text) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function bp_error_page($msg) {
	while(ob_get_level() > 0) @ob_end_clean();
	header("Content-Type: text/html; charset=utf-8");
	echo "<html><body><script>alert('" . str_replace("'", "\\'", $msg) . "');history.back();</script></body></html>";
	exit;
}

function bp_positive_int($name, $fallback, $min=1, $max=100000) {
	$value = isset($_POST[$name]) ? trim($_POST[$name]) : '';
	if($value == '') return $fallback;
	if(!ctype_digit($value)) bp_error_page('Campo numérico inválido: ' . $name);
	$value = intval($value);
	if($value < $min || $value > $max) bp_error_page('Valor fora do limite: ' . $name);
	return $value;
}

function bp_collect_cases() {
	$inputs = isset($_POST['case_input']) && is_array($_POST['case_input']) ? $_POST['case_input'] : array();
	$outputs = isset($_POST['case_output']) && is_array($_POST['case_output']) ? $_POST['case_output'] : array();
	$visibilities = isset($_POST['case_visibility']) && is_array($_POST['case_visibility']) ? $_POST['case_visibility'] : array();
	$cases = array();
	$total = max(count($inputs), count($outputs));
	for($i = 0; $i < $total; $i++) {
		$input = isset($inputs[$i]) ? $inputs[$i] : '';
		$output = isset($outputs[$i]) ? $outputs[$i] : '';
		if(trim($input) == '' && trim($output) == '') continue;
		if(trim($input) == '' || trim($output) == '')
			bp_error_page('Todo caso de teste precisa ter entrada e saída esperada.');
		$visibility = isset($visibilities[$i]) ? $visibilities[$i] : 'opaque';
		$cases[] = array('input' => $input, 'output' => $output, 'public' => $visibility == 'public');
	}
	if(count($cases) == 0)
		bp_error_page('Inclua pelo menos um caso de teste.');
	return $cases;
}

function bp_public_examples($cases) {
	$examples = array();
	foreach($cases as $case) {
		if(isset($case['public']) && $case['public'])
			$examples[] = array('input' => $case['input'], 'output' => $case['output']);
	}
	return $examples;
}

function bp_post_text($name) {
	return isset($_POST[$name]) ? trim($_POST[$name]) : '';
}

function bp_collect_latex_statement($fullname, $cases) {
	$title = bp_post_text('latex_title');
	if($title == '') $title = $fullname;
	$description = bp_post_text('latex_description');
	$input = bp_post_text('latex_input');
	$output = bp_post_text('latex_output');
	if($description == '' || $input == '' || $output == '')
		bp_error_page('No template LaTeX, preencha descrição, entrada e saída.');
	$fields = array(
		'title' => $title,
		'description' => $description,
		'input' => $input,
		'output' => $output,
		'examples' => bp_public_examples($cases),
		'notes' => bp_post_text('latex_notes')
	);
	return array('statement.tex', bpw_latex_statement($fields));
}

function bp_collect_statement($fullname, $cases) {
	$mode = isset($_POST['statement_mode']) ? $_POST['statement_mode'] : 'latex';
	if($mode == 'latex')
		return bp_collect_latex_statement($fullname, $cases);
	$manual = isset($_POST['statement_text']) ? trim($_POST['statement_text']) : '';
	if($mode == 'file') {
		if(!isset($_FILES['statement_file']) || $_FILES['statement_file']['error'] == UPLOAD_ERR_NO_FILE)
			bp_error_page('Envie um arquivo de enunciado.');
		if($_FILES['statement_file']['error'] != UPLOAD_ERR_OK)
			bp_error_page('Falha no upload do enunciado.');
		if(!is_uploaded_file($_FILES['statement_file']['tmp_name']))
			bp_error_page('Upload inválido do enunciado.');
		$name = bpw_sanitize_filename($_FILES['statement_file']['name'], 'statement.pdf');
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if(!in_array($ext, array('pdf', 'txt', 'md', 'tex')))
			bp_error_page('Use enunciado em PDF, TXT, MD ou TEX.');
		$content = file_get_contents($_FILES['statement_file']['tmp_name']);
		if($content === false || strlen($content) == 0)
			bp_error_page('Não foi possível ler o enunciado enviado.');
		return array($name, $content);
	}
	if($mode != 'manual')
		bp_error_page('Modo de enunciado inválido.');
	if($manual == '')
		bp_error_page('Informe o enunciado manualmente ou envie um arquivo.');
	return array('statement.md', "# Enunciado\n\n" . $manual . "\n");
}

function bp_generate_zip() {
	$basename = isset($_POST['basename']) ? bpw_sanitize_filename($_POST['basename'], '') : '';
	$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
	if($basename == '') bp_error_page('Informe o basename do problema.');
	if($fullname == '') bp_error_page('Informe o nome completo do problema.');
	$languages = isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : array('c', 'cpp', 'java', 'py3');
	$cases = bp_collect_cases();
	list($statementName, $statementContent) = bp_collect_statement($fullname, $cases);
	$spec = array(
		'basename' => $basename,
		'fullname' => $fullname,
		'time' => bp_positive_int('time_limit', 1, 1, 300),
		'repetitions' => bp_positive_int('repetitions', 1, 1, 100),
		'memory' => bp_positive_int('memory_limit', 512, 16, 32768),
		'output' => bp_positive_int('output_limit', 1024, 1, 1048576),
		'languages' => $languages,
		'statement_name' => $statementName,
		'statement_content' => $statementContent,
		'compile_pdf' => isset($_POST['statement_mode']) && $_POST['statement_mode'] == 'latex',
		'cases' => $cases
	);
	$temp = tempnam(sys_get_temp_dir(), 'boca_problem_');
	if($temp === false) bp_error_page('Não foi possível criar arquivo temporário.');
	@unlink($temp);
	$zipPath = $temp . '.zip';
	try {
		bpw_create_package(dirname(__DIR__, 2), $spec, $zipPath);
		$data = file_get_contents($zipPath);
		@unlink($zipPath);
		if($data === false) bp_error_page('Não foi possível ler o ZIP gerado.');
		while(ob_get_level() > 0) @ob_end_clean();
		header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"" . $basename . ".zip\"");
		echo $data;
		exit;
	} catch(Exception $e) {
		@unlink($zipPath);
		bp_error_page($e->getMessage());
	}
}

function bp_preview_pdf() {
	$basename = isset($_POST['basename']) ? bpw_sanitize_filename($_POST['basename'], '') : '';
	$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
	if($basename == '') bp_error_page('Informe o basename do problema.');
	if($fullname == '') bp_error_page('Informe o nome completo do problema.');
	$mode = isset($_POST['statement_mode']) ? $_POST['statement_mode'] : 'latex';
	if($mode != 'latex')
		bp_error_page('A pré-visualização em PDF está disponível apenas para o template LaTeX Caramel Coders.');
	$cases = bp_collect_cases();
	list($statementName, $statementContent) = bp_collect_latex_statement($fullname, $cases);
	$temp = tempnam(sys_get_temp_dir(), 'boca_pdf_');
	if($temp === false) bp_error_page('Não foi possível criar arquivo temporário.');
	@unlink($temp);
	$dir = $temp . '_preview';
	try {
		bpw_mkdir($dir);
		bpw_write_file($dir . DIRECTORY_SEPARATOR . $statementName, $statementContent, 0640);
		$logo = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . bpw_banner_name();
		if(is_readable($logo))
			bpw_copy_file($logo, $dir . DIRECTORY_SEPARATOR . 'banner-maratona.png', 0644);
		$pdfName = bpw_compile_latex_statement($dir, $statementName);
		$pdfPath = $dir . DIRECTORY_SEPARATOR . $pdfName;
		$data = file_get_contents($pdfPath);
		bpw_remove_dir($dir);
		if($data === false) bp_error_page('Não foi possível ler o PDF gerado.');
		while(ob_get_level() > 0) @ob_end_clean();
		header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-Type: application/pdf");
		header("Content-Disposition: inline; filename=\"" . $basename . "-preview.pdf\"");
		echo $data;
		exit;
	} catch(Exception $e) {
		bpw_remove_dir($dir);
		bp_error_page($e->getMessage());
	}
}

if($generating) bp_generate_zip();
if($previewing) bp_preview_pdf();
$langs = bpw_allowed_languages();
?>
<style>
.cc-builder { width: 94%; margin: 18px auto 40px auto; }
.cc-panel { border: 1px solid #555555; background: #efefdf; padding: 14px; }
.cc-head { width: 100%; border-collapse: collapse; background: #eeee00; border: 1px solid #555555; }
.cc-head td { padding: 10px; vertical-align: middle; }
.cc-logo { width: 118px; height: auto; display: block; }
.cc-title { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 18pt; font-weight: bold; }
.cc-subtitle { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; margin-top: 4px; }
.cc-section { margin-top: 14px; border: 1px solid #555555; background: #dfdfdf; }
.cc-section-title { background: #d0d0c0; padding: 7px 9px; font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; border-bottom: 1px solid #555555; }
.cc-section-body { padding: 10px; }
.cc-grid { width: 100%; border-collapse: collapse; }
.cc-grid td { padding: 5px; vertical-align: top; }
.cc-label { width: 230px; text-align: right; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11pt; }
.cc-help { color: #333333; font-size: 10pt; }
.cc-case { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.cc-case th { background: #eeee00; border: 1px solid #555555; padding: 4px; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; }
.cc-case td { border: 1px solid #555555; background: #f7f7ef; padding: 4px; }
.cc-case textarea { width: 98%; height: 95px; font-family: "Courier New", Courier, mono; font-size: 10pt; }
.cc-case select { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; }
.cc-actions { text-align: center; margin-top: 14px; }
.cc-warning { border: 1px solid #aa8800; background: #fff8bf; padding: 8px; margin-top: 8px; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; }
.cc-small-button { font-size: 10pt; }
.cc-mode { border: 1px solid #555555; background: #f7f7ef; padding: 8px; margin-bottom: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; }
.cc-mode label { margin-right: 18px; white-space: nowrap; }
.cc-mode-box { display: none; }
.cc-mode-box textarea { width: 94%; font-family: "Courier New", Courier, mono; font-size: 10pt; }
.cc-latex-preview { border: 1px solid #555555; background: #ffffff; text-align: center; padding: 16px; margin-bottom: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; }
.cc-latex-preview img { width: 130px; height: auto; display: block; margin: 10px auto 16px auto; }
.cc-latex-preview-title { font-size: 15pt; font-weight: bold; }
@media (max-width: 760px) {
	.cc-builder { width: 98%; }
	.cc-logo { width: 82px; }
	.cc-title { font-size: 14pt; }
	.cc-label { width: auto; text-align: left; display: block; }
	.cc-grid td { display: block; width: 100%; }
}
</style>
<script language="javascript">
function addCase() {
	var area = document.getElementById('cases');
	var total = area.getElementsByTagName('table').length + 1;
	var table = document.createElement('table');
	table.className = 'cc-case';
	table.innerHTML = '<tr><th colspan="2">Caso de teste ' + total + '</th></tr>' +
		'<tr><td colspan="2">Tipo do caso<br><select name="case_visibility[]"><option value="public">Público: aparece no PDF e no julgamento automático</option><option value="opaque" selected>Opaco: apenas julgamento automático</option></select></td></tr>' +
		'<tr><td width="50%">Entrada<br><textarea name="case_input[]"></textarea></td>' +
		'<td width="50%">Saída esperada<br><textarea name="case_output[]"></textarea></td></tr>';
	area.appendChild(table);
}
function removeCase() {
	var area = document.getElementById('cases');
	var tables = area.getElementsByTagName('table');
	if(tables.length > 1) area.removeChild(tables[tables.length - 1]);
}
function statementMode() {
	var modes = document.getElementsByName('statement_mode');
	for(var i = 0; i < modes.length; i++) {
		if(modes[i].checked) return modes[i].value;
	}
	return 'latex';
}
function showStatementMode() {
	var mode = statementMode();
	var boxes = ['latex', 'file', 'manual'];
	for(var i = 0; i < boxes.length; i++) {
		var box = document.getElementById('statement_' + boxes[i]);
		if(box) box.style.display = boxes[i] == mode ? 'block' : 'none';
	}
}
function validateBuilder() {
	var action = document.getElementById('builder_action');
	var builderAction = action ? action.value : 'zip';
	if(document.formbuilder.fullname.value == '' || document.formbuilder.basename.value == '') {
		alert('Informe o nome completo e o basename.');
		return false;
	}
	var mode = statementMode();
	if(builderAction == 'preview' && mode != 'latex') {
		alert('A pré-visualização em PDF está disponível apenas para o template LaTeX Caramel Coders.');
		return false;
	}
	if(mode == 'latex') {
		if(document.formbuilder.latex_description.value == '' || document.formbuilder.latex_input.value == '' || document.formbuilder.latex_output.value == '') {
			alert('No template LaTeX, preencha descrição, entrada e saída.');
			return false;
		}
	} else if(mode == 'file') {
		if(document.formbuilder.statement_file.value == '') {
			alert('Envie um arquivo de enunciado.');
			return false;
		}
	} else if(mode == 'manual') {
		if(document.formbuilder.statement_text.value == '') {
			alert('Preencha o texto manual do enunciado.');
			return false;
		}
	}
	var inputs = document.getElementsByName('case_input[]');
	var outputs = document.getElementsByName('case_output[]');
	var ok = false;
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].value.replace(/\s/g, '') != '' && outputs[i].value.replace(/\s/g, '') != '') ok = true;
	}
	if(!ok) {
		alert('Inclua pelo menos um caso de teste completo.');
		return false;
	}
	return true;
}
function setBuilderAction(action) {
	var field = document.getElementById('builder_action');
	if(field) field.value = action;
}
window.onload = function() { showStatementMode(); };
</script>
<div class="cc-builder">
	<table class="cc-head">
		<tr>
			<td width="130" align="center"><img class="cc-logo" src="../images/caramel-coders.png" alt="Caramel Coders"></td>
			<td>
				<div class="cc-title">Gerador de Problemas BOCA</div>
				<div class="cc-subtitle">Interface Caramel Coders para montar o ZIP de questão sem editar pastas, scripts ou arquivos internos.</div>
			</td>
			<td align="right" nowrap><a class="menu" href="problem.php">Voltar para Questões</a></td>
		</tr>
	</table>
	<form name="formbuilder" enctype="multipart/form-data" method="post" action="buildproblem.php" onsubmit="return validateBuilder();">
		<input type="hidden" name="builder_action" id="builder_action" value="zip">
		<div class="cc-section">
			<div class="cc-section-title">Dados do problema</div>
			<div class="cc-section-body">
				<table class="cc-grid">
					<tr>
						<td class="cc-label">Nome completo:</td>
						<td><input type="text" name="fullname" size="60" maxlength="100" value=""> <span class="cc-help">Exemplo: Soma Simples</span></td>
					</tr>
					<tr>
						<td class="cc-label">Nome do arquivo esperado (basename):</td>
						<td><input type="text" name="basename" size="30" maxlength="60" value=""> <span class="cc-help">Para receber A.java, use A. Para receber F.java, use F.</span></td>
					</tr>
					<tr>
						<td class="cc-label">Tempo:</td>
						<td><input type="text" name="time_limit" size="6" value="1"> segundos &nbsp; Repetições: <input type="text" name="repetitions" size="6" value="1"></td>
					</tr>
					<tr>
						<td class="cc-label">Memória e saída:</td>
						<td><input type="text" name="memory_limit" size="8" value="512"> MB &nbsp; <input type="text" name="output_limit" size="8" value="1024"> KB</td>
					</tr>
					<tr>
						<td class="cc-label">Linguagens:</td>
						<td>
<?php foreach($langs as $key => $label) { ?>
							<label><input type="checkbox" name="languages[]" value="<?php echo bp_html($key); ?>"<?php echo ' checked'; ?>> <?php echo bp_html($label); ?></label>&nbsp;&nbsp;
<?php } ?>
							<div class="cc-warning">O autoteste interno do pacote usa Python3 por padrão para evitar falha do jail quando C/C++ não estiver instalado.</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="cc-section">
			<div class="cc-section-title">Enunciado</div>
			<div class="cc-section-body">
				<div class="cc-mode">
					<label><input type="radio" name="statement_mode" value="latex" checked onclick="showStatementMode();"> Template LaTeX Caramel Coders</label>
					<label><input type="radio" name="statement_mode" value="file" onclick="showStatementMode();"> Arquivo pronto</label>
					<label><input type="radio" name="statement_mode" value="manual" onclick="showStatementMode();"> Texto manual simples</label>
				</div>
				<div id="statement_latex" class="cc-mode-box">
					<div class="cc-latex-preview">
						<div><b>Instituto Federal Goiano</b></div>
						<div class="cc-help">Campus Urutaí | Caramel Coders BOCA</div>
						<img style="width:100%;max-width:500px" src="../images/<?php echo bp_html(bpw_banner_name()); ?>" alt="Banner da maratona">
						<div class="cc-latex-preview-title">Título da questão</div>
						<div class="cc-help">O ZIP terá um statement.pdf com identidade visual do IF Goiano - Campus Urutaí.</div>
					</div>
					<table class="cc-grid">
						<tr>
							<td class="cc-label">Título no enunciado:</td>
							<td><input type="text" name="latex_title" size="60" maxlength="100" value=""> <span class="cc-help">Se vazio, usa o nome completo.</span></td>
						</tr>
						<tr>
							<td class="cc-label">Descrição:</td>
							<td><textarea name="latex_description" rows="7" cols="90"></textarea></td>
						</tr>
						<tr>
							<td class="cc-label">Entrada:</td>
							<td><textarea name="latex_input" rows="5" cols="90"></textarea></td>
						</tr>
						<tr>
							<td class="cc-label">Saída:</td>
							<td><textarea name="latex_output" rows="5" cols="90"></textarea></td>
						</tr>
						<tr>
							<td class="cc-label">Exemplos no PDF:</td>
							<td><div class="cc-warning">O PDF usa os casos de teste marcados como Público. Casos Opacos entram no ZIP e no julgamento automático, mas não aparecem no enunciado.</div></td>
						</tr>
						<tr>
							<td class="cc-label">Observações:</td>
							<td><textarea name="latex_notes" rows="4" cols="90"></textarea></td>
						</tr>
					</table>
				</div>
				<div id="statement_file" class="cc-mode-box">
					<table class="cc-grid">
						<tr>
							<td class="cc-label">Arquivo pronto:</td>
							<td><input type="file" name="statement_file" size="45"> <span class="cc-help">PDF, TXT, MD ou TEX.</span></td>
						</tr>
					</table>
				</div>
				<div id="statement_manual" class="cc-mode-box">
					<table class="cc-grid">
						<tr>
							<td class="cc-label">Texto manual:</td>
							<td><textarea class="edit" name="statement_text" rows="10" cols="90"></textarea></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div class="cc-section">
			<div class="cc-section-title">Casos de teste</div>
			<div class="cc-section-body">
				<div id="cases">
					<table class="cc-case">
						<tr><th colspan="2">Caso de teste 1</th></tr>
						<tr>
							<td colspan="2">Tipo do caso<br>
								<select name="case_visibility[]">
									<option value="public" selected>Público: aparece no PDF e no julgamento automático</option>
									<option value="opaque">Opaco: apenas julgamento automático</option>
								</select>
							</td>
						</tr>
						<tr>
							<td width="50%">Entrada<br><textarea name="case_input[]"></textarea></td>
							<td width="50%">Saída esperada<br><textarea name="case_output[]"></textarea></td>
						</tr>
					</table>
				</div>
				<div class="cc-warning">Cada caso vira um par separado de arquivos no ZIP: input/001 com output/001, input/002 com output/002, e assim por diante.</div>
				<input type="button" class="cc-small-button" value="Adicionar caso" onclick="addCase();">
				<input type="button" class="cc-small-button" value="Remover último" onclick="removeCase();">
			</div>
		</div>
		<div class="cc-actions">
			<input type="submit" name="SubmitPreview" value="Pré-visualizar PDF" formtarget="_blank" onclick="setBuilderAction('preview');">
			<input type="submit" name="SubmitWizard" value="Gerar ZIP" onclick="setBuilderAction('zip');">
			<input type="reset" value="Limpar">
		</div>
	</form>
	<div class="cc-warning">Use Pré-visualizar PDF para conferir o enunciado em nova aba. Depois de baixar o ZIP, volte para Questões, escolha o número e nome curto do problema, envie o pacote no campo Pacote da questão (ZIP) e confirme.</div>
</div>
</body>
</html>
