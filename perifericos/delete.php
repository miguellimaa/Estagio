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

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("ID invalido");
}

$stmt = $pdo->prepare("DELETE FROM perifericos WHERE id=?");
$stmt->execute([$id]);

header("Location: read.php");
exit;
