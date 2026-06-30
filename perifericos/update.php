<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'ID inválido para edição.'];
    header("Location: read.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM perifericos WHERE id=? AND deleted_at IS NULL");
$stmt->execute([$id]);
$dado = $stmt->fetch();

if (!$dado) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Periférico não encontrado.'];
    header("Location: read.php");
    exit;
}

$errors = [];

if (!empty($_SESSION['form_errors_update'])) {
    $errors = $_SESSION['form_errors_update'];
    unset($_SESSION['form_errors_update']);
}

if (!empty($_SESSION['form_old_update'])) {
    $old = $_SESSION['form_old_update'];
    $dado['nome_ativo'] = $old['nome'] ?? ($dado['nome_ativo'] ?? '');
    $dado['tipo'] = $old['tipo'] ?? ($dado['tipo'] ?? '');
    $dado['numero_serie'] = $old['serie'] ?? ($dado['numero_serie'] ?? '');
    $dado['chave_licenca'] = $old['licenca'] ?? ($dado['chave_licenca'] ?? '');
    $dado['quantidade'] = $old['quantidade'] ?? ($dado['quantidade'] ?? '');
    unset($_SESSION['form_old_update']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Falha de segurança (CSRF).'];
        header("Location: update.php?id={$id}");
        exit;
    }
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $serie = trim($_POST['serie'] ?? '');
    $licenca = trim($_POST['licenca'] ?? '');
    $quantidadeValor = $_POST['quantidade'] ?? '';

    if ($nome === '' || $tipo === '') {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Nome e tipo são necessários.'];
        $erros = [];
        if ($nome === '') {
            $erros['nome'] = true;
        }
        if ($tipo === '') {
            $erros['tipo'] = true;
        }
        $_SESSION['form_errors_update'] = $erros;
        $_SESSION['form_old_update'] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'serie' => $serie,
            'licenca' => $licenca,
            'quantidade' => $quantidadeValor,
        ];
        header("Location: update.php?id={$id}");
        exit;
    }

    if ($quantidade === false || $quantidade === null || $quantidade < 0) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Quantidade inválida.'];
        $_SESSION['form_errors_update'] = ['quantidade' => true];
        $_SESSION['form_old_update'] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'serie' => $serie,
            'licenca' => $licenca,
            'quantidade' => $quantidadeValor,
        ];
        header("Location: update.php?id={$id}");
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE perifericos SET
        nome_ativo=?,
        tipo=?,
        numero_serie=?,
        chave_licenca=?,
        quantidade=?
        WHERE id=? AND deleted_at IS NULL
    ");

    $stmt->execute([
        $nome,
        $tipo,
        $serie,
        $licenca,
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
<section class="surface-card form-shell fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title">Editar periférico</h1>
            <p class="page-subtitle">Atualize os dados do item selecionado mantendo o histórico consistente.</p>
        </div>
        <a href="read.php" class="btn-modern btn-light-modern"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-grid">
            <div class="form-field">
                <label for="nome">Nome</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-keyboard"></i>
                    <input id="nome" name="nome" value="<?= htmlspecialchars($dado['nome_ativo'] ?? '') ?>" class="<?= !empty($errors['nome']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="tipo">Tipo</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-tags"></i>
                    <input id="tipo" name="tipo" value="<?= htmlspecialchars($dado['tipo'] ?? '') ?>" class="<?= !empty($errors['tipo']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="serie">Número de Série</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-hashtag"></i>
                    <input id="serie" name="serie" value="<?= htmlspecialchars($dado['numero_serie'] ?? '') ?>">
                </div>
            </div>

            <div class="form-field">
                <label for="licenca">Licença</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-key"></i>
                    <input id="licenca" name="licenca" value="<?= htmlspecialchars($dado['chave_licenca'] ?? '') ?>">
                </div>
            </div>

            <div class="form-field">
                <label for="quantidade">Quantidade</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <input id="quantidade" name="quantidade" type="number" min="0" value="<?= htmlspecialchars($dado['quantidade'] ?? '') ?>" class="<?= !empty($errors['quantidade']) ? 'input-error' : '' ?>" required>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="read.php" class="btn-modern btn-light-modern"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
            <button type="submit" class="btn-modern btn-warning-modern"><i class="fa-solid fa-floppy-disk me-2"></i>Atualizar</button>
        </div>
    </form>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
