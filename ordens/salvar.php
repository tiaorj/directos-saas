<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";

exigirPerfil(["Admin", "Atendente"]);

$clienteId = $_POST["ClienteId"] ?? 0;
$servicoId = $_POST["ServicoId"] !== "" ? $_POST["ServicoId"] : null;
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

$sql = "
    INSERT INTO OS_OrdensServico
    (
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

registrarHistoricoOS(
    $conn,
    $ordemServicoId,
    $_SESSION["UsuarioId"],
    null,
    $status,
    "Ordem de serviço criada."
);

header("Location: listar.php");
exit;