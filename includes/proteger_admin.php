<?php
require_once "proteger.php";

if (($_SESSION["UsuarioPerfil"] ?? "") !== "Admin") {
    die("Acesso negado. Esta área é restrita a administradores.");
}