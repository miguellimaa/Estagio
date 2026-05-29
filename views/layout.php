<?php
$pagina = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Irtrade Sistema</title>

<link rel="stylesheet" href="/irtrade-crud/adminlte/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="/irtrade-crud/adminlte/dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini">

<div class="wrapper">

<!-- NAVBAR -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <span class="mr-3 font-weight-bold">Irtrade Sistema</span>

    <div class="ml-auto">
        <span class="mr-3"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user']['nome'] ?? '') ?></span>
        <a href="/irtrade-crud/auth/logout.php" class="btn btn-danger btn-sm">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</nav>

<!-- SIDEBAR -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a class="brand-link text-center text-white">Irtrade</a>

    <div class="sidebar mt-3">

        <a href="/irtrade-crud/index.php" class="nav-link <?= strpos($pagina,'index')?'active':'' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i> Dashboard
        </a>

        <a href="/irtrade-crud/perifericos/read.php" class="nav-link <?= strpos($pagina,'perifericos')?'active':'' ?>">
            <i class="nav-icon fas fa-keyboard"></i> Perifericos
        </a>

    </div>
</aside>

<!-- CONTEUDO -->
<div class="content-wrapper p-3">
    <?php echo $conteudo; ?>
</div>

</div>

<script src="/irtrade-crud/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/irtrade-crud/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/irtrade-crud/adminlte/dist/js/adminlte.min.js"></script>

</body>
</html>
