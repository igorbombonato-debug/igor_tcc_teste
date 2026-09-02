<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = file_get_contents('php://input');
$data = $input !== '' ? json_decode($input, true) : [];
if (!is_array($data)) {
    $data = [];
}

$usuarioId = isset($data['usuario_id']) ? (int) $data['usuario_id'] : 0;
$materiaId = isset($data['materia_id']) ? (int) $data['materia_id'] : 0;
$acertos = isset($data['acertos']) ? (int) $data['acertos'] : 0;
$erros = isset($data['erros']) ? (int) $data['erros'] : 0;

if ($usuarioId <= 0 || $materiaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos para atualizar desempenho.']);
    exit;
}

atualizar_desempenho_usuario($usuarioId, $materiaId, $acertos, $erros);

echo json_encode(['success' => true, 'usuario_id' => $usuarioId, 'materia_id' => $materiaId]);
