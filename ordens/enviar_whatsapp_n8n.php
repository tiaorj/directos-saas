<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/n8n.php";

header("Content-Type: application/json; charset=utf-8");

exigirPerfil(["Admin", "Atendente", "Tecnico", "SuperAdmin"]);
csrfValidarTokenPost();

$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$mensagem = trim($_POST["Mensagem"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");

try {
    if ($ordemServicoId <= 0) {
        throw new Exception("OS inválida.");
    }

    exigirOrdemDaEmpresa($conn, $ordemServicoId);

    if ($mensagem === "") {
        throw new Exception("Mensagem não informada.");
    }

    $sql = "
        SELECT
            os.OrdemServicoId,
            os.CodigoOS,
            os.Titulo,
            os.Status,
            os.TokenAcompanhamento,
            c.ClienteId,
            c.Nome AS ClienteNome,
            c.Telefone AS ClienteTelefone,
            c.Telefone AS ClienteWhatsApp,
            s.Nome AS ServicoNome,
            e.EmpresaId,
            e.NomeFantasia AS EmpresaNome
        FROM OS_OrdensServico os
        INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
        LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
        INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
        WHERE os.OrdemServicoId = :OrdemServicoId
          AND os.EmpresaId = :EmpresaId
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmt->bindValue(":EmpresaId", (int)$_SESSION["EmpresaId"], PDO::PARAM_INT);
    $stmt->execute();

    $ordem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ordem) {
        throw new Exception("OS não encontrada.");
    }

    if ($telefone === "") {
        $telefone = $ordem["ClienteWhatsApp"] ?? "";

        if ($telefone === "") {
            $telefone = $ordem["ClienteTelefone"] ?? "";
        }
    }

    $telefoneNormalizado = normalizarTelefoneWhatsApp($telefone);

    if ($telefoneNormalizado === "") {
        throw new Exception("Cliente sem telefone/WhatsApp cadastrado.");
    }

    $linkPublico = "";

    if (!empty($ordem["TokenAcompanhamento"])) {
        $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($ordem["TokenAcompanhamento"]);
    }

    $payload = [
        "origem" => "DirectOS",
        "evento" => "whatsapp_os",
        "empresa" => [
            "id" => (int)$ordem["EmpresaId"],
            "nome" => $ordem["EmpresaNome"]
        ],
        "usuario" => [
            "id" => (int)($_SESSION["UsuarioId"] ?? 0),
            "nome" => $_SESSION["UsuarioNome"] ?? "",
            "email" => $_SESSION["UsuarioEmail"] ?? ""
        ],
        "cliente" => [
            "id" => (int)$ordem["ClienteId"],
            "nome" => $ordem["ClienteNome"],
            "telefone" => $telefoneNormalizado
        ],
        "os" => [
            "id" => (int)$ordem["OrdemServicoId"],
            "codigo" => $ordem["CodigoOS"],
            "titulo" => $ordem["Titulo"],
            "status" => $ordem["Status"],
            "servico" => $ordem["ServicoNome"],
            "link_publico" => $linkPublico
        ],
        "whatsapp" => [
            "telefone" => $telefoneNormalizado,
            "mensagem" => $mensagem
        ],
        "data_envio" => date("c")
    ];

    $retorno = n8nEnviarWhatsApp($payload);

    registrarAuditoria(
        $conn,
        "N8N_WHATSAPP_OS",
        "OS_OrdensServico",
        $ordemServicoId,
        "Mensagem de WhatsApp enviada para o n8n."
    );

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Mensagem enviada para o n8n com sucesso.",
        "retorno" => $retorno
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}