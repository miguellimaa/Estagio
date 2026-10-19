<?php
session_start();
require '../config/conexao.php';

require_admin('../index.php');

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
    if (!validate_csrf()) {
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

    $fotoPerfil = $dado['foto_perfil'] ?? null;
    if (!empty($_POST['remover_foto_perfil'])) {
        $fotoPerfil = null;
    }

    if (!empty($_FILES['foto_perfil']['name'])) {
        $novaFoto = save_user_avatar($_FILES['foto_perfil']);
        if ($novaFoto === null) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'A imagem de perfil deve ser uma PNG, JPG, WEBP ou GIF de até 2 MB.'];
            $_SESSION['form_old_users_update'] = [
                'nome' => $nome,
                'email' => $email,
                'perfil' => $perfil,
            ];
            header("Location: update.php?id={$id}");
            exit;
        }

        $fotoPerfil = $novaFoto;
    }

    try {
        if ($senha !== '') {
            $senhaHash = hash_password($senha);
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, senha = ?, perfil = ?, foto_perfil = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $senhaHash, $perfil, $fotoPerfil, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil = ?, foto_perfil = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $perfil, $fotoPerfil, $id]);
        }

        $_SESSION['user']['foto_perfil'] = $fotoPerfil;
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
<section class="surface-card p-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0">Editar usuário</h1>
            <p class="page-subtitle text-secondary mb-0 mt-2">Atualize os dados do usuário selecionado.</p>
        </div>
        <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" novalidate class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="col-12 col-md-6">
            <label for="nome" class="form-label fw-semibold">Nome</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input id="nome" name="nome" value="<?= htmlspecialchars($dado['nome'] ?? '') ?>" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['nome'])): ?><div class="invalid-feedback d-block">Informe o nome.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="email" class="form-label fw-semibold">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($dado['email'] ?? '') ?>" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><div class="invalid-feedback d-block">Informe um e-mail válido.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="senha" class="form-label fw-semibold">Nova senha (opcional)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input id="senha" name="senha" type="text" placeholder="Deixe em branco para manter a atual" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="perfil" class="form-label fw-semibold">Perfil</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-shield"></i></span>
                <select id="perfil" name="perfil" class="form-select <?= !empty($errors['perfil']) ? 'is-invalid' : '' ?>" required>
                    <option value="usuario" <?= ($dado['perfil'] ?? 'usuario') === 'usuario' ? 'selected' : '' ?>>usuario</option>
                    <option value="admin" <?= ($dado['perfil'] ?? 'usuario') === 'admin' ? 'selected' : '' ?>>admin</option>
                </select>
            </div>
            <?php if (!empty($errors['perfil'])): ?><div class="invalid-feedback d-block">Selecione um perfil válido.</div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label for="foto_perfil" class="form-label fw-semibold">Nova foto de perfil</label>
            <div class="d-flex flex-column gap-2">
                <button type="button" id="triggerFotoPerfil" class="btn btn-primary btn-ripple" style="max-width: 220px;">
                    <i class="fa-solid fa-camera me-2"></i>Escolher nova foto
                </button>
                <input id="foto_perfil" name="foto_perfil" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="d-none">
                <?php if (!empty($dado['foto_perfil'])): ?>
                    <button type="button" id="removerFotoPerfil" class="btn btn-outline-danger btn-ripple" style="max-width: 220px;">
                        <i class="fa-solid fa-trash me-2"></i>Remover foto
                    </button>
                <?php endif; ?>
                <div class="form-text">Opcional. Substitui a imagem atual. Aceita PNG, JPG, WEBP e GIF até 2 MB.</div>
            </div>
            <div id="fotoPreview" class="mt-3 border rounded p-2 bg-light" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <?php if (!empty($dado['foto_perfil'])): ?>
                    <img src="<?= htmlspecialchars('../' . ltrim((string) $dado['foto_perfil'], '/')) ?>" alt="Foto atual" style="width: 160px; height: 160px; object-fit: cover; border-radius: 50%; border: 2px solid #dfe3e8;">
                <?php else: ?>
                    <span class="text-muted small">Pré-visualização da imagem</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2 mt-2">
            <a href="read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
            <button type="submit" class="btn btn-warning btn-ripple"><i class="fa-solid fa-floppy-disk me-2"></i>Atualizar</button>
        </div>
    </form>
</section>

<script>
    const fotoInputUpdate = document.getElementById('foto_perfil');
    const fotoPreviewUpdate = document.getElementById('fotoPreview');
    const triggerFotoPerfilUpdate = document.getElementById('triggerFotoPerfil');
    const removerFotoBtn = document.getElementById('removerFotoPerfil');

    if (triggerFotoPerfilUpdate && fotoInputUpdate) {
        triggerFotoPerfilUpdate.addEventListener('click', function () {
            fotoInputUpdate.click();
        });
    }

    if (fotoInputUpdate && fotoPreviewUpdate) {
        fotoInputUpdate.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                fotoPreviewUpdate.innerHTML = '<img src="' + event.target.result + '" alt="Pré-visualização da foto" style="width: 160px; height: 160px; object-fit: cover; border-radius: 50%; border: 2px solid #dfe3e8;">';
            };
            reader.readAsDataURL(file);
        });
    }

    if (removerFotoBtn) {
        removerFotoBtn.addEventListener('click', function () {
            const input = document.getElementById('foto_perfil');
            if (input) {
                input.value = '';
            }

            fotoPreviewUpdate.innerHTML = '<span class="text-muted small">Pré-visualização da imagem</span>';
            this.remove();

            const removerFlag = document.createElement('input');
            removerFlag.type = 'hidden';
            removerFlag.name = 'remover_foto_perfil';
            removerFlag.value = '1';
            document.querySelector('form').appendChild(removerFlag);
        });
    }
</script>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
