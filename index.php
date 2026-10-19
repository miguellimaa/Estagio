<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: auth/login.php');
    exit;
}

$lowStockThreshold = setting_get_int($pdo, 'low_stock_threshold', 5);
$exportRole = setting_get($pdo, 'export_role', 'admin') ?? 'admin';
$canExport = $exportRole === 'usuario' || is_admin();

$summary = $pdo->query('SELECT COUNT(*) total, COALESCE(SUM(quantidade), 0) quantidade, COUNT(DISTINCT tipo) tipos FROM perifericos WHERE deleted_at IS NULL')->fetch(PDO::FETCH_ASSOC);
$total = (int) ($summary['total'] ?? 0);
$quantidade = (int) ($summary['quantidade'] ?? 0);
$tipos = (int) ($summary['tipos'] ?? 0);

$grafico = $pdo->query('SELECT tipo, SUM(quantidade) total FROM perifericos WHERE deleted_at IS NULL GROUP BY tipo ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$dados = [];

foreach ($grafico as $g) {
    $labels[] = $g['tipo'];
    $dados[] = (int) $g['total'];
}

$baixoItens = $pdo->prepare('SELECT id, nome_ativo, tipo, quantidade FROM perifericos WHERE deleted_at IS NULL AND quantidade <= :threshold ORDER BY quantidade ASC, id DESC LIMIT 10');
$baixoItens->execute([':threshold' => $lowStockThreshold]);
$baixoItens = $baixoItens->fetchAll(PDO::FETCH_ASSOC);

$ultimos = $pdo->query('SELECT id, nome_ativo, tipo, quantidade FROM perifericos WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$perifericosBusca = $pdo->query('SELECT id, nome_ativo, tipo, numero_serie, chave_licenca, quantidade FROM perifericos WHERE deleted_at IS NULL ORDER BY nome_ativo ASC LIMIT 1000')->fetchAll(PDO::FETCH_ASSOC);

$labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$dadosJson = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$perifericosBuscaJson = json_encode($perifericosBusca, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($labelsJson === false) {
    $labelsJson = '[]';
}

if ($dadosJson === false) {
    $dadosJson = '[]';
}

if ($perifericosBuscaJson === false) {
    $perifericosBuscaJson = '[]';
}

ob_start();
?>
<section class="dashboard-shell fade-in">
    <header class="dashboard-header reveal-on-scroll">
        <div>
            <span class="dashboard-kicker">IRTRADE Asset Manager</span>
            <h1 class="dashboard-title">Dashboard Executivo de Inventário</h1>
            <p class="dashboard-subtitle">Acompanhe indicadores-chave, distribuição de categorias e alertas operacionais em uma visão única e profissional.</p>
        </div>
        <div class="dashboard-header-actions">
            <span class="badge rounded-pill text-bg-success">Bem-vindo, <?= htmlspecialchars($_SESSION['user']['nome']) ?></span>
            <div class="quick-actions">
                <a href="perifericos/create.php" class="btn btn-primary btn-ripple"><i class="fa-solid fa-plus me-2"></i>Novo</a>
                <a href="perifericos/read.php" class="btn btn-outline-secondary btn-ripple"><i class="fa-solid fa-table-list me-2"></i>Listar</a>
                <a href="relatorios/read.php" class="btn btn-outline-primary btn-ripple"><i class="fa-solid fa-chart-pie me-2"></i>Relatórios</a>
                <a href="perifericos/lixeira.php" class="btn btn-warning btn-ripple"><i class="fa-solid fa-trash-can-arrow-up me-2"></i>Lixeira</a>
                <?php if ($canExport): ?>
                    <a href="perifericos/export.php" class="btn btn-success btn-ripple"><i class="fa-solid fa-file-export me-2"></i>Exportar CSV</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="metric-grid fade-in delay-1">
        <article class="metric-card reveal-on-scroll metric-card-neutral">
            <div class="metric-head">
                <p class="metric-label">Total de Itens</p>
                <div class="metric-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <h3 class="metric-value" data-counter="<?= (int) $total ?>">0</h3>
            <p class="metric-footnote">Ativos cadastrados no inventário</p>
        </article>

        <article class="metric-card reveal-on-scroll metric-card-sky">
            <div class="metric-head">
                <p class="metric-label">Estoque Total</p>
                <div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div>
            </div>
            <h3 class="metric-value" data-counter="<?= (int) ($quantidade ?? 0) ?>">0</h3>
            <p class="metric-footnote">Somatório de unidades disponíveis</p>
        </article>

        <article class="metric-card reveal-on-scroll metric-card-steel">
            <div class="metric-head">
                <p class="metric-label">Tipos</p>
                <div class="metric-icon"><i class="fa-solid fa-tags"></i></div>
            </div>
            <h3 class="metric-value" data-counter="<?= (int) $tipos ?>">0</h3>
            <p class="metric-footnote">Categorias distintas no estoque</p>
        </article>

        <article class="metric-card reveal-on-scroll metric-card-alert">
            <div class="metric-head">
                <p class="metric-label">Estoque Baixo</p>
                <div class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <h3 class="metric-value" data-counter="<?= count($baixoItens) ?>">0</h3>
            <p class="metric-footnote">Itens com quantidade menor ou igual a <?= (int) $lowStockThreshold ?></p>
        </article>
    </section>

    <section class="surface-card detail-card reveal-on-scroll" id="dashboard-search-result" style="display:none;">
        <h2 class="section-title">Detalhes do periférico selecionado</h2>
        <p class="section-subtitle">Informações completas encontradas pela busca global.</p>
        <div class="search-result-grid">
            <div><strong>Nome:</strong> <span id="search-result-nome">-</span></div>
            <div><strong>Tipo:</strong> <span id="search-result-tipo">-</span></div>
            <div><strong>Quantidade:</strong> <span id="search-result-quantidade">-</span></div>
            <div><strong>Número de Série:</strong> <span id="search-result-serie">-</span></div>
            <div><strong>Licença:</strong> <span id="search-result-licenca">-</span></div>
            <div><strong>ID:</strong> <span id="search-result-id">-</span></div>
        </div>
    </section>

    <section class="chart-grid">
        <article class="surface-card panel-card chart-panel reveal-on-scroll">
            <div class="panel-head">
                <h2 class="section-title">Gráfico Pizza</h2>
                <p class="section-subtitle">Distribuição de quantidade por tipo de periférico.</p>
            </div>
            <div class="chart-wrap">
                <canvas id="grafico"></canvas>
            </div>
        </article>

        <article class="surface-card panel-card chart-panel reveal-on-scroll">
            <div class="panel-head">
                <h2 class="section-title">Gráfico Barras</h2>
                <p class="section-subtitle">Comparativo de volumes por categoria.</p>
            </div>
            <div class="chart-wrap">
                <canvas id="graficoBarras"></canvas>
            </div>
        </article>
    </section>

    <section class="surface-card panel-card reveal-on-scroll">
        <div class="panel-head">
            <h2 class="section-title">Últimos periféricos cadastrados</h2>
            <p class="section-subtitle">Lista dos cinco registros mais recentes no sistema.</p>
        </div>

        <ul class="list-clean list-cards">
            <?php foreach ($ultimos as $item): ?>
                <li>
                    <div class="item-main">
                        <strong><?= htmlspecialchars($item['nome_ativo']) ?></strong>
                        <span class="item-type"><?= htmlspecialchars($item['tipo']) ?></span>
                    </div>
                    <div class="item-meta">
                        <span class="item-qty">Quantidade: <?= htmlspecialchars($item['quantidade']) ?></span>
                        <span class="badge rounded-pill <?= (int)$item['quantidade'] <= $lowStockThreshold ? 'text-bg-warning' : 'text-bg-success' ?>">
                            <?= (int)$item['quantidade'] <= $lowStockThreshold ? 'Baixo' : 'Regular' ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (empty($ultimos)): ?>
                <li class="list-empty"><span class="text-muted">Nenhum periférico cadastrado.</span></li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="surface-card panel-card reveal-on-scroll <?= empty($baixoItens) ? 'alert-card-success' : 'alert-card-danger' ?>">
        <div class="panel-head">
            <h2 class="section-title">Alertas de estoque</h2>
            <p class="section-subtitle">Itens com nível crítico de disponibilidade.</p>
        </div>

        <?php if (!empty($baixoItens)): ?>
            <ul class="list-clean list-cards alert-list">
                <?php foreach ($baixoItens as $b): ?>
                    <li>
                        <div class="item-main">
                            <span class="low-stock-dot"></span>
                            <strong><?= htmlspecialchars($b['nome_ativo']) ?></strong>
                        </div>
                        <div class="item-meta">
                            <span class="item-type"><?= htmlspecialchars($b['tipo']) ?></span>
                            <span class="badge rounded-pill text-bg-danger">Restante: <?= htmlspecialchars($b['quantidade']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-alert-state">
                <i class="fa-solid fa-circle-check"></i>
                <p>Nenhum item com estoque baixo.</p>
            </div>
        <?php endif; ?>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.dashboardPieData = {
    labels: <?= $labelsJson ?>,
    values: <?= $dadosJson ?>
};

window.dashboardBarData = {
    labels: <?= $labelsJson ?>,
    values: <?= $dadosJson ?>
};

window.dashboardSearchItems = <?= $perifericosBuscaJson ?>;
</script>
<script src="assets/js/dashboard.js"></script>

<?php
$conteudo = ob_get_clean();
include 'views/layout.php';
?>