<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/planos.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
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
    die("Cliente invalido para esta empresa.");
}

if ($servicoId !== null && !servicoAtivoDaEmpresa($conn, $servicoId)) {
    die("Servico invalido para esta empresa.");
}

$sqlCliente = "
    SELECT COUNT(*)
    FROM OS_Clientes
    WHERE ClienteId = :ClienteId
      AND EmpresaId = :EmpresaId
      AND Ativo = 1
";

$stmtCliente = $conn->prepare($sqlCliente);
$stmtCliente->bindValue(":ClienteId", $clienteId, PDO::PARAM_INT);
$stmtCliente->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtCliente->execute();

if ((int)$stmtCliente->fetchColumn() === 0) {
    die("Cliente inválido para esta empresa.");
}

if ($servicoId !== null) {
    $sqlServico = "
        SELECT COUNT(*)
        FROM OS_Servicos
        WHERE ServicoId = :ServicoId
          AND EmpresaId = :EmpresaId
          AND Ativo = 1
    ";

    $stmtServico = $conn->prepare($sqlServico);
    $stmtServico->bindValue(":ServicoId", $servicoId, PDO::PARAM_INT);
    $stmtServico->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtServico->execute();

    if ((int)$stmtServico->fetchColumn() === 0) {
        die("Serviço inválido para esta empresa.");
    }
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

$ordemServicoId = $conn->lastInsertId();
$usuarioId = $_SESSION["UsuarioId"];

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

header("Location: listar.php");
exit;
