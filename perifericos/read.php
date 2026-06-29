<?php
session_start();
require '../config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM perifericos ORDER BY id DESC");
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-keyboard me-2"></i>Periféricos</h1>
            <p class="page-subtitle">Lista completa com pesquisa em tempo real, paginação e ações rápidas.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="create.php" class="btn-modern btn-primary-modern"><i class="fa-solid fa-plus me-2"></i>Novo</a>
            <a href="export.php" class="btn-modern btn-success-modern"><i class="fa-solid fa-file-export me-2"></i>Exportar</a>
        </div>
    </div>
</section>

<section class="surface-card table-shell reveal-on-scroll">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title">Tabela de Periféricos</h2>
            <p class="table-subtitle">Total de registros: <?= count($dados) ?></p>
        </div>

        <div class="table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por nome, tipo ou quantidade..." data-table-search>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table" data-table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['nome_ativo'] ?? '') ?></strong></td>
                        <td><span class="status-badge success"><?= htmlspecialchars($d['tipo'] ?? '') ?></span></td>
                        <td>
                            <?php if ((int)$d['quantidade'] <= 5): ?>
                                <span class="status-badge warning"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
                            <?php else: ?>
                                <span class="status-badge success"><?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="update.php?id=<?= htmlspecialchars($d['id'] ?? '') ?>" class="icon-btn warning" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <?php if ($_SESSION['user']['perfil'] == 'admin'): ?>
                                    <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este item?')">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($d['id'] ?? '') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="icon-btn danger" title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-pagination">
        <span data-page-info>Página 1</span>
        <div class="pagination-actions">
            <button type="button" data-page-prev><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" data-page-next><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
