<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

$data = $ano > 0 ? obter_ranking_por_ano($ano, $limit) : obter_ranking_geral($limit);

echo json_encode(['success' => true, 'ano' => $ano, 'ranking' => $data]);
