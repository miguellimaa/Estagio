<?php
session_start();
require '../config/conexao.php';

$config = require __DIR__ . '/../config/app.php';
$baseUrl = rtrim($config['base_url'] ?? '/Estagio-main', '/');

$email = '';

if($_POST){
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $erro = "Falha de segurança (CSRF)";
    } else {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user && $_POST['senha'] == $user['senha']){
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
</head>

<body class="login-body">
<main class="login-wrap">
    <section class="login-showcase">
        <span class="showcase-kicker">IRTRADE Asset Manager</span>
        <h1 class="showcase-title">Controle de ativos com experiência premium e foco em produtividade.</h1>
        <p class="showcase-text">Centralize inventário, acompanhamento de estoque e operações críticas em um painel corporativo confiável e elegante.</p>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <div class="login-logo">
                <img src="<?= $baseUrl ?>/assets/img/logo.svg" alt="IRTRADE">
                <div>
                    <strong>IRTRADE</strong>
                    <span>Asset Manager</span>
                </div>
            </div>

            <h2 class="login-title">Entrar na plataforma</h2>
            <p class="login-subtitle">Use suas credenciais para acessar o painel.</p>

            <?php if (isset($erro)): ?>
                <div class="login-alert"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <div class="login-field">
                    <label for="email">Email</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input id="email" type="email" name="email" class="login-input" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>

                <div class="login-field">
                    <label for="senha">Senha</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input id="senha" type="password" name="senha" class="login-input" required>
                        <button type="button" class="password-toggle" onclick="toggleSenha()" aria-label="Mostrar senha">
                            <i id="iconeSenha" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="login-submit" type="submit">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar
                </button>
            </form>
        </div>
    </section>
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