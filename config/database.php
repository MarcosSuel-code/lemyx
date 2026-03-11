<?php
$host = 'sql213.infinityfree.com';
$db   = 'if0_41325543_lemyx_db';
$user = 'if0_41325543';
$pass = 'sl5uQicSn7i';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo; // ✅ ESSENCIAL!
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
// Fim do arquivo database.php
// Use este arquivo para configurar a conexão com o banco de dados
// Certifique-se de que as credenciais estão corretas e que o banco de dados existe
// Você pode incluir este arquivo em outros scripts PHP para usar a conexão
// Exemplo de uso:  require_once 'config/database.php';
// Certifique-se de que o arquivo está no caminho correto

