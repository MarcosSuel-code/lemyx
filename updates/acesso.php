<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Verifica se o usuário é admin
if ($_SESSION['perfil'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}
?>
