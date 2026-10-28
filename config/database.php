<?php

// A configuração local tem precedência; no servidor, use config.php.
$localConfigPath = __DIR__ . '/config.local.php';
$serverConfigPath = __DIR__ . '/config.php';
$databaseConfigPath = is_file($localConfigPath) ? $localConfigPath : $serverConfigPath;
$databaseConfig = is_file($databaseConfigPath) ? require $databaseConfigPath : [];
if (!is_array($databaseConfig)) {
    throw new RuntimeException('A configuração ' . basename($databaseConfigPath) . ' deve retornar um array.');
}

$host = trim((string) ($databaseConfig['host'] ?? getenv('MATHPLAY_DB_HOST') ?: '127.0.0.1'));
$port = (int) ($databaseConfig['port'] ?? getenv('MATHPLAY_DB_PORT') ?: 3308);
$dbname = trim((string) ($databaseConfig['dbname'] ?? getenv('MATHPLAY_DB_NAME') ?: 'mathplay'));
$dbUser = trim((string) ($databaseConfig['username'] ?? getenv('MATHPLAY_DB_USER') ?: 'root'));
$dbPass = (string) ($databaseConfig['password'] ?? getenv('MATHPLAY_DB_PASSWORD') ?: '');

if ($host === '' || $port < 1 || $dbname === '' || $dbUser === '') {
    throw new RuntimeException('Host, porta, banco e usuário do MySQL devem estar configurados.');
}

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
    error_log('Falha na conexão MySQL do MindPlay: ' . $e->getMessage());
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados MindPlay. Confira as credenciais MySQL configuradas no servidor.');
}
