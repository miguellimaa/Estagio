<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');

    if ($nome === '' || $tipo === '') {
        die("Nome e tipo sao obrigatorios");
    }

    if ($quantidade === false || $quantidade === null || $quantidade < 0) {
        die("Quantidade invalida");
    }

    $stmt = $pdo->prepare("INSERT INTO perifericos 
    (nome_ativo, tipo, numero_serie, chave_licenca, quantidade)
    VALUES (?, ?, ?, ?, ?)");

    $stmt->execute([
        $nome,
        $tipo,
        trim($_POST['serie'] ?? ''),
        trim($_POST['licenca'] ?? ''),
        $quantidade
    ]);

    header("Location: read.php");
    exit;
}

ob_start();
?>

<h1>Novo Periferico</h1>

<form method="POST">

<input name="nome" class="form-control mb-2" placeholder="Nome" required>

<input name="tipo" class="form-control mb-2" placeholder="Tipo" required>

<input name="serie" class="form-control mb-2" placeholder="Numero de Serie">

<input name="licenca" class="form-control mb-2" placeholder="Licenca">

<input name="quantidade" type="number" min="0" class="form-control mb-2" placeholder="Quantidade" required>

<button class="btn btn-success">Salvar</button>

</form>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
