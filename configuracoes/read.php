<?php
session_start();
require '../config/conexao.php';

require_login();

if (!is_admin()) {
    flash('danger', 'Acesso restrito ao administrador.');
    redirect('../index.php');
}

$defaults = settings_defaults();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        flash('danger', 'Falha de seguranca (CSRF).');
        redirect('read.php');
    }

    $lowStock = filter_input(INPUT_POST, 'low_stock_threshold', FILTER_VALIDATE_INT);
    $tablePerPage = filter_input(INPUT_POST, 'table_per_page', FILTER_VALIDATE_INT);
    $sessionTimeout = filter_input(INPUT_POST, 'session_timeout_minutes', FILTER_VALIDATE_INT);
    $trashDays = filter_input(INPUT_POST, 'trash_auto_delete_days', FILTER_VALIDATE_INT);

    $exportRole = $_POST['export_role'] ?? 'admin';
    $csvDelimiter = $_POST['csv_delimiter'] ?? ';';
    $csvColumns = $_POST['csv_columns'] ?? [];

    $validColumns = ['nome', 'tipo', 'quantidade', 'numero_serie', 'chave_licenca'];
    $csvColumns = array_values(array_intersect($validColumns, (array) $csvColumns));

    if ($lowStock === false || $lowStock === null || $lowStock < 0) {
        $lowStock = (int) $defaults['low_stock_threshold'];
    }

    if ($tablePerPage === false || $tablePerPage === null || $tablePerPage < 5 || $tablePerPage > 100) {
        $tablePerPage = (int) $defaults['table_per_page'];
    }

    if ($sessionTimeout === false || $sessionTimeout === null || $sessionTimeout < 5 || $sessionTimeout > 240) {
        $sessionTimeout = (int) $defaults['session_timeout_minutes'];
    }

    if ($trashDays === false || $trashDays === null || $trashDays < 0 || $trashDays > 365) {
        $trashDays = (int) $defaults['trash_auto_delete_days'];
    }

    if (!in_array($exportRole, ['admin', 'usuario'], true)) {
        $exportRole = $defaults['export_role'];
    }

    if (!in_array($csvDelimiter, [';', ','], true)) {
        $csvDelimiter = $defaults['csv_delimiter'];
    }

    if (empty($csvColumns)) {
        $csvColumns = explode(',', $defaults['csv_columns']);
    }

    setting_set_many($pdo, [
        'low_stock_threshold' => (string) $lowStock,
        'table_per_page' => (string) $tablePerPage,
        'session_timeout_minutes' => (string) $sessionTimeout,
        'export_role' => $exportRole,
        'csv_delimiter' => $csvDelimiter,
        'csv_columns' => implode(',', $csvColumns),
        'trash_auto_delete_days' => (string) $trashDays,
    ]);

    flash('success', 'Configuracoes salvas com sucesso.');
    redirect('read.php');
}

$lowStockThreshold = setting_get_int($pdo, 'low_stock_threshold', (int) $defaults['low_stock_threshold']);
$tablePerPage = setting_get_int($pdo, 'table_per_page', (int) $defaults['table_per_page']);
$sessionTimeoutMinutes = setting_get_int($pdo, 'session_timeout_minutes', (int) $defaults['session_timeout_minutes']);
$trashAutoDeleteDays = setting_get_int($pdo, 'trash_auto_delete_days', (int) $defaults['trash_auto_delete_days']);
$exportRole = setting_get($pdo, 'export_role', $defaults['export_role']) ?? $defaults['export_role'];
$csvDelimiter = setting_get($pdo, 'csv_delimiter', $defaults['csv_delimiter']) ?? $defaults['csv_delimiter'];
$csvColumnsSetting = setting_get($pdo, 'csv_columns', $defaults['csv_columns']) ?? $defaults['csv_columns'];
$csvColumns = array_filter(array_map('trim', explode(',', $csvColumnsSetting)));

$availableColumns = [
    'nome' => 'Nome',
    'tipo' => 'Tipo',
    'quantidade' => 'Quantidade',
    'numero_serie' => 'Numero de Serie',
    'chave_licenca' => 'Chave de Licenca',
];

ob_start();
?>
<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0">
                <i class="fa-solid fa-gear me-2"></i>
                Configuracoes
            </h1>
            <p class="page-subtitle text-secondary mb-0 mt-2">
                Controle parametros globais do sistema.
            </p>
        </div>
    </div>
</section>

<section class="surface-card p-4 reveal-on-scroll">
    <form method="POST" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="low_stock_threshold">Limite de estoque baixo</label>
            <input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" class="form-control" value="<?= (int) $lowStockThreshold ?>" required>
            <div class="form-text">Usado nos alertas do dashboard e tabela.</div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="table_per_page">Itens por pagina</label>
            <input id="table_per_page" name="table_per_page" type="number" min="5" max="100" class="form-control" value="<?= (int) $tablePerPage ?>" required>
            <div class="form-text">Padrao da paginacao das tabelas.</div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="session_timeout_minutes">Timeout de sessao (minutos)</label>
            <input id="session_timeout_minutes" name="session_timeout_minutes" type="number" min="5" max="240" class="form-control" value="<?= (int) $sessionTimeoutMinutes ?>" required>
            <div class="form-text">Logout automatico por inatividade.</div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="export_role">Quem pode exportar CSV</label>
            <select id="export_role" name="export_role" class="form-select">
                <option value="admin" <?= $exportRole === 'admin' ? 'selected' : '' ?>>Somente admin</option>
                <option value="usuario" <?= $exportRole === 'usuario' ? 'selected' : '' ?>>Admin e usuario</option>
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="csv_delimiter">Separador CSV</label>
            <select id="csv_delimiter" name="csv_delimiter" class="form-select">
                <option value=";" <?= $csvDelimiter === ';' ? 'selected' : '' ?>>Ponto e virgula (;)</option>
                <option value="," <?= $csvDelimiter === ',' ? 'selected' : '' ?>>Virgula (,)</option>
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="trash_auto_delete_days">Limpeza automatica da lixeira (dias)</label>
            <input id="trash_auto_delete_days" name="trash_auto_delete_days" type="number" min="0" max="365" class="form-control" value="<?= (int) $trashAutoDeleteDays ?>" required>
            <div class="form-text">0 desativa. Campo salvo para uso em rotina futura.</div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold d-block">Colunas da exportacao CSV</label>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($availableColumns as $key => $label): ?>
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="csv_columns[]"
                            value="<?= htmlspecialchars($key) ?>"
                            id="csv_col_<?= htmlspecialchars($key) ?>"
                            <?= in_array($key, $csvColumns, true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="csv_col_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
            <button type="submit" class="btn btn-primary btn-ripple">
                <i class="fa-solid fa-floppy-disk me-2"></i>
                Salvar configuracoes
            </button>
        </div>
    </form>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
