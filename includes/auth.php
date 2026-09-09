<?php

// Inicia uma sessão somente quando ainda não existe uma ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    // A presença do ID na sessão indica que o usuário passou pelo login.
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    // Bloqueia páginas privadas para sessões inválidas ou usuários removidos.
    if (!is_logged_in() || get_logged_user() === null) {
        // Limpa os dados inválidos antes de redirecionar.
        $_SESSION = [];
        session_destroy();
        header('Location: /igor_tcc_teste/public/login.php');
        exit;
    }
}

function require_admin(): void
{
    // Primeiro exige uma sessão válida.
    require_login();

    // Depois verifica se o usuário tem permissão administrativa.
    if (empty($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'admin') {
        header('Location: /igor_tcc_teste/public/dashboard.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    // Impede que um usuário autenticado volte para login ou cadastro.
    if (is_logged_in() && get_logged_user() !== null) {
        header('Location: /igor_tcc_teste/public/dashboard.php');
        exit;
    }

    // Remove uma sessão cujo usuário não existe mais no banco.
    if (is_logged_in()) {
        $_SESSION = [];
        session_destroy();
    }
}

function get_logged_user(): ?array
{
    // Sem ID de sessão, não há usuário para consultar.
    if (!is_logged_in()) {
        return null;
    }

    // Usa a conexão compartilhada criada em config/database.php.
    global $pdo;

    // Busca os dados atuais do usuário pelo ID salvo na sessão.
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}
