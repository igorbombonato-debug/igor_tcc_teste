<?php
// Informa ao navegador que esta API sempre responde em JSON UTF-8.
header('Content-Type: application/json; charset=utf-8');

// Garante que o ID da partida possa ser mantido entre requisições.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega a conexão e as funções usadas para registrar resultados.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Converte o corpo JSON enviado pelo jogo em um array PHP.
$input = file_get_contents('php://input');
$data = $input !== '' ? json_decode($input, true) : [];
if (!is_array($data)) {
    $data = [];
}

// Dados de partida podem vir agrupados no campo partida.
$partida = isset($data['partida']) && is_array($data['partida']) ? $data['partida'] : [];

// Usa o usuário enviado ou, como fallback, o usuário da sessão.
$usuarioId = isset($data['usuario_id']) ? (int) $data['usuario_id'] : (int) ($_SESSION['user_id'] ?? 0);
if ($usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Normaliza os dados da resposta recebida.
$materiaId = (int) ($data['materia_id'] ?? $partida['materia_id'] ?? 1);
$materiaId = $materiaId > 0 ? $materiaId : 1;
$questaoId = isset($data['questao_id']) ? (int) $data['questao_id'] : 0;
$respostaUsuario = isset($data['resposta_usuario']) ? strtoupper((string) $data['resposta_usuario']) : null;
$jogadorNome = isset($data['jogador_nome']) ? trim((string) $data['jogador_nome']) : null;
$timeJogador = isset($data['time_jogador']) && in_array($data['time_jogador'], ['azul', 'vermelho'], true) ? $data['time_jogador'] : null;
$correta = isset($data['correta']) ? (int) $data['correta'] : 0;
$tempoResposta = isset($data['tempo_resposta']) ? (int) $data['tempo_resposta'] : 0;
$finalizar = !empty($data['finalizar']);

// Limpa a partida anterior quando o jogador inicia uma nova partida.
if (!empty($data['nova_partida'])) {
    unset($_SESSION['partida_atual']);
}

if ($finalizar) {
    // Reúne os totais finais enviados pelo jogo.
    $jogo = $data['jogo'] ?? $partida['jogo'] ?? 'queimada';
    $pontuacao = (int) ($data['pontuacao'] ?? $partida['pontuacao'] ?? 0);
    $xpGanho = (int) ($data['xp_ganho'] ?? $partida['xp_ganho'] ?? 0);
    $acertos = (int) ($data['acertos'] ?? $partida['acertos'] ?? 0);
    $erros = (int) ($data['erros'] ?? $partida['erros'] ?? 0);
    $totalQuestoes = (int) ($data['total_questoes'] ?? $partida['total_questoes'] ?? 0);
    $tempo = (int) ($data['tempo'] ?? $partida['tempo'] ?? 0);
    $anoEscolar = (int) ($data['ano_escolar'] ?? $partida['ano_escolar'] ?? 0);
    $equipeNomes = isset($data['equipe_nomes']) ? json_encode($data['equipe_nomes'], JSON_UNESCAPED_UNICODE) : null;

    // Atualiza a partida já criada durante as respostas ou cria uma nova.
    $partidaId = $_SESSION['partida_atual'] ?? 0;
    if ($partidaId > 0) {
        // Salva o resultado final na partida existente.
        $stmt = $pdo->prepare('UPDATE partidas SET jogo = :jogo, materia_id = :materia_id, ano_escolar = :ano_escolar, dificuldade = :dificuldade, pontuacao = :pontuacao, xp_ganho = :xp_ganho, acertos = :acertos, erros = :erros, total_questoes = :total_questoes, tempo = :tempo, equipe_nomes = :equipe_nomes, created_at = created_at WHERE id = :id');
        $stmt->execute([
            'jogo' => $jogo,
            'materia_id' => $materiaId,
            'ano_escolar' => $anoEscolar,
            'dificuldade' => $data['dificuldade'] ?? $partida['dificuldade'] ?? 'Médio',
            'pontuacao' => $pontuacao,
            'xp_ganho' => $xpGanho,
            'acertos' => $acertos,
            'erros' => $erros,
            'total_questoes' => $totalQuestoes,
            'tempo' => $tempo,
            'equipe_nomes' => $equipeNomes,
            'id' => $partidaId,
        ]);
    } else {
        // Cria uma partida para jogos individuais que só enviam o resultado final.
        $partidaId = registrar_partida([
            'usuario_id' => $usuarioId,
            'jogo' => $jogo,
            'materia_id' => $materiaId,
            'ano_escolar' => $anoEscolar,
            'dificuldade' => $data['dificuldade'] ?? 'Médio',
            'pontuacao' => $pontuacao,
            'xp_ganho' => $xpGanho,
            'acertos' => $acertos,
            'erros' => $erros,
            'total_questoes' => $totalQuestoes,
            'tempo' => $tempo,
            'equipe_nomes' => $equipeNomes,
        ]);
    }

    // Atualiza XP, nível, pontos e desempenho do aluno no fechamento.
    atualizar_xp_e_nivel($usuarioId, $xpGanho);
    atualizar_pontos($usuarioId, $pontuacao);
    atualizar_desempenho_usuario($usuarioId, $materiaId, $acertos, $erros);
    // Libera a sessão para que a próxima partida seja independente.
    unset($_SESSION['partida_atual']);

    echo json_encode(['success' => true, 'partida_id' => $partidaId, 'finalizado' => true]);
    exit;
}

// Recupera ou cria a partida que receberá esta resposta.
$partidaId = $_SESSION['partida_atual'] ?? 0;
if ($partidaId <= 0) {
    // A primeira resposta cria o registro base da nova partida.
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
    // Guarda a questão, resposta, jogador e time que participaram da rodada.
    $stmt = $pdo->prepare('INSERT INTO respostas (partida_id, questao_id, resposta_usuario, jogador_nome, time_jogador, correta, tempo_resposta, created_at) VALUES (:partida_id, :questao_id, :resposta_usuario, :jogador_nome, :time_jogador, :correta, :tempo_resposta, NOW())');
    $stmt->execute([
        'partida_id' => $partidaId,
        'questao_id' => $questaoId,
        'resposta_usuario' => $respostaUsuario,
        'jogador_nome' => $jogadorNome,
        'time_jogador' => $timeJogador,
        'correta' => $correta,
        'tempo_resposta' => $tempoResposta,
    ]);

}

// Acumula pontuação e quantidade de acertos/erros na partida.
$stmt = $pdo->prepare('UPDATE partidas SET pontuacao = pontuacao + :pontuacao, xp_ganho = xp_ganho + :xp_ganho, acertos = acertos + :acertos, erros = erros + :erros, total_questoes = total_questoes + 1 WHERE id = :id');
$stmt->execute([
    'pontuacao' => (int) ($data['pontuacao'] ?? $partida['pontuacao'] ?? 0),
    'xp_ganho' => (int) ($data['xp_ganho'] ?? $partida['xp_ganho'] ?? 0),
    'acertos' => $correta ? 1 : 0,
    'erros' => $correta ? 0 : 1,
    'id' => $partidaId,
]);

echo json_encode(['success' => true, 'partida_id' => $partidaId, 'finalizado' => false]);
