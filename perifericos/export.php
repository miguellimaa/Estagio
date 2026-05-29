<?php
require '../config/conexao.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=perifericos.xls");

echo "Nome\tTipo\tQuantidade\n";

foreach($pdo->query("SELECT * FROM perifericos") as $d){
echo "{$d['nome_ativo']}\t{$d['tipo']}\t{$d['quantidade']}\n";
}