<?php

function csrfGerarToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrfInput()
{
    $token = csrfGerarToken();

    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, "UTF-8") . '">';
}

function csrfTokenUrl()
{
    $token = csrfGerarToken();

    return "csrf_token=" . urlencode($token);
}

function csrfValidarTokenPost()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tokenSessao = $_SESSION["csrf_token"] ?? "";
    $tokenPost = $_POST["csrf_token"] ?? "";

    if ($tokenSessao === "" || $tokenPost === "" || !hash_equals($tokenSessao, $tokenPost)) {
        die("Requisição inválida. Token de segurança não confere.");
    }
}

function csrfValidarTokenGet()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tokenSessao = $_SESSION["csrf_token"] ?? "";
    $tokenGet = $_GET["csrf_token"] ?? "";

    if ($tokenSessao === "" || $tokenGet === "" || !hash_equals($tokenSessao, $tokenGet)) {
        die("Requisição inválida. Token de segurança não confere.");
    }
}