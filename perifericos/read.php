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

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h1 class="page-title"><i class="fas fa-keyboard"></i> Periféricos</h1>
        <p class="page-subtitle">Gerencie os itens, filtre rapidamente e mantenha o estoque controlado.</p>
        <span class="badge badge-pill badge-primary">Total: <?= count($dados) ?></span>
    </div>
    <div class="btn-toolbar">
        <a href="create.php" class="btn btn-primary mr-2 mb-2">
            <i class="fas fa-plus"></i> Novo
        </a>
        <a href="export.php" class="btn btn-success mb-2">
            <i class="fas fa-file-excel"></i> Exportar
        </a>
    </div>
</div>

<div class="card shadow-sm mb-3 form-card">
    <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="input-group w-100 w-md-50">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" id="busca" class="form-control" placeholder="Buscar por nome, tipo ou quantidade...">
        </div>
        <small class="text-muted">Atualizado em tempo real enquanto digita.</small>
    </div>
</div>

<div class="card shadow-sm">
</div>

<div class="card-header d-flex justify-content-between align-items-center">
    <div>
        <span class="font-weight-bold">Lista de Periféricos</span>
        <div class="small text-muted">Itens cadastrados</div>
    </div>
    <div class="badge badge-light text-dark">Itens: <?= count($dados) ?></div>
</div>

<div class="card-body p-0 table-responsive">
<table class="table table-striped table-hover text-center mb-0" id="tabela">

<thead>
<tr>
<th class="text-left">Nome</th>
<th>Tipo</th>
<th>Quantidade</th>
<th>Ações</th>
</tr>
</thead>

<tbody>
<?php foreach($dados as $d): ?>

<tr>

<td class="text-left"><?= htmlspecialchars($d['nome_ativo'] ?? '') ?></td>

<td>
<span class="badge badge-cat"><?= htmlspecialchars($d['tipo'] ?? '') ?></span>
</td>

<td>
<?php if($d['quantidade'] <= 5): ?>
<span class="badge badge-low">&#9888; <?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
<?php else: ?>
<span class="badge badge-success-custom"><?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
<?php endif; ?>
</td>

<td>
<div class="btn-group" role="group">
    <a href="update.php?id=<?= htmlspecialchars($d['id'] ?? '') ?>" class="btn btn-warning btn-sm" title="Editar">
        <i class="fas fa-edit"></i>
    </a>

    <?php if($_SESSION['user']['perfil']=='admin'): ?>
    <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este item?')">
        <input type="hidden" name="id" value="<?= htmlspecialchars($d['id'] ?? '') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
            <i class="fas fa-trash"></i>
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
</div>

<script>
document.getElementById("busca").addEventListener("keyup", function() {
    let valor = this.value.toLowerCase();
    let linhas = document.querySelectorAll("#tabela tbody tr");

    linhas.forEach(linha => {
        let texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(valor) ? "" : "none";
    });
});
</script>

<?php
$conteudo = ob_get_clean();
include '../views/layout.php';
?>
