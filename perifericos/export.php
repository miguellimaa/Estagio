<?php
require '../config/conexao.php';

require_login();

if (setting_get($pdo, 'export_role', 'admin') !== 'usuario' && !is_admin()) {
	flash('danger', 'Acesso negado para exportação.');
	redirect('read.php');
}

$csvDelimiter = setting_get($pdo, 'csv_delimiter', ';') ?? ';';
if (!in_array($csvDelimiter, [';', ','], true)) {
	$csvDelimiter = ';';
}

$columnsMap = [
	'nome' => ['db' => 'nome_ativo', 'label' => 'Nome'],
	'tipo' => ['db' => 'tipo', 'label' => 'Tipo'],
	'quantidade' => ['db' => 'quantidade', 'label' => 'Quantidade'],
	'valor' => ['db' => 'valor', 'label' => 'Valor'],
	'numero_serie' => ['db' => 'numero_serie', 'label' => 'Numero de Serie'],
	'chave_licenca' => ['db' => 'chave_licenca', 'label' => 'Chave de Licenca'],
];

$configuredColumns = setting_get($pdo, 'csv_columns', 'nome,tipo,quantidade') ?? 'nome,tipo,quantidade';
$requestedKeys = array_filter(array_map('trim', explode(',', $configuredColumns)));

$selectedKeys = [];
foreach ($requestedKeys as $key) {
	if (isset($columnsMap[$key])) {
		$selectedKeys[] = $key;
	}
}

if (empty($selectedKeys)) {
	$selectedKeys = ['nome', 'tipo', 'quantidade'];
}

$dbColumns = [];
$headers = [];
foreach ($selectedKeys as $key) {
	$dbColumns[] = $columnsMap[$key]['db'];
	$headers[] = $columnsMap[$key]['label'];
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="perifericos-' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if ($output === false) {
	exit;
}

// BOM UTF-8 para melhor compatibilidade com Excel e caracteres acentuados.
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, $headers, $csvDelimiter);

$selectList = implode(', ', $dbColumns);
$stmt = $pdo->query('SELECT ' . $selectList . ' FROM perifericos WHERE deleted_at IS NULL ORDER BY id DESC');

foreach ($stmt as $d) {
	$row = [];
	foreach ($selectedKeys as $key) {
		$row[] = $d[$columnsMap[$key]['db']] ?? '';
	}

	fputcsv($output, $row, $csvDelimiter);
}

fclose($output);
exit;