<?php
require_once "../config/conexao.php";

$token = $_GET["token"] ?? "";

if ($token === "") {
    die("Link de acompanhamento inválido.");
}

$sql = "
    SELECT
        os.OrdemServicoId,
        os.EmpresaId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        os.ValorPrevisto,
        os.ValorFinal,
        os.DescricaoProblema,
        os.DescricaoSolucao,
        os.MostrarValorCliente,
        os.MostrarSolucaoCliente,
        os.MostrarHistoricoCliente,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        c.Email AS ClienteEmail,
        s.Nome AS ServicoNome,
        e.NomeFantasia AS EmpresaNome,
        e.Telefone AS EmpresaTelefone,
        e.WhatsApp AS EmpresaWhatsApp,
        e.Email AS EmpresaEmail,
        p.PermiteAreaCliente,
        p.PermiteAnexos,
        p.PermiteWhatsapp
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId AND s.EmpresaId = os.EmpresaId
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    INNER JOIN OS_Planos p ON p.PlanoId = e.PlanoId
    WHERE os.TokenAcompanhamento = :Token
      AND e.Ativo = 1
      AND p.Ativo = 1
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Token", $token);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada ou link inválido.");
}

if ((int)($ordem["PermiteAreaCliente"] ?? 0) !== 1) {
    die("A área do cliente não está disponível no plano atual desta empresa.");
}

$historicos = [];

if ((int)($ordem["MostrarHistoricoCliente"] ?? 1) === 1) {
    $sqlHistorico = "
        SELECT
            StatusAnterior,
            StatusNovo,
            Descricao,
            DataRegistro
        FROM OS_Historico
        WHERE OrdemServicoId = :OrdemServicoId
        ORDER BY DataRegistro DESC
    ";

    $stmtHistorico = $conn->prepare($sqlHistorico);
    $stmtHistorico->bindValue(":OrdemServicoId", $ordem["OrdemServicoId"], PDO::PARAM_INT);
    $stmtHistorico->execute();
    $historicos = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);
}

$anexos = [];

if ((int)($ordem["PermiteAnexos"] ?? 0) === 1) {
    $sqlAnexos = "
        SELECT
            AnexoId,
            NomeOriginal,
            CaminhoArquivo,
            TipoArquivo,
            TamanhoBytes,
            DataCadastro
        FROM OS_OrdensServicoAnexos
        WHERE OrdemServicoId = :OrdemServicoId
          AND VisivelCliente = 1
        ORDER BY AnexoId DESC
    ";

    $stmtAnexos = $conn->prepare($sqlAnexos);
    $stmtAnexos->bindValue(":OrdemServicoId", $ordem["OrdemServicoId"], PDO::PARAM_INT);
    $stmtAnexos->execute();
    $anexos = $stmtAnexos->fetchAll(PDO::FETCH_ASSOC);
}

function formatarDataPublica($data, $comHora = false)
{
    if (empty($data)) {
        return "-";
    }

    return $comHora
        ? date("d/m/Y H:i", strtotime($data))
        : date("d/m/Y", strtotime($data));
}

function classeStatusPublica($status)
{
    if ($status === "Aberta") {
        return "bg-primary";
    }

    if ($status === "Em andamento") {
        return "bg-warning text-dark";
    }

    if ($status === "Concluída") {
        return "bg-success";
    }

    if ($status === "Cancelada") {
        return "bg-danger";
    }

    return "bg-secondary";
}

$codigoOS = $ordem["CodigoOS"] ?? ("#" . $ordem["OrdemServicoId"]);
$whatsappEmpresa = preg_replace('/\D/', '', $ordem["EmpresaWhatsApp"] ?? "");
$permiteWhatsapp = (int)($ordem["PermiteWhatsapp"] ?? 0) === 1;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento da OS - <?= htmlspecialchars($codigoOS) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<header class="bg-dark text-white py-4 mb-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h4 mb-1"><?= htmlspecialchars($ordem["EmpresaNome"] ?? "DirectOS") ?></h1>
                <div class="text-white-50">Acompanhamento online da ordem de serviço</div>
            </div>
            <span class="badge <?= classeStatusPublica($ordem["Status"] ?? "") ?> fs-6">
                <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
            </span>
        </div>
    </div>
</header>

<main class="container pb-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="text-muted mb-1">Ordem de Serviço</div>
            <h2 class="mb-1"><?= htmlspecialchars($codigoOS) ?></h2>
            <p class="lead mb-0"><?= htmlspecialchars($ordem["Titulo"] ?? "") ?></p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>Cliente</strong></div>
                <div class="card-body">
                    <div><strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong></div>
                    <div class="text-muted"><?= htmlspecialchars($ordem["ClienteTelefone"] ?? "") ?></div>
                    <div class="text-muted"><?= htmlspecialchars($ordem["ClienteEmail"] ?? "") ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>Dados da OS</strong></div>
                <div class="card-body">
                    <div><strong>Serviço:</strong> <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></div>
                    <div><strong>Prioridade:</strong> <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?></div>
                    <div><strong>Abertura:</strong> <?= formatarDataPublica($ordem["DataAbertura"] ?? null, true) ?></div>
                    <div><strong>Previsão:</strong> <?= formatarDataPublica($ordem["DataPrevisao"] ?? null) ?></div>
                    <div><strong>Conclusão:</strong> <?= formatarDataPublica($ordem["DataConclusao"] ?? null, true) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Descrição do Problema</strong></div>
        <div class="card-body">
            <?= !empty($ordem["DescricaoProblema"]) ? nl2br(htmlspecialchars($ordem["DescricaoProblema"])) : '<span class="text-muted">Nenhuma descrição informada.</span>' ?>
        </div>
    </div>

    <?php if ((int)($ordem["MostrarSolucaoCliente"] ?? 1) === 1 && !empty($ordem["DescricaoSolucao"])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Solução Aplicada</strong></div>
            <div class="card-body"><?= nl2br(htmlspecialchars($ordem["DescricaoSolucao"])) ?></div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarValorCliente"] ?? 1) === 1): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Valores</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>Valor previsto:</strong> R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?></div>
                    <div class="col-md-6"><strong>Valor final:</strong> R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($anexos) > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Anexos Disponíveis</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($anexos as $anexo): ?>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <strong><?= htmlspecialchars($anexo["NomeOriginal"] ?? "Anexo") ?></strong>
                                <div class="text-muted small mb-3"><?= number_format(((int)$anexo["TamanhoBytes"] / 1024), 2, ",", ".") ?> KB</div>
                                <a href="anexo.php?id=<?= (int)$anexo["AnexoId"] ?>&token=<?= urlencode($token) ?>" target="_blank" class="btn btn-sm btn-primary w-100">Abrir arquivo</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($historicos) > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Histórico</strong></div>
            <div class="card-body">
                <?php foreach ($historicos as $hist): ?>
                    <div class="border-start border-3 border-primary ps-3 mb-3">
                        <div class="small text-muted"><?= formatarDataPublica($hist["DataRegistro"] ?? null, true) ?></div>
                        <?php if (!empty($hist["StatusNovo"])): ?><span class="badge <?= classeStatusPublica($hist["StatusNovo"]) ?> mb-1"><?= htmlspecialchars($hist["StatusNovo"]) ?></span><?php endif; ?>
                        <div><?= nl2br(htmlspecialchars($hist["Descricao"] ?? "")) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($permiteWhatsapp && $whatsappEmpresa !== ""): ?>
        <div class="d-grid mb-4">
            <a href="https://wa.me/55<?= htmlspecialchars($whatsappEmpresa) ?>" target="_blank" class="btn btn-success btn-lg">Falar com a empresa pelo WhatsApp</a>
        </div>
    <?php endif; ?>
</main>

<footer class="text-center text-muted py-4">
    <strong>DirectOS</strong>
    <div>Acompanhamento online de ordem de serviço.</div>
</footer>

</body>
</html>
