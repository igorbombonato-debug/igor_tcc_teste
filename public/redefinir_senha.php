<?php
require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$pageTitle = 'Redefinir senha | MathPlay';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$erro = '';
$sucesso = '';
$recuperacao = null;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM recuperacao_senhas WHERE token_hash = :token_hash AND usado = 0 AND expira_em > NOW() LIMIT 1');
    $stmt->execute(['token_hash' => hash('sha256', $token)]);
    $recuperacao = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmar_senha'] ?? '';

    if (!$recuperacao) {
        $erro = 'Este link é inválido, já foi usado ou expirou.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erro = 'As senhas não conferem.';
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id')->execute([
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'id' => $recuperacao['usuario_id'],
        ]);
        $pdo->prepare('UPDATE recuperacao_senhas SET usado = 1 WHERE id = :id')->execute(['id' => $recuperacao['id']]);
        $pdo->commit();
        $sucesso = 'Senha alterada com sucesso. Agora você já pode entrar.';
        $recuperacao = null;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<style>
    body { background: linear-gradient(135deg, #eef4ff 0%, #f6f0ff 100%); }
    .recovery-shell { min-height: 100vh; display: grid; place-items: center; padding: 30px 16px; }
    .recovery-card { width: min(100%, 520px); padding: 42px; border-radius: 26px; background: #fff; box-shadow: 0 24px 60px rgba(58,76,155,.12); }
    .recovery-card .form-control { border-radius: 14px; padding: 13px 15px; background: #f6f8ff; border: 1px solid #e5eaf7; }
    @media (max-width: 576px) { .recovery-card { padding: 28px 22px; } }
</style>
<div class="recovery-shell">
    <section class="recovery-card">
        <span class="section-badge">Acesso seguro</span>
        <h1 class="mt-3 mb-2">Redefinir senha</h1>
        <?php if ($erro): ?><div class="alert alert-danger"><?php echo e($erro); ?></div><?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?php echo e($sucesso); ?></div>
            <a href="/igor_tcc_teste/public/login.php" class="btn btn-primary w-100">Ir para o login</a>
        <?php elseif ($recuperacao): ?>
            <p class="text-muted">Escolha uma nova senha com pelo menos 6 caracteres.</p>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo e($token); ?>">
                <label class="form-label" for="senha">Nova senha</label>
                <input id="senha" type="password" name="senha" class="form-control" required minlength="6" autocomplete="new-password">
                <label class="form-label mt-3" for="confirmar_senha">Confirmar senha</label>
                <input id="confirmar_senha" type="password" name="confirmar_senha" class="form-control" required minlength="6" autocomplete="new-password">
                <button class="btn btn-primary w-100 mt-3">Salvar nova senha</button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">Este link é inválido, já foi usado ou expirou.</div>
            <a href="/igor_tcc_teste/public/recuperar_senha.php" class="btn btn-primary w-100">Solicitar novo link</a>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
