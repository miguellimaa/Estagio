<?php
session_start();
require '../config/conexao.php';


require_login();

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
    $dado['valor'] = $old['valor'] ?? ($dado['valor'] ?? '');
    unset($_SESSION['form_old_update']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // CSRF validation
    if (!validate_csrf()) {
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
    $valorValor = trim($_POST['valor'] ?? '');
    $valorNormalizado = str_replace(',', '.', $valorValor);
    $valor = filter_var($valorNormalizado, FILTER_VALIDATE_FLOAT);

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
            'valor' => $valorValor,
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
            'valor' => $valorValor,
        ];
        header("Location: update.php?id={$id}");
        exit;
    }

    if ($valor === false || $valor < 0) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Valor inválido.'];
        $_SESSION['form_errors_update'] = ['valor' => true];
        $_SESSION['form_old_update'] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'serie' => $serie,
            'licenca' => $licenca,
            'quantidade' => $quantidadeValor,
            'valor' => $valorValor,
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
        quantidade=?,
        valor=?
        WHERE id=? AND deleted_at IS NULL
    ");

    $stmt->execute([
        $nome,
        $tipo,
        $serie,
        $licenca,
        $quantidade,
        $valor,
        $id
    ]);

    $pdo->commit();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Periférico atualizado com sucesso.'];

    header("Location: read.php");
    exit;
}

ob_start();
?>
<section class="surface-card p-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0">Editar periférico</h1>
            <p class="page-subtitle text-secondary mb-0 mt-2">Atualize os dados do item selecionado mantendo o histórico consistente.</p>
        </div>
        <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <form method="POST" novalidate class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="col-12 col-md-6">
            <label for="nome" class="form-label fw-semibold">Nome</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-keyboard"></i></span>
                <input id="nome" name="nome" value="<?= htmlspecialchars($dado['nome_ativo'] ?? '') ?>" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['nome'])): ?><div class="invalid-feedback d-block">Informe o nome.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="tipo" class="form-label fw-semibold">Tipo</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                <input id="tipo" name="tipo" value="<?= htmlspecialchars($dado['tipo'] ?? '') ?>" class="form-control <?= !empty($errors['tipo']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['tipo'])): ?><div class="invalid-feedback d-block">Informe o tipo.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="serie" class="form-label fw-semibold">Número de Série</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                <input id="serie" name="serie" value="<?= htmlspecialchars($dado['numero_serie'] ?? '') ?>" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="licenca" class="form-label fw-semibold">Licença</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                <input id="licenca" name="licenca" value="<?= htmlspecialchars($dado['chave_licenca'] ?? '') ?>" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="quantidade" class="form-label fw-semibold">Quantidade</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-boxes-stacked"></i></span>
                <input id="quantidade" name="quantidade" type="number" min="0" value="<?= htmlspecialchars($dado['quantidade'] ?? '') ?>" class="form-control <?= !empty($errors['quantidade']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['quantidade'])): ?><div class="invalid-feedback d-block">Informe uma quantidade válida.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="valor" class="form-label fw-semibold">Valor (R$)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                <input id="valor" name="valor" type="number" min="0" step="0.01" value="<?= htmlspecialchars($dado['valor'] ?? '') ?>" class="form-control <?= !empty($errors['valor']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['valor'])): ?><div class="invalid-feedback d-block">Informe um valor válido.</div><?php endif; ?>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2 mt-2">
            <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
            <button type="submit" class="btn btn-warning btn-ripple"><i class="fa-solid fa-floppy-disk me-2"></i>Atualizar</button>
        </div>
    </form>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
