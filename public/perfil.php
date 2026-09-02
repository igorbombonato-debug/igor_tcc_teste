<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Perfil | MathPlay';
$user = get_logged_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $avatar = trim($_POST['avatar'] ?? '');

    $stmt = $pdo->prepare('UPDATE usuarios SET nome = :nome, username = :username, avatar = :avatar WHERE id = :id');
    $stmt->execute([
        'nome' => $nome,
        'username' => $username,
        'avatar' => $avatar,
        'id' => $user['id'],
    ]);
    $user = get_logged_user();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
    $novaSenha = $_POST['nova_senha'];
    $confirmacao = $_POST['confirmar_senha'];
    if ($novaSenha !== '' && strlen($novaSenha) >= 6 && $novaSenha === $confirmacao) {
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
        $stmt->execute(['senha' => password_hash($novaSenha, PASSWORD_DEFAULT), 'id' => $user['id']]);
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-box text-center">
                <div class="profile-avatar"><?php echo strtoupper(substr($user['nome'], 0, 1)); ?></div>
                <h3 class="mt-3"><?php echo e($user['nome']); ?></h3>
                <p class="text-muted mb-0">@<?php echo e($user['username']); ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-box">
                <h4>Dados pessoais</h4>
                <form method="POST" class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?php echo e($user['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo e($user['username']); ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Avatar</label>
                        <input type="text" name="avatar" class="form-control" value="<?php echo e($user['avatar'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Salvar perfil</button>
                    </div>
                </form>
            </div>

            <div class="panel-box mt-4">
                <h4>Alterar senha</h4>
                <form method="POST" class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="nova_senha" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar senha</label>
                        <input type="password" name="confirmar_senha" class="form-control">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Salvar senha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
