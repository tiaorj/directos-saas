<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $id);

$sql = "
    SELECT
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.ValorPrevisto,
        os.ValorFinal,
        os.StatusFinanceiro,
        os.FormaPagamento,
        os.ValorPago,
        os.DataPagamento,
        os.ObservacaoFinanceira,
        os.DataAbertura,
        os.DataConclusao,
        c.Nome AS ClienteNome,
        c.Documento AS ClienteDocumento,
        c.Telefone AS ClienteTelefone,
        c.Email AS ClienteEmail,
        c.Endereco AS ClienteEndereco,
        c.Cidade AS ClienteCidade,
        c.Estado AS ClienteEstado,
        s.Nome AS ServicoNome,
        e.NomeFantasia AS EmpresaNome,
        e.Email AS EmpresaEmail,
        e.WhatsApp AS EmpresaWhatsApp
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    INNER JOIN OS_Empresas e
        ON e.EmpresaId = os.EmpresaId
    WHERE os.OrdemServicoId = :OrdemServicoId
      AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$sqlRecebimentos = "
    SELECT
        RecebimentoId,
        ValorRecebido,
        FormaPagamento,
        DataRecebimento,
        Observacao,
        DataCadastro
    FROM OS_Recebimentos
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
    ORDER BY DataRecebimento ASC, RecebimentoId ASC
";

$stmtRecebimentos = $conn->prepare($sqlRecebimentos);
$stmtRecebimentos->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtRecebimentos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtRecebimentos->execute();

$recebimentos = $stmtRecebimentos->fetchAll(PDO::FETCH_ASSOC);

$codigoOS = $ordem["CodigoOS"] ?? formatarCodigoOS($ordem["OrdemServicoId"], null, $ordem["DataAbertura"] ?? null);

$valorFinal = (float)($ordem["ValorFinal"] ?? 0);
$valorPrevisto = (float)($ordem["ValorPrevisto"] ?? 0);
$valorReferencia = $valorFinal > 0 ? $valorFinal : $valorPrevisto;

$totalRecebido = 0;

foreach ($recebimentos as $recebimento) {
    $totalRecebido += (float)($recebimento["ValorRecebido"] ?? 0);
}

if ($totalRecebido <= 0) {
    $totalRecebido = (float)($ordem["ValorPago"] ?? 0);
}

$saldo = $valorReferencia - $totalRecebido;

if ($saldo < 0) {
    $saldo = 0;
}

function dinheiroRecibo($valor)
{
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}

function dataRecibo($data)
{
    if (empty($data)) {
        return "-";
    }

    return date("d/m/Y", strtotime($data));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo - <?= htmlspecialchars($codigoOS) ?></title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #222;
            margin: 0;
            padding: 30px;
        }

        .recibo {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            padding: 35px;
            border-radius: 8px;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .empresa h1 {
            margin: 0;
            font-size: 26px;
        }

        .empresa p,
        .dados-recibo p {
            margin: 4px 0;
            color: #555;
            font-size: 14px;
        }

        .titulo {
            text-align: center;
            margin: 30px 0;
        }

        .titulo h2 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            background: #ffffff;
        }
        .box h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 25px;
        }

        .item small {
            display: block;
            color: #666;
            margin-bottom: 3px;
        }

        .item strong {
            font-size: 15px;
        }

        .valor-destaque {
            font-size: 32px;
            font-weight: bold;
            color: #198754;
        }

        .valor-saldo {
            font-size: 22px;
            font-weight: bold;
            color: #d39e00;
        }

        .texto-recibo {
            line-height: 1.6;
            font-size: 16px;
            margin: 25px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border-bottom: 1px solid #eee;
            padding: 10px 8px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            color: #333;
        }

        .text-end {
            text-align: right;
        }

        .assinaturas {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px;
            margin-top: 60px;
        }

        .assinatura {
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            font-size: 14px;
        }

        .acoes {
            max-width: 900px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 5px;
            text-decoration: none;
            border: 1px solid #ccc;
            color: #222;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .btn:hover {
            opacity: 0.92;
        }
        .alerta {
            background: #fff8e1;
            border: 1px solid #ffe08a;
            color: #7a5b00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            body {
                background: #fff;
                padding: 12px;
                overflow-x: hidden;
            }

            .acoes {
                width: 100%;
                max-width: 100%;
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 12px;
            }

            .btn {
                text-align: center;
            }

            .recibo {
                max-width: 100%;
                padding: 18px;
                border-radius: 8px;
            }

            .topo {
                flex-direction: column;
                gap: 12px;
            }

            .empresa h1 {
                font-size: 22px;
            }

            .titulo {
                margin: 22px 0;
            }

            .titulo h2 {
                font-size: 22px;
                line-height: 1.2;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .item[style*="grid-column"] {
                grid-column: auto !important;
            }

            .valor-destaque {
                font-size: 26px;
            }

            .valor-saldo {
                font-size: 20px;
            }

            .texto-recibo {
                font-size: 15px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            .assinaturas {
                grid-template-columns: 1fr;
                gap: 44px;
                margin-top: 46px;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .acoes {
                display: none;
            }

            .recibo {
                border: none;
                border-radius: 0;
                box-shadow: none;
                max-width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="acoes">
    <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn">
        Voltar
    </a>

    <button onclick="window.print()" class="btn btn-primary">
        Imprimir / Salvar PDF
    </button>
</div>

<div class="recibo">

    <div class="topo">
        <div class="empresa">
            <h1><?= htmlspecialchars($ordem["EmpresaNome"] ?? "DirectOS") ?></h1>

            <?php if (!empty($ordem["EmpresaEmail"])): ?>
                <p>E-mail: <?= htmlspecialchars($ordem["EmpresaEmail"]) ?></p>
            <?php endif; ?>

            <?php if (!empty($ordem["EmpresaWhatsApp"])): ?>
                <p>WhatsApp: <?= htmlspecialchars($ordem["EmpresaWhatsApp"]) ?></p>
            <?php endif; ?>
        </div>

        <div class="dados-recibo">
            <p><strong>Recibo da OS:</strong> <?= htmlspecialchars($codigoOS) ?></p>
            <p><strong>Data de emissão:</strong> <?= date("d/m/Y") ?></p>
            <p><strong>Status financeiro:</strong> <?= htmlspecialchars($ordem["StatusFinanceiro"] ?? "Pendente") ?></p>
        </div>
    </div>

    <div class="titulo">
        <h2>RECIBO GERAL DA ORDEM DE SERVIÇO</h2>
    </div>

    <?php if (count($recebimentos) === 0 && $totalRecebido <= 0): ?>
        <div class="alerta">
            Esta OS ainda não possui recebimentos registrados.
        </div>
    <?php endif; ?>

    <div class="box">
        <h3>Cliente</h3>

        <div class="grid">
            <div class="item">
                <small>Nome</small>
                <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>Documento</small>
                <strong><?= htmlspecialchars($ordem["ClienteDocumento"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>Telefone</small>
                <strong><?= htmlspecialchars($ordem["ClienteTelefone"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>E-mail</small>
                <strong><?= htmlspecialchars($ordem["ClienteEmail"] ?? "-") ?></strong>
            </div>

            <div class="item" style="grid-column: span 2;">
                <small>Endereço</small>
                <strong>
                    <?= htmlspecialchars($ordem["ClienteEndereco"] ?? "") ?>
                    <?= htmlspecialchars($ordem["ClienteCidade"] ?? "") ?>
                    <?= htmlspecialchars($ordem["ClienteEstado"] ?? "") ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Serviço</h3>

        <div class="grid">
            <div class="item">
                <small>Serviço</small>
                <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>
            </div>

            <div class="item">
                <small>OS</small>
                <strong><?= htmlspecialchars($codigoOS) ?></strong>
            </div>

            <div class="item" style="grid-column: span 2;">
                <small>Título</small>
                <strong><?= htmlspecialchars($ordem["Titulo"] ?? "-") ?></strong>
            </div>
        </div>
    </div>

    <p class="texto-recibo">
        Declaramos que recebemos de <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong>
        o valor total de <strong><?= dinheiroRecibo($totalRecebido) ?></strong>,
        referente aos recebimentos registrados para a ordem de serviço
        <strong><?= htmlspecialchars($codigoOS) ?></strong>,
        relacionada ao serviço <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>.
    </p>

    <div class="box">
        <h3>Resumo financeiro</h3>

        <div class="grid">
            <div class="item">
                <small>Valor da OS</small>
                <strong><?= dinheiroRecibo($valorReferencia) ?></strong>
            </div>

            <div class="item">
                <small>Total recebido</small>
                <div class="valor-destaque">
                    <?= dinheiroRecibo($totalRecebido) ?>
                </div>
            </div>

            <div class="item">
                <small>Saldo restante</small>
                <div class="<?= $saldo > 0 ? "valor-saldo" : "valor-destaque" ?>">
                    <?= dinheiroRecibo($saldo) ?>
                </div>
            </div>

            <div class="item">
                <small>Status financeiro</small>
                <strong><?= htmlspecialchars($ordem["StatusFinanceiro"] ?? "Pendente") ?></strong>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Recebimentos registrados</h3>

        <?php if (count($recebimentos) === 0): ?>
            <p>Nenhum recebimento detalhado registrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Forma</th>
                        <th>Observação</th>
                        <th class="text-end">Valor</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recebimentos as $recebimento): ?>
                        <tr>
                            <td><?= dataRecibo($recebimento["DataRecebimento"] ?? null) ?></td>

                            <td>
                                <?= htmlspecialchars($recebimento["FormaPagamento"] ?? "Não informado") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($recebimento["Observacao"] ?? "-") ?>
                            </td>

                            <td class="text-end">
                                <strong><?= dinheiroRecibo($recebimento["ValorRecebido"] ?? 0) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total recebido</th>
                        <th class="text-end"><?= dinheiroRecibo($totalRecebido) ?></th>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($ordem["ObservacaoFinanceira"])): ?>
        <div class="box">
            <h3>Observação financeira geral</h3>

            <div style="white-space: pre-line;">
                <?= nl2br(htmlspecialchars($ordem["ObservacaoFinanceira"])) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="assinaturas">
        <div class="assinatura">
            <?= htmlspecialchars($ordem["EmpresaNome"] ?? "Empresa") ?>
        </div>

        <div class="assinatura">
            <?= htmlspecialchars($ordem["ClienteNome"] ?? "Cliente") ?>
        </div>
    </div>

</div>

</body>
</html>
