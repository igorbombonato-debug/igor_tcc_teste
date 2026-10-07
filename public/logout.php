<?php
// Encerra a sessão e remove também o cookie usado para identificá-la no navegador.
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    // Expira o cookie com os mesmos atributos usados na criação da sessão.
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: /igor_tcc_teste/public/login.php');
exit;
