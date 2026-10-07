<?php
// Lista as contas cadastradas e permite ativar ou desativar cada uma.
// A alteração de status fica disponível somente para administradores.
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Usuários | Mathematics Education';
// Busca os perfis em ordem alfabética para preencher a tabela de usuários.
$stmt = $pdo->query('SELECT * FROM usuarios ORDER BY nome ASC');
$usuarios = $stmt->fetchAll();

// A ação é executada apenas quando o formulário envia um ID por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $id = (int) $_POST['toggle_id'];
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = :id');
    $stmt->execute(['id' => $id]);
    header('Location: /admin/usuarios.php');
    exit;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="mb-4">
        <span class="section-badge">Gerenciamento</span>
        <h1 class="mt-2 mb-0">Usuários</h1>
    </div>

    <div class="panel-box">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Ano</th>
                    <th>Pontos</th>
                    <th>XP</th>
                    <th>Nível</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo e($usuario['nome']); ?></td>
                        <td><?php echo e($usuario['email']); ?></td>
                        <td><?php echo e(ucfirst($usuario['tipo'])); ?></td>
                        <td><?php echo $usuario['tipo'] === 'aluno' ? (int) $usuario['ano_escolar'] . 'º' : '—'; ?></td>
                        <td><?php echo (int) $usuario['pontos']; ?></td>
                        <td><?php echo (int) $usuario['xp']; ?></td>
                        <td><?php echo (int) $usuario['nivel']; ?></td>
                        <td><?php echo (int) $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_id" value="<?php echo (int) $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo (int) $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
