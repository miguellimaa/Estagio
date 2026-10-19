<?php
require '../config/conexao.php';
require_login();

$lowStockThreshold = setting_get_int($pdo, 'low_stock_threshold', 5);
$tipo = trim((string) ($_GET['tipo'] ?? ''));
$status = (string) ($_GET['status'] ?? 'todos');
$busca = trim((string) ($_GET['busca'] ?? ''));
$where = ['deleted_at IS NULL'];
$params = [];

if ($tipo !== '') { $where[] = 'tipo = ?'; $params[] = $tipo; }
if ($status === 'baixo') { $where[] = 'quantidade <= ?'; $params[] = $lowStockThreshold; }
if ($status === 'regular') { $where[] = 'quantidade > ?'; $params[] = $lowStockThreshold; }
if ($busca !== '') {
    $where[] = '(nome_ativo LIKE ? OR numero_serie LIKE ? OR chave_licenca LIKE ?)';
    $searchParam = '%' . $busca . '%';
    array_push($params, $searchParam, $searchParam, $searchParam);
}

$delimiter = setting_get($pdo, 'csv_delimiter', ';');
$delimiter = in_array($delimiter, [';', ','], true) ? $delimiter : ';';
$stmt = $pdo->prepare('SELECT nome_ativo, tipo, quantidade, valor, numero_serie, chave_licenca FROM perifericos WHERE ' . implode(' AND ', $where) . ' ORDER BY nome_ativo ASC');
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="relatorio-inventario-' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Nome', 'Tipo', 'Quantidade', 'Valor Unitario', 'Numero de Serie', 'Chave de Licenca'], $delimiter);
foreach ($stmt as $item) {
    fputcsv($output, [$item['nome_ativo'], $item['tipo'], $item['quantidade'], $item['valor'], $item['numero_serie'], $item['chave_licenca']], $delimiter);
}
fclose($output);
exit;
