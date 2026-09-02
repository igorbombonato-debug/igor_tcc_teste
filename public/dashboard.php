<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = get_logged_user();
$pageTitle = 'Dashboard | MathPlay';

$statsSql = 'SELECT COUNT(*) as partidas, SUM(acertos) as acertos, SUM(erros) as erros, SUM(pontuacao) as pontos FROM partidas WHERE usuario_id = :id';
$stmt = $pdo->prepare($statsSql);
$stmt->execute(['id' => $user['id']]);
$stats = $stmt->fetch();

$materias = obter_materias($user['ano_escolar']);
$topDificuldades = [];
foreach ($materias as $materia) {
    $percent = calcular_percentual_materia($user['id'], $materia['id']);
    $topDificuldades[] = ['nome' => $materia['nome'], 'percentual' => $percent];
}
usort($topDificuldades, fn($a, $b) => $a['percentual'] <=> $b['percentual']);
$lowPerformance = array_slice($topDificuldades, 0, 4);

$xpAtual = (int) $user['xp'];
$proximo = get_xp_proximo_nivel($xpAtual);
$nivelAtual = get_nivel_por_xp($xpAtual);
$xpMeta = max($nivelAtual * 500, 500);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="dashboard-hero">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="section-badge">Bem-vindo</span>
                <h1 class="mt-3 mb-0">Olá, <?php echo e($user['nome']); ?>!</h1>
            </div>
            <a href="/igor_tcc_teste/jogos/index.php" class="btn btn-primary btn-lg">🎮 Jogar agora</a>
        </div>
    </div>
</section>

<section class="container dashboard-section">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="icon">⚡</div>
                <div>
                    <small>XP</small>
                    <h3><?php echo (int) $user['xp']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="icon">🏅</div>
                <div>
                    <small>NÍVEL</small>
                    <h3><?php echo $nivelAtual; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="icon">⭐</div>
                <div>
                    <small>PONTOS</small>
                    <h3><?php echo (int) $user['pontos']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <div class="icon">🎯</div>
                <div>
                    <small>ACERTOS</small>
                    <h3><?php echo (int) ($stats['acertos'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-7">
            <div class="panel-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Progresso de XP</h4>
                    <span><?php echo $xpAtual; ?> / <?php echo $xpMeta; ?> XP</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo min(100, (($xpAtual / $xpMeta) * 100)); ?>%"></div>
                </div>
                <small class="text-muted mt-2 d-block">Faltam <?php echo $proximo; ?> XP para o próximo nível.</small>
            </div>

            <div class="panel-box mt-4">
                <h4>Continue aprendendo</h4>
                <?php foreach ($lowPerformance as $item): ?>
                    <div class="study-item">
                        <div>
                            <strong><?php echo e($item['nome']); ?></strong>
                            <p class="mb-0"><?php echo (int) $item['percentual']; ?>%</p>
                        </div>
                        <div class="text-end">
                            <div class="mini-badge">Você pode melhorar!</div>
                            <a href="/igor_tcc_teste/public/materias.php?materia=<?php echo rawurlencode($item['nome']); ?>" class="btn btn-outline-primary mt-2">ESTUDAR AGORA</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel-box">
                <h4>Conquistas</h4>
                <ul class="list-unstyled achievement-list">
                    <li>🏆 Primeira partida</li>
                    <li>🔥 5 acertos seguidos</li>
                    <li>⭐ 1000 pontos</li>
                    <li>🧠 Mestre das Frações</li>
                    <li>🎯 90% de aproveitamento</li>
                    <li>🏅 10 partidas concluídas</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
