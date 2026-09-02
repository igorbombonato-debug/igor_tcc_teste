<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Admin | MathPlay';
$stats = [
    'usuarios' => $pdo->query('SELECT COUNT(*) as total FROM usuarios WHERE tipo = "aluno"')->fetch()['total'],
    'questoes' => $pdo->query('SELECT COUNT(*) as total FROM questoes')->fetch()['total'],
    'partidas' => $pdo->query('SELECT COUNT(*) as total FROM partidas')->fetch()['total'],
    'mediaAcertos' => $pdo->query('SELECT ROUND(AVG(acertos), 1) as media FROM partidas')->fetch()['media'] ?? 0,
];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Painel administrativo</span>
        <h1 class="mt-2 mb-0">Dashboard administrativa</h1>
    </div>

    <div class="row g-4">
        <div class="col-md-3"><div class="stat-card blue"><small>Total de alunos</small><h3><?php echo $stats['usuarios']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card purple"><small>Total de questões</small><h3><?php echo $stats['questoes']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card green"><small>Total de partidas</small><h3><?php echo $stats['partidas']; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card orange"><small>Média de acertos</small><h3><?php echo $stats['mediaAcertos']; ?></h3></div></div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
