<?php
require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$pageTitle = 'Cadastro | MathPlay';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';
    $ano = (int) ($_POST['ano_escolar'] ?? 6);

    if ($nome === '' || $username === '' || $email === '' || $senha === '' || $confirmar === '') {
        $error = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmar) {
        $error = 'As senhas não conferem.';
    } elseif (buscar_usuario_por_email($email)) {
        $error = 'Este e-mail já está cadastrado.';
    } elseif (buscar_usuario_por_username($username)) {
        $error = 'Este username já existe.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, username, email, senha, tipo, ano_escolar, xp, nivel, pontos, ativo, created_at) VALUES (:nome, :username, :email, :senha, :tipo, :ano, 0, 1, 0, 1, NOW())');
        $stmt->execute([
            'nome' => $nome,
            'username' => $username,
            'email' => $email,
            'senha' => $hash,
            'tipo' => 'aluno',
            'ano' => $ano,
        ]);

        $success = 'Cadastro realizado com sucesso!';
        header('Location: /igor_tcc_teste/public/login.php?cadastro=1');
        exit;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<style>
    body { background: linear-gradient(135deg, #eef3ff 0%, #f7f0ff 100%); }
    .register-shell { padding: 80px 16px 50px; }
    .register-box { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 28px; box-shadow: 0 25px 60px rgba(44, 66, 180, 0.12); overflow: hidden; }
    .register-header { background: linear-gradient(135deg, #1f5eff 0%, #7c5cff 100%); color: white; padding: 36px 40px; }
    .register-body { padding: 32px 40px 40px; }
    .form-control, .form-select { border-radius: 15px; padding: 12px 14px; background: #f6f8ff; border: 1px solid #e7ebf6; }
    .btn-primary { border-radius: 15px; padding: 12px 18px; font-weight: 700; }
</style>
<div class="register-shell">
    <div class="register-box">
        <div class="register-header">
            <h2 class="mb-0">Criar minha conta</h2>
        </div>
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ano escolar</label>
                    <select name="ano_escolar" class="form-select" required>
                        <option value="6">6º ano</option>
                        <option value="7">7º ano</option>
                        <option value="8">8º ano</option>
                        <option value="9">9º ano</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar senha</label>
                    <input type="password" name="confirmar_senha" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary w-100">Criar conta</button>
                </div>
                <div class="col-12 text-center">
                    <a href="/igor_tcc_teste/public/login.php" class="text-decoration-none">Já tenho conta</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
