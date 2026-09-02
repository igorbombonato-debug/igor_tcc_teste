<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Matérias | MathPlay';
$user = get_logged_user();
$ano = (int) ($user['ano_escolar'] ?? 6);
$materias = obter_materias($ano);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="section-badge">Conteúdos</span>
            <h1 class="mt-2 mb-0">Matérias do <?php echo $ano; ?>º ano</h1>
        </div>
        <a href="/igor_tcc_teste/jogos/index.php" class="btn btn-primary">🎮 Jogar</a>
    </div>

    <div class="row g-4">
        <?php foreach ($materias as $materia): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card materia-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="materia-icon"><i class="bi <?php echo e($materia['icone'] ?: 'bi-star'); ?>"></i></div>
                            <span class="mini-badge"><?php echo e($materia['ano_escolar']); ?>º</span>
                        </div>
                        <h5><?php echo e($materia['nome']); ?></h5>
                        <p><?php echo e($materia['descricao'] ?: 'Pratique esta matéria para evoluir seu desempenho.'); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Desempenho: <?php echo calcular_percentual_materia($user['id'], $materia['id']); ?>%</span>
                            <a href="/igor_tcc_teste/jogos/index.php?materia=<?php echo (int) $materia['id']; ?>" class="btn btn-sm btn-primary">Estudar</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
