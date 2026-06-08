<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$recebimentoId = (int)($_GET["id"] ?? 0);

if ($recebimentoId <= 0) {
    die("Recebimento inválido.");
}

$sql = "
    SELECT
        r.RecebimentoId,
        r.OrdemServicoId,
        r.ValorRecebido,
        r.FormaPagamento,
        r.DataRecebimento,
        r.Observacao,
        r.DataCadastro,

        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.ValorPrevisto,
        os.ValorFinal,
        os.StatusFinanceiro,
        os.ValorPago,
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
    FROM OS_Recebimentos r
    INNER JOIN OS_OrdensServico os
        ON os.OrdemServicoId = r.OrdemServicoId
       AND os.EmpresaId = r.EmpresaId
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    INNER JOIN OS_Empresas e
        ON e.EmpresaId = os.EmpresaId
    WHERE r.RecebimentoId = :RecebimentoId
      AND r.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":RecebimentoId", $recebimentoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recibo) {
    die("Recebimento não encontrado.");
}

$ordemServicoId = (int)$recibo["OrdemServicoId"];

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$sqlTotal = "
    SELECT
        ISNULL(SUM(ValorRecebido), 0) AS TotalRecebido
    FROM OS_Recebimentos
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtTotal->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtTotal->execute();

$totalRecebido = (float)$stmtTotal->fetchColumn();

$codigoOS = $recibo["CodigoOS"] ?? formatarCodigoOS(
    $recibo["OrdemServicoId"],
    null,
    $recibo["DataAbertura"] ?? null
);

$valorFinal = (float)($recibo["ValorFinal"] ?? 0);
$valorPrevisto = (float)($recibo["ValorPrevisto"] ?? 0);
$valorReferencia = $valorFinal > 0 ? $valorFinal : $valorPrevisto;

$valorRecebidoAtual = (float)($recibo["ValorRecebido"] ?? 0);

$saldo = $valorReferencia - $totalRecebido;

if ($saldo < 0) {
    $saldo = 0;
}

function dinheiroReciboIndividual($valor)
{
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}

function dataReciboIndividual($data)
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
    <title>Recibo de Recebimento - <?= htmlspecialchars($codigoOS) ?></title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef2f7;
            color: #1f2937;
            margin: 0;
            padding: 30px;
        }

        .recibo {
            max-width: 920px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 38px;
            border-radius: 12px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
        }

        .topo {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 25px;
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

        .titulo p {
            margin-top: 8px;
            color: #666;
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

        .texto-recibo {
            line-height: 1.6;
            font-size: 16px;
            margin: 25px 0;
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
            max-width: 850px;
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
                box-shadow: none;
            }

            .topo {
                flex-direction: column;
                gap: 12px;
            }

            .empresa h1 {
                font-size: 22px;
                margin-bottom: 8px;
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

            .texto-recibo {
                font-size: 15px;
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
    <a href="recebimento.php?id=<?= (int)$ordemServicoId ?>" class="btn">
        Voltar
    </a>

    <button onclick="window.print()" class="btn btn-primary">
        Imprimir / Salvar PDF
    </button>
</div>

<div class="recibo">

    <div class="topo">
        <div class="empresa">
            <h1><?= htmlspecialchars($recibo["EmpresaNome"] ?? "DirectOS") ?></h1>

            <?php if (!empty($recibo["EmpresaEmail"])): ?>
                <p>E-mail: <?= htmlspecialchars($recibo["EmpresaEmail"]) ?></p>
            <?php endif; ?>

            <?php if (!empty($recibo["EmpresaWhatsApp"])): ?>
                <p>WhatsApp: <?= htmlspecialchars($recibo["EmpresaWhatsApp"]) ?></p>
            <?php endif; ?>
        </div>

        <div class="dados-recibo">
            <p><strong>Recibo nº:</strong> <?= (int)$recibo["RecebimentoId"] ?></p>
            <p><strong>OS:</strong> <?= htmlspecialchars($codigoOS) ?></p>
            <p><strong>Emissão:</strong> <?= date("d/m/Y") ?></p>
        </div>
    </div>

    <div class="titulo">
        <h2>RECIBO DE PAGAMENTO INDIVIDUAL</h2>
        <p>Comprovante referente a um recebimento específico registrado na ordem de serviço.</p>
    </div>

    <div class="box">
        <h3>Cliente</h3>

        <div class="grid">
            <div class="item">
                <small>Nome</small>
                <strong><?= htmlspecialchars($recibo["ClienteNome"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>Documento</small>
                <strong><?= htmlspecialchars($recibo["ClienteDocumento"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>Telefone</small>
                <strong><?= htmlspecialchars($recibo["ClienteTelefone"] ?? "-") ?></strong>
            </div>

            <div class="item">
                <small>E-mail</small>
                <strong><?= htmlspecialchars($recibo["ClienteEmail"] ?? "-") ?></strong>
            </div>

            <div class="item" style="grid-column: span 2;">
                <small>Endereço</small>
                <strong>
                    <?= htmlspecialchars($recibo["ClienteEndereco"] ?? "") ?>
                    <?= htmlspecialchars($recibo["ClienteCidade"] ?? "") ?>
                    <?= htmlspecialchars($recibo["ClienteEstado"] ?? "") ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Serviço</h3>

        <div class="grid">
            <div class="item">
                <small>Serviço</small>
                <strong><?= htmlspecialchars($recibo["ServicoNome"] ?? "Não informado") ?></strong>
            </div>

            <div class="item">
                <small>OS</small>
                <strong><?= htmlspecialchars($codigoOS) ?></strong>
            </div>

            <div class="item" style="grid-column: span 2;">
                <small>Título</small>
                <strong><?= htmlspecialchars($recibo["Titulo"] ?? "-") ?></strong>
            </div>
        </div>
    </div>

    <p class="texto-recibo">
        Declaramos que recebemos de <strong><?= htmlspecialchars($recibo["ClienteNome"] ?? "-") ?></strong>
        o valor de <strong><?= dinheiroReciboIndividual($valorRecebidoAtual) ?></strong>,
        referente a um pagamento registrado na ordem de serviço
        <strong><?= htmlspecialchars($codigoOS) ?></strong>.
    </p>

    <div class="box">
        <h3>Dados deste recebimento</h3>

        <div class="grid">
            <div class="item">
                <small>Valor recebido</small>
                <div class="valor-destaque">
                    <?= dinheiroReciboIndividual($valorRecebidoAtual) ?>
                </div>
            </div>

            <div class="item">
                <small>Data do recebimento</small>
                <strong><?= dataReciboIndividual($recibo["DataRecebimento"] ?? null) ?></strong>
            </div>

            <div class="item">
                <small>Forma de pagamento</small>
                <strong><?= htmlspecialchars($recibo["FormaPagamento"] ?? "Não informado") ?></strong>
            </div>

            <div class="item">
                <small>Registrado em</small>
                <strong><?= !empty($recibo["DataCadastro"]) ? date("d/m/Y H:i", strtotime($recibo["DataCadastro"])) : "-" ?></strong>
            </div>

            <?php if (!empty($recibo["Observacao"])): ?>
                <div class="item" style="grid-column: span 2;">
                    <small>Observação</small>
                    <strong><?= nl2br(htmlspecialchars($recibo["Observacao"])) ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="box">
        <h3>Resumo da OS</h3>

        <div class="grid">
            <div class="item">
                <small>Valor total da OS</small>
                <strong><?= dinheiroReciboIndividual($valorReferencia) ?></strong>
            </div>

            <div class="item">
                <small>Total já recebido na OS</small>
                <strong><?= dinheiroReciboIndividual($totalRecebido) ?></strong>
            </div>

            <div class="item">
                <small>Saldo restante</small>
                <strong><?= dinheiroReciboIndividual($saldo) ?></strong>
            </div>

            <div class="item">
                <small>Status financeiro atual</small>
                <strong><?= htmlspecialchars($recibo["StatusFinanceiro"] ?? "Pendente") ?></strong>
            </div>
        </div>
    </div>

    <div class="assinaturas">
        <div class="assinatura">
            <?= htmlspecialchars($recibo["EmpresaNome"] ?? "Empresa") ?>
        </div>

        <div class="assinatura">
            <?= htmlspecialchars($recibo["ClienteNome"] ?? "Cliente") ?>
        </div>
    </div>

</div>

</body>
</html>
