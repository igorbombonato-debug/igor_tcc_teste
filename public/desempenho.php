<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Desempenho | MathPlay';
$user = get_logged_user();
$materias = obter_materias($user['ano_escolar']);
$dados = [];
foreach ($materias as $materia) {
    $dados[] = [
        'nome' => $materia['nome'],
        'percentual' => calcular_percentual_materia($user['id'], $materia['id']),
        'classificacao' => classificar_desempenho(calcular_percentual_materia($user['id'], $materia['id']))
    ];
}
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
