<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$lowStockThreshold = setting_get_int($pdo, 'low_stock_threshold', 5);
$tablePerPage = setting_get_int($pdo, 'table_per_page', 8);
$tablePerPage = max(5, min(100, $tablePerPage));
$exportRole = setting_get($pdo, 'export_role', 'admin') ?? 'admin';
$canExport = $exportRole === 'usuario' || is_admin();

// BUSCA SOMENTE OS ITENS ATIVOS
$stmt = $pdo->query("
    SELECT *
    FROM perifericos
    WHERE deleted_at IS NULL
    ORDER BY id DESC
");

$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

        <div>
            <h1 class="page-title display-6 fw-bold mb-0">
                <i class="fa-solid fa-keyboard me-2"></i>
                Periféricos
            </h1>

            <p class="page-subtitle text-secondary mb-0 mt-2">
                Lista completa com pesquisa em tempo real.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="create.php" class="btn btn-primary btn-ripple">
                <i class="fa-solid fa-plus me-2"></i>
                Novo
            </a>

            <?php if ($canExport): ?>
                <a href="export.php" class="btn btn-success btn-ripple">
                    <i class="fa-solid fa-file-export me-2"></i>
                    Exportar CSV
                </a>
            <?php endif; ?>

        </div>

    </div>
</section>


<section class="surface-card table-shell reveal-on-scroll">

    <div class="table-toolbar">

        <div>
            <h2 class="table-title h5 fw-bold mb-0">Tabela de Periféricos</h2>
            <p class="table-subtitle text-secondary small mb-0 mt-1">
                Total de registros: <?= count($dados) ?>
            </p>
        </div>

        <div class="table-search input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input
                type="text"
                class="form-control"
                placeholder="Buscar..."
                data-table-search>
        </div>

    </div>


    <div class="table-scroll">

        <table class="table table-hover align-middle data-table" data-table data-per-page="<?= (int) $tablePerPage ?>">

            <thead>

                <tr>

                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Valor</th>
                    <th width="120">Ações</th>

                </tr>

            </thead>

            <tbody>

            <?php if(count($dados) > 0): ?>

                <?php foreach($dados as $d): ?>

                <tr>

                    <td>
                        <strong><?= htmlspecialchars($d['nome_ativo']) ?></strong>
                    </td>

                    <td>
                        <span class="badge rounded-pill text-bg-success">
                            <?= htmlspecialchars($d['tipo']) ?>
                        </span>
                    </td>

                    <td>

                        <?php if($d['quantidade'] <= $lowStockThreshold): ?>

                            <span class="badge rounded-pill text-bg-warning">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                <?= $d['quantidade'] ?>
                            </span>

                        <?php else: ?>

                            <span class="badge rounded-pill text-bg-success">
                                <?= $d['quantidade'] ?>
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>
                        <span class="fw-semibold">R$ <?= number_format((float) ($d['valor'] ?? 0), 2, ',', '.') ?></span>
                    </td>

                    <td>

                        <div class="d-flex gap-2">

                            <a
                                href="update.php?id=<?= $d['id'] ?>"
                                class="btn btn-outline-warning btn-sm"
                                title="Editar">

                                <i class="fa-solid fa-pen"></i>

                            </a>

                            <?php if($_SESSION['user']['perfil'] == 'admin'): ?>

                            <form
                                method="POST"
                                action="delete.php"
                                class="d-inline"
                                onsubmit="return confirm('Deseja mover este item para a lixeira?')">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $d['id'] ?>">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= $_SESSION['csrf_token'] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger btn-sm"
                                    title="Excluir">

                                    <i class="fa-solid fa-trash"></i>

                                </button>

                            </form>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="5" class="text-center p-5">

                        <i class="fa-solid fa-box-open fa-2x mb-3 text-secondary"></i>

                        <br>

                        Nenhum periférico cadastrado.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <div class="table-pagination d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span data-page-info>Total de <?= count($dados) ?> registro(s)</span>

        <nav aria-label="Paginação da tabela de periféricos">
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

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
