<?php

function load_env_file(string $path): array
{
    $values = [];

    if (!is_file($path)) {
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $values[$key] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

$env = load_env_file(__DIR__ . '/../.env');
$env = array_merge($_ENV, $_SERVER, $env);

$dbHost = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
$dbName = getenv('DB_NAME') ?: ($env['DB_NAME'] ?? 'irtrade_estoque');
$dbUser = getenv('DB_USER') ?: ($env['DB_USER'] ?? 'root');
$dbPass = getenv('DB_PASS') ?: ($env['DB_PASS'] ?? '');

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8";

try {
	$pdo = new PDO($dsn, $dbUser, $dbPass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Erro de conexão</title><style>body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#f8fafc;color:#111827;display:grid;place-items:center;min-height:100vh}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;max-width:560px;box-shadow:0 8px 30px rgba(0,0,0,.06)}h1{margin:0 0 8px;font-size:1.2rem}p{margin:0;color:#6b7280}</style></head><body><div class="card"><h1>Não foi possível conectar ao banco de dados</h1><p>Verifique se o MySQL do XAMPP está iniciado e se as variáveis de ambiente/configuração estão corretas.</p></div></body></html>';
	exit;
}

if (session_status() === PHP_SESSION_NONE) {
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');

	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'domain' => '',
		'secure' => $secure,
		'httponly' => true,
		'samesite' => 'Lax',
	]);

	session_start();
}

require_once __DIR__ . '/helpers.php';

ensure_perifericos_value_column($pdo);
ensure_usuarios_avatar_column($pdo);
ensure_settings_table($pdo);
ensure_trash_cleanup_logs_table($pdo);
ensure_database_indexes($pdo);

$trashAutoDeleteDays = setting_get_int($pdo, 'trash_auto_delete_days', 90);

if ($trashAutoDeleteDays > 0) {
	$now = time();
	$lastCleanupRun = (int) ($_SESSION['trash_auto_cleanup_last_run'] ?? 0);
	$cleanupIntervalSeconds = 300;

	if ($lastCleanupRun <= 0 || ($now - $lastCleanupRun) >= $cleanupIntervalSeconds) {
		$_SESSION['trash_auto_cleanup_last_run'] = $now;

		$cutoffDate = date('Y-m-d H:i:s', $now - ($trashAutoDeleteDays * 86400));
		$cleanupStmt = $pdo->prepare('DELETE FROM perifericos WHERE deleted_at IS NOT NULL AND deleted_at <= ?');
		$cleanupStmt->execute([$cutoffDate]);
		$deletedCount = (int) $cleanupStmt->rowCount();
		$currentUserId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
		$requestUri = $_SERVER['REQUEST_URI'] ?? null;

		log_trash_cleanup(
			$pdo,
			$trashAutoDeleteDays,
			$cutoffDate,
			$deletedCount,
			$currentUserId,
			$requestUri
		);

		if ($deletedCount > 0) {
			$_SESSION['trash_auto_cleanup_notice'] = [
				'count' => $deletedCount,
				'days' => $trashAutoDeleteDays,
				'at' => $now,
			];
		}
	}
}

if (empty($_SESSION['csrf_token'])) {
	try {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	} catch (Exception $e) {
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
	}
}

$sessionTimeoutMinutes = setting_get_int($pdo, 'session_timeout_minutes', 30);
$sessionTimeoutSeconds = max(300, $sessionTimeoutMinutes * 60);

if (!empty($_SESSION['user'])) {
	$now = time();
	$lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

	if ($lastActivity > 0 && ($now - $lastActivity) > $sessionTimeoutSeconds) {
		session_unset();
		session_destroy();
		session_start();
		flash('warning', 'Sessao encerrada por inatividade. Faça login novamente.');
		header('Location: /Estagio-main/auth/login.php');
		exit;
	}

	$_SESSION['last_activity'] = $now;
}