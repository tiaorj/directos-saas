<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/planos.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/n8n.php";
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
$enviarWhatsAppAposSalvar = (int)($_POST["EnviarWhatsAppAposSalvar"] ?? 0) === 1;

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

$mensagemRedirect = "";

if ($enviarWhatsAppAposSalvar) {
    try {
        $sqlDadosEnvio = "
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

        $stmtDadosEnvio = $conn->prepare($sqlDadosEnvio);
        $stmtDadosEnvio->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
        $stmtDadosEnvio->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
        $stmtDadosEnvio->execute();

        $ordem = $stmtDadosEnvio->fetch(PDO::FETCH_ASSOC);

        if (!$ordem) {
            throw new Exception("OS criada, mas não foi possível carregar dados para envio.");
        }

        $telefone = $ordem["ClienteWhatsApp"] ?? "";

        if (trim($telefone) === "") {
            $telefone = $ordem["ClienteTelefone"] ?? "";
        }

        $telefoneNormalizado = normalizarTelefoneWhatsApp($telefone);

        if ($telefoneNormalizado === "") {
            throw new Exception("OS criada, mas cliente não possui WhatsApp/telefone cadastrado.");
        }

        $linkPublico = "";

        if (!empty($ordem["TokenAcompanhamento"])) {
            $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($ordem["TokenAcompanhamento"]);
        }

        $mensagemWhatsApp = "Olá, " . $ordem["ClienteNome"] . "! Sua ordem de serviço " . $codigoOS . " foi registrada com sucesso.";

        if (!empty($ordem["Titulo"])) {
            $mensagemWhatsApp .= "\n\nAtendimento: " . $ordem["Titulo"];
        }

        if (!empty($ordem["Status"])) {
            $mensagemWhatsApp .= "\nStatus atual: " . $ordem["Status"];
        }

        if ($linkPublico !== "") {
            $mensagemWhatsApp .= "\n\nVocê pode acompanhar pelo link:\n" . $linkPublico;
        }

        $mensagemWhatsApp .= "\n\n" . ($ordem["EmpresaNome"] ?? "DirectOS");

        $payload = [
            "origem" => "DirectOS",
            "evento" => "whatsapp_os_criada",
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
                "mensagem" => $mensagemWhatsApp
            ],
            "data_envio" => date("c")
        ];

        n8nEnviarWhatsApp($payload);

        registrarAuditoria(
            $conn,
            "N8N_WHATSAPP_OS_CRIADA",
            "OS_OrdensServico",
            $ordemServicoId,
            "Mensagem de abertura da OS enviada via n8n."
        );

        $mensagemRedirect = "OS criada e WhatsApp enviado com sucesso.";

    } catch (Exception $e) {
        registrarAuditoria(
            $conn,
            "N8N_WHATSAPP_OS_CRIADA_ERRO",
            "OS_OrdensServico",
            $ordemServicoId,
            "Erro ao enviar WhatsApp após criar OS: " . $e->getMessage()
        );

        $mensagemRedirect = "OS criada, mas o WhatsApp não foi enviado: " . $e->getMessage();
    }
} else {
    $mensagemRedirect = "OS criada com sucesso.";
}

header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode($mensagemRedirect));
exit;