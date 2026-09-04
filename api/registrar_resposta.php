<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = file_get_contents('php://input');
$data = $input !== '' ? json_decode($input, true) : [];
if (!is_array($data)) {
    $data = [];
}

$partida = isset($data['partida']) && is_array($data['partida']) ? $data['partida'] : [];

$usuarioId = isset($data['usuario_id']) ? (int) $data['usuario_id'] : (int) ($_SESSION['user_id'] ?? 0);
if ($usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$materiaId = (int) ($data['materia_id'] ?? $partida['materia_id'] ?? 1);
$materiaId = $materiaId > 0 ? $materiaId : 1;
$questaoId = isset($data['questao_id']) ? (int) $data['questao_id'] : 0;
$respostaUsuario = isset($data['resposta_usuario']) ? strtoupper((string) $data['resposta_usuario']) : null;
$correta = isset($data['correta']) ? (int) $data['correta'] : 0;
$tempoResposta = isset($data['tempo_resposta']) ? (int) $data['tempo_resposta'] : 0;
$finalizar = !empty($data['finalizar']);

if ($finalizar) {
    $jogo = $data['jogo'] ?? $partida['jogo'] ?? 'queimada';
    $pontuacao = (int) ($data['pontuacao'] ?? $partida['pontuacao'] ?? 0);
    $xpGanho = (int) ($data['xp_ganho'] ?? $partida['xp_ganho'] ?? 0);
    $acertos = (int) ($data['acertos'] ?? $partida['acertos'] ?? 0);
    $erros = (int) ($data['erros'] ?? $partida['erros'] ?? 0);
    $totalQuestoes = (int) ($data['total_questoes'] ?? $partida['total_questoes'] ?? 0);
    $tempo = (int) ($data['tempo'] ?? $partida['tempo'] ?? 0);

    $partidaId = $_SESSION['partida_atual'] ?? 0;
    if ($partidaId > 0) {
        $stmt = $pdo->prepare('UPDATE partidas SET jogo = :jogo, materia_id = :materia_id, dificuldade = :dificuldade, pontuacao = :pontuacao, xp_ganho = :xp_ganho, acertos = :acertos, erros = :erros, total_questoes = :total_questoes, tempo = :tempo, created_at = created_at WHERE id = :id');
        $stmt->execute([
            'jogo' => $jogo,
            'materia_id' => $materiaId,
            'dificuldade' => $data['dificuldade'] ?? $partida['dificuldade'] ?? 'Médio',
            'pontuacao' => $pontuacao,
            'xp_ganho' => $xpGanho,
            'acertos' => $acertos,
            'erros' => $erros,
            'total_questoes' => $totalQuestoes,
            'tempo' => $tempo,
            'id' => $partidaId,
        ]);
    } else {
        $partidaId = registrar_partida([
            'usuario_id' => $usuarioId,
            'jogo' => $jogo,
            'materia_id' => $materiaId,
            'dificuldade' => $data['dificuldade'] ?? 'Médio',
            'pontuacao' => $pontuacao,
            'xp_ganho' => $xpGanho,
            'acertos' => $acertos,
            'erros' => $erros,
            'total_questoes' => $totalQuestoes,
            'tempo' => $tempo,
        ]);
    }

    atualizar_xp_e_nivel($usuarioId, $xpGanho);
    atualizar_pontos($usuarioId, $pontuacao);
    atualizar_desempenho_usuario($usuarioId, $materiaId, $acertos, $erros);
    unset($_SESSION['partida_atual']);

    echo json_encode(['success' => true, 'partida_id' => $partidaId, 'finalizado' => true]);
    exit;
}

$partidaId = $_SESSION['partida_atual'] ?? 0;
if ($partidaId <= 0) {
    $partidaId = registrar_partida([
        'usuario_id' => $usuarioId,
        'jogo' => $data['jogo'] ?? $partida['jogo'] ?? 'queimada',
        'materia_id' => $materiaId,
        'dificuldade' => $data['dificuldade'] ?? $partida['dificuldade'] ?? 'Médio',
        'pontuacao' => 0,
        'xp_ganho' => 0,
        'acertos' => 0,
        'erros' => 0,
        'total_questoes' => 0,
        'tempo' => 0,
    ]);
    $_SESSION['partida_atual'] = $partidaId;
}

if ($questaoId > 0) {
    $stmt = $pdo->prepare('INSERT INTO respostas (partida_id, questao_id, resposta_usuario, correta, tempo_resposta, created_at) VALUES (:partida_id, :questao_id, :resposta_usuario, :correta, :tempo_resposta, NOW())');
    $stmt->execute([
        'partida_id' => $partidaId,
        'questao_id' => $questaoId,
        'resposta_usuario' => $respostaUsuario,
        'correta' => $correta,
        'tempo_resposta' => $tempoResposta,
    ]);

}

$stmt = $pdo->prepare('UPDATE partidas SET pontuacao = pontuacao + :pontuacao, xp_ganho = xp_ganho + :xp_ganho, acertos = acertos + :acertos, erros = erros + :erros, total_questoes = total_questoes + 1 WHERE id = :id');
$stmt->execute([
    'pontuacao' => (int) ($data['pontuacao'] ?? $partida['pontuacao'] ?? 0),
    'xp_ganho' => (int) ($data['xp_ganho'] ?? $partida['xp_ganho'] ?? 0),
    'acertos' => $correta ? 1 : 0,
    'erros' => $correta ? 0 : 1,
    'id' => $partidaId,
]);

echo json_encode(['success' => true, 'partida_id' => $partidaId, 'finalizado' => false]);
