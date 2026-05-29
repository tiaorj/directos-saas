<?php
require_once __DIR__ . "/../config/config.php";

$baseUrl = rtrim(APP_URL, "/");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>DirectOS - Sistema de Ordem de Serviço</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <link 
        href="<?= htmlspecialchars($baseUrl) ?>/assets/css/directos.css" 
        rel="stylesheet"
    >
</head>

<body>