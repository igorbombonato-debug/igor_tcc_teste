<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Desempenho | MathPlay';
$user = get_logged_user();
$stmt = $pdo->prepare('SELECT m.nome, COALESCE(d.partidas, 0) AS partidas, COALESCE(d.acertos, 0) AS acertos, COALESCE(d.erros, 0) AS erros, COALESCE(d.percentual, 0) AS percentual, COALESCE(d.classificacao, \'SEM PARTIDAS\') AS classificacao
    FROM materias m
    LEFT JOIN desempenho d ON d.materia_id = m.id AND d.usuario_id = :usuario_id
    WHERE m.ativo = 1 AND m.ano_escolar = :ano
    ORDER BY m.nome ASC');
$stmt->execute(['usuario_id' => $user['id'], 'ano' => $user['ano_escolar']]);
$dados = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Análise</span>
        <h1 class="mt-2 mb-0">Meu desempenho</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel-box">
                <canvas id="desempenhoChart" height="120"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel-box">
                <h4>Resumo</h4>
                <?php foreach ($dados as $item): ?>
                    <div class="progress-row">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo e($item['nome']); ?></strong>
                            <span><?php echo $item['percentual']; ?>%</span>
                        </div>
                        <small class="text-muted"><?php echo (int) $item['partidas']; ?> partida(s) · <?php echo (int) $item['acertos']; ?> acerto(s) · <?php echo (int) $item['erros']; ?> erro(s)</small>
                        <div class="progress mt-2">
                            <div class="progress-bar" style="width: <?php echo $item['percentual']; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</section>

<script>
    const labels = <?php echo json_encode(array_map(fn($n) => $n['nome'], $dados)); ?>;
    const values = <?php echo json_encode(array_map(fn($n) => $n['percentual'], $dados)); ?>;

    new Chart(document.getElementById('desempenhoChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Desempenho por matéria',
                data: values,
                backgroundColor: ['#4f6ef7', '#7c5cff', '#4cc9a7', '#ffb84d', '#f66d6d', '#69d2ff'],
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
