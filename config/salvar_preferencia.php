<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

require_once '../config/database.php';

$usuario = $_SESSION['usuario'];
$tipo = $_POST['tipo'] ?? null;        // darkMode, fontSize ou colorTheme
$valor = $_POST['valor'] ?? null;

if (!in_array($tipo, ['darkMode', 'fontSize', 'colorTheme'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo inválido']);
    exit();
}

$campoDB = [
    'darkMode' => 'dark_mode',
    'fontSize' => 'font_size',
    'colorTheme' => 'color_theme'
][$tipo];

// Atualiza no banco
$stmt = $pdo->prepare("UPDATE preferencias_usuario SET $campoDB = ? WHERE usuario = ?");
$stmt->execute([$valor, $usuario]);

echo json_encode(['success' => true]);
