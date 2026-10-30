<?php

$dbHost = 'sql110.infinityfree.com';
$dbPort = 3306;
$dbName = 'if0_43255516_mathplay';
$dbUser = 'if0_43255516';
$dbPass = 'Senai507';

$dbOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $dbOptions
    );
} catch (PDOException $e) {
    error_log('Falha na conexão MySQL do MindPlay: ' . $e->getMessage());
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados. Confira as credenciais em config/config.php.');
}
