<?php
// Carrega autenticação, sessão e funções de banco.
require_once __DIR__ . '/../includes/auth.php';

// Usuários já logados são enviados diretamente ao dashboard.
redirect_if_logged_in();

// Define o título da página e inicia a mensagem de erro.
$pageTitle = 'Login | MathPlay';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê e limpa os dados enviados pelo formulário.
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($login === '' || $senha === '') {
        // Impede consulta quando algum campo obrigatório está vazio.
        $error = 'Informe seu e-mail ou usuário e sua senha.';
    } else {
        // Decide se a busca será feita por e-mail ou username.
        $user = str_contains($login, '@')
            ? buscar_usuario_por_email($login)
            : buscar_usuario_por_username($login);
        if ($user && password_verify($senha, $user['senha'])) {
            // A senha é comparada com o hash armazenado no banco.
            if ((int) $user['ativo'] !== 1) {
                // Contas desativadas não podem iniciar sessão.
                $error = 'Sua conta está inativa. Contate o administrador.';
            } else {
                // Salva na sessão os dados necessários para as páginas privadas.
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_tipo'] = $user['tipo'];
                $_SESSION['user_nome'] = $user['nome'];
                // Envia o usuário autenticado para o dashboard.
                header('Location: /igor_tcc_teste/public/dashboard.php');
                exit;
            }
        } else {
            // Não revela se o erro foi no usuário ou na senha.
            $error = 'Credenciais inválidas.';
        }
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<style>
    body { background: linear-gradient(135deg, #eef4ff 0%, #f6f0ff 100%); }
    .login-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 16px; }
    .login-card { max-width: 1100px; width: 100%; border: none; border-radius: 28px; overflow: hidden; background: #fff; box-shadow: 0 30px 80px rgba(82, 90, 186, 0.12); }
    .login-brand { background: linear-gradient(135deg, #1f5eff 0%, #7c5cff 60%, #4cc9a7 100%); color: white; padding: 60px 50px; }
    .login-brand h1 { font-size: 2.7rem; font-weight: 800; }
    .login-brand p { font-size: 1.1rem; opacity: 0.9; }
    .form-panel { padding: 60px 50px; }
    .login-form .form-control { border-radius: 16px; padding: 14px 16px; background: #f5f7ff; border: 1px solid #e6eaf5; }
    .login-form .btn-primary { border-radius: 16px; padding: 14px 20px; font-weight: 700; }
    @media (max-width: 768px) {
        .login-brand, .form-panel { padding: 35px 24px; }
        .login-brand h1 { font-size: 2rem; }
    }
</style>
<div class="login-shell">
    <div class="login-card row g-0">
        <div class="col-lg-6 login-brand d-flex flex-column justify-content-center">
            <div class="mb-4"><span class="brand-mark">🎓</span> <strong>MATHPLAY</strong></div>
            <h1>Aprender matemática pode ser divertido.</h1>
            <p class="mt-3">Desafios, evolução e conquistas em uma plataforma feita para você aprender brincando.</p>
        </div>

        <div class="col-lg-6 form-panel">
            <div class="text-center mb-4">
                <span class="section-badge">Acesso ao aluno</span>
            </div>
            <h2 class="fw-bold mb-4">Entrar no sistema</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="mb-3">
                    <label class="form-label">E-mail ou usuário</label>
                    <input type="text" name="login" class="form-control" placeholder="Digite seu e-mail ou usuário" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control" placeholder="Digite sua senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">ENTRAR</button>
                <div class="d-flex justify-content-between mt-3">
                    <a href="/igor_tcc_teste/public/cadastro.php" class="text-decoration-none">Criar conta</a>
                    <a href="#" class="text-decoration-none">Esqueci minha senha</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
