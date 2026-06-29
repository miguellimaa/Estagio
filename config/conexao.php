<?php

// Configuracao padrao para ambiente XAMPP local.
$dbHost = 'localhost';
$dbName = 'irtrade_estoque';
$dbUser = 'root';
$dbPass = '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8";

try {
	$pdo = new PDO($dsn, $dbUser, $dbPass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Erro de conexão</title><style>body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#f8fafc;color:#111827;display:grid;place-items:center;min-height:100vh}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;max-width:560px;box-shadow:0 8px 30px rgba(0,0,0,.06)}h1{margin:0 0 8px;font-size:1.2rem}p{margin:0;color:#6b7280}</style></head><body><div class="card"><h1>Não foi possível conectar ao banco de dados</h1><p>Verifique se o MySQL do XAMPP está iniciado e se as credenciais em config/conexao.php estão corretas.</p></div></body></html>';
	exit;
}

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/helpers.php';

if (empty($_SESSION['csrf_token'])) {
	try {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	} catch (Exception $e) {
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
	}
}