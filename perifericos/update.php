<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("ID invalido");
}

$stmt = $pdo->prepare("SELECT * FROM perifericos WHERE id=?");
$stmt->execute([$id]);
$dado = $stmt->fetch();

if (!$dado) {
    die("Periferico nao encontrado");
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

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE perifericos SET
        nome_ativo=?,
        tipo=?,
        numero_serie=?,
        chave_licenca=?,
        quantidade=?
        WHERE id=?
    ");

    $stmt->execute([
        $nome,
        $tipo,
        trim($_POST['serie'] ?? ''),
        trim($_POST['licenca'] ?? ''),
        $quantidade,
        $id
    ]);

    $pdo->commit();

    header("Location: read.php");
    exit;
}

ob_start();
?>

<h2>Editar Periferico</h2>

<form method="POST">
    <div class="form-group">
        <input name="nome" class="form-control" value="<?= htmlspecialchars($dado['nome_ativo'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <input name="tipo" class="form-control" value="<?= htmlspecialchars($dado['tipo'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <input name="serie" class="form-control" value="<?= htmlspecialchars($dado['numero_serie'] ?? '') ?>">
    </div>

    <div class="form-group">
        <input name="licenca" class="form-control" value="<?= htmlspecialchars($dado['chave_licenca'] ?? '') ?>">
    </div>

    <div class="form-group">
        <input name="quantidade" type="number" min="0" class="form-control" value="<?= htmlspecialchars($dado['quantidade'] ?? '') ?>" required>
    </div>

    <button class="btn btn-warning">Atualizar</button>
</form>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
