<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

if (
    !pode($_SESSION['usuario_id'], 'usuarios', match ($action) {
        'add' => 'criar',
        'edit' => 'editar',
        'delete' => 'excluir',
        default => 'visualizar'
    })
) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão']);
    exit;
}

try {

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, usuario, senha, perfil, email, ativo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['nome'],
            $_POST['usuario'],
            password_hash($_POST['senha'], PASSWORD_DEFAULT),
            $_POST['perfil'],
            $_POST['email'],
            (int) $_POST['ativo']
        ]);
    }

    if ($action === 'edit') {
        $sql = "
            UPDATE usuarios SET
                nome = ?, usuario = ?, email = ?, perfil = ?, ativo = ?
        ";
        $params = [
            $_POST['nome'],
            $_POST['usuario'],
            $_POST['email'],
            $_POST['perfil'],
            (int) $_POST['ativo']
        ];

        if (!empty($_POST['senha'])) {
            $sql .= ", senha = ?";
            $params[] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE usuario_id = ?";
        $params[] = (int) $_POST['usuario_id'];

        $pdo->prepare($sql)->execute($params);
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM usuarios WHERE usuario_id = ?")
            ->execute([(int) $_POST['usuario_id']]);
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Operação realizada com sucesso']);

} catch (Throwable $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}