<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/seguranca.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$ordemServicoId = $_POST["OrdemServicoId"] ?? 0;
$clienteId = $_POST["ClienteId"] ?? 0;
$servicoId = $_POST["ServicoId"] !== "" ? $_POST["ServicoId"] : null;
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

exigirOrdemServicoDaEmpresa($conn, $ordemServicoId);

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

if ($clienteId <= 0) {
    die("Cliente é obrigatório.");
}

if ($titulo === "") {
    die("Título é obrigatório.");
}

$sqlAtual = "
    SELECT 
        Status, 
        DataConclusao
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
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
        DataPrevisao = :DataPrevisao,
        DataConclusao = :DataConclusao,
        Observacao = :Observacao,
        MostrarValorCliente = :MostrarValorCliente,
        MostrarSolucaoCliente = :MostrarSolucaoCliente,
        MostrarHistoricoCliente = :MostrarHistoricoCliente
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
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
$stmt->execute();

$usuarioId = $_SESSION["UsuarioId"];
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

header("Location: visualizar.php?id=" . $ordemServicoId);
exit;