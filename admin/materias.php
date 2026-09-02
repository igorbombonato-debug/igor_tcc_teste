<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Matérias Admin | MathPlay';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    if ($acao === 'salvar') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ano = (int) ($_POST['ano_escolar'] ?? 6);
        $icone = trim($_POST['icone'] ?? 'bi-book');
        if ($nome !== '') {
            $stmt = $pdo->prepare('INSERT INTO materias (nome, descricao, ano_escolar, icone, ativo, created_at) VALUES (:nome, :descricao, :ano, :icone, 1, NOW())');
            $stmt->execute(['nome' => $nome, 'descricao' => $descricao, 'ano' => $ano, 'icone' => $icone]);
        }
    }
    if ($acao === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE materias SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    if ($acao === 'excluir') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM materias WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    header('Location: /admin/materias.php');
    exit;
}

$materias = $pdo->query('SELECT * FROM materias ORDER BY ano_escolar, nome')->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Gerenciamento</span>
        <h1 class="mt-2 mb-0">Matérias</h1>
    </div>

    <div class="panel-box mb-4">
        <h4>Adicionar matéria</h4>
        <form method="POST" class="row g-3 mt-2">
            <input type="hidden" name="acao" value="salvar">
            <div class="col-md-4"><label class="form-label">Nome</label><input type="text" name="nome" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Ano</label><select name="ano_escolar" class="form-select"><option value="6">6º ano</option><option value="7">7º ano</option><option value="8">8º ano</option><option value="9">9º ano</option></select></div>
            <div class="col-md-3"><label class="form-label">Ícone</label><input type="text" name="icone" class="form-control" value="bi-book"></div>
            <div class="col-md-12"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-primary">Salvar matéria</button></div>
        </form>
    </div>

    <div class="panel-box">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th>Nome</th><th>Ano</th><th>Status</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach ($materias as $materia): ?>
                    <tr>
                        <td><?php echo e($materia['nome']); ?></td>
                        <td><?php echo (int) $materia['ano_escolar']; ?>º</td>
                        <td><?php echo (int) $materia['ativo'] ? 'Ativa' : 'Inativa'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int) $materia['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo (int) $materia['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                            </form>
                            <form method="POST" style="display:inline; margin-left:6px;">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo (int) $materia['id']; ?>">
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
