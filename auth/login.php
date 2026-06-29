<?php
session_start();
require '../config/conexao.php';

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
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Irtrade</title>

    <!-- AdminLTE -->
    <link rel="stylesheet" href="/Estagio/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/Estagio/adminlte/dist/css/adminlte.min.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, rgba(255,255,255,.18), transparent 28%),
                        linear-gradient(135deg, #2a2d3c, #101827);
            margin: 0;
            font-family: "Source Sans Pro", sans-serif;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            animation: fadeIn 0.6s ease-in-out;
            padding: 1rem;
        }

        .login-logo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: .04em;
            color: #fff;
            text-align: center;
            margin-bottom: 1rem;
        }

        .card {
            border-radius: 1.25rem;
            box-shadow: 0 28px 90px rgba(0, 0, 0, .25);
            border: none;
            overflow: hidden;
        }

        .login-card-body {
            border-radius: 1.25rem;
            padding: 2rem;
            background: rgba(255,255,255,.95);
        }

        .btn-primary {
            width: 100%;
            border-radius: .85rem;
            font-weight: 600;
        }

        .login-box-msg {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .input-group-text {
            background: #f4f6f9;
            border: 1px solid #d7dae3;
        }

        .form-control {
            border-radius: .75rem;
        }

        .alert {
            border-radius: .85rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="hold-transition login-page">

<div class="login-box">

    <div class="login-logo text-white">
        <b>Irtrade</b> Sistema
    </div>

    <div class="card">
        <div class="card-body login-card-body">

            <p class="login-box-msg">Acesse sua conta</p>

            <?php if(isset($erro)): ?>
                <div class="alert alert-danger text-center">
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <!-- EMAIL -->
                <div class="input-group mb-3">
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Email"
                        value="<?= $email ?>"
                        required
                    >
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <!-- SENHA -->
                <div class="input-group mb-3">
                    <input 
                        type="password" 
                        id="senha"
                        name="senha" 
                        class="form-control" 
                        placeholder="Senha"
                        required
                    >
                    <div class="input-group-append">
                        <div class="input-group-text" onclick="toggleSenha()" style="cursor:pointer;">
                            <span id="iconeSenha" class="fas fa-eye"></span>
                        </div>
                    </div>
                </div>

                <!-- BOTÃO -->
                <button class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>

            </form>

        </div>
    </div>

</div>

<!-- JS -->
<script src="/Estagio/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/Estagio/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/Estagio/adminlte/dist/js/adminlte.min.js"></script>

<script>
function toggleSenha() {
    const input = document.getElementById("senha");
    const icon = document.getElementById("iconeSenha");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>