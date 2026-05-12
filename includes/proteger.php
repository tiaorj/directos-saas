<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["UsuarioId"])) {
    header("Location: /sistema-os-php-sqlserver/login.php");
    exit;
}