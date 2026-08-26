<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$autoCleanupNotice = $_SESSION['trash_auto_cleanup_notice'] ?? null;
if (!empty($autoCleanupNotice)) {
    unset($_SESSION['trash_auto_cleanup_notice']);
}

$cleanupLogs = [];
$cleanupPeriod = $_GET['cleanup_period'] ?? '7d';
$cleanupPeriodOptions = [
    'today' => 'Hoje',
    '7d' => 'Ultimos 7 dias',
    '30d' => 'Ultimos 30 dias',
    'all' => 'Todos',
];

if (!isset($cleanupPeriodOptions[$cleanupPeriod])) {
    $cleanupPeriod = '7d';
}

$cleanupFromDateTime = null;
if ($cleanupPeriod === 'today') {
    $cleanupFromDateTime = date('Y-m-d 00:00:00');
} elseif ($cleanupPeriod === '7d') {
    $cleanupFromDateTime = date('Y-m-d H:i:s', time() - (7 * 86400));
} elseif ($cleanupPeriod === '30d') {
    $cleanupFromDateTime = date('Y-m-d H:i:s', time() - (30 * 86400));
}

if (is_admin()) {
    $cleanupLogs = get_recent_trash_cleanup_logs($pdo, 50, $cleanupFromDateTime);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Falha de seguranca (CSRF).'
        ];
        header('Location: lixeira.php');
        exit;
    }

    if (($_SESSION['user']['perfil'] ?? '') !== 'admin') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Acesso negado para esta operacao.'
        ];
        header('Location: lixeira.php');
        exit;
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';

    if (!$id) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'ID invalido.'
        ];
        header('Location: lixeira.php');
        exit;
    }

    if ($acao === 'restaurar') {
        $stmt = $pdo->prepare('
            UPDATE perifericos
            SET deleted_at = NULL
            WHERE id = ?
        ');
        $stmt->execute([$id]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Periferico restaurado com sucesso.'
        ];

        header('Location: lixeira.php');
        exit;
    }

    if ($acao === 'excluir_definitivo') {
        $stmt = $pdo->prepare('
            DELETE FROM perifericos
            WHERE id = ?
        ');
        $stmt->execute([$id]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Periferico excluido definitivamente.'
        ];

        header('Location: lixeira.php');
        exit;
    }

    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Acao invalida.'
    ];
    header('Location: lixeira.php');
    exit;
}

$stmt = $pdo->query('
    SELECT *
    FROM perifericos
    WHERE deleted_at IS NOT NULL
    ORDER BY deleted_at DESC
');

$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0">
                <i class="fa-solid fa-trash-can me-2"></i>
                Lixeira de Perifericos
            </h1>

            <p class="page-subtitle text-secondary mb-0 mt-2">
                Itens removidos logicamente. Voce pode restaurar ou excluir definitivamente.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="read.php" class="btn btn-outline-secondary btn-ripple">
                <i class="fa-solid fa-arrow-left me-2"></i>
                Voltar para lista
            </a>
        </div>
    </div>
</section>

<?php if (!empty($autoCleanupNotice)): ?>
<section class="surface-card p-3 mb-4 fade-in">
    <div class="alert alert-info mb-0" role="alert">
        Limpeza automatica executada: <?= (int) ($autoCleanupNotice['count'] ?? 0) ?> item(ns) removido(s) da lixeira (mais antigos que <?= (int) ($autoCleanupNotice['days'] ?? 0) ?> dia(s)).
    </div>
</section>
<?php endif; ?>

<section class="surface-card table-shell reveal-on-scroll">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title h5 fw-bold mb-0">Registros excluidos</h2>
            <p class="table-subtitle text-secondary small mb-0 mt-1">
                Total de registros na lixeira: <?= count($dados) ?>
            </p>
        </div>

        <div class="table-search input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input
                type="text"
                class="form-control"
                placeholder="Buscar na lixeira..."
                data-table-search>
        </div>
    </div>

    <div class="table-scroll">
        <table class="table table-hover align-middle data-table" data-table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Excluido em</th>
                    <th width="180">Acoes</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($dados) > 0): ?>
                <?php foreach ($dados as $d): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($d['nome_ativo']) ?></strong></td>
                    <td>
                        <span class="badge rounded-pill text-bg-warning">
                            <?= htmlspecialchars($d['tipo']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars((string)$d['quantidade']) ?></td>
                    <td><?= htmlspecialchars((string)$d['deleted_at']) ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <?php if (($_SESSION['user']['perfil'] ?? '') === 'admin'): ?>
                            <form
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Deseja restaurar este item?')">

                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="acao" value="restaurar">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <button type="submit" class="btn btn-outline-success btn-sm" title="Restaurar">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </form>

                            <form
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Deseja excluir definitivamente este item? Esta acao nao pode ser desfeita.')">

                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="acao" value="excluir_definitivo">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir definitivamente">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">Somente admin</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center p-5">
                        <i class="fa-solid fa-trash-can fa-2x mb-3 text-secondary"></i>
                        <br>
                        Nenhum item na lixeira.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-pagination d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span data-page-info>Total de <?= count($dados) ?> registro(s)</span>

        <nav aria-label="Paginação da lixeira">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item">
                    <button type="button" class="page-link" data-page-prev aria-label="Anterior">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </li>
                <li class="page-item">
                    <button type="button" class="page-link" data-page-next aria-label="Próxima">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</section>

<?php if (is_admin()): ?>
<section class="surface-card table-shell reveal-on-scroll mt-4">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title h5 fw-bold mb-0">Historico da limpeza automatica</h2>
            <p class="table-subtitle text-secondary small mb-0 mt-1">
                Execucoes registradas em banco para: <?= htmlspecialchars($cleanupPeriodOptions[$cleanupPeriod]) ?>.
            </p>
        </div>

        <form method="GET" class="d-flex align-items-end gap-2">
            <div>
                <label for="cleanup_period" class="form-label small mb-1">Periodo</label>
                <select id="cleanup_period" name="cleanup_period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($cleanupPeriodOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $cleanupPeriod === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Filtrar</button>
        </form>
    </div>

    <div class="table-scroll">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Dias de retencao</th>
                    <th>Itens removidos</th>
                    <th>Usuario</th>
                    <th>Origem</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($cleanupLogs)): ?>
                <?php foreach ($cleanupLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($log['executed_at'] ?? '')) ?></td>
                    <td><?= (int) ($log['retention_days'] ?? 0) ?></td>
                    <td>
                        <span class="badge rounded-pill text-bg-info">
                            <?= (int) ($log['deleted_count'] ?? 0) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars((string) ($log['user_id'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($log['request_uri'] ?? '-')) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center p-4 text-muted">Nenhum log de limpeza automatica registrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
