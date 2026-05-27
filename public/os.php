<?php
require_once "../config/conexao.php";

$token = $_GET["token"] ?? "";

if ($token === "") {
    die("Link de acompanhamento inválido.");
}

$sql = "
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
        e.Email AS EmpresaEmail
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    LEFT JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    WHERE os.TokenAcompanhamento = :Token
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Token", $token);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada ou link inválido.");
}

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

function formatarData($data, $comHora = false)
{
    if (empty($data)) {
        return "-";
    }

    return $comHora
        ? date("d/m/Y H:i", strtotime($data))
        : date("d/m/Y", strtotime($data));
}

function classeStatus($status)
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento da OS</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        body {
            background: #f5f6f8;
        }

        .status-badge {
            font-size: 1rem;
            padding: 10px 14px;
        }

        .timeline-item {
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 18px;
        }

        .card {
            border: none;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="text-center mb-4">
        <h2 class="mb-1">
            <?= htmlspecialchars($ordem["EmpresaNome"] ?? "DirectOS") ?>
        </h2>
        <p class="text-muted mb-0">
            Acompanhamento da Ordem de Serviço
        </p>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body text-center">
            <h4 class="mb-2">
                OS <?= htmlspecialchars($ordem["CodigoOS"] ?? ("#" . $ordem["OrdemServicoId"])) ?>
            </h4>

            <p class="text-muted mb-3">
                <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
            </p>

            <span class="badge status-badge <?= classeStatus($ordem["Status"]) ?>">
                <?= htmlspecialchars($ordem["Status"]) ?>
            </span>
        </div>
    </div>

    <div class="row">

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    Dados do Cliente
                </div>

                <div class="card-body">
                    <strong>Nome:</strong><br>
                    <?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?><br><br>

                    <strong>Telefone:</strong><br>
                    <?= htmlspecialchars($ordem["ClienteTelefone"] ?? "") ?><br><br>

                    <strong>Email:</strong><br>
                    <?= htmlspecialchars($ordem["ClienteEmail"] ?? "") ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    Dados da OS
                </div>

                <div class="card-body">
                    <strong>Serviço:</strong><br>
                    <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?><br><br>

                    <strong>Data de abertura:</strong><br>
                    <?= formatarData($ordem["DataAbertura"], true) ?><br><br>

                    <strong>Previsão:</strong><br>
                    <?= formatarData($ordem["DataPrevisao"]) ?><br><br>

                    <strong>Conclusão:</strong><br>
                    <?= formatarData($ordem["DataConclusao"], true) ?>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Descrição do Problema
        </div>

        <div class="card-body">
            <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"] ?? "")) ?>
        </div>
    </div>

    <?php if (count($anexos) > 0): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                Anexos Disponíveis
            </div>

            <div class="card-body">
                <p class="text-muted">
                    Arquivos disponibilizados pela empresa para acompanhamento desta ordem de serviço.
                </p>

                <div class="row">
                    <?php foreach ($anexos as $anexo): ?>
                        <?php
                            $caminhoArquivo = "../" . $anexo["CaminhoArquivo"];
                            $extensao = strtolower(pathinfo($anexo["NomeOriginal"], PATHINFO_EXTENSION));
                            $ehImagem = in_array($extensao, ["jpg", "jpeg", "png", "gif"]);
                        ?>

                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border">
                                <?php if ($ehImagem): ?>
                                    <a href="<?= htmlspecialchars($caminhoArquivo) ?>" target="_blank">
                                        <img 
                                            src="<?= htmlspecialchars($caminhoArquivo) ?>" 
                                            class="card-img-top" 
                                            style="height: 180px; object-fit: cover;"
                                            alt="Anexo"
                                        >
                                    </a>
                                <?php else: ?>
                                    <div class="card-body text-center">
                                        <div style="font-size: 48px;">
                                            📄
                                        </div>
                                        <p class="mb-0">
                                            Documento
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <h6 class="card-title">
                                        <?= htmlspecialchars($anexo["NomeOriginal"]) ?>
                                    </h6>

                                    <p class="text-muted small mb-2">
                                        <?= number_format(((int)$anexo["TamanhoBytes"] / 1024), 2, ",", ".") ?> KB
                                    </p>

                                    <a 
                                        href="<?= htmlspecialchars($caminhoArquivo) ?>" 
                                        target="_blank"
                                        class="btn btn-sm btn-primary w-100"
                                    >
                                        Abrir Arquivo
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarSolucaoCliente"] ?? 1) === 1 && !empty($ordem["DescricaoSolucao"])): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                Solução Aplicada
            </div>

            <div class="card-body">
                <?= nl2br(htmlspecialchars($ordem["DescricaoSolucao"])) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarValorCliente"] ?? 1) === 1): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                Valores
            </div>

            <div class="card-body">
                <strong>Valor previsto:</strong>
                R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?><br>

                <strong>Valor final:</strong>
                R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarHistoricoCliente"] ?? 1) === 1): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Histórico de Movimentações
        </div>

        <div class="card-body">
            <?php if (count($historicos) === 0): ?>
                <p class="text-muted mb-0">
                    Nenhuma movimentação registrada até o momento.
                </p>
            <?php else: ?>

                <?php foreach ($historicos as $hist): ?>
                    <div class="timeline-item">
                        <strong>
                            <?= formatarData($hist["DataRegistro"], true) ?>
                        </strong>

                        <br>

                        <?php if (!empty($hist["StatusNovo"])): ?>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($hist["StatusNovo"]) ?>
                            </span>
                            <br>
                        <?php endif; ?>

                        <span>
                            <?= nl2br(htmlspecialchars($hist["Descricao"] ?? "")) ?>
                        </span>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($ordem["EmpresaWhatsApp"])): ?>
        <?php
            $whatsapp = preg_replace('/\D/', '', $ordem["EmpresaWhatsApp"]);
        ?>

        <div class="d-grid mb-4">
            <a 
                href="https://wa.me/55<?= $whatsapp ?>" 
                target="_blank"
                class="btn btn-success btn-lg"
            >
                Falar com a empresa pelo WhatsApp
            </a>
        </div>
    <?php endif; ?>

    <p class="text-center text-muted small">
        Acompanhamento gerado pelo DirectOS
    </p>

</div>

</body>
</html>