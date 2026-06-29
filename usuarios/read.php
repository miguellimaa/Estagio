<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['user']['perfil'] ?? '') !== 'admin') {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Acesso restrito ao administrador.'];
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->query('SELECT id, nome, email, perfil FROM usuarios ORDER BY id DESC');
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-users me-2"></i>Usuários</h1>
            <p class="page-subtitle">Gerencie contas de acesso da plataforma.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="create.php" class="btn-modern btn-primary-modern"><i class="fa-solid fa-user-plus me-2"></i>Novo usuário</a>
        </div>
    </div>
</section>

<section class="surface-card table-shell reveal-on-scroll">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title">Tabela de Usuários</h2>
            <p class="table-subtitle">Total de registros: <?= count($dados) ?></p>
        </div>

        <div class="table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por nome, e-mail ou perfil..." data-table-search>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table" data-table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['id'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($d['nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['email'] ?? '') ?></td>
                        <td>
                            <span class="status-badge <?= ($d['perfil'] ?? '') === 'admin' ? 'warning' : 'success' ?>">
                                <?= htmlspecialchars($d['perfil'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="update.php?id=<?= htmlspecialchars($d['id'] ?? '') ?>" class="icon-btn warning" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este usuário?')">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($d['id'] ?? '') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="icon-btn danger" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
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
