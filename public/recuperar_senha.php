<?php
require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$pageTitle = 'Recuperar senha | MathPlay';
$mensagem = '';
$erro = '';
$linkRecuperacao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $usuario = buscar_usuario_por_email($email);

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $pdo->prepare('UPDATE recuperacao_senhas SET usado = 1 WHERE usuario_id = :usuario_id AND usado = 0')
                ->execute(['usuario_id' => $usuario['id']]);

            $stmt = $pdo->prepare('INSERT INTO recuperacao_senhas (usuario_id, token_hash, expira_em) VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $stmt->execute([
                'usuario_id' => $usuario['id'],
                'token_hash' => $tokenHash,
            ]);

            $linkRecuperacao = '/igor_tcc_teste/public/redefinir_senha.php?token=' . rawurlencode($token);
            $mensagem = 'Link de recuperação criado. Ele é válido por 1 hora.';
        } else {
            $mensagem = 'Se o e-mail estiver cadastrado, um link de recuperação será disponibilizado.';
        }
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<style>
    body { background: linear-gradient(135deg, #eef4ff 0%, #f6f0ff 100%); }
    .recovery-shell { min-height: 100vh; display: grid; place-items: center; padding: 30px 16px; }
    .recovery-card { width: min(100%, 520px); padding: 42px; border-radius: 26px; background: #fff; box-shadow: 0 24px 60px rgba(58,76,155,.12); }
    .recovery-card .form-control { border-radius: 14px; padding: 13px 15px; background: #f6f8ff; border: 1px solid #e5eaf7; }
    .recovery-link { display: block; margin-top: 14px; padding: 12px; overflow-wrap: anywhere; border-radius: 12px; background: #eef5ff; color: #215bb8; }
    @media (max-width: 576px) { .recovery-card { padding: 28px 22px; } }
</style>
<div class="recovery-shell">
    <section class="recovery-card">
        <span class="section-badge">Acesso seguro</span>
        <h1 class="mt-3 mb-2">Recuperar senha</h1>
        <p class="text-muted">Informe o e-mail cadastrado para criar um link de redefinição.</p>

        <?php if ($erro): ?><div class="alert alert-danger"><?php echo e($erro); ?></div><?php endif; ?>
        <?php if ($mensagem): ?><div class="alert alert-success"><?php echo e($mensagem); ?></div><?php endif; ?>
        <?php if ($linkRecuperacao): ?>
            <a class="recovery-link" href="<?php echo e($linkRecuperacao); ?>">Abrir redefinição de senha</a>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <label class="form-label" for="email">E-mail</label>
            <input id="email" type="email" name="email" class="form-control" required autocomplete="email">
            <button class="btn btn-primary w-100 mt-3">Gerar link de recuperação</button>
        </form>
        <a href="/igor_tcc_teste/public/login.php" class="btn btn-link w-100 mt-2">Voltar ao login</a>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
