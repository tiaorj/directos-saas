<?php

function usuarioTemPerfil($perfisPermitidos)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $perfilAtual = $_SESSION["UsuarioPerfil"] ?? "";

    return in_array($perfilAtual, $perfisPermitidos);
}

function exigirPerfil($perfisPermitidos)
{
    if (!usuarioTemPerfil($perfisPermitidos)) {
        header("Location: /sistema-os-php-sqlserver/acesso_negado.php");
        exit;
    }
}