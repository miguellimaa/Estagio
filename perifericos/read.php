<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

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
            <h1 class="page-title">
                <i class="fa-solid fa-keyboard me-2"></i>
                Periféricos
            </h1>

            <p class="page-subtitle">
                Lista completa com pesquisa em tempo real.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="create.php" class="btn-modern btn-primary-modern">
                <i class="fa-solid fa-plus me-2"></i>
                Novo
            </a>

            <a href="export.php" class="btn-modern btn-success-modern">
                <i class="fa-solid fa-file-export me-2"></i>
                Exportar
            </a>

        </div>

    </div>
</section>


<section class="surface-card table-shell reveal-on-scroll">

    <div class="table-toolbar">

        <div>
            <h2 class="table-title">Tabela de Periféricos</h2>
            <p class="table-subtitle">
                Total de registros: <?= count($dados) ?>
            </p>
        </div>

        <div class="table-search">
            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Buscar..."
                data-table-search>
        </div>

    </div>


    <div class="table-scroll">

        <table class="data-table" data-table>

            <thead>

                <tr>

                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
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
                        <span class="status-badge success">
                            <?= htmlspecialchars($d['tipo']) ?>
                        </span>
                    </td>

                    <td>

                        <?php if($d['quantidade'] <= 5): ?>

                            <span class="status-badge warning">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                <?= $d['quantidade'] ?>
                            </span>

                        <?php else: ?>

                            <span class="status-badge success">
                                <?= $d['quantidade'] ?>
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <div class="actions">

                            <a
                                href="update.php?id=<?= $d['id'] ?>"
                                class="icon-btn warning"
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
                                    class="icon-btn danger"
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

                    <td colspan="4" class="text-center p-5">

                        <i class="fa-solid fa-box-open fa-2x mb-3 text-secondary"></i>

                        <br>

                        Nenhum periférico cadastrado.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <div class="table-pagination">

        <span data-page-info>
            Total de <?= count($dados) ?> registro(s)
        </span>

    </div>

</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
