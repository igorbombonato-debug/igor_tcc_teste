<?php
// Busca uma questão aleatória conforme a série e os filtros opcionais enviados.
// O resultado, incluindo as alternativas, é retornado como JSON.
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$usuarioStmt = $pdo->prepare('SELECT id, ano_escolar FROM usuarios WHERE id = :id AND tipo = \'aluno\' AND ativo = 1 LIMIT 1');
$usuarioStmt->execute(['id' => $usuarioId]);
$usuario = $usuarioStmt->fetch();
if (!$usuario) {
    http_response_code($usuarioId > 0 ? 403 : 401);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ano = (int) $usuario['ano_escolar'];
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$dificuldade = $_GET['dificuldade'] ?? null;
if ($dificuldade !== null && !in_array($dificuldade, ['Fácil', 'Médio', 'Difícil'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Dificuldade inválida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// O ano vem da conta autenticada; matéria e questão devem pertencer à mesma série.
$sql = 'SELECT q.* FROM questoes q
        INNER JOIN materias m ON m.id = q.materia_id AND m.ano_escolar = q.ano_escolar
        WHERE q.ativo = 1 AND m.ativo = 1 AND q.ano_escolar = :ano';
$params = ['ano' => $ano];

if ($materiaId > 0) {
    $materiaStmt = $pdo->prepare('SELECT id FROM materias WHERE id = :id AND ano_escolar = :ano AND ativo = 1 LIMIT 1');
    $materiaStmt->execute(['id' => $materiaId, 'ano' => $ano]);
    if (!$materiaStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Esta matéria não pertence à sua série.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql .= ' AND q.materia_id = :materia_id';
    $params['materia_id'] = $materiaId;
}

if ($dificuldade) {
    $sql .= ' AND q.dificuldade = :dificuldade';
    $params['dificuldade'] = $dificuldade;
}

$sql .= ' ORDER BY RAND() LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questao = $stmt->fetch();

// Encerra com erro de negócio quando não há questão compatível com os filtros.
if (!$questao) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma questão encontrada para este filtro.']);
    exit;
}

// Agrupa as alternativas em uma única chave e remove os campos originais.
$questao['alternativas'] = [
    'A' => $questao['alternativa_a'],
    'B' => $questao['alternativa_b'],
    'C' => $questao['alternativa_c'],
    'D' => $questao['alternativa_d'],
];

unset($questao['alternativa_a'], $questao['alternativa_b'], $questao['alternativa_c'], $questao['alternativa_d']);

echo json_encode(['success' => true, 'questao' => $questao]);
