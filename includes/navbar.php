<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = get_logged_user();
$activePage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?php echo is_logged_in() ? '/igor_tcc_teste/public/dashboard.php' : '/igor_tcc_teste/public/login.php'; ?>">
            <span class="brand-mark">🎓</span>
            MATHPLAY
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <?php if (is_logged_in()): ?>
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'dashboard.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/dashboard.php">🏠 Início</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'materias.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/materias.php">📚 Matérias</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'ranking.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/ranking.php">🏆 Ranking</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'desempenho.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/desempenho.php">📊 Desempenho</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'historico.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/historico.php">📜 Histórico</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $activePage === 'perfil.php' ? 'active' : ''; ?>" href="/igor_tcc_teste/public/perfil.php">👤 Perfil</a></li>
                    <?php if (!empty($user) && $user['tipo'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link <?php echo in_array($activePage, ['index.php','usuarios.php','questoes.php','materias.php','desempenho.php','partidas.php']) ? 'active' : ''; ?>" href="/igor_tcc_teste/admin/index.php">⚙️ Admin</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <div class="user-pill">
                        <span class="avatar-mini"><?php echo strtoupper(substr($user['nome'] ?? 'U', 0, 1)); ?></span>
                        <span><?php echo e($user['nome'] ?? 'Usuário'); ?></span>
                    </div>
                    <a href="/igor_tcc_teste/public/logout.php" class="btn btn-outline-light btn-sm">🚪 Sair</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
