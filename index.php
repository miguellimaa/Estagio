<?php
session_start();
require 'config/conexao.php';

if(!isset($_SESSION['user'])){
    header("Location: auth/login.php");
    exit;
}

$total = $pdo->query("SELECT COUNT(*) FROM perifericos")->fetchColumn();
$quantidade = $pdo->query("SELECT SUM(quantidade) FROM perifericos")->fetchColumn();
$tipos = $pdo->query("SELECT COUNT(DISTINCT tipo) FROM perifericos")->fetchColumn();

$grafico = $pdo->query("SELECT tipo, SUM(quantidade) total FROM perifericos GROUP BY tipo");

$labels = [];
$dados = [];

foreach($grafico as $g){
    $labels[] = $g['tipo'];
    $dados[] = $g['total'];
}

$baixo = $pdo->query("SELECT * FROM perifericos WHERE quantidade <= 5");

ob_start();
?>

<h1>Dashboard</h1>

<div class="alert alert-info">
👋 Bem-vindo, <b><?= $_SESSION['user']['nome'] ?></b>
</div>

<div class="row">

<div class="col-lg-4">
<div class="small-box bg-info">
<div class="inner">
<h3><?= $total ?></h3>
<p>Total de Itens</p>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="small-box bg-success">
<div class="inner">
<h3><?= $quantidade ?? 0 ?></h3>
<p>Estoque</p>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="small-box bg-warning">
<div class="inner">
<h3><?= $tipos ?></h3>
<p>Tipos</p>
</div>
</div>
</div>

</div>

<?php if($baixo->rowCount()): ?>
<div class="alert alert-danger">
⚠ Estoque baixo:
<ul>
<?php foreach($baixo as $b): ?>
<li><?= $b['nome_ativo'] ?> (<?= $b['quantidade'] ?>)</li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<div style="max-width:400px;margin:auto;">
<canvas id="grafico"></canvas>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('grafico'),{
type:'pie',
data:{
labels:<?= json_encode($labels) ?>,
datasets:[{
data:<?= json_encode($dados) ?>,
backgroundColor:['#007bff','#28a745','#ffc107','#dc3545','#6f42c1']
}]
},
options:{
animation:{duration:1200},
plugins:{legend:{position:'bottom'}},
hover:{mode:'nearest',intersect:true}
}
});

// CONTADOR ANIMADO
document.querySelectorAll('.small-box h3').forEach(el=>{
let valor=parseInt(el.innerText);
let atual=0;

let intervalo=setInterval(()=>{
atual+=Math.ceil(valor/20);
if(atual>=valor){
atual=valor;
clearInterval(intervalo);
}
el.innerText=atual;
},30);
});
</script>

<?php
$conteudo = ob_get_clean();
include 'views/layout.php';
?>