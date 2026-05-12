<?php
require_once "proteger.php";

if (($_SESSION["UsuarioPerfil"] ?? "") !== "Admin") {
    header("Location: /sistema-os-php-sqlserver/acesso_negado.php");
    exit;
}