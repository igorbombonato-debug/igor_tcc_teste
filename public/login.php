<?php
// Autentica alunos e professores e encaminha cada perfil para sua área.
// Carrega autenticação, sessão e funções de banco.
require_once __DIR__ . '/../includes/auth.php';

// Usuários já logados são enviados diretamente ao dashboard.
redirect_if_logged_in();

// Define o título da página e inicia a mensagem de erro.
$pageTitle = 'Login | Mathematics Education';
$error = '';
$perfil = ($_POST['perfil'] ?? $_GET['perfil'] ?? 'aluno') === 'professor' ? 'professor' : 'aluno';

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
        $perfilCorresponde = $user
            && ($perfil === 'professor'
                ? $user['tipo'] === 'professor'
                : in_array($user['tipo'], ['aluno', 'admin'], true));
        // Além da senha, confirma que o tipo de conta corresponde ao perfil escolhido.
        if ($perfilCorresponde && password_verify($senha, $user['senha'])) {
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
                $destino = $user['tipo'] === 'professor'
                    ? '/igor_tcc_teste/professor/index.php'
                    : '/igor_tcc_teste/public/dashboard.php';
                header('Location: ' . $destino);
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
    .login-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 16px 140px; }
    .login-card { max-width: 1100px; width: 100%; border: none; border-radius: 28px; overflow: hidden; background: #fff; box-shadow: 0 30px 80px rgba(82, 90, 186, 0.12); }
    .login-brand { min-height: 650px; justify-content: flex-start !important; overflow: hidden; background: linear-gradient(145deg, #2356e8 0%, #6254e9 58%, #49bcae 100%); color: white; padding: 48px 50px 30px; }
    .login-brand-content { position: relative; z-index: 1; }
    .login-brand h1 { max-width: 520px; font-size: 2.7rem; font-weight: 800; }
    .login-brand p { max-width: 480px; font-size: 1.1rem; opacity: 0.96; }
    .login-illustration { display: block; width: min(100%, 470px); height: 300px; flex: 1 1 auto; min-height: 200px; align-self: center; margin: 12px auto 0; object-fit: contain; }
    .form-panel { padding: 60px 50px; }
    .login-form .form-control { border-radius: 16px; padding: 14px 16px; background: #f5f7ff; border: 1px solid #e6eaf5; }
    .login-form .btn-primary { border-radius: 16px; padding: 14px 20px; font-weight: 700; }
    @media (max-width: 768px) {
        .login-brand { min-height: 620px; padding: 35px 24px 24px; }
        .form-panel { padding: 35px 24px; }
        .login-brand h1 { font-size: 2rem; }
        .login-illustration { height: 270px; }
    }
</style>
<div class="login-shell">
    <div class="login-card row g-0">
        <div class="col-lg-6 login-brand d-flex flex-column justify-content-center">
            <div class="login-brand-content">
                <div class="mb-4"><span class="brand-mark">🎓</span> <strong>Mathematics Education</strong></div>
                <h1>Aprender matemática pode ser divertido.</h1>
                <p class="mt-3">Desafios, evolução e conquistas em uma plataforma feita para você aprender brincando.</p>
            </div>
            <img class="login-illustration" src="/igor_tcc_teste/assets/img/classroom-math.svg" alt="Uma professora e um estudante resolvem atividades de matemática diante de um quadro.">
        </div>

        <div class="col-lg-6 form-panel">
            <div class="text-center mb-4">
                <span class="section-badge">Acesso ao sistema</span>
            </div>
            <h2 class="fw-bold mb-4">Entrar no sistema</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="mb-3">
                    <label class="form-label" for="perfil">Entrar como</label>
                    <select id="perfil" name="perfil" class="form-select">
                        <option value="aluno" <?php echo $perfil === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                        <option value="professor" <?php echo $perfil === 'professor' ? 'selected' : ''; ?>>Professor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail ou usuário</label>
                    <input type="text" name="login" class="form-control" placeholder="Digite seu e-mail ou usuário" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control" placeholder="Digite sua senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">ENTRAR</button>
                <div class="d-flex justify-content-between flex-wrap gap-2 mt-3">
                    <div class="d-flex flex-wrap gap-3">
                        <a href="/igor_tcc_teste/public/cadastro.php" class="text-decoration-none">Cadastro de aluno</a>
                        <a href="/igor_tcc_teste/public/cadastro_professor.php" class="text-decoration-none">Cadastro de professor</a>
                    </div>
                    <a href="/igor_tcc_teste/public/recuperar_senha.php" class="text-decoration-none">Esqueci minha senha</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
