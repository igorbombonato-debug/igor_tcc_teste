<?php

// Endereço local usado pelo MySQL.
$host = '127.0.0.1';
// Portas testadas em ordem; 3307 é a instância usada pelo site atualmente.
$ports = [3307, 3308, 3306];
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

// Mantém a conexão disponível para os arquivos que incluem este arquivo.
$pdo = null;
// Guarda o último erro para exibir uma mensagem útil caso todas as portas falhem.
$lastError = null;

// Tenta conectar às instâncias configuradas até encontrar uma disponível.
foreach ($ports as $port) {
    try {
        // Cria a conexão usando UTF-8 para preservar acentos.
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4',
            $dbUser,
            $dbPass,
            $options
        );
        // Interrompe o loop assim que a conexão funciona.
        break;
    } catch (PDOException $e) {
        // Continua tentando a próxima porta e guarda o erro atual.
        $lastError = $e;
    }
}

if (!$pdo) {
    // Interrompe a página porque nenhuma operação pode funcionar sem banco.
    http_response_code(500);
    die(
        'Não foi possível conectar ao banco de dados MathPlay. ' .
        'Verifique se o MySQL está rodando, se o banco mathplay foi criado e se a porta/credenciais em config/database.php estão corretas. ' .
        'Erro: ' . ($lastError ? $lastError->getMessage() : 'Conexão indisponível.')
    );
}
