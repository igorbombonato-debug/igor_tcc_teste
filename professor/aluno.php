<?php
// Exibe o histórico e o desempenho de um único aluno para acompanhamento docente.
// A página inteira exige uma sessão autenticada com perfil de professor.
require_once __DIR__ . '/../includes/auth.php';
require_professor();

// Valida o identificador antes de consultar o banco e informa erro HTTP para
// uma URL inválida, em vez de tentar carregar um aluno sem ID.
$alunoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$alunoId || $alunoId < 1) {
    http_response_code(400);
    exit('Identificador de aluno inválido.');
}

$stmt = $pdo->prepare('SELECT id, nome, email, ano_escolar, pontos, xp, nivel FROM usuarios WHERE id = :id AND tipo = \'aluno\' AND ativo = 1 LIMIT 1');
$stmt->execute(['id' => $alunoId]);
$aluno = $stmt->fetch();
// Exibe 404 se o ID não corresponder a um aluno ativo.
if (!$aluno) {
    http_response_code(404);
    exit('Aluno não encontrado.');
}

// Calcula os totais do aluno, preservando zero quando ainda não há partidas.
$stmt = $pdo->prepare('SELECT COUNT(*) AS partidas,
        COALESCE(SUM(acertos), 0) AS acertos,
        COALESCE(SUM(erros), 0) AS erros,
        COALESCE(ROUND(100 * SUM(acertos) / NULLIF(SUM(acertos + erros), 0)), 0) AS percentual
    FROM partidas WHERE usuario_id = :usuario_id');
$stmt->execute(['usuario_id' => $alunoId]);
$resumo = $stmt->fetch();

// Agrupa por matéria e jogo para destacar áreas de maior dificuldade.
$stmt = $pdo->prepare('SELECT COALESCE(m.nome, \'Matéria removida\') AS materia,
        COUNT(p.id) AS partidas, SUM(p.acertos) AS acertos, SUM(p.erros) AS erros,
        COALESCE(ROUND(100 * SUM(p.acertos) / NULLIF(SUM(p.acertos + p.erros), 0)), 0) AS percentual
    FROM partidas p
    LEFT JOIN materias m ON m.id = p.materia_id
    WHERE p.usuario_id = :usuario_id
    GROUP BY p.materia_id, m.nome
    ORDER BY percentual ASC, materia ASC');
$stmt->execute(['usuario_id' => $alunoId]);
$materias = $stmt->fetchAll();

// Resume separadamente os resultados de cada tipo de jogo.
$stmt = $pdo->prepare('SELECT jogo, COUNT(*) AS partidas, SUM(acertos) AS acertos, SUM(erros) AS erros,
        COALESCE(ROUND(100 * SUM(acertos) / NULLIF(SUM(acertos + erros), 0)), 0) AS percentual,
        MAX(created_at) AS ultima_partida
    FROM partidas WHERE usuario_id = :usuario_id
    GROUP BY jogo ORDER BY partidas DESC');
$stmt->execute(['usuario_id' => $alunoId]);
$jogos = $stmt->fetchAll();

// Limita o histórico de partidas para mostrar somente as 30 mais recentes.
$stmt = $pdo->prepare('SELECT p.id, p.jogo, p.dificuldade, p.pontuacao, p.xp_ganho, p.acertos, p.erros,
        p.total_questoes, p.tempo, p.created_at, COALESCE(m.nome, \'Matéria não informada\') AS materia
    FROM partidas p
    LEFT JOIN materias m ON m.id = p.materia_id
    WHERE p.usuario_id = :usuario_id
    ORDER BY p.created_at DESC, p.id DESC LIMIT 30');
$stmt->execute(['usuario_id' => $alunoId]);
$partidas = $stmt->fetchAll();

// A junção com partidas garante que respostas de outros alunos nunca apareçam.
// O LEFT JOIN preserva o histórico mesmo se a questão ou matéria foi removida.
$stmt = $pdo->prepare('SELECT r.resposta_usuario, r.resposta_correta, r.enunciado_snapshot, r.correta,
        r.created_at, q.enunciado, q.alternativa_a, q.alternativa_b, q.alternativa_c, q.alternativa_d,
        q.resposta_correta AS letra_correta, p.jogo, COALESCE(m.nome, \'Matéria não informada\') AS materia
    FROM respostas r
    INNER JOIN partidas p ON p.id = r.partida_id AND p.usuario_id = :usuario_id
    LEFT JOIN questoes q ON q.id = r.questao_id
    LEFT JOIN materias m ON m.id = p.materia_id
    ORDER BY r.created_at DESC, r.id DESC LIMIT 100');
$stmt->execute(['usuario_id' => $alunoId]);
$respostas = $stmt->fetchAll();

$pageTitle = 'Acompanhamento de ' . $aluno['nome'] . ' | Mathematics Education';
$rotulosJogos = ['queimada' => 'Queimada Matemática', 'memoria' => 'Memória Matemática'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <a href="/igor_tcc_teste/professor/index.php" class="text-decoration-none">← Voltar aos alunos</a>
            <h1 class="mt-2 mb-1"><?php echo e($aluno['nome']); ?></h1>
            <p class="text-muted mb-0"><?php echo e($aluno['email']); ?> · <?php echo (int) $aluno['ano_escolar']; ?>º ano</p>
        </div>
        <span class="mini-badge"><?php echo (int) $aluno['pontos']; ?> pontos · <?php echo (int) $aluno['xp']; ?> XP · nível <?php echo (int) $aluno['nivel']; ?></span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card blue"><small>Partidas</small><h3><?php echo (int) $resumo['partidas']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card green"><small>Acertos</small><h3><?php echo (int) $resumo['acertos']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card orange"><small>Erros</small><h3><?php echo (int) $resumo['erros']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card purple"><small>Aproveitamento</small><h3><?php echo (int) $resumo['percentual']; ?>%</h3></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="panel-box h-100">
                <h4>Dificuldades por matéria</h4>
                <?php if (!$materias): ?><p class="text-muted mb-0">O aluno ainda não concluiu partidas.</p><?php endif; ?>
                <?php foreach ($materias as $materia): ?>
                    <div class="progress-row">
                        <div class="d-flex justify-content-between"><strong><?php echo e($materia['materia']); ?></strong><span><?php echo (int) $materia['percentual']; ?>%</span></div>
                        <small class="text-muted"><?php echo (int) $materia['partidas']; ?> partida(s) · <?php echo (int) $materia['acertos']; ?> acerto(s) · <?php echo (int) $materia['erros']; ?> erro(s)</small>
                        <div class="progress mt-2"><div class="progress-bar" style="width: <?php echo (int) $materia['percentual']; ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel-box h-100">
                <h4>Desempenho por jogo</h4>
                <?php if (!$jogos): ?><p class="text-muted mb-0">O aluno ainda não concluiu partidas.</p><?php endif; ?>
                <?php foreach ($jogos as $jogo): ?>
                    <div class="progress-row">
                        <div class="d-flex justify-content-between"><strong><?php echo e($rotulosJogos[$jogo['jogo']] ?? $jogo['jogo']); ?></strong><span><?php echo (int) $jogo['percentual']; ?>%</span></div>
                        <small class="text-muted"><?php echo (int) $jogo['partidas']; ?> partida(s) · <?php echo (int) $jogo['acertos']; ?> acerto(s) · <?php echo (int) $jogo['erros']; ?> erro(s)</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel-box mb-4">
        <h4>Partidas recentes</h4>
        <?php if (!$partidas): ?><p class="text-muted mb-0">Nenhuma partida registrada.</p><?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Data</th><th>Jogo</th><th>Matéria</th><th>Resultado</th><th>Pontos</th><th>XP</th></tr></thead>
                    <tbody>
                        <?php foreach ($partidas as $partida): ?>
                            <tr>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($partida['created_at']))); ?></td>
                                <td><?php echo e($rotulosJogos[$partida['jogo']] ?? $partida['jogo']); ?></td>
                                <td><?php echo e($partida['materia']); ?></td>
                                <td><?php echo (int) $partida['acertos']; ?> acerto(s) · <?php echo (int) $partida['erros']; ?> erro(s)</td>
                                <td><?php echo (int) $partida['pontuacao']; ?></td>
                                <td><?php echo (int) $partida['xp_ganho']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel-box">
        <h4>Respostas e tentativas recentes</h4>
        <p class="text-muted">São exibidas até 100 respostas mais recentes. A Memória registra as tentativas de associação entre cartas.</p>
        <?php if (!$respostas): ?><p class="text-muted mb-0">Ainda não há respostas detalhadas registradas.</p><?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Data</th><th>Jogo</th><th>Matéria</th><th>Enunciado</th><th>Resposta do aluno</th><th>Resposta correta</th><th>Resultado</th></tr></thead>
                    <tbody>
                        <?php foreach ($respostas as $resposta): ?>
                            <?php
                            $enunciado = $resposta['enunciado_snapshot'] ?: $resposta['enunciado'];
                            $respostaDada = $resposta['resposta_usuario'] ?? '';
                            $respostaCorreta = $resposta['resposta_correta'] ?? '';
                            if ($resposta['letra_correta'] !== null) {
                                $alternativas = [
                                    'A' => $resposta['alternativa_a'],
                                    'B' => $resposta['alternativa_b'],
                                    'C' => $resposta['alternativa_c'],
                                    'D' => $resposta['alternativa_d'],
                                ];
                                if (isset($alternativas[strtoupper($respostaDada)])) {
                                    $respostaDada = strtoupper($respostaDada) . '. ' . $alternativas[strtoupper($respostaDada)];
                                }
                                if ($respostaCorreta === '' && isset($alternativas[strtoupper($resposta['letra_correta'])])) {
                                    $respostaCorreta = strtoupper($resposta['letra_correta']) . '. ' . $alternativas[strtoupper($resposta['letra_correta'])];
                                }
                            }
                            ?>
                            <tr>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($resposta['created_at']))); ?></td>
                                <td><?php echo e($rotulosJogos[$resposta['jogo']] ?? $resposta['jogo']); ?></td>
                                <td><?php echo e($resposta['materia']); ?></td>
                                <td><?php echo e($enunciado ?: 'Tentativa do jogo'); ?></td>
                                <td><?php echo e($respostaDada ?: 'Não informada'); ?></td>
                                <td><?php echo e($respostaCorreta ?: 'Não disponível'); ?></td>
                                <td><span class="badge <?php echo (int) $resposta['correta'] === 1 ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo (int) $resposta['correta'] === 1 ? 'Acertou' : 'Errou'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
