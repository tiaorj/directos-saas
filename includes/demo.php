<?php

function usuarioDemo()
{
    $emailsDemo = [
        "demo@directos.com.br",
        "atendente.demo@directos.com.br",
        "tecnico.demo@directos.com.br"
    ];

    $emailUsuario = strtolower(trim($_SESSION["UsuarioEmail"] ?? ""));

    if ($emailUsuario !== "" && in_array($emailUsuario, $emailsDemo, true)) {
        return true;
    }

    $empresaNome = strtolower(trim($_SESSION["EmpresaNome"] ?? ""));

    if ($empresaNome === "directos demo") {
        return true;
    }

    return false;
}

function bloquearAcaoDemo($mensagem = "Esta ação está bloqueada no ambiente de demonstração.")
{
    if (!usuarioDemo()) {
        return;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        die($mensagem);
    }

    $voltar = $_SERVER["HTTP_REFERER"] ?? "../dashboard.php";

    header("Location: " . $voltar . (strpos($voltar, "?") === false ? "?" : "&") . "mensagem=" . urlencode($mensagem));
    exit;
}