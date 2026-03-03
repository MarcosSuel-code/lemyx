<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../categorias/categorias.php");
        exit();
    }
}

function isAdmin() {
    return (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
}

function hasPermission($permission) {
    // Simplificado: só admins têm todas permissões por enquanto
    if (isAdmin()) return true;
    // Pode adicionar lógica de permissões aqui
    return false;
}
