<?php
session_start();
require '../config/conexao.php';

require_admin('../index.php');

$stmt = $pdo->query('SELECT id, nome, email, perfil FROM usuarios ORDER BY id DESC');
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<section class="surface-card p-4 mb-4 fade-in">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title display-6 fw-bold mb-0"><i class="fa-solid fa-users me-2"></i>Usuários</h1>
            <p class="page-subtitle text-secondary mb-0 mt-2">Gerencie contas de acesso da plataforma.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="create.php" class="btn btn-primary btn-ripple"><i class="fa-solid fa-user-plus me-2"></i>Novo usuário</a>
        </div>
    </div>
</section>

<section class="surface-card table-shell reveal-on-scroll">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title h5 fw-bold mb-0">Tabela de Usuários</h2>
            <p class="table-subtitle text-secondary small mb-0 mt-1">Total de registros: <?= count($dados) ?></p>
        </div>

        <div class="table-search input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" class="form-control" placeholder="Buscar por nome, e-mail ou perfil..." data-table-search>
        </div>
    </div>

    <div class="table-scroll">
        <table class="table table-hover align-middle data-table" data-table>
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
                            <span class="badge rounded-pill <?= ($d['perfil'] ?? '') === 'admin' ? 'text-bg-warning' : 'text-bg-success' ?>">
                                <?= htmlspecialchars($d['perfil'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="update.php?id=<?= htmlspecialchars($d['id'] ?? '') ?>" class="btn btn-outline-warning btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este usuário?')">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($d['id'] ?? '') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir">
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

    <div class="table-pagination d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span data-page-info>Página 1</span>
        <nav aria-label="Paginação da tabela de usuários">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item">
                    <button type="button" class="page-link" data-page-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                </li>
                <li class="page-item">
                    <button type="button" class="page-link" data-page-next aria-label="Próxima"><i class="fa-solid fa-chevron-right"></i></button>
                </li>
            </ul>
        </nav>
    </div>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
