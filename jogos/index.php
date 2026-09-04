<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Jogos | MathPlay';
$user = get_logged_user();
$materiaId = (int) ($_GET['materia_id'] ?? 0);
$materias = obter_materias($user['ano_escolar']);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Jogos</span>
        <h1 class="mt-2 mb-0">Escolha seu desafio</h1>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="game-card">
                <div class="game-icon purple">🔥</div>
                <h3>Queimada Matemática</h3>
                <p>Responda corretamente antes do tempo acabar e acumule pontos e XP.</p>
                <a href="/igor_tcc_teste/jogos/queimada.php?materia_id=<?php echo $materiaId; ?>" class="btn btn-primary">Jogar agora</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="game-card">
                <div class="game-icon blue">🧠</div>
                <h3>Memória Matemática</h3>
                <p>Combine operações, frações, porcentagens e respostas para completar os pares.</p>
                <a href="/igor_tcc_teste/jogos/memoria.php?materia_id=<?php echo $materiaId; ?>" class="btn btn-primary">Jogar agora</a>
            </div>
        </div>
    </div>

    <div class="panel-box mt-5">
        <h4>Matérias disponíveis</h4>
        <div class="row g-3 mt-2">
            <?php foreach ($materias as $materia): ?>
                <div class="col-md-4">
                    <div class="mini-materia">
                        <strong><?php echo e($materia['nome']); ?></strong>
                        <small><?php echo e($materia['descricao'] ?: 'Conteúdo do ano atual'); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
