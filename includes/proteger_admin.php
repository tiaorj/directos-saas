<?php
require_once "proteger.php";

if (($_SESSION["UsuarioPerfil"] ?? "") !== "Admin") {
    header("Location: " . $baseUrl . "/acesso_negado.php");
    exit;
}