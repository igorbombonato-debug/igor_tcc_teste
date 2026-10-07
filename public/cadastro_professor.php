<?php
// Esta página valida os dados e cria uma conta com perfil de professor.
require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$pageTitle = 'Cadastro de professor | Mathematics Education';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê os campos do formulário; os textos são limpos antes de validar e gravar.
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    // Cada etapa valida um requisito antes de permitir a criação da conta.
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
        $error = 'Este usuário já existe.';
    } else {
        // Armazena o hash da senha, nunca a senha original.
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, username, email, senha, tipo, ano_escolar, xp, nivel, pontos, ativo, created_at) VALUES (:nome, :username, :email, :senha, \'professor\', 0, 0, 1, 0, 1, NOW())');
        $stmt->execute([
            'nome' => $nome,
            'username' => $username,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
        ]);

        // O professor entra pela tela de login específica para esse perfil.
        header('Location: /igor_tcc_teste/public/login.php?perfil=professor&cadastro=1');
        exit;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="container page-section">
    <div class="panel-box mx-auto" style="max-width: 700px;">
        <span class="section-badge">Cadastro de professor</span>
        <h1 class="mt-3">Criar conta</h1>
        <p class="text-muted">Após o cadastro, você poderá acompanhar o desempenho e as respostas dos alunos.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="nome">Nome</label>
                <input id="nome" type="text" name="nome" class="form-control" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="username">Usuário</label>
                <input id="username" type="text" name="username" class="form-control" maxlength="100" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="email">E-mail</label>
                <input id="email" type="email" name="email" class="form-control" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="senha">Senha</label>
                <input id="senha" type="password" name="senha" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="confirmar_senha">Confirmar senha</label>
                <input id="confirmar_senha" type="password" name="confirmar_senha" class="form-control" minlength="6" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">Criar conta de professor</button>
            </div>
            <div class="col-12 text-center">
                <a href="/igor_tcc_teste/public/login.php?perfil=professor">Já tenho conta</a>
            </div>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
