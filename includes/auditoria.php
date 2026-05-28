<?php

function registrarAuditoria($conn, $acao, $entidade = null, $entidadeId = null, $descricao = null, $empresaId = null, $usuarioId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($empresaId === null) {
        $empresaId = isset($_SESSION["EmpresaId"]) ? (int)$_SESSION["EmpresaId"] : null;
    }

    if ($usuarioId === null) {
        $usuarioId = isset($_SESSION["UsuarioId"]) ? (int)$_SESSION["UsuarioId"] : null;
    }

    $ipAcesso = $_SERVER["REMOTE_ADDR"] ?? null;
    $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? null;

    if ($userAgent !== null && strlen($userAgent) > 500) {
        $userAgent = substr($userAgent, 0, 500);
    }

    $sql = "
        INSERT INTO OS_Auditoria
        (
            EmpresaId,
            UsuarioId,
            Acao,
            Entidade,
            EntidadeId,
            Descricao,
            IpAcesso,
            UserAgent
        )
        VALUES
        (
            :EmpresaId,
            :UsuarioId,
            :Acao,
            :Entidade,
            :EntidadeId,
            :Descricao,
            :IpAcesso,
            :UserAgent
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, $empresaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(":UsuarioId", $usuarioId, $usuarioId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(":Acao", $acao);
    $stmt->bindValue(":Entidade", $entidade);
    $stmt->bindValue(":EntidadeId", $entidadeId, $entidadeId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(":Descricao", $descricao);
    $stmt->bindValue(":IpAcesso", $ipAcesso);
    $stmt->bindValue(":UserAgent", $userAgent);

    $stmt->execute();
}