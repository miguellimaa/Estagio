<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['user']['perfil'] ?? '') !== 'admin') {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Acesso restrito ao administrador.'];
    header('Location: ../index.php');
    exit;
}

$nome = '';
$email = '';
$perfil = 'usuario';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Falha de segurança (CSRF).'];
        header('Location: create.php');
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');
    $perfil = trim($_POST['perfil'] ?? 'usuario');

    if ($nome === '') {
        $errors['nome'] = true;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = true;
    }

    if ($senha === '') {
        $errors['senha'] = true;
    }

    if (!in_array($perfil, ['admin', 'usuario'], true)) {
        $errors['perfil'] = true;
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Preencha os campos obrigatórios corretamente.'];
        $_SESSION['form_errors_users_create'] = $errors;
        $_SESSION['form_old_users_create'] = [
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
        ];
        header('Location: create.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nome, $email, $senha, $perfil]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Usuário criado com sucesso.'];
        header('Location: read.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Não foi possível criar o usuário. Verifique se o e-mail já existe.'];
        $_SESSION['form_old_users_create'] = [
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
        ];
        header('Location: create.php');
        exit;
    }
}

$old = $_SESSION['form_old_users_create'] ?? [];
if (!empty($old)) {
    $nome = $old['nome'] ?? '';
    $email = $old['email'] ?? '';
    $perfil = $old['perfil'] ?? 'usuario';
    unset($_SESSION['form_old_users_create']);
}

$errors = $_SESSION['form_errors_users_create'] ?? [];
if (!empty($errors)) {
    unset($_SESSION['form_errors_users_create']);
}

ob_start();
?>
<section class="surface-card form-shell fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title">Adicionar usuário</h1>
            <p class="page-subtitle">Cadastre um novo usuário para acesso ao sistema.</p>
        </div>
        <a href="read.php" class="btn-modern btn-light-modern"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-grid">
            <div class="form-field">
                <label for="nome">Nome</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" class="<?= !empty($errors['nome']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="email">E-mail</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($email) ?>" class="<?= !empty($errors['email']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="senha">Senha</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input id="senha" name="senha" type="text" class="<?= !empty($errors['senha']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="perfil">Perfil</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-shield"></i>
                    <select id="perfil" name="perfil" class="<?= !empty($errors['perfil']) ? 'input-error' : '' ?>" required>
                        <option value="usuario" <?= $perfil === 'usuario' ? 'selected' : '' ?>>usuario</option>
                        <option value="admin" <?= $perfil === 'admin' ? 'selected' : '' ?>>admin</option>
                    </select>
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
