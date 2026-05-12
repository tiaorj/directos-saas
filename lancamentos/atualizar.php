<?php
require_once "../config/conexao.php";

$lancamentoId = $_POST["LancamentoId"] ?? 0;
$categoriaId = $_POST["CategoriaId"] !== "" ? $_POST["CategoriaId"] : null;
$descricao = $_POST["Descricao"] ?? "";
$valorEstimado = $_POST["ValorEstimado"] ?? 0;
$valorReal = $_POST["ValorReal"] !== "" ? $_POST["ValorReal"] : 0;
$dataVencimento = $_POST["DataVencimento"] ?? null;
$pago = $_POST["Pago"] ?? 0;
$mesReferencia = $_POST["MesReferencia"] ?? 5;
$anoReferencia = $_POST["AnoReferencia"] ?? 2026;
$observacao = $_POST["Observacao"] ?? "";
$carteiraId = $_POST["CarteiraId"] !== "" ? $_POST["CarteiraId"] : null;

$sql = "
    UPDATE FIN_Lancamentos
    SET
        CategoriaId = :CategoriaId,
        Descricao = :Descricao,
        ValorEstimado = :ValorEstimado,
        ValorReal = :ValorReal,
        DataVencimento = :DataVencimento,
        Pago = :Pago,
        MesReferencia = :MesReferencia,
        AnoReferencia = :AnoReferencia,
        Observacao = :Observacao,
        CarteiraId = :CarteiraId
    WHERE LancamentoId = :LancamentoId
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":CategoriaId", $categoriaId, $categoriaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$stmt->bindValue(":Descricao", $descricao);
$stmt->bindValue(":ValorEstimado", $valorEstimado);
$stmt->bindValue(":ValorReal", $valorReal);
$stmt->bindValue(":DataVencimento", $dataVencimento);
$stmt->bindValue(":Pago", $pago, PDO::PARAM_INT);
$stmt->bindValue(":MesReferencia", $mesReferencia, PDO::PARAM_INT);
$stmt->bindValue(":AnoReferencia", $anoReferencia, PDO::PARAM_INT);
$stmt->bindValue(":Observacao", $observacao);
$stmt->bindValue(":CarteiraId", $carteiraId, $carteiraId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$stmt->bindValue(":LancamentoId", $lancamentoId, PDO::PARAM_INT);

$stmt->execute();

header("Location: listar.php");
exit;