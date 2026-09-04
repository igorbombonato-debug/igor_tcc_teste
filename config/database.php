<?php

$host = '127.0.0.1';
$ports = [3306, 3307, 3308];
$dbname = 'mathplay';
$dbUser = 'root';
$dbPass = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = null;
$lastError = null;

foreach ($ports as $port) {
    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4',
            $dbUser,
            $dbPass,
            $options
        );
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!$pdo) {
    http_response_code(500);
    die(
        'Não foi possível conectar ao banco de dados MathPlay. ' .
        'Verifique se o MySQL está rodando, se o banco mathplay foi criado e se a porta/credenciais em config/database.php estão corretas. ' .
        'Erro: ' . ($lastError ? $lastError->getMessage() : 'Conexão indisponível.')
    );
}
