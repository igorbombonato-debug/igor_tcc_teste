<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Ranking | MathPlay';
$user = get_logged_user();
$anoFiltro = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;
$rankGeral = obter_ranking_geral(10);
$rankAno = $anoFiltro ? obter_ranking_por_ano($anoFiltro, 10) : $rankGeral;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="section-badge">Competição</span>
            <h1 class="mt-2 mb-0">🏆 Ranking</h1>
        </div>
        <div class="btn-group" role="group">
            <a href="/igor_tcc_teste/public/ranking.php?ano=0" class="btn btn-sm <?php echo $anoFiltro === 0 ? 'btn-primary' : 'btn-outline-primary'; ?>">Geral</a>
            <a href="/igor_tcc_teste/public/ranking.php?ano=6" class="btn btn-sm <?php echo $anoFiltro === 6 ? 'btn-primary' : 'btn-outline-primary'; ?>">6º ano</a>
            <a href="/igor_tcc_teste/public/ranking.php?ano=7" class="btn btn-sm <?php echo $anoFiltro === 7 ? 'btn-primary' : 'btn-outline-primary'; ?>">7º ano</a>
            <a href="/igor_tcc_teste/public/ranking.php?ano=8" class="btn btn-sm <?php echo $anoFiltro === 8 ? 'btn-primary' : 'btn-outline-primary'; ?>">8º ano</a>
            <a href="/igor_tcc_teste/public/ranking.php?ano=9" class="btn btn-sm <?php echo $anoFiltro === 9 ? 'btn-primary' : 'btn-outline-primary'; ?>">9º ano</a>
        </div>
    </div>

    <div class="panel-box">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Avatar</th>
                    <th>Nome</th>
                    <th>Ano</th>
                    <th>Pontos</th>
                    <th>XP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rankAno as $index => $aluno): ?>
                    <tr>
                        <td><?php echo $index + 1; ?>º</td>
                        <td><span class="avatar-circle"><?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?></span></td>
                        <td><?php echo e($aluno['nome']); ?></td>
                        <td><?php echo (int) $aluno['ano_escolar']; ?>º</td>
                        <td><?php echo (int) $aluno['pontos']; ?></td>
                        <td><?php echo (int) $aluno['xp']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
