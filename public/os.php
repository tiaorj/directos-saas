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

    if ($status === "Aguardando cliente" || $status === "Aguardando peça") {
        return "bg-secondary";
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento da OS - <?= htmlspecialchars($codigoOS) ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        :root {
            --public-bg: #f3f4f6;
            --public-dark: #111827;
            --public-primary: #2563eb;
            --public-muted: #64748b;
        }

        body {
            background: var(--public-bg);
            color: #111827;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .public-header {
            background: linear-gradient(135deg, #111827, #2563eb);
            color: #fff;
            padding: 34px 0 92px;
        }

        .public-brand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 36px;
        }

        .brand-name {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .brand-badge {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
        }

        .hero-card {
            background: #fff;
            color: #111827;
            border: 0;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            margin-top: -70px;
        }

        .hero-title {
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .status-badge {
            font-size: 0.95rem;
            padding: 10px 14px;
            border-radius: 12px;
        }

        .public-container {
            max-width: 1120px;
        }

        .info-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            height: 100%;
        }

        .info-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 800;
            border-radius: 18px 18px 0 0;
        }

        .label-small {
            color: var(--public-muted);
            font-size: 0.82rem;
            margin-bottom: 2px;
        }

        .value-strong {
            font-weight: 700;
        }

        .attachment-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
        }

        .attachment-thumb {
            height: 180px;
            object-fit: cover;
            width: 100%;
            background: #f8fafc;
        }

        .doc-placeholder {
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #f8fafc;
            color: #64748b;
        }

        .timeline-item {
            border-left: 3px solid var(--public-primary);
            padding-left: 14px;
            margin-bottom: 18px;
        }

        .timeline-date {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 700;
        }

        .footer-public {
            color: #64748b;
            padding: 28px 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .public-header {
                padding-bottom: 82px;
            }

            .public-brand {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-card {
                margin-top: -62px;
            }
        }
    </style>
</head>
<body>

<header class="public-header">
    <div class="container public-container">

        <div class="public-brand">
            <div>
                <div class="brand-name">
                    <?= htmlspecialchars($ordem["EmpresaNome"] ?? "DirectOS") ?>
                </div>

                <div style="color: rgba(255,255,255,.76);">
                    Acompanhamento online da ordem de serviço
                </div>
            </div>

            <div class="brand-badge">
                DirectOS
            </div>
        </div>

    </div>
</header>

<main class="container public-container pb-4">

    <div class="card hero-card mb-4">
        <div class="card-body p-4 p-md-5">

            <div class="row align-items-center">

                <div class="col-md-8">
                    <div class="text-muted mb-2">
                        Ordem de Serviço
                    </div>

                    <h1 class="hero-title mb-2">
                        <?= htmlspecialchars($codigoOS) ?>
                    </h1>

                    <p class="lead text-muted mb-0">
                        <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
                    </p>
                </div>

                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <span class="badge status-badge <?= classeStatusPublica($ordem["Status"] ?? "") ?>">
                        <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                    </span>
                </div>

            </div>

        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card info-card">
                <div class="card-header">
                    Dados do Cliente
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <div class="label-small">Nome</div>
                            <div class="value-strong">
                                <?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="label-small">Telefone</div>
                            <div class="value-strong">
                                <?= htmlspecialchars($ordem["ClienteTelefone"] ?? "-") ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="label-small">E-mail</div>
                            <div class="value-strong">
                                <?= htmlspecialchars($ordem["ClienteEmail"] ?? "-") ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card info-card">
                <div class="card-header">
                    Dados da OS
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="label-small">Serviço</div>
                            <div class="value-strong">
                                <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="label-small">Prioridade</div>
                            <div class="value-strong">
                                <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="label-small">Abertura</div>
                            <div class="value-strong">
                                <?= formatarDataPublica($ordem["DataAbertura"], true) ?>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="label-small">Previsão</div>
                            <div class="value-strong">
                                <?= formatarDataPublica($ordem["DataPrevisao"]) ?>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="label-small">Conclusão</div>
                            <div class="value-strong">
                                <?= formatarDataPublica($ordem["DataConclusao"], true) ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card info-card mb-4">
        <div class="card-header">
            Descrição do Problema
        </div>

        <div class="card-body">
            <?php if (!empty($ordem["DescricaoProblema"])): ?>
                <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"])) ?>
            <?php else: ?>
                <span class="text-muted">Nenhuma descrição informada.</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($anexos) > 0): ?>
        <div class="card info-card mb-4">
            <div class="card-header">
                Anexos Disponíveis
            </div>

            <div class="card-body">
                <p class="text-muted">
                    Arquivos disponibilizados pela empresa para acompanhamento desta ordem de serviço.
                </p>

                <div class="row g-3">
                    <?php foreach ($anexos as $anexo): ?>
                        <?php
                            $linkAnexo = "anexo.php?id=" . (int)$anexo["AnexoId"] . "&token=" . urlencode($token);
                            $extensao = strtolower(pathinfo($anexo["NomeOriginal"], PATHINFO_EXTENSION));
                            $ehImagem = in_array($extensao, ["jpg", "jpeg", "png", "gif"]);
                        ?>

                        <div class="col-md-4">
                            <div class="attachment-card">
                                <?php if ($ehImagem): ?>
                                    <a href="<?= htmlspecialchars($linkAnexo) ?>" target="_blank">
                                        <img 
                                            src="<?= htmlspecialchars($linkAnexo) ?>" 
                                            class="attachment-thumb"
                                            alt="Anexo"
                                        >
                                    </a>
                                <?php else: ?>
                                    <div class="doc-placeholder">
                                        <div style="font-size: 48px;">📄</div>
                                        <div>Documento</div>
                                    </div>
                                <?php endif; ?>

                                <div class="p-3">
                                    <strong>
                                        <?= htmlspecialchars($anexo["NomeOriginal"]) ?>
                                    </strong>

                                    <div class="text-muted small mb-3">
                                        <?= number_format(((int)$anexo["TamanhoBytes"] / 1024), 2, ",", ".") ?> KB
                                    </div>

                                    <a 
                                        href="<?= htmlspecialchars($linkAnexo) ?>" 
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
        <div class="card info-card mb-4">
            <div class="card-header">
                Solução Aplicada
            </div>

            <div class="card-body">
                <?= nl2br(htmlspecialchars($ordem["DescricaoSolucao"])) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarValorCliente"] ?? 1) === 1): ?>
        <div class="card info-card mb-4">
            <div class="card-header">
                Valores
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="label-small">Valor previsto</div>
                        <h5 class="mb-0">
                            R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?>
                        </h5>
                    </div>

                    <div class="col-md-6">
                        <div class="label-small">Valor final</div>
                        <h5 class="mb-0">
                            R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($ordem["MostrarHistoricoCliente"] ?? 1) === 1): ?>
        <div class="card info-card mb-4">
            <div class="card-header">
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
                            <div class="timeline-date">
                                <?= formatarDataPublica($hist["DataRegistro"], true) ?>
                            </div>

                            <?php if (!empty($hist["StatusNovo"])): ?>
                                <span class="badge <?= classeStatusPublica($hist["StatusNovo"]) ?> mt-1 mb-1">
                                    <?= htmlspecialchars($hist["StatusNovo"]) ?>
                                </span>
                            <?php endif; ?>

                            <div>
                                <?= nl2br(htmlspecialchars($hist["Descricao"] ?? "")) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($whatsappEmpresa !== ""): ?>
        <div class="d-grid mb-4">
            <a 
                href="https://wa.me/55<?= $whatsappEmpresa ?>" 
                target="_blank"
                class="btn btn-success btn-lg"
            >
                Falar com a empresa pelo WhatsApp
            </a>
        </div>
    <?php endif; ?>

</main>

<footer class="footer-public text-center">
    <div class="container public-container">
        <strong>DirectOS</strong>
        <div>
            Acompanhamento online de ordem de serviço.
        </div>
    </div>
</footer>

</body>
</html>