<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["UsuarioId"])) {
    header("Location: /sistema-os-php-sqlserver/login.php");
    exit;
}

if (!isset($_SESSION["EmpresaId"]) || empty($_SESSION["EmpresaId"])) {
    session_destroy();
    header("Location: /sistema-os-php-sqlserver/login.php?erro=Empresa não vinculada ao usuário.");
    exit;
}