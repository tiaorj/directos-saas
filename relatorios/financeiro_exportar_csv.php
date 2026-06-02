<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$dataInicial = trim($_GET["data_inicial"] ?? date("Y-m-01"));
$dataFinal = trim($_GET["data_final"] ?? date("Y-m-d"));
$statusFiltro = trim($_GET["status"] ?? "");
$clienteIdFiltro = (int)($_GET["cliente_id"] ?? 0);
$servicoIdFiltro = (int)($_GET["servico_id"] ?? 0);

$statusPermitidos = [
    "",
    "Aberta",
    "Em andamento",
    "Aguardando cliente",
    "Aguardando peça",
    "Concluída",
    "Cancelada"
];

if (!in_array($statusFiltro, $statusPermitidos, true)) {
    $statusFiltro = "";
}

if ($dataInicial === "") {
    $dataInicial = date("Y-m-01");
}

if ($dataFinal === "") {
    $dataFinal = date("Y-m-d");
}

$where = "
    os.EmpresaId = :EmpresaId
    AND CAST(os.DataAbertura AS DATE) BETWEEN :DataInicial AND :DataFinal
";

$params = [
    ":EmpresaId" => [$empresaId, PDO::PARAM_INT],
    ":DataInicial" => [$dataInicial, PDO::PARAM_STR],
    ":DataFinal" => [$dataFinal, PDO::PARAM_STR],
];

if ($statusFiltro !== "") {
    $where .= " AND os.Status = :Status ";
    $params[":Status"] = [$statusFiltro, PDO::PARAM_STR];
}

if ($clienteIdFiltro > 0) {
    $where .= " AND os.ClienteId = :ClienteId ";
    $params[":ClienteId"] = [$clienteIdFiltro, PDO::PARAM_INT];
}

if ($servicoIdFiltro > 0) {
    $where .= " AND os.ServicoId = :ServicoId ";
    $params[":ServicoId"] = [$servicoIdFiltro, PDO::PARAM_INT];
}

$sql = "
    SELECT
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.StatusFinanceiro,
        os.FormaPagamento,
        os.ValorPrevisto,
        os.ValorFinal,
        os.ValorPago,
        os.DataAbertura,
        os.DataConclusao,
        os.DataPagamento,
        os.ObservacaoFinanceira,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    WHERE {$where}
    ORDER BY os.DataAbertura DESC, os.OrdemServicoId DESC
";

$stmt = $conn->prepare($sql);

foreach ($params as $chave => $param) {
    $stmt->bindValue($chave, $param[0], $param[1]);
}

$stmt->execute();
$ordens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomeArquivo = "financeiro_ordens_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$nomeArquivo}\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    "Código",
    "Cliente",
    "Serviço",
    "Título",
    "Status OS",
    "Status Financeiro",
    "Forma de Pagamento",
    "Data Abertura",
    "Data Conclusão",
    "Data Pagamento",
    "Valor Previsto",
    "Valor Final",
    "Valor Pago",
    "Valor a Receber",
    "Observação Financeira"
], ";");

foreach ($ordens as $ordem) {
    $codigo = formatarCodigoOS(
        $ordem["OrdemServicoId"],
        $ordem["CodigoOS"] ?? null,
        $ordem["DataAbertura"] ?? null
    );

    $valorPrevisto = (float)($ordem["ValorPrevisto"] ?? 0);
    $valorFinal = (float)($ordem["ValorFinal"] ?? 0);
    $valorPago = (float)($ordem["ValorPago"] ?? 0);

    $valorReferencia = $valorFinal > 0 ? $valorFinal : $valorPrevisto;
    $valorAReceber = $valorReferencia - $valorPago;

    if ($valorAReceber < 0) {
        $valorAReceber = 0;
    }

    $dataAbertura = !empty($ordem["DataAbertura"])
        ? date("d/m/Y H:i", strtotime($ordem["DataAbertura"]))
        : "";

    $dataConclusao = !empty($ordem["DataConclusao"])
        ? date("d/m/Y H:i", strtotime($ordem["DataConclusao"]))
        : "";

    $dataPagamento = !empty($ordem["DataPagamento"])
        ? date("d/m/Y", strtotime($ordem["DataPagamento"]))
        : "";

    fputcsv($output, [
        $codigo,
        $ordem["ClienteNome"] ?? "",
        $ordem["ServicoNome"] ?? "Não informado",
        $ordem["Titulo"] ?? "",
        $ordem["Status"] ?? "",
        $ordem["StatusFinanceiro"] ?? "Pendente",
        $ordem["FormaPagamento"] ?? "",
        $dataAbertura,
        $dataConclusao,
        $dataPagamento,
        number_format($valorPrevisto, 2, ",", "."),
        number_format($valorFinal, 2, ",", "."),
        number_format($valorPago, 2, ",", "."),
        number_format($valorAReceber, 2, ",", "."),
        $ordem["ObservacaoFinanceira"] ?? ""
    ], ";");
}

fclose($output);
exit;