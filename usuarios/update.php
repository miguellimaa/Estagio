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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'ID inválido para edição de usuário.'];
    header('Location: read.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, nome, email, perfil FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$dado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dado) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Usuário não encontrado.'];
    header('Location: read.php');
    exit;
}

$errors = $_SESSION['form_errors_users_update'] ?? [];
if (!empty($errors)) {
    unset($_SESSION['form_errors_users_update']);
}

$old = $_SESSION['form_old_users_update'] ?? [];
if (!empty($old)) {
    $dado['nome'] = $old['nome'] ?? ($dado['nome'] ?? '');
    $dado['email'] = $old['email'] ?? ($dado['email'] ?? '');
    $dado['perfil'] = $old['perfil'] ?? ($dado['perfil'] ?? 'usuario');
    unset($_SESSION['form_old_users_update']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Falha de segurança (CSRF).'];
        header("Location: update.php?id={$id}");
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = trim($_POST['perfil'] ?? 'usuario');
    $senha = (string)($_POST['senha'] ?? '');

    $formErrors = [];

    if ($nome === '') {
        $formErrors['nome'] = true;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors['email'] = true;
    }

    if (!in_array($perfil, ['admin', 'usuario'], true)) {
        $formErrors['perfil'] = true;
    }

    if (!empty($formErrors)) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Preencha os campos obrigatórios corretamente.'];
        $_SESSION['form_errors_users_update'] = $formErrors;
        $_SESSION['form_old_users_update'] = [
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
        ];
        header("Location: update.php?id={$id}");
        exit;
    }

    try {
        if ($senha !== '') {
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, senha = ?, perfil = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $senha, $perfil, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $perfil, $id]);
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Usuário atualizado com sucesso.'];
        header('Location: read.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Não foi possível atualizar o usuário. Verifique se o e-mail já existe.'];
        $_SESSION['form_old_users_update'] = [
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
        ];
        header("Location: update.php?id={$id}");
        exit;
    }
}

ob_start();
?>
<section class="surface-card form-shell fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title">Editar usuário</h1>
            <p class="page-subtitle">Atualize os dados do usuário selecionado.</p>
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
                    <input id="nome" name="nome" value="<?= htmlspecialchars($dado['nome'] ?? '') ?>" class="<?= !empty($errors['nome']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="email">E-mail</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($dado['email'] ?? '') ?>" class="<?= !empty($errors['email']) ? 'input-error' : '' ?>" required>
                </div>
            </div>

            <div class="form-field">
                <label for="senha">Nova senha (opcional)</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input id="senha" name="senha" type="text" placeholder="Deixe em branco para manter a atual">
                </div>
            </div>

            <div class="form-field">
                <label for="perfil">Perfil</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-shield"></i>
                    <select id="perfil" name="perfil" class="<?= !empty($errors['perfil']) ? 'input-error' : '' ?>" required>
                        <option value="usuario" <?= ($dado['perfil'] ?? 'usuario') === 'usuario' ? 'selected' : '' ?>>usuario</option>
                        <option value="admin" <?= ($dado['perfil'] ?? 'usuario') === 'admin' ? 'selected' : '' ?>>admin</option>
                    </select>
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
