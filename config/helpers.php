<?php
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

function validate_csrf(string $token = null): bool
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

function escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: /Estagio-main/auth/login.php');
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
