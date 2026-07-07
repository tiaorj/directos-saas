<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/mensagens_whatsapp.php";
require_once "../includes/campos_os.php";
require_once "../includes/demo.php";
require_once "../includes/planos.php";
bloquearAcaoDemo();
csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$clienteId = (int)($_POST["ClienteId"] ?? 0);
$servicoIdPost = $_POST["ServicoId"] ?? "";
$servicoId = $servicoIdPost !== "" ? (int)$servicoIdPost : null;
$titulo = trim($_POST["Titulo"] ?? "");
$descricaoProblema = trim($_POST["DescricaoProblema"] ?? "");
$descricaoSolucao = trim($_POST["DescricaoSolucao"] ?? "");
$status = $_POST["Status"] ?? "Aberta";
$prioridade = $_POST["Prioridade"] ?? "Normal";
$valorPrevisto = $_POST["ValorPrevisto"] !== "" ? $_POST["ValorPrevisto"] : null;
$valorFinal = $_POST["ValorFinal"] !== "" ? $_POST["ValorFinal"] : null;
$dataPrevisao = $_POST["DataPrevisao"] !== "" ? $_POST["DataPrevisao"] : null;
$observacao = trim($_POST["Observacao"] ?? "");
$mostrarValorCliente = isset($_POST["MostrarValorCliente"]) ? 1 : 0;
$mostrarSolucaoCliente = isset($_POST["MostrarSolucaoCliente"]) ? 1 : 0;
$mostrarHistoricoCliente = isset($_POST["MostrarHistoricoCliente"]) ? 1 : 0;
$prepararWhatsAppAposAtualizar = (int)($_POST["PrepararWhatsAppAposAtualizar"] ?? 0) === 1;

if ($prepararWhatsAppAposAtualizar) {
    $validacaoWhatsApp = empresaPodeUsarRecursoPlano($conn, $empresaId, "whatsapp");

    if (!$validacaoWhatsApp["permitido"]) {
        die($validacaoWhatsApp["mensagem"]);
    }
}

$camposPersonalizadosOS = buscarCamposPersonalizadosOS($conn, $empresaId, true);
validarCamposPersonalizadosOS($camposPersonalizadosOS, $_POST);

$statusFinanceiro = trim($_POST["StatusFinanceiro"] ?? "Pendente");
$formaPagamento = trim($_POST["FormaPagamento"] ?? "");
$valorPago = $_POST["ValorPago"] !== "" ? $_POST["ValorPago"] : null;
$dataPagamento = trim($_POST["DataPagamento"] ?? "");
$observacaoFinanceira = trim($_POST["ObservacaoFinanceira"] ?? "");

$statusFinanceiroPermitidos = ["Pendente", "Parcial", "Pago", "Cancelado"];

if (!in_array($statusFinanceiro, $statusFinanceiroPermitidos, true)) {
    $statusFinanceiro = "Pendente";
}

if ($dataPagamento === "") {
    $dataPagamento = null;
}

if ($formaPagamento === "") {
    $formaPagamento = null;
}

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

if ($clienteId <= 0) {
    die("Cliente é obrigatório.");
}

if ($titulo === "") {
    die("Título é obrigatório.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

if (!clienteAtivoDaEmpresa($conn, $clienteId)) {
    die("Cliente inválido para esta empresa.");
}

if ($servicoId !== null && !servicoAtivoDaEmpresa($conn, $servicoId)) {
    die("Serviço inválido para esta empresa.");
}

$sqlAtual = "
    SELECT 
        Status, 
        DataConclusao
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId 
      AND EmpresaId = :EmpresaId
";

$stmtAtual = $conn->prepare($sqlAtual);
$stmtAtual->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtAtual->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtAtual->execute();

$ordemAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

if (!$ordemAtual) {
    die("Ordem de serviço não encontrada.");
}

$dataConclusao = $ordemAtual["DataConclusao"];

if ($status === "Concluída" && empty($dataConclusao)) {
    $dataConclusao = date("Y-m-d H:i:s");
}

if ($status !== "Concluída") {
    $dataConclusao = null;
}

$sql = "
    UPDATE OS_OrdensServico
    SET
        ClienteId = :ClienteId,
        ServicoId = :ServicoId,
        Titulo = :Titulo,
        DescricaoProblema = :DescricaoProblema,
        DescricaoSolucao = :DescricaoSolucao,
        Status = :Status,
        Prioridade = :Prioridade,
        ValorPrevisto = :ValorPrevisto,
        ValorFinal = :ValorFinal,
        StatusFinanceiro = :StatusFinanceiro,
        FormaPagamento = :FormaPagamento,
        ValorPago = :ValorPago,
        DataPagamento = :DataPagamento,
        ObservacaoFinanceira = :ObservacaoFinanceira,
        DataPrevisao = :DataPrevisao,
        DataConclusao = :DataConclusao,
        Observacao = :Observacao,
        MostrarValorCliente = :MostrarValorCliente,
        MostrarSolucaoCliente = :MostrarSolucaoCliente,
        MostrarHistoricoCliente = :MostrarHistoricoCliente
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":ClienteId", $clienteId, PDO::PARAM_INT);
$stmt->bindValue(":ServicoId", $servicoId, $servicoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$stmt->bindValue(":Titulo", $titulo);
$stmt->bindValue(":DescricaoProblema", $descricaoProblema);
$stmt->bindValue(":DescricaoSolucao", $descricaoSolucao);
$stmt->bindValue(":Status", $status);
$stmt->bindValue(":Prioridade", $prioridade);
$stmt->bindValue(":ValorPrevisto", $valorPrevisto, $valorPrevisto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ValorFinal", $valorFinal, $valorFinal === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataPrevisao", $dataPrevisao, $dataPrevisao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataConclusao", $dataConclusao, $dataConclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Observacao", $observacao);
$stmt->bindValue(":MostrarValorCliente", $mostrarValorCliente, PDO::PARAM_INT);
$stmt->bindValue(":MostrarSolucaoCliente", $mostrarSolucaoCliente, PDO::PARAM_INT);
$stmt->bindValue(":MostrarHistoricoCliente", $mostrarHistoricoCliente, PDO::PARAM_INT);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->bindValue(":StatusFinanceiro", $statusFinanceiro);
$stmt->bindValue(":FormaPagamento", $formaPagamento, $formaPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ValorPago", $valorPago, $valorPago === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataPagamento", $dataPagamento, $dataPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ObservacaoFinanceira", $observacaoFinanceira);
$stmt->execute();

salvarValoresCamposPersonalizadosOS(
    $conn,
    $empresaId,
    $ordemServicoId,
    $camposPersonalizadosOS,
    $_POST
);

$usuarioId = (int)$_SESSION["UsuarioId"];
$statusAnterior = $ordemAtual["Status"];

$descricaoHistorico = "Ordem de serviço atualizada pela edição completa.";

if ($statusAnterior !== $status) {
    $descricaoHistorico .= " Status alterado de '{$statusAnterior}' para '{$status}'.";
}

registrarHistoricoOS(
    $conn,
    $ordemServicoId,
    $usuarioId,
    $statusAnterior,
    $status,
    $descricaoHistorico
);

if ($prepararWhatsAppAposAtualizar) {
    try {
        $sqlDadosWhatsApp = "
            SELECT
                os.OrdemServicoId,
                os.CodigoOS,
                os.Titulo,
                os.Status,
                os.TokenAcompanhamento,
                c.ClienteId,
                c.Nome AS ClienteNome,
                c.Telefone AS ClienteTelefone,
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

        $stmtDadosWhatsApp = $conn->prepare($sqlDadosWhatsApp);
        $stmtDadosWhatsApp->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
        $stmtDadosWhatsApp->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
        $stmtDadosWhatsApp->execute();

        $ordemWhatsApp = $stmtDadosWhatsApp->fetch(PDO::FETCH_ASSOC);

        if ($ordemWhatsApp) {
            $telefone = preg_replace('/\D/', '', $ordemWhatsApp["ClienteTelefone"] ?? "");

            if ($telefone !== "" && (strlen($telefone) === 10 || strlen($telefone) === 11)) {
                $telefone = "55" . $telefone;
            }

            $codigoOS = $ordemWhatsApp["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordemServicoId, 6, "0", STR_PAD_LEFT));

            $linkPublico = "";

            if (!empty($ordemWhatsApp["TokenAcompanhamento"])) {
                $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($ordemWhatsApp["TokenAcompanhamento"]);
            }

            $partesMensagem = [];

            $partesMensagem[] = "Olá, " . $ordemWhatsApp["ClienteNome"] . "! Sua ordem de serviço " . $codigoOS . " recebeu uma atualização.";

            if ($statusAnterior !== $status) {
                $partesMensagem[] = "Status alterado de " . $statusAnterior . " para " . $status . ".";
            } else {
                $partesMensagem[] = "Status atual: " . $status . ".";
            }

            if (!empty($ordemWhatsApp["Titulo"])) {
                $partesMensagem[] = "Atendimento: " . $ordemWhatsApp["Titulo"] . ".";
            }

            if ($linkPublico !== "") {
                $partesMensagem[] = "Você pode acompanhar pelo link: " . $linkPublico;
            }

            $partesMensagem[] = $ordemWhatsApp["EmpresaNome"] ?? "DirectOS";

            $mensagemWhatsApp = implode(" ", $partesMensagem);
            $mensagemWhatsApp = preg_replace("/\s+/", " ", $mensagemWhatsApp);
            $mensagemWhatsApp = trim($mensagemWhatsApp);

            $_SESSION["WhatsAppAposAtualizarOS"] = [
                "OrdemServicoId" => $ordemServicoId,
                "Telefone" => $telefone,
                "Mensagem" => $mensagemWhatsApp
            ];

            registrarMensagemWhatsAppOS(
                $conn,
                $empresaId,
                $ordemServicoId,
                $usuarioId,
                "ATUALIZACAO_OS",
                "MANUAL",
                $telefone,
                $mensagemWhatsApp
            );

            registrarAuditoria(
                $conn,
                "WHATSAPP_MANUAL_OS_ATUALIZADA",
                "OS_OrdensServico",
                $ordemServicoId,
                "Mensagem de WhatsApp preparada após atualização da OS."
            );
        }
    } catch (Exception $e) {
        registrarAuditoria(
            $conn,
            "WHATSAPP_MANUAL_OS_ATUALIZADA_ERRO",
            "OS_OrdensServico",
            $ordemServicoId,
            "Erro ao preparar mensagem WhatsApp após atualização: " . $e->getMessage()
        );
    }
}

header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("OS atualizada com sucesso."));
exit;
