<?php
session_start();
require '../config/conexao.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
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
            <h1 class="page-title">
                <i class="fa-solid fa-trash-can me-2"></i>
                Lixeira de Perifericos
            </h1>

            <p class="page-subtitle">
                Itens removidos logicamente. Voce pode restaurar ou excluir definitivamente.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="read.php" class="btn-modern btn-light-modern">
                <i class="fa-solid fa-arrow-left me-2"></i>
                Voltar para lista
            </a>
        </div>
    </div>
</section>

<section class="surface-card table-shell reveal-on-scroll">
    <div class="table-toolbar">
        <div>
            <h2 class="table-title">Registros excluidos</h2>
            <p class="table-subtitle">
                Total de registros na lixeira: <?= count($dados) ?>
            </p>
        </div>

        <div class="table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="text"
                placeholder="Buscar na lixeira..."
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
                        <span class="status-badge warning">
                            <?= htmlspecialchars($d['tipo']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars((string)$d['quantidade']) ?></td>
                    <td><?= htmlspecialchars((string)$d['deleted_at']) ?></td>
                    <td>
                        <div class="actions">
                            <?php if (($_SESSION['user']['perfil'] ?? '') === 'admin'): ?>
                            <form
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Deseja restaurar este item?')">

                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="acao" value="restaurar">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <button type="submit" class="icon-btn success" title="Restaurar">
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

                                <button type="submit" class="icon-btn danger" title="Excluir definitivamente">
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

    <div class="table-pagination">
        <span data-page-info>
            Total de <?= count($dados) ?> registro(s)
        </span>
    </div>
</section>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
