<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/ia.php";
require_once "../includes/auditoria.php";
require_once "../includes/planos.php";

header("Content-Type: application/json; charset=utf-8");

exigirPerfil(["Admin", "Atendente", "Tecnico", "SuperAdmin"]);
csrfValidarTokenPost();

$tipo = trim($_POST["TipoIA"] ?? "");

$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$descricaoProblema = trim($_POST["DescricaoProblema"] ?? "");
$titulo = trim($_POST["Titulo"] ?? "");
$servico = trim($_POST["Servico"] ?? "");
$cliente = trim($_POST["Cliente"] ?? "");
$status = trim($_POST["Status"] ?? "");
$codigoOS = trim($_POST["CodigoOS"] ?? "");
$tokenAcompanhamento = trim($_POST["TokenAcompanhamento"] ?? "");

try {
    $empresaId = (int)$_SESSION["EmpresaId"];
    $validacaoIA = empresaPodeUsarRecursoPlano($conn, $empresaId, "ia");

    if (!$validacaoIA["permitido"]) {
        throw new Exception($validacaoIA["mensagem"]);
    }

    if ($tipo === "whatsapp") {
        $validacaoWhatsApp = empresaPodeUsarRecursoPlano($conn, $empresaId, "whatsapp");

        if (!$validacaoWhatsApp["permitido"]) {
            throw new Exception($validacaoWhatsApp["mensagem"]);
        }
    }

    if ($ordemServicoId > 0) {
        exigirOrdemDaEmpresa($conn, $ordemServicoId);
    }

    $empresaNome = $_SESSION["EmpresaNome"] ?? "DirectOS";
    $linkPublico = "";

    if ($tokenAcompanhamento !== "") {
        $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($tokenAcompanhamento);
    }

    if ($tipo === "resumo") {
        $resumo = iaGerarResumoOS($descricaoProblema, [
            "Titulo" => $titulo,
            "Servico" => $servico,
            "Cliente" => $cliente
        ]);

        registrarAuditoria(
            $conn,
            "IA_RESUMO_OS",
            "OS_OrdensServico",
            $ordemServicoId > 0 ? $ordemServicoId : null,
            "Resumo profissional da OS gerado com IA."
        );

        echo json_encode([
            "sucesso" => true,
            "tipo" => "resumo",
            "conteudo" => $resumo
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($tipo === "whatsapp") {
        $mensagem = iaGerarMensagemWhatsAppOS([
            "CodigoOS" => $codigoOS,
            "Cliente" => $cliente,
            "Status" => $status,
            "Titulo" => $titulo,
            "LinkPublico" => $linkPublico,
            "Empresa" => $empresaNome
        ]);

        registrarAuditoria(
            $conn,
            "IA_MENSAGEM_WHATSAPP_OS",
            "OS_OrdensServico",
            $ordemServicoId > 0 ? $ordemServicoId : null,
            "Mensagem de WhatsApp da OS gerada com IA."
        );

        echo json_encode([
            "sucesso" => true,
            "tipo" => "whatsapp",
            "conteudo" => $mensagem
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($tipo === "prioridade") {
        $sugestao = iaSugerirPrioridadeOS($descricaoProblema, [
            "Titulo" => $titulo,
            "Servico" => $servico
        ]);

        registrarAuditoria(
            $conn,
            "IA_PRIORIDADE_OS",
            "OS_OrdensServico",
            $ordemServicoId > 0 ? $ordemServicoId : null,
            "Prioridade da OS sugerida com IA: " . $sugestao["prioridade"]
        );

        echo json_encode([
            "sucesso" => true,
            "tipo" => "prioridade",
            "prioridade" => $sugestao["prioridade"],
            "justificativa" => $sugestao["justificativa"]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($tipo === "checklist") {
        $checklist = iaGerarChecklistTecnicoOS($descricaoProblema, [
            "Titulo" => $titulo,
            "Servico" => $servico
        ]);

        registrarAuditoria(
            $conn,
            "IA_CHECKLIST_OS",
            "OS_OrdensServico",
            $ordemServicoId > 0 ? $ordemServicoId : null,
            "Checklist técnico da OS gerado com IA."
        );

        echo json_encode([
            "sucesso" => true,
            "tipo" => "checklist",
            "conteudo" => $checklist
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception("Tipo de ação IA inválido.");

} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
