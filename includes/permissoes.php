<?php

function usuarioTemPerfil($perfisPermitidos)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $perfilAtual = $_SESSION["UsuarioPerfil"] ?? "";

    return in_array($perfilAtual, $perfisPermitidos, true);
}

function exigirPerfil($perfisPermitidos)
{
    if (!usuarioTemPerfil($perfisPermitidos)) {
        header("Location: /sistema-os-php-sqlserver/acesso_negado.php");
        exit;
    }
}

function usuarioEhSuperAdmin()
{
    return usuarioTemPerfil(["SuperAdmin"]);
}

function usuarioEhAdmin()
{
    return usuarioTemPerfil(["Admin", "SuperAdmin"]);
}

function usuarioPodeGerenciar()
{
    return usuarioTemPerfil(["Admin", "SuperAdmin"]);
}

function usuarioPodeAtenderOS()
{
    return usuarioTemPerfil(["Admin", "Atendente", "Tecnico", "SuperAdmin"]);
}

function obterEmpresaIdSessao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return (int)($_SESSION["EmpresaId"] ?? 0);
}

function obterUsuarioIdSessao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return (int)($_SESSION["UsuarioId"] ?? 0);
}

function bloquearSuperAdminEmRotinaEmpresa()
{
    if (usuarioEhSuperAdmin()) {
        header("Location: /sistema-os-php-sqlserver/acesso_negado.php");
        exit;
    }
}