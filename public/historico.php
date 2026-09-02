<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Histórico | MathPlay';
$user = get_logged_user();
$stmt = $pdo->prepare('SELECT p.*, m.nome as nome_materia FROM partidas p LEFT JOIN materias m ON m.id = p.materia_id WHERE p.usuario_id = :id ORDER BY p.created_at DESC LIMIT 50');
$stmt->execute(['id' => $user['id']]);
$partidas = $stmt->fetchAll();
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
                    <tr>
                        <td><?php echo e($partida['jogo']); ?></td>
                        <td><?php echo e($partida['nome_materia'] ?? 'Geral'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($partida['created_at'])); ?></td>
                        <td><?php echo e($partida['dificuldade']); ?></td>
                        <td><?php echo (int) $partida['acertos']; ?></td>
                        <td><?php echo (int) $partida['erros']; ?></td>
                        <td><?php echo (int) $partida['pontuacao']; ?></td>
                        <td><?php echo (int) $partida['xp_ganho']; ?></td>
                        <td><?php echo (int) $partida['tempo']; ?>s</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
