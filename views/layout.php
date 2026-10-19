<?php
$config = require __DIR__ . '/../config/app.php';
$baseUrl = rtrim($config['base_url'] ?? '/Estagio-main', '/');
$pagina = $_SERVER['REQUEST_URI'] ?? '';

$menu = [
    ['label' => 'Dashboard', 'icon' => 'fa-chart-line', 'href' => $baseUrl . '/index.php', 'match' => '/index.php'],
    ['label' => 'Periféricos', 'icon' => 'fa-keyboard', 'href' => $baseUrl . '/perifericos/read.php', 'match' => '/perifericos/'],
    ['label' => 'Lixeira', 'icon' => 'fa-trash-can', 'href' => $baseUrl . '/perifericos/lixeira.php', 'match' => '/perifericos/lixeira.php'],
    ['label' => 'Usuários', 'icon' => 'fa-users', 'href' => $baseUrl . '/usuarios/read.php', 'match' => '/usuarios/'],
    ['label' => 'Relatórios', 'icon' => 'fa-chart-pie', 'href' => $baseUrl . '/relatorios/read.php', 'match' => '/relatorios/'],
    ['label' => 'Configurações', 'icon' => 'fa-gear', 'href' => $baseUrl . '/configuracoes/read.php', 'match' => '/configuracoes/'],
];

$flash = null;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$loggedUser = $_SESSION['user'] ?? [];
$avatarSrc = $baseUrl . '/assets/img/avatar.png';
if (!empty($loggedUser['foto_perfil'])) {
    $foto = (string) $loggedUser['foto_perfil'];
    if (str_starts_with($foto, 'http')) {
        $avatarSrc = $foto;
    } else {
        $avatarSrc = $baseUrl . '/' . ltrim($foto, '/');
    }
}

$notifications = $_SESSION['notifications'] ?? [
    ['title' => 'Sistema atualizado', 'message' => 'Dashboard e inventário prontos para uso.', 'time' => 'agora'],
    ['title' => 'Estoque em alerta', 'message' => '2 itens estão próximos do limite mínimo.', 'time' => '2h atrás'],
    ['title' => 'Usuário ativo', 'message' => 'Sua sessão foi renovada com sucesso.', 'time' => 'hoje'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($config['app_name'] ?? 'IRTRADE Asset Manager') ?></title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/sidebar.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/components.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/forms.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/animations.css">
</head>
<body class="app-body">
<div class="loading-screen" data-loading-screen>
    <div class="loading-ring"></div>
</div>

<div class="app-toast-host" data-toast-host></div>

<div class="app-shell d-flex">
    <aside class="app-sidebar d-flex flex-column" data-sidebar>
        <a href="<?= $baseUrl ?>/index.php" class="sidebar-brand">
            <img src="<?= $baseUrl ?>/assets/img/logo.svg" alt="IRTRADE">
            <div class="sidebar-brand-text">
                <strong>IRTRADE</strong>
                <span>Asset Manager</span>
            </div>
        </a>

        <div class="sidebar-user">
            <small>Usuário logado</small>
            <strong><?= htmlspecialchars($_SESSION['user']['nome'] ?? 'Usuário') ?></strong>
        </div>

        <span class="menu-heading">Navegação</span>
        <ul class="menu-list">
            <?php foreach ($menu as $item): ?>
                <?php if ($item['match'] === '/configuracoes/' && (($_SESSION['user']['perfil'] ?? '') !== 'admin')) continue; ?>
                <?php $active = strpos($pagina, $item['match']) !== false ? 'active' : ''; ?>
                <li>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="menu-link <?= $active ?>">
                        <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                        <span class="menu-label"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="sidebar-footer">
            <a class="sidebar-logout" href="<?= $baseUrl ?>/auth/logout.php">
                <i class="fa-solid fa-right-from-bracket me-2"></i>
                <span class="menu-label">Logout</span>
            </a>
        </div>
    </aside>

    <main class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-toggle" data-sidebar-toggle aria-label="Abrir menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <p class="breadcrumbs mb-0">Painel / <strong data-breadcrumb-current>Dashboard</strong></p>
                </div>
            </div>

            <div class="topbar-right">
                <div class="search-box input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" placeholder="Pesquisar periférico..." data-global-search autocomplete="off">
                </div>

                <div class="notify-wrapper">
                    <button class="notify-btn" type="button" id="notifyToggle" aria-label="Notificações" aria-expanded="false">
                        <i class="fa-regular fa-bell"></i>
                        <span></span>
                    </button>
                </div>

                <div class="profile-pill">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar" style="object-fit: cover;">
                    <div>
                        <small>Conta ativa</small>
                        <strong><?= htmlspecialchars($_SESSION['user']['nome'] ?? 'Usuário') ?></strong>
                    </div>
                </div>
            </div>
        </header>

        <section class="app-content">
            <div class="flash-zone">
                <?php if ($flash): ?>
                    <div data-flash data-message="<?= htmlspecialchars($flash['message'] ?? '') ?>" data-type="<?= htmlspecialchars($flash['type'] ?? 'info') ?>"></div>
                <?php endif; ?>
            </div>
            <?= $conteudo ?>
        </section>
    </main>
</div>

<script src="<?= $baseUrl ?>/assets/js/app.js"></script>
<script src="<?= $baseUrl ?>/assets/js/table.js"></script>
</body>
</html>
