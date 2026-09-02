<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : 6;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$dificuldade = $_GET['dificuldade'] ?? null;

$sql = 'SELECT * FROM questoes WHERE ativo = 1 AND ano_escolar = :ano';
$params = ['ano' => $ano];

if ($materiaId > 0) {
    $sql .= ' AND materia_id = :materia_id';
    $params['materia_id'] = $materiaId;
}

if ($dificuldade) {
    $sql .= ' AND dificuldade = :dificuldade';
    $params['dificuldade'] = $dificuldade;
}

$sql .= ' ORDER BY RAND() LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questao = $stmt->fetch();

if (!$questao) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma questão encontrada para este filtro.']);
    exit;
}

$questao['alternativas'] = [
    'A' => $questao['alternativa_a'],
    'B' => $questao['alternativa_b'],
    'C' => $questao['alternativa_c'],
    'D' => $questao['alternativa_d'],
];

unset($questao['alternativa_a'], $questao['alternativa_b'], $questao['alternativa_c'], $questao['alternativa_d']);

echo json_encode(['success' => true, 'questao' => $questao]);
