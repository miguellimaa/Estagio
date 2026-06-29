<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

$nome = '';
$tipo = '';
$serie = '';
$licenca = '';
$quantidadeValor = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Falha de segurança (CSRF).'];
        header("Location: create.php");
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
        $_SESSION['form_errors_create'] = $erros;
        $_SESSION['form_old_create'] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'serie' => $serie,
            'licenca' => $licenca,
            'quantidade' => $quantidadeValor,
        ];
        header("Location: create.php");
        exit;
    }

    if ($quantidade === false || $quantidade === null || $quantidade < 0) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Quantidade inválida.'];
        $_SESSION['form_errors_create'] = ['quantidade' => true];
        $_SESSION['form_old_create'] = [
            'nome' => $nome,
            'tipo' => $tipo,
            'serie' => $serie,
            'licenca' => $licenca,
            'quantidade' => $quantidadeValor,
        ];
        header("Location: create.php");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO perifericos 
    (nome_ativo, tipo, numero_serie, chave_licenca, quantidade)
    VALUES (?, ?, ?, ?, ?)");

    $stmt->execute([
        $nome,
        $tipo,
        $serie,
        $licenca,
        $quantidade
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Periférico salvo com sucesso.'];
    header("Location: read.php");
    exit;
}

$old = $_SESSION['form_old_create'] ?? [];
$errors = $_SESSION['form_errors_create'] ?? [];

if (!empty($old)) {
    $nome = $old['nome'] ?? '';
    $tipo = $old['tipo'] ?? '';
    $serie = $old['serie'] ?? '';
    $licenca = $old['licenca'] ?? '';
    $quantidadeValor = $old['quantidade'] ?? '';
    unset($_SESSION['form_old_create']);
}

if (!empty($errors)) {
    unset($_SESSION['form_errors_create']);
}

ob_start();
?>
<section class="surface-card form-shell fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title">Adicionar periférico</h1>
            <p class="page-subtitle">Preencha os campos para cadastrar um novo ativo no inventário.</p>
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
                    <input id="nome" name="nome" placeholder="Nome do periférico" value="<?= htmlspecialchars($nome) ?>" class="<?= !empty($errors['nome']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="tipo">Tipo</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-tags"></i>
                    <input id="tipo" name="tipo" placeholder="Categoria / tipo" value="<?= htmlspecialchars($tipo) ?>" class="<?= !empty($errors['tipo']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="serie">Número de Série</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-hashtag"></i>
                    <input id="serie" name="serie" placeholder="Número de série" value="<?= htmlspecialchars($serie) ?>">
                </div>
            </div>

            <div class="form-field">
                <label for="licenca">Licença</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-key"></i>
                    <input id="licenca" name="licenca" placeholder="Chave da licença" value="<?= htmlspecialchars($licenca) ?>">
                </div>
            </div>

            <div class="form-field">
                <label for="quantidade">Quantidade</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <input id="quantidade" name="quantidade" type="number" min="0" placeholder="Quantidade em estoque" value="<?= htmlspecialchars((string)$quantidadeValor) ?>" class="<?= !empty($errors['quantidade']) ? 'input-error' : '' ?>" required>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="read.php" class="btn-modern btn-light-modern"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
            <button type="submit" class="btn-modern btn-success-modern"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar</button>
        </div>
    </form>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
