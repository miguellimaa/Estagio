<?php
session_start();
require '../config/conexao.php';

require_admin('../index.php');

$nome = '';
$email = '';
$perfil = 'usuario';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
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

    $fotoPerfil = null;
    if (!empty($_FILES['foto_perfil']['name'])) {
        $fotoPerfil = save_user_avatar($_FILES['foto_perfil']);
        if ($fotoPerfil === null) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'A imagem de perfil deve ser uma PNG, JPG, WEBP ou GIF de até 2 MB.'];
            $_SESSION['form_old_users_create'] = [
                'nome' => $nome,
                'email' => $email,
                'perfil' => $perfil,
            ];
            header('Location: create.php');
            exit;
        }
    }

    try {
        $senhaHash = hash_password($senha);
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil, foto_perfil) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nome, $email, $senhaHash, $perfil, $fotoPerfil]);
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
<section class="surface-card p-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0">Adicionar usuário</h1>
            <p class="page-subtitle text-secondary mb-0 mt-2">Cadastre um novo usuário para acesso ao sistema.</p>
        </div>
        <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" novalidate class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="col-12 col-md-6">
            <label for="nome" class="form-label fw-semibold">Nome</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['nome'])): ?><div class="invalid-feedback d-block">Informe o nome.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="email" class="form-label fw-semibold">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($email) ?>" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><div class="invalid-feedback d-block">Informe um e-mail válido.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="senha" class="form-label fw-semibold">Senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input id="senha" name="senha" type="text" class="form-control <?= !empty($errors['senha']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['senha'])): ?><div class="invalid-feedback d-block">Informe a senha.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="perfil" class="form-label fw-semibold">Perfil</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-shield"></i></span>
                <select id="perfil" name="perfil" class="form-select <?= !empty($errors['perfil']) ? 'is-invalid' : '' ?>" required>
                    <option value="usuario" <?= $perfil === 'usuario' ? 'selected' : '' ?>>usuario</option>
                    <option value="admin" <?= $perfil === 'admin' ? 'selected' : '' ?>>admin</option>
                </select>
            </div>
            <?php if (!empty($errors['perfil'])): ?><div class="invalid-feedback d-block">Selecione um perfil válido.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="foto_perfil" class="form-label fw-semibold">Foto de perfil</label>
            <div class="d-flex flex-column gap-2">
                <button type="button" id="triggerFotoPerfil" class="btn btn-primary btn-ripple" style="max-width: 220px;">
                    <i class="fa-solid fa-image me-2"></i>Escolher foto
                </button>
                <input id="foto_perfil" name="foto_perfil" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="d-none">
                <div class="form-text">Opcional. Aceita PNG, JPG, WEBP e GIF até 2 MB.</div>
            </div>
            <div id="fotoPreview" class="mt-3 border rounded p-2 bg-light" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <span class="text-muted small">Pré-visualização da imagem</span>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2 mt-2">
            <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
            <button type="submit" class="btn btn-success btn-ripple"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar</button>
        </div>
    </form>
</section>

<script>
    const fotoInputCreate = document.getElementById('foto_perfil');
    const fotoPreviewCreate = document.getElementById('fotoPreview');
    const triggerFotoPerfilCreate = document.getElementById('triggerFotoPerfil');

    if (triggerFotoPerfilCreate && fotoInputCreate) {
        triggerFotoPerfilCreate.addEventListener('click', function () {
            fotoInputCreate.click();
        });
    }

    if (fotoInputCreate && fotoPreviewCreate) {
        fotoInputCreate.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                fotoPreviewCreate.innerHTML = '<span class="text-muted small">Pré-visualização da imagem</span>';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                fotoPreviewCreate.innerHTML = '<img src="' + event.target.result + '" alt="Pré-visualização da foto" style="width: 160px; height: 160px; object-fit: cover; border-radius: 50%; border: 2px solid #dfe3e8;">';
            };
            reader.readAsDataURL(file);
        });
    }
</script>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
