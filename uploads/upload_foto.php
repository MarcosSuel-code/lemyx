<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

// ===============================
// AUTENTICAÇÃO
// ===============================
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];

// ===============================
// ARQUIVO
// ===============================
if (!isset($_FILES['foto'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhuma imagem enviada'
    ]);
    exit;
}

$foto = $_FILES['foto'];

if ($foto['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro no upload'
    ]);
    exit;
}

// ===============================
// VALIDAÇÃO
// ===============================
$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
$extensao = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));

if (!in_array($extensao, $extensoesPermitidas)) {
    echo json_encode([
        'success' => false,
        'message' => 'Formato não permitido'
    ]);
    exit;
}

// ===============================
// CAMINHOS
// ===============================
$nomeArquivo = 'usuario_' . $usuarioId . '.' . $extensao;
$diretorio = $_SERVER['DOCUMENT_ROOT'] . '/Controller/uploads/usuarios';
$destino = $diretorio . '/' . $nomeArquivo;

if (!is_dir($diretorio)) {
    echo json_encode([
        'success' => false,
        'message' => 'Pasta de destino não existe'

    ]);
    exit;
}

// ===============================
// MOVE
// ===============================
if (!move_uploaded_file($foto['tmp_name'], $destino)) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar imagem'
    ]);
    exit;
}

// ===============================
// BANCO
// ===============================
$sql = "UPDATE usuarios SET foto = :foto WHERE usuario_id = :usuario_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':foto' => $nomeArquivo,
    ':usuario_id' => $usuarioId
]);

// ===============================
// SUCESSO
// ===============================
echo json_encode([
    'success' => true,
    'foto' => '/Controller/uploads/usuarios/' . $nomeArquivo
]);
exit;
