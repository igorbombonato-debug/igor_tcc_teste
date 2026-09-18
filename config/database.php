<?php

// Endereço local usado pelo MySQL.
$host = '127.0.0.1';
// Porta configurada pelo MySQL do XAMPP que atende o site local.
$port = 3307;
// Nome do banco de dados da aplicação.
$dbname = 'mathplay';
// Usuário local do MySQL.
$dbUser = 'root';
// Senha do usuário local.
$dbPass = '';

// Opções que deixam o PDO seguro e retornam resultados como arrays associativos.
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        $options
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(
        'Não foi possível conectar ao banco de dados MathPlay. ' .
        'Verifique se o MySQL está rodando, se o banco mathplay foi criado e se a senha em config/database.php está correta. ' .
        'Erro: ' . $e->getMessage()
    );
}
