<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/planos.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";

csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$validacaoPlano = empresaPodeCriarOS($conn, $empresaId);

if (!$validacaoPlano["permitido"]) {
    die($validacaoPlano["mensagem"]);
}

$clienteId = (int)($_POST["ClienteId"] ?? 0);
$servicoIdPost = $_POST["ServicoId"] ?? "";
$servicoId = $servicoIdPost !== "" ? (int)$servicoIdPost : null;
$titulo = trim($_POST["Titulo"] ?? "");
$descricaoProblema = trim($_POST["DescricaoProblema"] ?? "");
$status = $_POST["Status"] ?? "Aberta";
$prioridade = $_POST["Prioridade"] ?? "Normal";
$valorPrevisto = $_POST["ValorPrevisto"] !== "" ? $_POST["ValorPrevisto"] : null;
$valorFinal = $_POST["ValorFinal"] !== "" ? $_POST["ValorFinal"] : null;
$dataPrevisao = $_POST["DataPrevisao"] !== "" ? $_POST["DataPrevisao"] : null;
$observacao = trim($_POST["Observacao"] ?? "");
$prepararWhatsAppAposSalvar = (int)($_POST["PrepararWhatsAppAposSalvar"] ?? 0) === 1;

if ($clienteId <= 0) {
    die("Cliente é obrigatório.");
}

if ($titulo === "") {
    die("Título é obrigatório.");
}

$dataConclusao = null;

if ($status === "Concluída") {
    $dataConclusao = date("Y-m-d H:i:s");
}

if (!clienteAtivoDaEmpresa($conn, $clienteId)) {
    die("Cliente inválido para esta empresa.");
}

if ($servicoId !== null && !servicoAtivoDaEmpresa($conn, $servicoId)) {
    die("Serviço inválido para esta empresa.");
}

$sql = "
    INSERT INTO OS_OrdensServico
    (
        EmpresaId,
        ClienteId,
        ServicoId,
        Titulo,
        DescricaoProblema,
        Status,
        Prioridade,
        ValorPrevisto,
        ValorFinal,
        DataPrevisao,
        DataConclusao,
        Observacao
    )
    VALUES
    (
        :EmpresaId,
        :ClienteId,
        :ServicoId,
        :Titulo,
        :DescricaoProblema,
        :Status,
        :Prioridade,
        :ValorPrevisto,
        :ValorFinal,
        :DataPrevisao,
        :DataConclusao,
        :Observacao
    )
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->bindValue(":ClienteId", $clienteId, PDO::PARAM_INT);
$stmt->bindValue(":ServicoId", $servicoId, $servicoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$stmt->bindValue(":Titulo", $titulo);
$stmt->bindValue(":DescricaoProblema", $descricaoProblema);
$stmt->bindValue(":Status", $status);
$stmt->bindValue(":Prioridade", $prioridade);
$stmt->bindValue(":ValorPrevisto", $valorPrevisto, $valorPrevisto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ValorFinal", $valorFinal, $valorFinal === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataPrevisao", $dataPrevisao, $dataPrevisao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataConclusao", $dataConclusao, $dataConclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Observacao", $observacao);

$stmt->execute();

$ordemServicoId = (int)$conn->lastInsertId();
$usuarioId = (int)$_SESSION["UsuarioId"];

$anoAtual = date("Y");
$codigoOS = "OS-" . $anoAtual . "-" . str_pad($ordemServicoId, 6, "0", STR_PAD_LEFT);

$sqlCodigo = "
    UPDATE OS_OrdensServico
    SET CodigoOS = :CodigoOS
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmtCodigo = $conn->prepare($sqlCodigo);
$stmtCodigo->bindValue(":CodigoOS", $codigoOS);
$stmtCodigo->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtCodigo->execute();

registrarHistoricoOS(
    $conn,
    $ordemServicoId,
    $usuarioId,
    null,
    $status,
    "Ordem de serviço criada. Código: {$codigoOS}."
);

if ($prepararWhatsAppAposSalvar) {
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

            $linkPublico = "";

            if (!empty($ordemWhatsApp["TokenAcompanhamento"])) {
                $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($ordemWhatsApp["TokenAcompanhamento"]);
            }

            $partesMensagem = [];

            $partesMensagem[] = "Olá, " . $ordemWhatsApp["ClienteNome"] . "! Sua ordem de serviço " . $codigoOS . " foi registrada com sucesso.";

            if (!empty($ordemWhatsApp["Titulo"])) {
                $partesMensagem[] = "Atendimento: " . $ordemWhatsApp["Titulo"] . ".";
            }

            if (!empty($ordemWhatsApp["Status"])) {
                $partesMensagem[] = "Status atual: " . $ordemWhatsApp["Status"] . ".";
            }

            if ($linkPublico !== "") {
                $partesMensagem[] = "Você pode acompanhar pelo link: " . $linkPublico;
            }

            $partesMensagem[] = $ordemWhatsApp["EmpresaNome"] ?? "DirectOS";

            $mensagemWhatsApp = implode(" ", $partesMensagem);
            $mensagemWhatsApp = preg_replace("/\s+/", " ", $mensagemWhatsApp);
            $mensagemWhatsApp = trim($mensagemWhatsApp);

            $_SESSION["WhatsAppAposCriarOS"] = [
                "OrdemServicoId" => $ordemServicoId,
                "Telefone" => $telefone,
                "Mensagem" => $mensagemWhatsApp
            ];

            registrarAuditoria(
                $conn,
                "WHATSAPP_MANUAL_OS_CRIADA",
                "OS_OrdensServico",
                $ordemServicoId,
                "Mensagem de WhatsApp preparada após criação da OS."
            );
        }
    } catch (Exception $e) {
        registrarAuditoria(
            $conn,
            "WHATSAPP_MANUAL_OS_CRIADA_ERRO",
            "OS_OrdensServico",
            $ordemServicoId,
            "Erro ao preparar mensagem WhatsApp: " . $e->getMessage()
        );
    }
}

header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("OS criada com sucesso."));
exit;