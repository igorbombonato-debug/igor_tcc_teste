<?php
// Encaminha visitantes ao login e usuários autenticados ao painel apropriado.
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: /igor_tcc_teste/public/dashboard.php');
    exit;
}

header('Location: /igor_tcc_teste/public/login.php');
exit;
