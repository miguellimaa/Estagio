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
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die("Token CSRF inválido");
    }
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

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Periférico atualizado com sucesso.'];

    header("Location: read.php");
    exit;
}

ob_start();
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <small class="text-muted">Periféricos / Edição</small>
            <h1 class="h4 mb-0">Editar periférico</h1>
            <p class="mb-0 text-muted">Ajuste os dados do item e salve a alteração.</p>
        </div>
        <a href="read.php" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    <div class="card-body">
        <div class="form-card">
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nome">Nome</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-keyboard"></i></span></div>
                        <input id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($dado['nome_ativo'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="tipo">Tipo</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-tags"></i></span></div>
                        <input id="tipo" name="tipo" class="form-control" value="<?= htmlspecialchars($dado['tipo'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="serie">Número de Série</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                        <input id="serie" name="serie" class="form-control" value="<?= htmlspecialchars($dado['numero_serie'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="licenca">Licença</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
                        <input id="licenca" name="licenca" class="form-control" value="<?= htmlspecialchars($dado['chave_licenca'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="quantidade">Quantidade</label>
                <input id="quantidade" name="quantidade" type="number" min="0" class="form-control" value="<?= htmlspecialchars($dado['quantidade'] ?? '') ?>" required>
            </div>
            <div class="d-flex justify-content-between flex-wrap mt-4">
                <a href="read.php" class="btn btn-outline-secondary px-4 mb-2">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" class="btn btn-warning px-4 mb-2">
                    <i class="fas fa-save"></i> Atualizar
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
