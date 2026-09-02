<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in() || get_logged_user() === null) {
        $_SESSION = [];
        session_destroy();
        header('Location: /igor_tcc_teste/public/login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (empty($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'admin') {
        header('Location: /igor_tcc_teste/public/dashboard.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (is_logged_in() && get_logged_user() !== null) {
        header('Location: /igor_tcc_teste/public/dashboard.php');
        exit;
    }

    if (is_logged_in()) {
        $_SESSION = [];
        session_destroy();
    }
}

function get_logged_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}
