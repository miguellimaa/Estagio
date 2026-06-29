<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: auth/login.php');
    exit;
}

$total = $pdo->query('SELECT COUNT(*) FROM perifericos')->fetchColumn();
$quantidade = $pdo->query('SELECT SUM(quantidade) FROM perifericos')->fetchColumn();
$tipos = $pdo->query('SELECT COUNT(DISTINCT tipo) FROM perifericos')->fetchColumn();

$grafico = $pdo->query('SELECT tipo, SUM(quantidade) total FROM perifericos GROUP BY tipo');

$labels = [];
$dados = [];

foreach ($grafico as $g) {
    $labels[] = $g['tipo'];
    $dados[] = $g['total'];
}

$baixo = $pdo->query('SELECT * FROM perifericos WHERE quantidade <= 5');
$baixoItens = $baixo->fetchAll(PDO::FETCH_ASSOC);

$ultimos = $pdo->query('SELECT * FROM perifericos ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$perifericosBusca = $pdo->query('SELECT id, nome_ativo, tipo, numero_serie, chave_licenca, quantidade FROM perifericos ORDER BY nome_ativo ASC')->fetchAll(PDO::FETCH_ASSOC);

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
<section class="dashboard-hero fade-in">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <span class="hero-kicker">IRTRADE Asset Manager</span>
            <h1 class="hero-title">Visão executiva de ativos e estoque</h1>
            <p class="hero-text">Monitore indicadores, acompanhe o status dos periféricos e antecipe necessidades de reposição com uma visão clara do ambiente.</p>
        </div>
        <div class="text-md-end mt-3 mt-md-0">
            <span class="status-badge success">Bem-vindo, <?= htmlspecialchars($_SESSION['user']['nome']) ?></span>
        </div>
    </div>
</section>

<section class="metric-grid fade-in delay-1">
    <article class="metric-card reveal-on-scroll">
        <p class="metric-label">Total Periféricos</p>
        <h3 class="metric-value" data-counter="<?= (int) $total ?>">0</h3>
        <div class="metric-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
    </article>

    <article class="metric-card reveal-on-scroll">
        <p class="metric-label">Quantidade Estoque</p>
        <h3 class="metric-value" data-counter="<?= (int) ($quantidade ?? 0) ?>">0</h3>
        <div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div>
    </article>

    <article class="metric-card reveal-on-scroll">
        <p class="metric-label">Categorias</p>
        <h3 class="metric-value" data-counter="<?= (int) $tipos ?>">0</h3>
        <div class="metric-icon"><i class="fa-solid fa-tags"></i></div>
    </article>

    <article class="metric-card reveal-on-scroll">
        <p class="metric-label">Estoque Crítico</p>
        <h3 class="metric-value" data-counter="<?= count($baixoItens) ?>">0</h3>
        <div class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </article>
</section>

<section class="surface-card chart-card mt-4 reveal-on-scroll">
    <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center mb-3">
        <div>
            <h2 class="section-title">Ações rápidas</h2>
            <p class="section-subtitle mb-0">Operações frequentes do módulo de ativos.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="perifericos/create.php" class="btn-modern btn-primary-modern"><i class="fa-solid fa-plus me-2"></i>Novo</a>
            <a href="perifericos/read.php" class="btn-modern btn-light-modern"><i class="fa-solid fa-table-list me-2"></i>Listar</a>
            <a href="perifericos/export.php" class="btn-modern btn-success-modern"><i class="fa-solid fa-file-export me-2"></i>Exportar</a>
        </div>
    </div>
</section>

<section class="surface-card chart-card mt-3 reveal-on-scroll" id="dashboard-search-result" style="display:none;">
    <h2 class="section-title">Detalhes do periférico selecionado</h2>
    <p class="section-subtitle">Informações completas encontradas pela barra de pesquisa.</p>

    <div class="row g-3">
        <div class="col-md-4"><strong>Nome:</strong> <span id="search-result-nome">-</span></div>
        <div class="col-md-4"><strong>Tipo:</strong> <span id="search-result-tipo">-</span></div>
        <div class="col-md-4"><strong>Quantidade:</strong> <span id="search-result-quantidade">-</span></div>
        <div class="col-md-4"><strong>Número de Série:</strong> <span id="search-result-serie">-</span></div>
        <div class="col-md-4"><strong>Licença:</strong> <span id="search-result-licenca">-</span></div>
        <div class="col-md-4"><strong>ID:</strong> <span id="search-result-id">-</span></div>
    </div>
</section>

<section class="dashboard-grid">
    <article class="surface-card chart-card reveal-on-scroll">
        <h2 class="section-title">Gráfico Pizza</h2>
        <p class="section-subtitle">Distribuição por tipo de periférico.</p>
        <div class="chart-wrap">
            <canvas id="grafico"></canvas>
        </div>
    </article>

    <article class="surface-card activity-card reveal-on-scroll">
        <h2 class="section-title">Últimos periféricos</h2>
        <p class="section-subtitle">Cadastros mais recentes no sistema.</p>
        <ul class="list-clean">
            <?php foreach ($ultimos as $item): ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars($item['nome_ativo']) ?></strong>
                        <div class="text-muted"><?= htmlspecialchars($item['tipo']) ?></div>
                    </div>
                    <span class="status-badge <?= (int)$item['quantidade'] <= 5 ? 'warning' : 'success' ?>">
                        Qtd: <?= htmlspecialchars($item['quantidade']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($ultimos)): ?>
                <li><span class="text-muted">Nenhum periférico cadastrado.</span></li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="dashboard-grid mt-3">
    <article class="surface-card chart-card reveal-on-scroll">
        <h2 class="section-title">Gráfico Barras</h2>
        <p class="section-subtitle">Quantidade por categoria.</p>
        <div class="chart-wrap">
            <canvas id="graficoBarras"></canvas>
        </div>
    </article>

    <article class="surface-card activity-card reveal-on-scroll">
        <h2 class="section-title">Atividades recentes</h2>
        <p class="section-subtitle">Eventos de acompanhamento do estoque.</p>
        <ul class="list-clean">
            <li><span>Atualização de inventário</span><span class="text-muted">Hoje</span></li>
            <li><span>Exportação XLS executada</span><span class="text-muted">Hoje</span></li>
            <li><span>Revisão de categorias</span><span class="text-muted">Ontem</span></li>
            <li><span>Conferência de disponibilidade</span><span class="text-muted">Ontem</span></li>
        </ul>
    </article>
</section>

<?php if (!empty($baixoItens)): ?>
<section class="low-stock-alert reveal-on-scroll">
    <h3><i class="fa-solid fa-triangle-exclamation me-2"></i>Alerta de estoque baixo</h3>
    <ul class="list-clean">
        <?php foreach ($baixoItens as $b): ?>
            <li>
                <span><span class="low-stock-dot me-2"></span><strong><?= htmlspecialchars($b['nome_ativo']) ?></strong></span>
                <span class="status-badge danger">Restante: <?= htmlspecialchars($b['quantidade']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

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