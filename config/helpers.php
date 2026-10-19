<?php
if (defined('IRTRADE_HELPERS_LOADED')) {
    return;
}

define('IRTRADE_HELPERS_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
        }
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return hash_equals(csrf_token(), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_message(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    $passwordInfo = password_get_info($storedHash);
    if (is_array($passwordInfo) && isset($passwordInfo['algo']) && $passwordInfo['algo'] !== null && $passwordInfo['algo'] !== 0) {
        return password_verify($password, $storedHash);
    }

    return hash_equals((string) $storedHash, $password);
}

function escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function save_user_avatar(array $file): ?string
{
    if (empty($file['name']) || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return null;
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mimeType = strtolower((string) ($file['type'] ?? ''));
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!in_array($mimeType, $allowedMimeTypes, true) && !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return null;
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/usuarios';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return null;
    }

    $safeExtension = $extension !== '' ? $extension : match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };

    try {
        $filename = 'user_' . bin2hex(random_bytes(8)) . '.' . $safeExtension;
    } catch (Exception $e) {
        $filename = 'user_' . bin2hex(openssl_random_pseudo_bytes(8)) . '.' . $safeExtension;
    }

    $targetPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/usuarios/' . $filename;
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: /Estagio-main/auth/login.php');
        exit;
    }
}

function require_admin(string $redirectTo = '/Estagio-main/index.php'): void
{
    require_login();

    if (!is_admin()) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Acesso restrito ao administrador.'];
        header('Location: ' . $redirectTo);
        exit;
    }
}

function is_admin(): bool
{
    return !empty($_SESSION['user']['perfil']) && $_SESSION['user']['perfil'] === 'admin';
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function ensure_settings_table(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    $initialized = true;
}

function ensure_perifericos_value_column(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute(['perifericos', 'valor']);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE perifericos ADD COLUMN valor DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantidade');
    }

    $initialized = true;
}

function ensure_usuarios_avatar_column(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute(['usuarios', 'foto_perfil']);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL DEFAULT NULL AFTER perfil');
    }

    $initialized = true;
}

function ensure_trash_cleanup_logs_table(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS trash_cleanup_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            executed_at DATETIME NOT NULL,
            retention_days INT NOT NULL,
            cutoff_datetime DATETIME NOT NULL,
            deleted_count INT NOT NULL DEFAULT 0,
            user_id INT NULL,
            request_uri VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_executed_at (executed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    $initialized = true;
}

function settings_defaults(): array
{
    return [
        'low_stock_threshold' => '5',
        'table_per_page' => '8',
        'session_timeout_minutes' => '30',
        'export_role' => 'admin',
        'csv_delimiter' => ';',
        'csv_columns' => 'nome,tipo,quantidade',
        'trash_auto_delete_days' => '90',
    ];
}

function setting_get(PDO $pdo, string $key, ?string $default = null): ?string
{
    ensure_settings_table($pdo);

    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    if ($value === false || $value === null) {
        $defaults = settings_defaults();
        return $default ?? ($defaults[$key] ?? null);
    }

    return (string) $value;
}

function setting_get_int(PDO $pdo, string $key, int $default): int
{
    $value = setting_get($pdo, $key, (string) $default);

    if ($value === null || !is_numeric($value)) {
        return $default;
    }

    return (int) $value;
}

function setting_set(PDO $pdo, string $key, string $value): void
{
    ensure_settings_table($pdo);

    $stmt = $pdo->prepare('
        INSERT INTO settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');
    $stmt->execute([$key, $value]);
}

function setting_set_many(PDO $pdo, array $settings): void
{
    foreach ($settings as $key => $value) {
        setting_set($pdo, (string) $key, (string) $value);
    }
}

function log_trash_cleanup(
    PDO $pdo,
    int $retentionDays,
    string $cutoffDateTime,
    int $deletedCount,
    ?int $userId = null,
    ?string $requestUri = null
): void {
    ensure_trash_cleanup_logs_table($pdo);

    $stmt = $pdo->prepare('
        INSERT INTO trash_cleanup_logs (executed_at, retention_days, cutoff_datetime, deleted_count, user_id, request_uri)
        VALUES (NOW(), ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $retentionDays,
        $cutoffDateTime,
        $deletedCount,
        $userId,
        $requestUri,
    ]);
}

function ensure_database_indexes(PDO $pdo): void
{
    $indexes = [
        'perifericos' => [
            ['deleted_at', 'deleted_at'],
            ['deleted_at_quantidade', 'deleted_at', 'quantidade'],
            ['deleted_at_tipo', 'deleted_at', 'tipo'],
            ['deleted_at_nome_ativo', 'deleted_at', 'nome_ativo'],
        ],
        'usuarios' => [
            ['email', 'email'],
            ['perfil', 'perfil'],
        ],
    ];

    foreach ($indexes as $table => $tableIndexes) {
        foreach ($tableIndexes as $index) {
            $indexName = $index[0];
            $indexColumns = array_slice($index, 1);

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
            $stmt->execute([$table, $indexName]);

            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }

            $columnList = implode(', ', $indexColumns);
            $pdo->exec('CREATE INDEX ' . $indexName . ' ON ' . $table . ' (' . $columnList . ')');
        }
    }
}

function get_recent_trash_cleanup_logs(PDO $pdo, int $limit = 20, ?string $fromDateTime = null): array
{
    ensure_trash_cleanup_logs_table($pdo);

    $limit = max(1, min(100, $limit));
    $sql = '
        SELECT id, executed_at, retention_days, cutoff_datetime, deleted_count, user_id, request_uri
        FROM trash_cleanup_logs';

    $params = [];
    if (!empty($fromDateTime)) {
        $sql .= ' WHERE executed_at >= ?';
        $params[] = $fromDateTime;
    }

    $sql .= ' ORDER BY executed_at DESC LIMIT ' . (int) $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
