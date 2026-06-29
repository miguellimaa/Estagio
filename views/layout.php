<?php
$pagina = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Irtrade Sistema</title>

<link rel="stylesheet" href="/Estagio/adminlte/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="/Estagio/adminlte/dist/css/adminlte.min.css">
<link rel="stylesheet" href="/Estagio/assets/css/custom.css">

</head>

<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">

<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <ul class="navbar-nav">
        <li class="nav-item">
            <span class="navbar-brand font-weight-bold">
                Sistema Irtrade
            </span>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">

        <li class="nav-item mr-2 mt-2 d-flex align-items-center">
            <button id="themeToggle" class="btn btn-theme-toggle" aria-pressed="false" title="Alternar tema: claro / escuro">
                <span class="toggle-icon"><i id="themeIcon" class="fas fa-moon"></i></span>
                <span class="toggle-label" id="themeLabel">Modo</span>
            </button>
        </li>

        <li class="nav-item mr-3 mt-2">
            <i class="fas fa-user-circle"></i>
            <?= htmlspecialchars($_SESSION['user']['nome'] ?? '') ?>
        </li>

        <li class="nav-item">
            <a href="/Estagio/auth/logout.php" class="btn btn-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i>
                Sair
            </a>
        </li>

    </ul>

</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="#" class="brand-link text-center">
        <span class="brand-text font-weight-light">
            Irtrade
        </span>
    </a>

    <div class="sidebar">

        <div class="user-panel d-flex align-items-center justify-content-center">
            <div class="info text-center">
                <span class="d-block font-weight-bold">Olá, <?= htmlspecialchars($_SESSION['user']['nome'] ?? 'Usuário') ?></span>
                <small>Bem-vindo ao painel Irtrade</small>
            </div>
        </div>

        <nav class="mt-3">

            <ul class="nav nav-pills nav-sidebar flex-column">

                <li class="nav-item">
                    <a href="/Estagio/index.php"
                       class="nav-link <?= strpos($pagina,'index.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/Estagio/perifericos/read.php"
                       class="nav-link <?= strpos($pagina,'perifericos') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-keyboard"></i>
                        <p>Periféricos</p>
                    </a>
                </li>

            </ul>

        </nav>

    </div>

</aside>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <?php
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            if (!empty($_SESSION['flash'])) {
                $flash = $_SESSION['flash'];
                $type = $flash['type'] ?? 'info';
                $msg = $flash['message'] ?? '';
                $class = 'alert-info';
                if ($type === 'success') $class = 'alert-success';
                elseif ($type === 'danger' || $type === 'error') $class = 'alert-danger';
                elseif ($type === 'warning') $class = 'alert-warning';

                echo "<div class=\"alert $class alert-dismissible fade show\" role=\"alert\">" . htmlspecialchars($msg) . "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button></div>";

                unset($_SESSION['flash']);
            }
            ?>
            <?php echo $conteudo; ?>
        </div>
    </section>
</div>

</div>

<script src="/Estagio/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/Estagio/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/Estagio/adminlte/dist/js/adminlte.min.js"></script>
<script src="/Estagio/assets/js/custom.js"></script>

</body>
</html>
