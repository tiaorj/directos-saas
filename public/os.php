<?php
require_once "../config/conexao.php";

$token = $_GET["token"] ?? "";

if ($token === "") {
    die("Link inválido.");
}

$sql = "
    SELECT
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        os.ValorPrevisto,
        os.ValorFinal,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    WHERE os.TokenAcompanhamento = :Token
      AND os.Ativo = 1
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Token", $token);
$stmt->execute();

$os = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$os) {
    die("Ordem de serviço não encontrada.");
}
?>