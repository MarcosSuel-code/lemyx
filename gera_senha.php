<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

$usuario = 'admin';
$nome = 'Admin';
$senha = password_hash('1234', PASSWORD_DEFAULT);
$perfil = 'ADMIN';

$stmt = $pdo->prepare(
    "INSERT INTO usuarios (usuario, nome, senha, perfil)
     VALUES (?, ?, ?, ?)"
);

$stmt->execute([$usuario,$nome, $senha, $perfil]);

echo "Usuário criado com sucesso!";
