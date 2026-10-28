<?php

// Configuração privada do servidor.
$localConfigPath = __DIR__ . '/config.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('A configuração config/config.php deve retornar um array.');
}

$host = trim((string) ($localConfig['host'] ?? getenv('MATHPLAY_DB_HOST') ?: '127.0.0.1'));
$port = (int) ($localConfig['port'] ?? getenv('MATHPLAY_DB_PORT') ?: 3308);
$dbname = trim((string) ($localConfig['dbname'] ?? getenv('MATHPLAY_DB_NAME') ?: 'mathplay'));
$dbUser = trim((string) ($localConfig['username'] ?? getenv('MATHPLAY_DB_USER') ?: 'root'));
$dbPass = (string) ($localConfig['password'] ?? getenv('MATHPLAY_DB_PASSWORD') ?: '');

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
