<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function chat_responder(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function chat_tipo_permitido(string $tipoAtual, string $tipoContato): bool
{
    return ($tipoAtual === 'aluno' && in_array($tipoContato, ['aluno', 'professor'], true))
        || ($tipoAtual === 'professor' && $tipoContato === 'aluno');
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
if ($usuarioId <= 0) {
    chat_responder(['success' => false, 'message' => 'Faça login para usar o chat.'], 401);
}

$usuarioStmt = $pdo->prepare('SELECT id, nome, tipo FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1');
$usuarioStmt->execute(['id' => $usuarioId]);
$usuarioAtual = $usuarioStmt->fetch();
if (!$usuarioAtual || !in_array($usuarioAtual['tipo'], ['aluno', 'professor'], true)) {
    chat_responder(['success' => false, 'message' => 'Este perfil não pode usar o chat.'], 403);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$dadosPost = [];
if ($metodo === 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $dadosPost = is_array($json) ? $json : $_POST;
    $tokenEnviado = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $tokenSessao = (string) ($_SESSION['chat_csrf_token'] ?? '');
    if ($tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
        chat_responder(['success' => false, 'message' => 'A sessão expirou. Atualize a página e tente novamente.'], 403);
    }
}

$acao = (string) ($metodo === 'POST' ? ($dadosPost['action'] ?? '') : ($_GET['action'] ?? ''));

if ($metodo === 'GET' && $acao === 'contatos') {
    $busca = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($busca) < 2) {
        chat_responder(['success' => true, 'contatos' => []]);
    }

    $tipoFiltro = $usuarioAtual['tipo'] === 'professor' ? "u.tipo = 'aluno'" : "u.tipo IN ('aluno', 'professor')";
    $stmt = $pdo->prepare("SELECT u.id, u.nome, u.username, u.tipo, u.ano_escolar
        FROM usuarios u
        WHERE u.ativo = 1 AND u.id <> :usuario_id AND {$tipoFiltro}
          AND (u.nome LIKE :busca_nome OR u.username LIKE :busca_username)
        ORDER BY u.nome ASC LIMIT 20");
    $stmt->execute([
        'usuario_id' => $usuarioId,
        'busca_nome' => '%' . $busca . '%',
        'busca_username' => '%' . $busca . '%',
    ]);
    chat_responder(['success' => true, 'contatos' => $stmt->fetchAll()]);
}

if ($metodo === 'GET' && $acao === 'conversas') {
    $stmt = $pdo->prepare("SELECT c.id AS conversa_id,
            contato.id AS contato_id, contato.nome, contato.username, contato.tipo,
            (SELECT m.conteudo FROM mensagens m WHERE m.conversa_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultima_mensagem,
            (SELECT m.enviada_em FROM mensagens m WHERE m.conversa_id = c.id ORDER BY m.id DESC LIMIT 1) AS atualizada_em,
            (SELECT COUNT(*) FROM mensagens m WHERE m.conversa_id = c.id AND m.remetente_id <> :usuario_nao_lido AND m.lida_em IS NULL) AS nao_lidas
        FROM conversas c
        INNER JOIN usuarios contato ON contato.id = CASE WHEN c.usuario_a_id = :usuario_contato THEN c.usuario_b_id ELSE c.usuario_a_id END
        WHERE (c.usuario_a_id = :usuario_a OR c.usuario_b_id = :usuario_b)
          AND contato.ativo = 1
        ORDER BY c.atualizada_em DESC");
    $stmt->execute([
        'usuario_nao_lido' => $usuarioId,
        'usuario_contato' => $usuarioId,
        'usuario_a' => $usuarioId,
        'usuario_b' => $usuarioId,
    ]);
    chat_responder(['success' => true, 'conversas' => $stmt->fetchAll()]);
}

if ($metodo === 'GET' && $acao === 'mensagens') {
    $conversaId = (int) ($_GET['conversa_id'] ?? 0);
    $conversaStmt = $pdo->prepare('SELECT id FROM conversas WHERE id = :id AND (usuario_a_id = :usuario_a OR usuario_b_id = :usuario_b) LIMIT 1');
    $conversaStmt->execute(['id' => $conversaId, 'usuario_a' => $usuarioId, 'usuario_b' => $usuarioId]);
    if (!$conversaStmt->fetch()) {
        chat_responder(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
    }

    $marcarLidas = $pdo->prepare('UPDATE mensagens SET lida_em = CURRENT_TIMESTAMP WHERE conversa_id = :conversa_id AND remetente_id <> :usuario_id AND lida_em IS NULL');
    $marcarLidas->execute(['conversa_id' => $conversaId, 'usuario_id' => $usuarioId]);

    $stmt = $pdo->prepare('SELECT recentes.id, recentes.remetente_id, u.nome AS remetente_nome, recentes.conteudo, recentes.enviada_em
        FROM (
            SELECT id, remetente_id, conteudo, enviada_em
            FROM mensagens
            WHERE conversa_id = :conversa_id
            ORDER BY id DESC
            LIMIT 100
        ) recentes
        INNER JOIN usuarios u ON u.id = recentes.remetente_id
        ORDER BY recentes.id ASC');
    $stmt->execute(['conversa_id' => $conversaId]);
    chat_responder(['success' => true, 'mensagens' => $stmt->fetchAll()]);
}

if ($metodo === 'GET' && $acao === 'notificacoes') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mensagens m INNER JOIN conversas c ON c.id = m.conversa_id
        WHERE (c.usuario_a_id = :usuario_a OR c.usuario_b_id = :usuario_b)
          AND m.remetente_id <> :remetente AND m.lida_em IS NULL');
    $stmt->execute(['usuario_a' => $usuarioId, 'usuario_b' => $usuarioId, 'remetente' => $usuarioId]);
    $totalNaoLidas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT m.id AS mensagem_id, c.id AS conversa_id, m.conteudo, m.enviada_em,
            remetente.nome AS remetente_nome, remetente.tipo AS remetente_tipo
        FROM mensagens m
        INNER JOIN conversas c ON c.id = m.conversa_id
        INNER JOIN usuarios remetente ON remetente.id = m.remetente_id
        WHERE (c.usuario_a_id = :usuario_a OR c.usuario_b_id = :usuario_b)
          AND m.remetente_id <> :remetente AND m.lida_em IS NULL
        ORDER BY m.id DESC LIMIT 10');
    $stmt->execute(['usuario_a' => $usuarioId, 'usuario_b' => $usuarioId, 'remetente' => $usuarioId]);
    chat_responder(['success' => true, 'total' => $totalNaoLidas, 'notificacoes' => $stmt->fetchAll()]);
}

if ($metodo === 'POST' && $acao === 'abrir') {
    $contatoId = (int) ($dadosPost['contato_id'] ?? 0);
    $contatoStmt = $pdo->prepare('SELECT id, tipo FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1');
    $contatoStmt->execute(['id' => $contatoId]);
    $contato = $contatoStmt->fetch();

    if (!$contato || $contatoId === $usuarioId || !chat_tipo_permitido($usuarioAtual['tipo'], $contato['tipo'])) {
        chat_responder(['success' => false, 'message' => 'Este contato não está disponível para conversa.'], 403);
    }

    $usuarioA = min($usuarioId, $contatoId);
    $usuarioB = max($usuarioId, $contatoId);
    $stmt = $pdo->prepare('INSERT INTO conversas (usuario_a_id, usuario_b_id) VALUES (:usuario_a, :usuario_b)
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $stmt->execute(['usuario_a' => $usuarioA, 'usuario_b' => $usuarioB]);
    $conversaId = (int) $pdo->lastInsertId();
    chat_responder(['success' => true, 'conversa_id' => $conversaId]);
}

if ($metodo === 'POST' && $acao === 'enviar') {
    $conversaId = (int) ($dadosPost['conversa_id'] ?? 0);
    $conteudo = trim((string) ($dadosPost['conteudo'] ?? ''));
    $conversaStmt = $pdo->prepare('SELECT id, usuario_a_id, usuario_b_id FROM conversas WHERE id = :id AND (usuario_a_id = :usuario_a OR usuario_b_id = :usuario_b) LIMIT 1');
    $conversaStmt->execute(['id' => $conversaId, 'usuario_a' => $usuarioId, 'usuario_b' => $usuarioId]);
    $conversa = $conversaStmt->fetch();

    if (!$conversa) {
        chat_responder(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
    }
    if ($conteudo === '' || mb_strlen($conteudo) > 2000) {
        chat_responder(['success' => false, 'message' => 'A mensagem deve ter entre 1 e 2.000 caracteres.'], 422);
    }

    $destinatarioId = (int) $conversa['usuario_a_id'] === $usuarioId
        ? (int) $conversa['usuario_b_id']
        : (int) $conversa['usuario_a_id'];
    $destinatarioStmt = $pdo->prepare('SELECT tipo, ativo FROM usuarios WHERE id = :id LIMIT 1');
    $destinatarioStmt->execute(['id' => $destinatarioId]);
    $destinatario = $destinatarioStmt->fetch();
    if (!$destinatario || !(int) $destinatario['ativo'] || !chat_tipo_permitido($usuarioAtual['tipo'], $destinatario['tipo'])) {
        chat_responder(['success' => false, 'message' => 'Este contato não está disponível para conversa.'], 403);
    }

    $stmt = $pdo->prepare('INSERT INTO mensagens (conversa_id, remetente_id, conteudo) VALUES (:conversa_id, :remetente_id, :conteudo)');
    $stmt->execute(['conversa_id' => $conversaId, 'remetente_id' => $usuarioId, 'conteudo' => $conteudo]);
    $mensagemId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE conversas SET atualizada_em = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $conversaId]);

    chat_responder(['success' => true, 'mensagem_id' => $mensagemId]);
}

chat_responder(['success' => false, 'message' => 'Ação de chat inválida.'], 400);