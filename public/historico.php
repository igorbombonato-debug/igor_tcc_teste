<?php
// Exige autenticação para que cada aluno veja somente o próprio histórico.
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Histórico | MathPlay';
$user = get_logged_user();
// Busca as partidas antigas primeiro para manter a sequência cronológica.
$stmt = $pdo->prepare('SELECT p.*, m.nome as nome_materia FROM partidas p LEFT JOIN materias m ON m.id = p.materia_id WHERE p.usuario_id = :id ORDER BY p.created_at ASC, p.id ASC LIMIT 50');
$stmt->execute(['id' => $user['id']]);
$partidas = $stmt->fetchAll();
// Organiza as respostas detalhadas por ID de partida.
$respostasPorPartida = [];
    // Usa placeholders para consultar vários IDs sem concatenar valores diretamente.
if ($partidas) {
    $ids = array_column($partidas, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $detalhes = $pdo->prepare("SELECT r.*, q.enunciado, q.resposta_correta, q.alternativa_a, q.alternativa_b, q.alternativa_c, q.alternativa_d
        FROM respostas r INNER JOIN questoes q ON q.id = r.questao_id
        WHERE r.partida_id IN ($placeholders) ORDER BY r.id ASC");
    $detalhes->execute($ids);
    foreach ($detalhes->fetchAll() as $resposta) {
        $respostasPorPartida[$resposta['partida_id']][] = $resposta;
    }
}

function texto_alternativa(array $questao, ?string $letra): string
{
    // Relaciona a letra escolhida ao texto da alternativa correspondente.
    $mapa = ['A' => 'alternativa_a', 'B' => 'alternativa_b', 'C' => 'alternativa_c', 'D' => 'alternativa_d'];
    return $letra && isset($mapa[$letra]) ? (string) $questao[$mapa[$letra]] : 'Não respondeu';
}

?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Registro</span>
        <h1 class="mt-2 mb-0">Histórico de partidas</h1>
    </div>

    <div class="panel-box">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Jogo</th>
                    <th>Série</th>
                    <th>Matéria</th>
                    <th>Data</th>
                    <th>Dificuldade</th>
                    <th>Acertos</th>
                    <th>Erros</th>
                    <th>Pontuação</th>
                    <th>XP</th>
                    <th>Tempo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partidas as $partida): ?>
                    <!-- Separador visual para não misturar partidas diferentes. -->
                                        <!-- Resumo da partida permanece visível. -->
                                        <!-- Detalhes das questões ficam recolhidos até o aluno clicar. -->
                    <?php $seriePartida = (int) ($partida['ano_escolar'] ?? $user['ano_escolar']); ?>
                    <tr class="history-separator">
                        <td colspan="10">Partida <?php echo (int) $partida['id']; ?> · <?php echo e(ucfirst($partida['jogo'])); ?> · <?php echo $seriePartida; ?>º ano</td>
                    </tr>
                    <tr>
                        <td><?php echo e($partida['jogo']); ?></td>
                        <td><?php echo $seriePartida; ?>º ano</td>
                        <td><?php echo e($partida['nome_materia'] ?? 'Geral'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($partida['created_at'])); ?></td>
                        <td><?php echo e($partida['dificuldade']); ?></td>
                        <td><?php echo (int) $partida['acertos']; ?></td>
                        <td><?php echo (int) $partida['erros']; ?></td>
                        <td><?php echo (int) $partida['pontuacao']; ?></td>
                        <td><?php echo (int) $partida['xp_ganho']; ?></td>
                        <td><?php echo (int) $partida['tempo']; ?>s</td>
                    </tr>
                    <tr>
                        <td colspan="10" class="history-toggle-cell">
                            <button class="btn btn-sm btn-outline-primary history-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#detalhes-<?php echo (int) $partida['id']; ?>" aria-expanded="false">
                                <i class="bi bi-chevron-down"></i> Ver mais
                            </button>
                        </td>
                    </tr>
                    <tr id="detalhes-<?php echo (int) $partida['id']; ?>" class="history-details-row collapse">
                            <td colspan="10">
                                <?php if (!empty($respostasPorPartida[$partida['id']])): ?>
                                <strong>Questões da partida</strong>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Questão</th><th>Resposta escolhida</th><th>Resposta correta</th><th>Resultado</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($respostasPorPartida[$partida['id']] as $resposta): ?>
                                            <tr>
                                                <td><?php echo e($resposta['enunciado']); ?></td>
                                                <td><?php echo e($resposta['resposta_usuario'] ? $resposta['resposta_usuario'] . ' - ' . texto_alternativa($resposta, $resposta['resposta_usuario']) : 'Não respondeu'); ?></td>
                                                <td><?php echo e($resposta['resposta_correta'] . ' - ' . texto_alternativa($resposta, $resposta['resposta_correta'])); ?></td>
                                                <td><span class="badge <?php echo (int) $resposta['correta'] === 1 ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo (int) $resposta['correta'] === 1 ? 'Acertou' : 'Errou'; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Ainda não há respostas detalhadas para esta partida.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
    document.querySelectorAll('.history-toggle').forEach(button => {
        const target = document.querySelector(button.dataset.bsTarget);
        target?.addEventListener('shown.bs.collapse', () => {
            button.innerHTML = '<i class="bi bi-chevron-up"></i> Ver menos';
        });
        target?.addEventListener('hidden.bs.collapse', () => {
            button.innerHTML = '<i class="bi bi-chevron-down"></i> Ver mais';
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
