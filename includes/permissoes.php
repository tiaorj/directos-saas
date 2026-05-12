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
        die("Acesso negado. Você não tem permissão para acessar esta funcionalidade.");
    }
}