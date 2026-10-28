<?php
// Página inicial dos jogos: mostra as opções disponíveis para o aluno autenticado.
require_once __DIR__ . '/../includes/auth.php';
require_student();

$pageTitle = 'Jogos | Mathematics Education';
$user = get_logged_user();
// Mantém a matéria selecionada dentro das matérias válidas para a série do aluno.
$materias = obter_materias($user['ano_escolar']);
$materiaIds = array_map('intval', array_column($materias, 'id'));
$materiaSolicitada = (int) ($_GET['materia_id'] ?? 0);
$materiaId = in_array($materiaSolicitada, $materiaIds, true)
    ? $materiaSolicitada
    : (int) ($materiaIds[0] ?? 0);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<!-- Cartões de acesso aos jogos e lista de conteúdos disponíveis nesta série. -->
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
                <form action="./jogos/queimada.php" method="GET">
                    <input type="hidden" name="materia_id" value="<?php echo $materiaId; ?>">
                    <label class="form-label" for="dificuldadeQueimada">Escolha a dificuldade</label>
                    <select class="form-select mb-3" id="dificuldadeQueimada" name="dificuldade">
                        <option value="Fácil">Fácil</option>
                        <option value="Médio" selected>Médio</option>
                        <option value="Difícil">Difícil</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Jogar agora</button>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="game-card">
                <div class="game-icon blue">🧠</div>
                <h3>Memória Matemática</h3>
                <p>Combine operações, frações, porcentagens e respostas para completar os pares.</p>
                <form action="./jogos/memoria.php" method="GET">
                    <input type="hidden" name="materia_id" value="<?php echo $materiaId; ?>">
                    <label class="form-label" for="dificuldadeMemoria">Escolha a dificuldade</label>
                    <select class="form-select mb-3" id="dificuldadeMemoria" name="dificuldade">
                        <option value="Fácil">Fácil</option>
                        <option value="Médio" selected>Médio</option>
                        <option value="Difícil">Difícil</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Jogar agora</button>
                </form>
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
