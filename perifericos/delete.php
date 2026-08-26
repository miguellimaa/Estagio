<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION['user']['perfil'] != 'admin') {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Acesso negado para exclusão.'
    ];
    header("Location: read.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: read.php");
    exit;
}

if (!validate_csrf()) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Falha de segurança (CSRF).'
    ];
    header("Location: read.php");
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'ID inválido.'
    ];
    header("Location: read.php");
    exit;
}

$stmt = $pdo->prepare("
    UPDATE perifericos
    SET deleted_at = NOW()
    WHERE id = ? AND deleted_at IS NULL
");

$stmt->execute([$id]);

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Periférico movido para a lixeira.'
];

header("Location: read.php");
exit;
