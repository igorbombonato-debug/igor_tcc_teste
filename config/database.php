<?php

// Configura e abre a conexão compartilhada com o banco de dados MySQL da aplicação.
// Endereço local usado pelo MySQL.
$host = '127.0.0.1';
// Porta configurada para o MySQL do Laragon.
$port = 3308;
// Nome do banco de dados da aplicação.
$dbname = 'mathplay';
// Usuário local do MySQL.
$dbUser = 'root';
// Informe aqui a senha configurada para o usuário local do MySQL.
$dbPass = '';

// PDO lança exceções em falhas, retorna colunas por nome e usa prepared statements nativos.
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
    // Interrompe a página com status HTTP 500 e uma orientação para corrigir a conexão.
    http_response_code(500);
    die(
        'Não foi possível conectar ao banco de dados Mathematics Education. ' .
        'Verifique se o MySQL está rodando, se o banco mathplay foi criado e se a senha em config/database.php está correta. ' .
        'Erro: ' . $e->getMessage()
    );
}
