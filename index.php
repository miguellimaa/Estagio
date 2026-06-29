<?php
session_start();
require 'config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: auth/login.php");
    exit;
}

$total = $pdo->query("SELECT COUNT(*) FROM perifericos")->fetchColumn();
$quantidade = $pdo->query("SELECT SUM(quantidade) FROM perifericos")->fetchColumn();
$tipos = $pdo->query("SELECT COUNT(DISTINCT tipo) FROM perifericos")->fetchColumn();

$grafico = $pdo->query("SELECT tipo, SUM(quantidade) total FROM perifericos GROUP BY tipo");

$labels = [];
$dados = [];

foreach($grafico as $g){
    $labels[] = $g['tipo'];
    $dados[] = $g['total'];
}

$baixo = $pdo->query("SELECT * FROM perifericos WHERE quantidade <= 5");

ob_start();
?>
<div class="dashboard-hero">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
            <h1>Dashboard</h1>
            <p>Resumo poderoso dos seus periféricos com alertas e gráficos instantâneos.</p>
        </div>
        <div class="text-md-right mt-3 mt-md-0">
            <span class="badge badge-pill badge-light py-2 px-3 shadow-sm">Bem-vindo, <strong><?= htmlspecialchars($_SESSION['user']['nome']) ?></strong></span>
        </div>
    </div>
</div>

<div class="row dashboard-metric">
    <div class="col-md-4 mb-4">
        <div class="small-box bg-info text-white">
            <div class="inner">
                <h3><?= htmlspecialchars($total) ?></h3>
                <p>Total de itens</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="small-box bg-success text-white">
            <div class="inner">
                <h3><?= htmlspecialchars($quantidade ?? 0) ?></h3>
                <p>Total em estoque</p>
            </div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="small-box bg-warning text-white">
            <div class="inner">
                <h3><?= htmlspecialchars($tipos) ?></h3>
                <p>Tipos diferentes</p>
            </div>
            <div class="icon"><i class="fas fa-tags"></i></div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
        <div>
            <h5 class="mb-0">Ações rápidas</h5>
            <small class="text-muted">Crie, liste ou exporte periféricos rapidamente.</small>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="perifericos/create.php" class="btn btn-primary btn-sm mr-2"><i class="fas fa-plus"></i> Novo</a>
            <a href="perifericos/read.php" class="btn btn-light btn-sm mr-2"><i class="fas fa-list"></i> Listar</a>
            <a href="perifericos/export.php" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Exportar</a>
        </div>
    </div>
</div>

<?php if($baixo->rowCount()): ?>
<div class="card low-stock-card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <span class="badge badge-danger mr-3 py-2 px-3"><i class="fas fa-exclamation-triangle"></i> Estoque baixo</span>
            <div>
                <h5 class="mb-0">Alguns itens precisam de reposição</h5>
                <small class="text-muted">Verifique e reponha antes de faltar.</small>
            </div>
        </div>
        <ul>
            <?php foreach($baixo as $b): ?>
                <li><strong><?= htmlspecialchars($b['nome_ativo']) ?></strong> — apenas <?= htmlspecialchars($b['quantidade']) ?> em estoque</li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="card dashboard-chart-card shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0">Distribuição por tipo</h3>
            <small class="text-muted">Entenda rapidamente como o estoque está dividido.</small>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-center" style="max-width:520px;margin:auto;">
            <canvas id="grafico" style="width:100%;max-width:520px;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('grafico');
const grafico = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($dados) ?>,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            borderWidth: 0,
            hoverOffset: 18,
        }]
    },
    options: {
        cutout: '60%',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
            tooltip: { bodySpacing: 8, usePointStyle: true }
        },
        animation: { duration: 1300 }
    }
});

// Contador animado
const counters = document.querySelectorAll('.small-box h3');
counters.forEach(el => {
    const valor = parseInt(el.innerText, 10) || 0;
    let atual = 0;
    const passo = Math.max(1, Math.round(valor / 25));
    const intervalo = setInterval(() => {
        atual += passo;
        if (atual >= valor) {
            el.innerText = valor;
            clearInterval(intervalo);
            return;
        }
        el.innerText = atual;
    }, 20);
});
</script>

<?php
$conteudo = ob_get_clean();
include 'views/layout.php';
?>