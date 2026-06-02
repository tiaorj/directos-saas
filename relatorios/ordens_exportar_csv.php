<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente", "Tecnico"]);

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

$sqlOrdens = "
    SELECT
        os.OrdemServicoId,
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
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    WHERE {$where}
    ORDER BY os.DataAbertura DESC, os.OrdemServicoId DESC
";

$stmtOrdens = $conn->prepare($sqlOrdens);

foreach ($params as $chave => $param) {
    $stmtOrdens->bindValue($chave, $param[0], $param[1]);
}

$stmtOrdens->execute();
$ordens = $stmtOrdens->fetchAll(PDO::FETCH_ASSOC);

$nomeArquivo = "ordens_servico_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$nomeArquivo}\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

/*
    BOM UTF-8 para abrir corretamente no Excel com acentos.
*/
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    "Código",
    "Cliente",
    "Serviço",
    "Título",
    "Status",
    "Prioridade",
    "Data Abertura",
    "Data Previsão",
    "Data Conclusão",
    "Valor Previsto",
    "Valor Final"
], ";");

foreach ($ordens as $ordem) {
    $codigo = formatarCodigoOS(
        $ordem["OrdemServicoId"],
        $ordem["CodigoOS"] ?? null,
        $ordem["DataAbertura"] ?? null
    );

    $dataAbertura = !empty($ordem["DataAbertura"])
        ? date("d/m/Y H:i", strtotime($ordem["DataAbertura"]))
        : "";

    $dataPrevisao = !empty($ordem["DataPrevisao"])
        ? date("d/m/Y", strtotime($ordem["DataPrevisao"]))
        : "";

    $dataConclusao = !empty($ordem["DataConclusao"])
        ? date("d/m/Y H:i", strtotime($ordem["DataConclusao"]))
        : "";

    $valorPrevisto = number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".");
    $valorFinal = number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".");

    fputcsv($output, [
        $codigo,
        $ordem["ClienteNome"] ?? "",
        $ordem["ServicoNome"] ?? "Não informado",
        $ordem["Titulo"] ?? "",
        $ordem["Status"] ?? "",
        $ordem["Prioridade"] ?? "",
        $dataAbertura,
        $dataPrevisao,
        $dataConclusao,
        $valorPrevisto,
        $valorFinal
    ], ";");
}

fclose($output);
exit;