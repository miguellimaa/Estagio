<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
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

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Periférico salvo com sucesso.'];
    header("Location: read.php");
    exit;
}

ob_start();
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <small class="text-muted">Periféricos / Novo</small>
            <h1 class="h4 mb-0">Adicionar novo periférico</h1>
            <p class="mb-0 text-muted">Preencha os dados para cadastrar o item no estoque.</p>
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
                        <input id="nome" name="nome" class="form-control" placeholder="Nome" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="tipo">Tipo</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-tags"></i></span></div>
                        <input id="tipo" name="tipo" class="form-control" placeholder="Tipo" required>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="serie">Número de Série</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                        <input id="serie" name="serie" class="form-control" placeholder="Número de Série">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="licenca">Licença</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
                        <input id="licenca" name="licenca" class="form-control" placeholder="Licença">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="quantidade">Quantidade</label>
                <input id="quantidade" name="quantidade" type="number" min="0" class="form-control" placeholder="Quantidade" required>
            </div>
            <div class="d-flex justify-content-between flex-wrap mt-4">
                <a href="read.php" class="btn btn-outline-secondary px-4 mb-2">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" class="btn btn-success px-4 mb-2">
                    <i class="fas fa-save"></i> Salvar
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
