<?php

$dbHost = '__DB_HOST__';
$dbPort = 3308;
$dbName = '__DB_NAME__';
$dbUser = '__DB_USER__';
$dbPass = '__DB_PASS__';

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
