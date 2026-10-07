<?php
// Apresenta aos professores o desempenho agregado dos alunos ativos.
// Esta verificação impede que outros perfis consultem o painel.
require_once __DIR__ . '/../includes/auth.php';
require_professor();

$pageTitle = 'Acompanhamento | Mathematics Education';
// Aceita todas as séries (0) ou uma das séries disponíveis no filtro.
$anoFiltro = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;
$filtroValido = in_array($anoFiltro, [0, 6, 7, 8, 9], true);
if (!$filtroValido) {
    // Valores inesperados voltam ao filtro geral.
    $anoFiltro = 0;
}

// Junta cada aluno às partidas para somar resultados, inclusive quando não
// há partidas; nesse caso, as funções de agregação exibem zero em vez de NULL.
$sql = 'SELECT u.id, u.nome, u.email, u.ano_escolar,
            COUNT(p.id) AS partidas,
            COALESCE(SUM(p.acertos), 0) AS acertos,
            COALESCE(SUM(p.erros), 0) AS erros,
            COALESCE(ROUND(100 * SUM(p.acertos) / NULLIF(SUM(p.acertos + p.erros), 0)), 0) AS percentual
        FROM usuarios u
        LEFT JOIN partidas p ON p.usuario_id = u.id
        WHERE u.tipo = \'aluno\' AND u.ativo = 1';
$params = [];
if ($anoFiltro > 0) {
    $sql .= ' AND u.ano_escolar = :ano';
    $params['ano'] = $anoFiltro;
}
$sql .= ' GROUP BY u.id, u.nome, u.email, u.ano_escolar ORDER BY percentual ASC, u.nome ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll();

// Soma os dados exibidos na lista para montar os indicadores gerais do painel.
$totalPartidas = 0;
$totalAcertos = 0;
$totalErros = 0;
foreach ($alunos as $aluno) {
    $totalPartidas += (int) $aluno['partidas'];
    $totalAcertos += (int) $aluno['acertos'];
    $totalErros += (int) $aluno['erros'];
}
$percentualGeral = calcular_percentual($totalAcertos, $totalAcertos + $totalErros);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Painel do professor</span>
        <h1 class="mt-2 mb-1">Acompanhamento dos alunos</h1>
        <p class="text-muted mb-0">Consulte partidas, aproveitamento e respostas registradas nos jogos.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card blue"><small>Alunos</small><h3><?php echo count($alunos); ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card purple"><small>Partidas</small><h3><?php echo $totalPartidas; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card green"><small>Acertos</small><h3><?php echo $totalAcertos; ?></h3></div></div>
        <div class="col-md-3"><div class="stat-card orange"><small>Aproveitamento</small><h3><?php echo $percentualGeral; ?>%</h3></div></div>
    </div>

    <div class="panel-box">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h4 class="mb-1">Alunos</h4>
                <small class="text-muted">A lista é ordenada pelo menor aproveitamento para destacar quem pode precisar de apoio.</small>
            </div>
            <form method="GET" class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0" for="ano">Série</label>
                <select class="form-select" id="ano" name="ano" onchange="this.form.submit()">
                    <option value="0" <?php echo $anoFiltro === 0 ? 'selected' : ''; ?>>Todas</option>
                    <?php foreach ([6, 7, 8, 9] as $ano): ?>
                        <option value="<?php echo $ano; ?>" <?php echo $anoFiltro === $ano ? 'selected' : ''; ?>><?php echo $ano; ?>º ano</option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!$alunos): ?>
            <p class="text-muted mb-0">Nenhum aluno ativo encontrado para este filtro.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Aluno</th><th>Série</th><th>Partidas</th><th>Acertos</th><th>Erros</th><th>Aproveitamento</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td><strong><?php echo e($aluno['nome']); ?></strong><br><small class="text-muted"><?php echo e($aluno['email']); ?></small></td>
                                <td><?php echo (int) $aluno['ano_escolar']; ?>º</td>
                                <td><?php echo (int) $aluno['partidas']; ?></td>
                                <td><?php echo (int) $aluno['acertos']; ?></td>
                                <td><?php echo (int) $aluno['erros']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1"><div class="progress-bar" style="width: <?php echo (int) $aluno['percentual']; ?>%"></div></div>
                                        <span><?php echo (int) $aluno['percentual']; ?>%</span>
                                    </div>
                                </td>
                                <td><a class="btn btn-sm btn-outline-primary" href="/igor_tcc_teste/professor/aluno.php?id=<?php echo (int) $aluno['id']; ?>">Ver acompanhamento</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
