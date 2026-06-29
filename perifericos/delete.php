<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['perfil'] != 'admin'){
    die("Acesso negado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: read.php");
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    die("Token CSRF inválido");
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("ID invalido");
}

$stmt = $pdo->prepare("DELETE FROM perifericos WHERE id=?");
$stmt->execute([$id]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Periférico excluído com sucesso.'];

header("Location: read.php");
exit;
