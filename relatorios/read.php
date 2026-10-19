<?php
session_start();
require '../config/conexao.php';

require_login();

$lowStockThreshold = setting_get_int($pdo, 'low_stock_threshold', 5);
$tipoSelecionado = trim((string) ($_GET['tipo'] ?? ''));
$statusSelecionado = (string) ($_GET['status'] ?? 'todos');
$busca = trim((string) ($_GET['busca'] ?? ''));

$tipos = $pdo->query("SELECT DISTINCT tipo FROM perifericos WHERE deleted_at IS NULL AND tipo IS NOT NULL AND tipo <> '' ORDER BY tipo ASC")->fetchAll(PDO::FETCH_COLUMN);

$where = ['deleted_at IS NULL'];
$params = [];

if ($tipoSelecionado !== '') {
    $where[] = 'tipo = ?';
    $params[] = $tipoSelecionado;
}

if ($statusSelecionado === 'baixo') {
    $where[] = 'quantidade <= ?';
    $params[] = $lowStockThreshold;
} elseif ($statusSelecionado === 'regular') {
    $where[] = 'quantidade > ?';
    $params[] = $lowStockThreshold;
}

if ($busca !== '') {
    $where[] = '(nome_ativo LIKE ? OR numero_serie LIKE ? OR chave_licenca LIKE ?)';
    $searchParam = '%' . $busca . '%';
    array_push($params, $searchParam, $searchParam, $searchParam);
}

$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT id, nome_ativo, tipo, quantidade, valor, numero_serie, chave_licenca FROM perifericos WHERE {$whereSql} ORDER BY nome_ativo ASC");
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItens = count($itens);
$totalUnidades = array_sum(array_map(static fn ($item) => (int) $item['quantidade'], $itens));
$valorTotal = array_sum(array_map(static fn ($item) => (float) ($item['valor'] ?? 0), $itens));
$itensBaixo = count(array_filter($itens, static fn ($item) => (int) $item['quantidade'] <= $lowStockThreshold));

$porTipo = [];
foreach ($itens as $item) {
    $tipo = $item['tipo'] ?: 'Sem tipo';
    $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + (int) $item['quantidade'];
}
arsort($porTipo);

$queryFiltros = http_build_query([
    'tipo' => $tipoSelecionado,
    'status' => $statusSelecionado,
    'busca' => $busca,
]);

ob_start();
?>
<section class="dashboard-shell reports-shell fade-in">
    <header class="dashboard-header reveal-on-scroll">
        <div>
            <span class="dashboard-kicker">Visao analitica</span>
            <h1 class="dashboard-title"><i class="fa-solid fa-chart-pie me-2"></i>Relatorios</h1>
            <p class="dashboard-subtitle">Consulte o inventario por categoria, disponibilidade e valor patrimonial.</p>
        </div>
        <div class="dashboard-header-actions">
            <span class="badge rounded-pill text-bg-light">Dados atualizados em <?= date('d/m/Y H:i') ?></span>
            <div class="quick-actions">
                <a href="export.php?<?= htmlspecialchars($queryFiltros) ?>" class="btn btn-success btn-ripple">
                    <i class="fa-solid fa-file-csv me-2"></i>Exportar recorte
                </a>
            </div>
        </div>
    </header>

    <form method="GET" class="surface-card report-filters reveal-on-scroll">
        <div class="report-filter-field report-filter-search">
            <label for="busca" class="form-label">Pesquisar item</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="busca" name="busca" value="<?= htmlspecialchars($busca) ?>" class="form-control" placeholder="Nome, serie ou licenca">
            </div>
        </div>
        <div class="report-filter-field">
            <label for="tipo" class="form-label">Tipo</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipoSelecionado === $tipo ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="report-filter-field">
            <label for="status" class="form-label">Disponibilidade</label>
            <select id="status" name="status" class="form-select">
                <option value="todos" <?= $statusSelecionado === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="baixo" <?= $statusSelecionado === 'baixo' ? 'selected' : '' ?>>Estoque baixo</option>
                <option value="regular" <?= $statusSelecionado === 'regular' ? 'selected' : '' ?>>Estoque regular</option>
            </select>
        </div>
        <div class="report-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-2"></i>Aplicar</button>
            <a href="read.php" class="btn btn-outline-secondary" title="Limpar filtros"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>

    <section class="metric-grid report-metrics fade-in delay-1">
        <article class="metric-card metric-card-neutral reveal-on-scroll"><div class="metric-head"><p class="metric-label">Itens encontrados</p><div class="metric-icon"><i class="fa-solid fa-boxes-stacked"></i></div></div><h2 class="metric-value"><?= number_format($totalItens, 0, ',', '.') ?></h2><p class="metric-footnote">Registros ativos no recorte</p></article>
        <article class="metric-card metric-card-sky reveal-on-scroll"><div class="metric-head"><p class="metric-label">Unidades</p><div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div></div><h2 class="metric-value"><?= number_format($totalUnidades, 0, ',', '.') ?></h2><p class="metric-footnote">Quantidade total em estoque</p></article>
        <article class="metric-card metric-card-steel reveal-on-scroll"><div class="metric-head"><p class="metric-label">Valor estimado</p><div class="metric-icon"><i class="fa-solid fa-sack-dollar"></i></div></div><h2 class="metric-value report-money">R$ <?= number_format($valorTotal, 2, ',', '.') ?></h2><p class="metric-footnote">Soma dos valores cadastrados</p></article>
        <article class="metric-card metric-card-alert reveal-on-scroll"><div class="metric-head"><p class="metric-label">Estoque baixo</p><div class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></div></div><h2 class="metric-value"><?= number_format($itensBaixo, 0, ',', '.') ?></h2><p class="metric-footnote">Limite configurado: <?= (int) $lowStockThreshold ?> unidades</p></article>
    </section>

    <section class="chart-grid reports-chart-grid">
        <article class="surface-card panel-card chart-panel reveal-on-scroll">
            <div class="panel-head"><h2 class="section-title">Unidades por tipo</h2><p class="section-subtitle">Distribuicao do estoque no recorte atual.</p></div>
            <div class="chart-wrap"><canvas id="reportTypeChart"></canvas></div>
        </article>
        <article class="surface-card panel-card reveal-on-scroll">
            <div class="panel-head"><h2 class="section-title">Resumo por categoria</h2><p class="section-subtitle">Compare o volume de cada tipo.</p></div>
            <div class="report-category-list">
                <?php foreach ($porTipo as $tipo => $quantidade): ?>
                    <div class="report-category-row"><span><?= htmlspecialchars($tipo) ?></span><strong><?= number_format($quantidade, 0, ',', '.') ?></strong></div>
                <?php endforeach; ?>
                <?php if (empty($porTipo)): ?><p class="text-secondary mb-0">Nenhum dado para os filtros selecionados.</p><?php endif; ?>
            </div>
        </article>
    </section>

    <section class="surface-card panel-card report-table-panel reveal-on-scroll">
        <div class="panel-head"><h2 class="section-title">Detalhamento do inventario</h2><p class="section-subtitle"><?= number_format($totalItens, 0, ',', '.') ?> registro(s) no resultado.</p></div>
        <div class="table-scroll"><table class="table table-hover align-middle mb-0"><thead><tr><th>Item</th><th>Tipo</th><th>Quantidade</th><th>Valor unitario</th><th>Situacao</th></tr></thead><tbody>
        <?php foreach ($itens as $item): $baixo = (int) $item['quantidade'] <= $lowStockThreshold; ?>
            <tr><td><strong><?= htmlspecialchars($item['nome_ativo']) ?></strong><?php if (!empty($item['numero_serie'])): ?><small class="d-block text-secondary">Serie: <?= htmlspecialchars($item['numero_serie']) ?></small><?php endif; ?></td><td><?= htmlspecialchars($item['tipo']) ?></td><td><?= number_format((int) $item['quantidade'], 0, ',', '.') ?></td><td>R$ <?= number_format((float) ($item['valor'] ?? 0), 2, ',', '.') ?></td><td><span class="badge rounded-pill <?= $baixo ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $baixo ? 'Estoque baixo' : 'Regular' ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($itens)): ?><tr><td colspan="5" class="text-center p-5 text-secondary"><i class="fa-solid fa-magnifying-glass mb-2"></i><br>Nenhum registro encontrado.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const canvas = document.getElementById('reportTypeChart');
    if (!canvas || !window.Chart) return;
    new Chart(canvas, {
        type: 'doughnut',
        data: { labels: <?= json_encode(array_keys($porTipo), JSON_UNESCAPED_UNICODE) ?>, datasets: [{ data: <?= json_encode(array_values($porTipo)) ?>, backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#64748b', '#14b8a6'], borderWidth: 3, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
