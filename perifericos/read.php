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

<h1><i class="fas fa-keyboard"></i> Perifericos</h1>

<a href="create.php" class="btn btn-primary mb-2">+ Novo</a>

<a href="export.php" class="btn btn-success mb-2">
    <i class="fas fa-file-excel"></i> Exportar Excel
</a>

<input type="text" id="busca" class="form-control mb-3" placeholder="Buscar...">

<div class="card">
<div class="card-header bg-primary text-white">
Lista de Perifericos
</div>

<div class="card-body p-0">
<table class="table table-hover text-center" id="tabela">

<thead class="thead-dark">
<tr>
<th>Nome</th>
<th>Tipo</th>
<th>Quantidade</th>
<th>Acoes</th>
</tr>
</thead>

<tbody>
<?php foreach($dados as $d): ?>

<tr>

<td class="text-left"><?= htmlspecialchars($d['nome_ativo'] ?? '') ?></td>

<td>
<span class="badge badge-info text-dark">
<?= htmlspecialchars($d['tipo'] ?? '') ?>
</span>
</td>

<td>
<?php if($d['quantidade'] <= 5): ?>
<span class="badge badge-danger text-dark">&#9888; <?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
<?php else: ?>
<span class="badge badge-success text-dark"><?= htmlspecialchars($d['quantidade'] ?? '') ?></span>
<?php endif; ?>
</td>

<td>
<a href="update.php?id=<?= htmlspecialchars($d['id'] ?? '') ?>" class="btn btn-warning btn-sm">
<i class="fas fa-edit"></i>
</a>

<?php if($_SESSION['user']['perfil']=='admin'): ?>
<form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Excluir?')">
<input type="hidden" name="id" value="<?= htmlspecialchars($d['id'] ?? '') ?>">
<button type="submit" class="btn btn-danger btn-sm">
<i class="fas fa-trash"></i>
</button>
</form>
<?php endif; ?>
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
