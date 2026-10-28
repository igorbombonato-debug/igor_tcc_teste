<?php
// Encaminha visitantes ao login e usuários autenticados ao painel apropriado.
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . app_url('/public/dashboard.php'));
    exit;
}

header('Location: ' . app_url('/public/login.php'));
exit;
