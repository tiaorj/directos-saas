<?php

function obterEmpresaIdSeguranca()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return (int)($_SESSION["EmpresaId"] ?? 0);
}

function obterUsuarioIdSeguranca()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return (int)($_SESSION["UsuarioId"] ?? 0);
}

function bloquearAcesso($mensagem = "Acesso negado.")
{
    die($mensagem);
}

function registroExisteNaEmpresa($conn, $tabela, $campoId, $id, $empresaId)
{
    $tabelasPermitidas = [
        "OS_Clientes",
        "OS_Servicos",
        "OS_OrdensServico",
        "OS_OrdensServicoAnexos",
        "OS_Usuarios"
    ];

    $camposPermitidos = [
        "ClienteId",
        "ServicoId",
        "OrdemServicoId",
        "AnexoId",
        "UsuarioId"
    ];

    if (!in_array($tabela, $tabelasPermitidas, true)) {
        bloquearAcesso("Tabela inválida para validação.");
    }

    if (!in_array($campoId, $camposPermitidos, true)) {
        bloquearAcesso("Campo inválido para validação.");
    }

    $sql = "
        SELECT COUNT(*)
        FROM {$tabela}
        WHERE {$campoId} = :Id
          AND EmpresaId = :EmpresaId
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":Id", (int)$id, PDO::PARAM_INT);
    $stmt->bindValue(":EmpresaId", (int)$empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn() > 0;
}

function exigirClienteDaEmpresa($conn, $clienteId)
{
    $empresaId = obterEmpresaIdSeguranca();

    if ($clienteId <= 0 || !registroExisteNaEmpresa($conn, "OS_Clientes", "ClienteId", $clienteId, $empresaId)) {
        bloquearAcesso("Cliente não encontrado para esta empresa.");
    }
}

function exigirServicoDaEmpresa($conn, $servicoId)
{
    $empresaId = obterEmpresaIdSeguranca();

    if ($servicoId <= 0 || !registroExisteNaEmpresa($conn, "OS_Servicos", "ServicoId", $servicoId, $empresaId)) {
        bloquearAcesso("Serviço não encontrado para esta empresa.");
    }
}

function exigirOrdemDaEmpresa($conn, $ordemServicoId)
{
    $empresaId = obterEmpresaIdSeguranca();

    if ($ordemServicoId <= 0 || !registroExisteNaEmpresa($conn, "OS_OrdensServico", "OrdemServicoId", $ordemServicoId, $empresaId)) {
        bloquearAcesso("Ordem de serviço não encontrada para esta empresa.");
    }
}

function exigirAnexoDaEmpresa($conn, $anexoId)
{
    $empresaId = obterEmpresaIdSeguranca();

    if ($anexoId <= 0 || !registroExisteNaEmpresa($conn, "OS_OrdensServicoAnexos", "AnexoId", $anexoId, $empresaId)) {
        bloquearAcesso("Anexo não encontrado para esta empresa.");
    }
}

function exigirUsuarioDaEmpresa($conn, $usuarioId)
{
    $empresaId = obterEmpresaIdSeguranca();

    if ($usuarioId <= 0 || !registroExisteNaEmpresa($conn, "OS_Usuarios", "UsuarioId", $usuarioId, $empresaId)) {
        bloquearAcesso("Usuário não encontrado para esta empresa.");
    }
}

function clienteAtivoDaEmpresa($conn, $clienteId)
{
    $empresaId = obterEmpresaIdSeguranca();

    $sql = "
        SELECT COUNT(*)
        FROM OS_Clientes
        WHERE ClienteId = :ClienteId
          AND EmpresaId = :EmpresaId
          AND Ativo = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":ClienteId", (int)$clienteId, PDO::PARAM_INT);
    $stmt->bindValue(":EmpresaId", (int)$empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn() > 0;
}

function servicoAtivoDaEmpresa($conn, $servicoId)
{
    $empresaId = obterEmpresaIdSeguranca();

    $sql = "
        SELECT COUNT(*)
        FROM OS_Servicos
        WHERE ServicoId = :ServicoId
          AND EmpresaId = :EmpresaId
          AND Ativo = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":ServicoId", (int)$servicoId, PDO::PARAM_INT);
    $stmt->bindValue(":EmpresaId", (int)$empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn() > 0;
}