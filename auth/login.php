<?php
session_start();
require '../config/conexao.php';

$email = '';

if($_POST){
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
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Irtrade</title>

    <!-- AdminLTE -->
    <link rel="stylesheet" href="/irtrade-crud/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/irtrade-crud/adminlte/dist/css/adminlte.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .login-box {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            border-radius: 10px;
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
<script src="/irtrade-crud/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/irtrade-crud/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/irtrade-crud/adminlte/dist/js/adminlte.min.js"></script>

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