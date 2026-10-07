<?php
// Abre a estrutura HTML comum, prepara a sessão e carrega estilos e navegação.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) : 'Mathematics Education'; ?></title>
    <script>
        // Aplica o tema salvo antes da renderização para evitar uma troca visual tardia.
        try {
            const savedTheme = window.localStorage.getItem('mathplay-theme');
            document.documentElement.dataset.theme = savedTheme === 'dark' || savedTheme === 'light'
                ? savedTheme
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (error) {
            document.documentElement.dataset.theme = 'light';
            console.warn('Não foi possível carregar a preferência de tema salva.', error);
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/igor_tcc_teste/assets/css/style.css?v=6">
    <?php if (isset($extraCss)) { echo $extraCss; } ?>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<aside class="accessibility-tools" aria-label="Opções de acessibilidade">
    <button class="theme-toggle" type="button" id="themeToggle" aria-pressed="false">
        <i class="bi bi-moon-stars-fill" aria-hidden="true"></i>
        <span>Tema escuro</span>
    </button>
    <button class="accessibility-toggle" type="button" id="accessibilityToggle" aria-expanded="false" aria-controls="accessibilityPanel">
        <i class="bi bi-universal-access" aria-hidden="true"></i>
        <span>Acessibilidade</span>
    </button>
    <div class="accessibility-panel" id="accessibilityPanel" aria-labelledby="accessibilityTitle" hidden>
        <h2 id="accessibilityTitle">Acessibilidade</h2>
        <p>Ajuste o tamanho do texto nesta página.</p>
        <div class="accessibility-actions">
            <button type="button" id="decreaseText" aria-label="Diminuir tamanho do texto">A−</button>
            <button type="button" id="increaseText" aria-label="Aumentar tamanho do texto">A+</button>
            <button type="button" id="resetText">Padrão</button>
        </div>
        <span class="visually-hidden" id="accessibilityStatus" aria-live="polite"></span>
    </div>
    <span class="visually-hidden" id="themeStatus" role="status" aria-live="polite"></span>
</aside>
<main class="main-content">
