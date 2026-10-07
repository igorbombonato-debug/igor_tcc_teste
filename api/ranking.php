<?php
// Retorna o ranking geral ou o ranking dos alunos de uma série específica.
// Os filtros são recebidos pela URL e o resultado é serializado em JSON.
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

// Série positiva seleciona um ranking por ano; zero mantém o ranking geral.
$data = $ano > 0 ? obter_ranking_por_ano($ano, $limit) : obter_ranking_geral($limit);

// Devolve também o filtro aplicado para o cliente saber a qual ranking pertence a lista.
echo json_encode(['success' => true, 'ano' => $ano, 'ranking' => $data]);
