<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Questões | MathPlay';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    if ($acao === 'salvar') {
        $id = (int) ($_POST['id'] ?? 0);
        $materiaId = (int) ($_POST['materia_id'] ?? 0);
        $ano = (int) ($_POST['ano_escolar'] ?? 6);
        $dificuldade = $_POST['dificuldade'] ?? 'Fácil';
        $enunciado = trim($_POST['enunciado'] ?? '');
        $a = trim($_POST['alternativa_a'] ?? '');
        $b = trim($_POST['alternativa_b'] ?? '');
        $c = trim($_POST['alternativa_c'] ?? '');
        $d = trim($_POST['alternativa_d'] ?? '');
        $resposta = strtoupper(trim($_POST['resposta_correta'] ?? 'A'));
        $exp = trim($_POST['explicacao'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE questoes SET materia_id = :materia_id, ano_escolar = :ano_escolar, dificuldade = :dificuldade, enunciado = :enunciado, alternativa_a = :a, alternativa_b = :b, alternativa_c = :c, alternativa_d = :d, resposta_correta = :resposta, explicacao = :exp WHERE id = :id');
            $stmt->execute([
                'materia_id' => $materiaId,
                'ano_escolar' => $ano,
                'dificuldade' => $dificuldade,
                'enunciado' => $enunciado,
                'a' => $a,
                'b' => $b,
                'c' => $c,
                'd' => $d,
                'resposta' => $resposta,
                'exp' => $exp,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO questoes (materia_id, ano_escolar, dificuldade, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, ativo, created_at) VALUES (:materia_id, :ano_escolar, :dificuldade, :enunciado, :a, :b, :c, :d, :resposta, :exp, 1, NOW())');
            $stmt->execute([
                'materia_id' => $materiaId,
                'ano_escolar' => $ano,
                'dificuldade' => $dificuldade,
                'enunciado' => $enunciado,
                'a' => $a,
                'b' => $b,
                'c' => $c,
                'd' => $d,
                'resposta' => $resposta,
                'exp' => $exp,
            ]);
        }
        header('Location: /admin/questoes.php');
        exit;
    }

    if ($acao === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE questoes SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $stmt->execute(['id' => $id]);
        header('Location: /admin/questoes.php');
        exit;
    }

    if ($acao === 'excluir') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM questoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        header('Location: /admin/questoes.php');
        exit;
    }
}

$questoes = $pdo->query('SELECT q.*, m.nome as materia_nome FROM questoes q LEFT JOIN materias m ON m.id = q.materia_id ORDER BY q.id DESC LIMIT 100')->fetchAll();
$materias = $pdo->query('SELECT * FROM materias WHERE ativo = 1 ORDER BY nome ASC')->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Gerenciamento</span>
        <h1 class="mt-2 mb-0">Questões</h1>
    </div>

    <div class="panel-box mb-4">
        <h4>Adicionar / editar questão</h4>
        <form method="POST" class="row g-3 mt-2">
            <input type="hidden" name="acao" value="salvar">
            <div class="col-md-3">
                <label class="form-label">Ano</label>
                <select name="ano_escolar" class="form-select">
                    <option value="6">6º ano</option>
                    <option value="7">7º ano</option>
                    <option value="8">8º ano</option>
                    <option value="9">9º ano</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Matéria</label>
                <select name="materia_id" class="form-select">
                    <?php foreach ($materias as $materia): ?>
                        <option value="<?php echo (int) $materia['id']; ?>"><?php echo e($materia['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dificuldade</label>
                <select name="dificuldade" class="form-select">
                    <option>Fácil</option>
                    <option>Médio</option>
                    <option>Difícil</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Resposta correta</label>
                <select name="resposta_correta" class="form-select">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Pergunta</label>
                <textarea name="enunciado" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-md-6"><label class="form-label">Alternativa A</label><input type="text" name="alternativa_a" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Alternativa B</label><input type="text" name="alternativa_b" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Alternativa C</label><input type="text" name="alternativa_c" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Alternativa D</label><input type="text" name="alternativa_d" class="form-control" required></div>
            <div class="col-12">
                <label class="form-label">Explicação</label>
                <textarea name="explicacao" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Salvar questão</button>
            </div>
        </form>
    </div>

    <div class="panel-box">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Matéria</th>
                    <th>Simples</th>
                    <th>Dificuldade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questoes as $questao): ?>
                    <tr>
                        <td><?php echo (int) $questao['id']; ?></td>
                        <td><?php echo e($questao['materia_nome'] ?? 'Geral'); ?></td>
                        <td><?php echo e(substr($questao['enunciado'], 0, 40)); ?></td>
                        <td><?php echo e($questao['dificuldade']); ?></td>
                        <td><?php echo (int) $questao['ativo'] ? 'Ativa' : 'Inativa'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int) $questao['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo (int) $questao['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                            </form>
                            <form method="POST" style="display:inline; margin-left:6px;">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo (int) $questao['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
