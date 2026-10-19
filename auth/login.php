<?php
session_start();
require '../config/conexao.php';

$config = require __DIR__ . '/../config/app.php';
$baseUrl = rtrim($config['base_url'] ?? '/Estagio-main', '/');

$email = '';

if($_POST){
    // CSRF validation
    if (!validate_csrf()) {
        $erro = "Falha de segurança (CSRF)";
    } else {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && verify_password((string)($_POST['senha'] ?? ''), (string)($user['senha'] ?? ''))) {
        $storedInfo = password_get_info((string) $user['senha']);
        if (!is_array($storedInfo) || !isset($storedInfo['algo']) || $storedInfo['algo'] === null || $storedInfo['algo'] === 0) {
            $newHash = hash_password((string)($_POST['senha'] ?? ''));
            $updateStmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
            $updateStmt->execute([$newHash, (int) $user['id']]);
            $user['senha'] = $newHash;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        header("Location: ../index.php");
        exit;
    } else {
        $erro = "Email ou senha inválidos";
    }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Irtrade</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
</head>

<body class="login-body">
<main class="login-wrap container-fluid px-0">
    <div class="row g-0 min-vh-100 w-100">
        <section class="login-showcase col-lg-6 d-none d-lg-flex">
            <div>
                <span class="showcase-kicker">IRTRADE Asset Manager</span>
                <h1 class="showcase-title">Controle de ativos com experiência premium e foco em produtividade.</h1>
                <p class="showcase-text">Centralize inventário, acompanhamento de estoque e operações críticas em um painel corporativo confiável e elegante.</p>
            </div>
        </section>

        <section class="login-panel col-12 col-lg-6 d-flex align-items-center justify-content-center p-3 p-md-4">
            <div class="login-card card shadow-lg w-100">
                <div class="card-body p-4 p-md-5">
                    <div class="login-logo">
                        <img src="<?= $baseUrl ?>/assets/img/logo.svg" alt="IRTRADE">
                        <div>
                            <strong>IRTRADE</strong>
                            <span>Asset Manager</span>
                        </div>
                    </div>

                    <h2 class="login-title">Entrar na plataforma</h2>
                    <p class="login-subtitle">Use suas credenciais para acessar o painel.</p>
                    <span class="badge text-bg-primary mb-3">Bootstrap POC</span>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger login-alert" role="alert"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="login-field mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group login-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input id="email" type="email" name="email" class="form-control login-input" value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                        </div>

                        <div class="login-field mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group login-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input id="senha" type="password" name="senha" class="form-control login-input" required>
                                <button type="button" class="btn btn-outline-secondary password-toggle" onclick="toggleSenha()" aria-label="Mostrar senha">
                                    <i id="iconeSenha" class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button class="btn btn-primary login-submit w-100 py-2" type="submit">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
function toggleSenha() {
    const input = document.getElementById("senha");
    const icon = document.getElementById('iconeSenha');

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>