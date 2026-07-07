<?php

function planoTemColunaPermiteIA($conn)
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    try {
        $stmt = $conn->query("SELECT COL_LENGTH('OS_Planos', 'PermiteIA')");
        $cache = $stmt && $stmt->fetchColumn() !== null;
    } catch (Exception $e) {
        $cache = false;
    }

    return $cache;
}

function selectPermiteIAPlano($conn)
{
    return planoTemColunaPermiteIA($conn)
        ? "p.PermiteIA"
        : "CAST(1 AS BIT) AS PermiteIA";
}

function obterPlanoEmpresa($conn, $empresaId)
{
    $selectPermiteIA = selectPermiteIAPlano($conn);

    $sql = "
        SELECT
            p.PlanoId,
            p.Nome,
            p.Slug,
            p.Descricao,
            p.LimiteOSMes,
            p.LimiteUsuarios,
            p.PermiteAnexos,
            p.PermiteAreaCliente,
            p.PermiteWhatsapp,
            {$selectPermiteIA},
            p.ValorMensal,
            e.StatusComercial AS StatusAssinatura,
            e.DataInicioTeste AS DataInicio,
            e.DataFimTeste AS DataFim
        FROM OS_Empresas e
        INNER JOIN OS_Planos p ON p.PlanoId = e.PlanoId
        WHERE e.EmpresaId = :EmpresaId
          AND e.Ativo = 1
          AND p.Ativo = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    $plano = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($plano) {
        return $plano;
    }

    $sqlFallback = "
        SELECT TOP 1
            p.PlanoId,
            p.Nome,
            p.Slug,
            p.Descricao,
            p.LimiteOSMes,
            p.LimiteUsuarios,
            p.PermiteAnexos,
            p.PermiteAreaCliente,
            p.PermiteWhatsapp,
            {$selectPermiteIA},
            p.ValorMensal,
            a.Status AS StatusAssinatura,
            a.DataInicio,
            a.DataFim
        FROM OS_Assinaturas a
        INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
        WHERE a.EmpresaId = :EmpresaId
          AND a.Status = 'Ativa'
          AND p.Ativo = 1
        ORDER BY a.AssinaturaId DESC
    ";

    $stmtFallback = $conn->prepare($sqlFallback);
    $stmtFallback->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtFallback->execute();

    return $stmtFallback->fetch(PDO::FETCH_ASSOC);
}

function totalOSMesEmpresa($conn, $empresaId)
{
    $sql = "
        SELECT COUNT(*)
        FROM OS_OrdensServico
        WHERE EmpresaId = :EmpresaId
          AND DataAbertura >= DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)
          AND DataAbertura < DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

function empresaPodeCriarOS($conn, $empresaId)
{
    $plano = obterPlanoEmpresa($conn, $empresaId);

    if (!$plano) {
        return [
            "permitido" => false,
            "mensagem" => "Empresa sem plano ativo.",
            "plano" => null,
            "totalMes" => 0,
            "limite" => 0
        ];
    }

    $limite = $plano["LimiteOSMes"];
    $totalMes = totalOSMesEmpresa($conn, $empresaId);

    if ($limite === null || $limite === "") {
        return [
            "permitido" => true,
            "mensagem" => "",
            "plano" => $plano,
            "totalMes" => $totalMes,
            "limite" => null
        ];
    }

    if ($totalMes >= (int)$limite) {
        return [
            "permitido" => false,
            "mensagem" => "Limite mensal de OS atingido para o plano " . $plano["Nome"] . ".",
            "plano" => $plano,
            "totalMes" => $totalMes,
            "limite" => (int)$limite
        ];
    }

    return [
        "permitido" => true,
        "mensagem" => "",
        "plano" => $plano,
        "totalMes" => $totalMes,
        "limite" => (int)$limite
    ];
}

function totalUsuariosEmpresa($conn, $empresaId)
{
    $sql = "
        SELECT COUNT(*)
        FROM OS_Usuarios
        WHERE EmpresaId = :EmpresaId
          AND Ativo = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

function empresaPodeCriarUsuario($conn, $empresaId)
{
    $plano = obterPlanoEmpresa($conn, $empresaId);

    if (!$plano) {
        return [
            "permitido" => false,
            "mensagem" => "Empresa sem plano ativo.",
            "plano" => null,
            "totalUsuarios" => 0,
            "limite" => 0
        ];
    }

    $limite = $plano["LimiteUsuarios"];
    $totalUsuarios = totalUsuariosEmpresa($conn, $empresaId);

    if ($limite === null || $limite === "") {
        return [
            "permitido" => true,
            "mensagem" => "",
            "plano" => $plano,
            "totalUsuarios" => $totalUsuarios,
            "limite" => null
        ];
    }

    if ($totalUsuarios >= (int)$limite) {
        return [
            "permitido" => false,
            "mensagem" => "Limite de usuários atingido para o plano " . $plano["Nome"] . ".",
            "plano" => $plano,
            "totalUsuarios" => $totalUsuarios,
            "limite" => (int)$limite
        ];
    }

    return [
        "permitido" => true,
        "mensagem" => "",
        "plano" => $plano,
        "totalUsuarios" => $totalUsuarios,
        "limite" => (int)$limite
    ];
}

function mapaRecursosPlano()
{
    return [
        "anexos" => [
            "campo" => "PermiteAnexos",
            "nome" => "anexos"
        ],
        "area_cliente" => [
            "campo" => "PermiteAreaCliente",
            "nome" => "área do cliente"
        ],
        "whatsapp" => [
            "campo" => "PermiteWhatsapp",
            "nome" => "WhatsApp assistido"
        ],
        "ia" => [
            "campo" => "PermiteIA",
            "nome" => "integrações de texto com IA"
        ]
    ];
}

function empresaPodeUsarRecursoPlano($conn, $empresaId, $recurso)
{
    $mapa = mapaRecursosPlano();

    if (!isset($mapa[$recurso])) {
        return [
            "permitido" => false,
            "mensagem" => "Recurso do plano inválido.",
            "plano" => null
        ];
    }

    $plano = obterPlanoEmpresa($conn, $empresaId);

    if (!$plano) {
        return [
            "permitido" => false,
            "mensagem" => "Empresa sem plano ativo.",
            "plano" => null
        ];
    }

    $campo = $mapa[$recurso]["campo"];
    $nome = $mapa[$recurso]["nome"];

    if ((int)($plano[$campo] ?? 0) !== 1) {
        return [
            "permitido" => false,
            "mensagem" => "O plano " . $plano["Nome"] . " não permite " . $nome . ". Solicite alteração de plano para liberar este recurso.",
            "plano" => $plano
        ];
    }

    return [
        "permitido" => true,
        "mensagem" => "",
        "plano" => $plano
    ];
}

function bloquearRecursoPlano($conn, $empresaId, $recurso)
{
    $validacao = empresaPodeUsarRecursoPlano($conn, $empresaId, $recurso);

    if (!$validacao["permitido"]) {
        die($validacao["mensagem"]);
    }

    return $validacao;
}
