<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/campos_os.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $id);

$sql = "
    SELECT 
        os.*,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        c.Email AS ClienteEmail,
        c.Documento AS ClienteDocumento,
        c.Endereco AS ClienteEndereco,
        c.Cidade AS ClienteCidade,
        c.Estado AS ClienteEstado,
        s.Nome AS ServicoNome,
        os.StatusFinanceiro,
        os.FormaPagamento,
        os.ValorPago,
        os.DataPagamento,
        os.ObservacaoFinanceira
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId AND s.EmpresaId = os.EmpresaId
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

$whatsAppAposCriarOS = null;

if (
    isset($_SESSION["WhatsAppAposCriarOS"]) &&
    (int)($_SESSION["WhatsAppAposCriarOS"]["OrdemServicoId"] ?? 0) === $id
) {
    $whatsAppAposCriarOS = $_SESSION["WhatsAppAposCriarOS"];
    unset($_SESSION["WhatsAppAposCriarOS"]);
}

$whatsAppAposAtualizarOS = null;

if (
    isset($_SESSION["WhatsAppAposAtualizarOS"]) &&
    (int)($_SESSION["WhatsAppAposAtualizarOS"]["OrdemServicoId"] ?? 0) === $id
) {
    $whatsAppAposAtualizarOS = $_SESSION["WhatsAppAposAtualizarOS"];
    unset($_SESSION["WhatsAppAposAtualizarOS"]);
}

$whatsAppManualAvulsoOS = null;

if (
    isset($_SESSION["WhatsAppManualOS"]) &&
    (int)($_SESSION["WhatsAppManualOS"]["OrdemServicoId"] ?? 0) === $id
) {
    $whatsAppManualAvulsoOS = $_SESSION["WhatsAppManualOS"];
    unset($_SESSION["WhatsAppManualOS"]);
}

$whatsAppManualOS = $whatsAppAposCriarOS ?: $whatsAppAposAtualizarOS;

if (!$whatsAppManualOS && $whatsAppManualAvulsoOS) {
    $whatsAppManualOS = $whatsAppManualAvulsoOS;
}


$sqlHistorico = "
    SELECT 
        h.HistoricoId,
        h.StatusAnterior,
        h.StatusNovo,
        h.Descricao,
        h.DataRegistro,
        u.Nome AS UsuarioNome
    FROM OS_Historico h
    INNER JOIN OS_OrdensServico os2 ON os2.OrdemServicoId = h.OrdemServicoId
    INNER JOIN OS_Usuarios u ON u.UsuarioId = h.UsuarioId AND u.EmpresaId = os2.EmpresaId
    WHERE h.OrdemServicoId = :OrdemServicoId
      AND os2.EmpresaId = :EmpresaId
    ORDER BY h.DataRegistro DESC
";

$stmtHistorico = $conn->prepare($sqlHistorico);
$stmtHistorico->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtHistorico->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtHistorico->execute();

$historicos = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

$sqlAnexos = "
    SELECT
        AnexoId,
        NomeOriginal,
        CaminhoArquivo,
        TipoArquivo,
        TamanhoBytes,
        VisivelCliente,
        DataCadastro
    FROM OS_OrdensServicoAnexos
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
    ORDER BY AnexoId DESC
";

$stmtAnexos = $conn->prepare($sqlAnexos);
$stmtAnexos->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtAnexos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtAnexos->execute();

$anexos = $stmtAnexos->fetchAll(PDO::FETCH_ASSOC);

$sqlMensagensWhatsApp = "
    SELECT TOP 20
        m.MensagemWhatsAppId,
        m.TipoMensagem,
        m.Origem,
        m.Telefone,
        m.Mensagem,
        m.DataCadastro,
        u.Nome AS UsuarioNome
    FROM OS_MensagensWhatsApp m
    LEFT JOIN OS_Usuarios u ON u.UsuarioId = m.UsuarioId
    WHERE m.OrdemServicoId = :OrdemServicoId
      AND m.EmpresaId = :EmpresaId
    ORDER BY m.MensagemWhatsAppId DESC
";

$stmtMensagensWhatsApp = $conn->prepare($sqlMensagensWhatsApp);
$stmtMensagensWhatsApp->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtMensagensWhatsApp->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtMensagensWhatsApp->execute();

$mensagensWhatsApp = $stmtMensagensWhatsApp->fetchAll(PDO::FETCH_ASSOC);

$camposPersonalizadosValoresOS = buscarCamposPersonalizadosComValoresOS($conn, $empresaId, $id);

function formatarDataOS($data, $comHora = false)
{
    if (empty($data)) {
        return "-";
    }

    return $comHora
        ? date("d/m/Y H:i", strtotime($data))
        : date("d/m/Y", strtotime($data));
}

function classeStatusOS($status)
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

function classePrioridadeOS($prioridade)
{
    if ($prioridade === "Baixa") {
        return "bg-info text-dark";
    }

    if ($prioridade === "Normal") {
        return "bg-secondary";
    }

    if ($prioridade === "Alta") {
        return "bg-warning text-dark";
    }

    if ($prioridade === "Urgente") {
        return "bg-danger";
    }

    return "bg-secondary";
}

$codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT));

$mensagemUrl = trim($_GET["mensagem"] ?? "");

function classeStatusFinanceiroOS($status)
{
    if ($status === "Pago") {
        return "bg-success";
    }

    if ($status === "Parcial") {
        return "bg-warning text-dark";
    }

    if ($status === "Cancelado") {
        return "bg-danger";
    }

    return "bg-secondary";
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                <?= htmlspecialchars($codigoOS) ?>
            </h3>

            <p>
                <?= htmlspecialchars($ordem["Titulo"] ?? "Detalhes da ordem de serviço") ?>
            </p>

            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="badge <?= classeStatusOS($ordem["Status"] ?? "") ?>">
                    <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                </span>

                <span class="badge <?= classePrioridadeOS($ordem["Prioridade"] ?? "") ?>">
                    Prioridade: <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?>
                </span>

                <span class="badge <?= classeStatusFinanceiroOS($ordem["StatusFinanceiro"] ?? "Pendente") ?>">
                    Financeiro: <?= htmlspecialchars($ordem["StatusFinanceiro"] ?? "Pendente") ?>
                </span>
            </div>
        </div>

        <div class="form-actions d-flex flex-wrap gap-2" style="border-top: 0; margin-top: 0; padding-top: 0;">
            <a href="atendimento.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-primary">
                Atendimento
            </a>

            <a href="recebimento.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-success">
                Recebimento
            </a>

            <a href="recibo.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-success">
                Recibo
            </a>

            <a href="nova_mensagem_whatsapp.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-success">
                WhatsApp
            </a>

            <a href="anexar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-primary">
                Anexar
            </a>

            <a href="editar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-warning">
                Editar
            </a>

            <button onclick="window.print()" class="btn btn-outline-secondary">
                Imprimir
            </button>

            <a href="listar.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <?php if ($mensagemUrl !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagemUrl) ?>
        </div>
    <?php endif; ?>

    <?php if ($whatsAppManualOS): ?>
        <?php
            $telefoneWhats = preg_replace('/\D/', '', $whatsAppManualOS["Telefone"] ?? "");
            $mensagemWhats = $whatsAppManualOS["Mensagem"] ?? "";

            if ($telefoneWhats !== "") {
                $urlWhatsApp = "https://wa.me/" . $telefoneWhats . "?text=" . urlencode($mensagemWhats);
            } else {
                $urlWhatsApp = "https://wa.me/?text=" . urlencode($mensagemWhats);
            }
        ?>

        <div class="alert alert-success border-0 shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <strong>Mensagem de WhatsApp preparada.</strong>
                    <br>
                    Clique no botão para abrir o WhatsApp com a mensagem já preenchida para o cliente.
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a 
                        href="<?= htmlspecialchars($urlWhatsApp) ?>" 
                        target="_blank" 
                        class="btn btn-success"
                    >
                        Abrir WhatsApp
                    </a>

                    <button 
                        type="button" 
                        class="btn btn-outline-secondary"
                        onclick="copiarMensagemWhatsAppCriacao()"
                    >
                        Copiar mensagem
                    </button>
                </div>
            </div>

            <div class="mt-3 p-3 bg-light rounded border" style="white-space: pre-line;">
                <?= htmlspecialchars($mensagemWhats) ?>
            </div>
        </div>

        <script>
        function copiarMensagemWhatsAppCriacao() {
            const mensagem = <?= json_encode($mensagemWhats, JSON_UNESCAPED_UNICODE) ?>;

            navigator.clipboard.writeText(mensagem)
                .then(function () {
                    alert("Mensagem copiada.");
                })
                .catch(function () {
                    alert("Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.");
                });
        }
        </script>
    <?php endif; ?>

    <div class="row g-3 mb-4">

        <div class="row g-3 mb-4">

            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="small text-muted">Status da OS</div>

                        <span class="badge <?= classeStatusOS($ordem["Status"] ?? "") ?> mt-2">
                            <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">Prioridade</div>

                        <span class="badge <?= classePrioridadeOS($ordem["Prioridade"] ?? "") ?> mt-2">
                            <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">Valor previsto</div>

                        <h5 class="mb-0 mt-2">
                            R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?>
                        </h5>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="small text-muted">Valor final</div>

                        <h5 class="mb-0 mt-2">
                            R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?>
                        </h5>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="card form-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Controle financeiro</span>

            <a href="recebimento.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-sm btn-outline-success">
                Ver recebimentos
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Status financeiro</div>

                    <span class="badge <?= classeStatusFinanceiroOS($ordem["StatusFinanceiro"] ?? "Pendente") ?>">
                        <?= htmlspecialchars($ordem["StatusFinanceiro"] ?? "Pendente") ?>
                    </span>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Forma de pagamento</div>
                    <strong><?= htmlspecialchars($ordem["FormaPagamento"] ?? "Não informado") ?></strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Valor recebido</div>
                    <strong class="text-success">
                        R$ <?= number_format((float)($ordem["ValorPago"] ?? 0), 2, ",", ".") ?>
                    </strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Último recebimento</div>
                    <strong>
                        <?= !empty($ordem["DataPagamento"])
                            ? date("d/m/Y", strtotime($ordem["DataPagamento"]))
                            : "-"
                        ?>
                    </strong>
                </div>

                <?php if (!empty($ordem["ObservacaoFinanceira"])): ?>
                    <div class="col-md-12">
                        <div class="small text-muted">Observação financeira</div>

                        <div style="white-space: pre-line;">
                            <?= nl2br(htmlspecialchars($ordem["ObservacaoFinanceira"])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
        <div class="card form-card mb-3">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card form-card mb-3 h-100">
                    <div class="card-header">
                        Dados da OS
                    </div>

                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Código</div>
                                <strong><?= htmlspecialchars($codigoOS) ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Serviço</div>
                                <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Abertura</div>
                                <strong><?= formatarDataOS($ordem["DataAbertura"] ?? null, true) ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Previsão</div>
                                <strong><?= formatarDataOS($ordem["DataPrevisao"] ?? null) ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Conclusão</div>
                                <strong><?= formatarDataOS($ordem["DataConclusao"] ?? null, true) ?></strong>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card form-card mb-3 h-100">
                    <div class="card-header">
                        Cliente
                    </div>

                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Nome</div>
                                <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Telefone</div>
                                <strong><?= htmlspecialchars($ordem["ClienteTelefone"] ?? "-") ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">E-mail</div>
                                <strong><?= htmlspecialchars($ordem["ClienteEmail"] ?? "-") ?></strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="small text-muted">Documento</div>
                                <strong><?= htmlspecialchars($ordem["ClienteDocumento"] ?? "-") ?></strong>
                            </div>

                            <div class="col-md-12">
                                <div class="small text-muted">Endereço</div>
                                <strong>
                                    <?= htmlspecialchars($ordem["ClienteEndereco"] ?? "") ?>
                                    <?= htmlspecialchars($ordem["ClienteCidade"] ?? "") ?>
                                    <?= htmlspecialchars($ordem["ClienteEstado"] ?? "") ?>
                                </strong>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php if (count($camposPersonalizadosValoresOS) > 0): ?>
        <div class="card form-card mb-3">
            <div class="card-header">
                Campos personalizados da OS
            </div>

            <div class="card-body">
                <div class="row">
                    <?php foreach ($camposPersonalizadosValoresOS as $campo): ?>
                        <div class="<?= ($campo["TipoCampo"] ?? "") === "textarea" ? "col-md-12" : "col-md-6" ?> mb-3">
                            <div class="small text-muted">
                                <?= htmlspecialchars($campo["Rotulo"] ?? "") ?>
                            </div>

                            <strong>
                                <?php if (($campo["TipoCampo"] ?? "") === "textarea"): ?>
                                    <?= nl2br(htmlspecialchars($campo["Valor"] ?? "-")) ?>
                                <?php elseif (($campo["TipoCampo"] ?? "") === "data" && !empty($campo["Valor"])): ?>
                                    <?= date("d/m/Y", strtotime($campo["Valor"])) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($campo["Valor"] ?? "-") ?>
                                <?php endif; ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card form-card mb-3">
        <div class="card-header">
           Solicitação do cliente
        </div>

        <div class="card-body">
            <?php if (!empty($ordem["DescricaoProblema"])): ?>
                <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"])) ?>
            <?php else: ?>
                <span class="text-muted">Nenhuma solicitação detalhada foi informada.</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card form-card mb-3">
        <div class="card-header">
            Solução aplicada
        </div>

        <div class="card-body">
            <?php if (!empty($ordem["DescricaoSolucao"])): ?>
                <?= nl2br(htmlspecialchars($ordem["DescricaoSolucao"])) ?>
            <?php else: ?>
                <span class="text-muted">Nenhuma solução foi registrada até o momento.</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($ordem["Observacao"])): ?>
        <div class="card form-card mb-3">
            <div class="card-header">
                Observações internas
            </div>

            <div class="card-body">
                <?= nl2br(htmlspecialchars($ordem["Observacao"])) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card form-card mb-3">
        <div class="card-header">
            <strong>Anexos da OS</strong>

            <a href="anexar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-sm btn-primary">
                Novo Anexo
            </a>
        </div>

       <div class="card-body">
            <?php if (count($anexos) === 0): ?>
                <div class="empty-state">
                    Nenhum anexo cadastrado.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Tipo</th>
                                <th>Tamanho</th>
                                <th>Visível Cliente</th>
                                <th>Data</th>
                                <th width="260">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($anexos as $anexo): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($anexo["NomeOriginal"]) ?></strong>
                                    </td>

                                    <td>
                                        <span class="text-muted">
                                            <?= htmlspecialchars($anexo["TipoArquivo"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= number_format(((int)$anexo["TamanhoBytes"] / 1024), 2, ",", ".") ?> KB
                                    </td>

                                    <td>
                                        <?php if ((int)$anexo["VisivelCliente"] === 1): ?>
                                            <span class="badge bg-success">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= formatarDataOS($anexo["DataCadastro"] ?? null, true) ?>
                                    </td>

                                    <td>
                                        <div class="table-actions">
                                            <a 
                                                href="abrir_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Abrir
                                            </a>

                                            <a 
                                                href="alternar_visibilidade_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>&<?= csrfTokenUrl() ?>"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <?= (int)$anexo["VisivelCliente"] === 1 ? "Ocultar" : "Liberar" ?>
                                            </a>

                                            <a 
                                                href="excluir_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>&<?= csrfTokenUrl() ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Deseja realmente excluir este anexo?')"
                                            >
                                                Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <div class="card form-card mb-3">
        <div class="card-header">
            <strong>Mensagens de WhatsApp</strong>

            <span class="badge bg-success">
                <?= count($mensagensWhatsApp) ?> registro(s)
            </span>
        </div>

        <div class="card-body">
            <?php if (count($mensagensWhatsApp) === 0): ?>
                <div class="empty-state">
                    Nenhuma mensagem WhatsApp preparada para esta OS.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Origem</th>
                                <th>Usuário</th>
                                <th>Telefone</th>
                                <th>Mensagem</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($mensagensWhatsApp as $msg): ?>
                                <tr>
                                    <td>
                                        <?= !empty($msg["DataCadastro"])
                                            ? date("d/m/Y H:i", strtotime($msg["DataCadastro"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($msg["TipoMensagem"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($msg["Origem"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($msg["UsuarioNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($msg["Telefone"] ?? "-") ?>
                                    </td>

                                    <td style="min-width: 320px;">
                                        <div style="white-space: pre-line;">
                                            <?= nl2br(htmlspecialchars($msg["Mensagem"] ?? "")) ?>
                                        </div>

                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-secondary mt-2"
                                            onclick="copiarMensagemHistoricoWhatsApp(<?= (int)$msg["MensagemWhatsAppId"] ?>)"
                                        >
                                            Copiar
                                        </button>

                                        <?php
                                            $telefoneMsg = preg_replace('/\D/', '', $msg["Telefone"] ?? "");
                                            $mensagemMsg = $msg["Mensagem"] ?? "";

                                            if ($telefoneMsg !== "") {
                                                $urlMsgWhats = "https://wa.me/" . $telefoneMsg . "?text=" . urlencode($mensagemMsg);
                                            } else {
                                                $urlMsgWhats = "https://wa.me/?text=" . urlencode($mensagemMsg);
                                            }
                                        ?>

                                        <a 
                                            href="<?= htmlspecialchars($urlMsgWhats) ?>" 
                                            target="_blank" 
                                            class="btn btn-sm btn-success mt-2"
                                        >
                                            Abrir WhatsApp
                                        </a>

                                        <textarea 
                                            id="mensagem-whatsapp-<?= (int)$msg["MensagemWhatsAppId"] ?>" 
                                            class="d-none"
                                        ><?= htmlspecialchars($msg["Mensagem"] ?? "") ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
    function copiarMensagemHistoricoWhatsApp(id) {
        const campo = document.getElementById("mensagem-whatsapp-" + id);

        if (!campo) {
            alert("Mensagem não encontrada.");
            return;
        }

        navigator.clipboard.writeText(campo.value)
            .then(function () {
                alert("Mensagem copiada.");
            })
            .catch(function () {
                alert("Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.");
            });
    }
    </script>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <strong>Histórico de Movimentações</strong>
        </div>

        <div class="card-body p-0">
            <?php if (count($historicos) === 0): ?>
                <div class="empty-state">
                    Nenhuma movimentação registrada.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Status Anterior</th>
                                <th>Status Novo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($historicos as $hist): ?>
                                <tr>
                                    <td>
                                        <?= formatarDataOS($hist["DataRegistro"] ?? null, true) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($hist["UsuarioNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($hist["StatusAnterior"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($hist["StatusNovo"])): ?>
                                            <span class="badge <?= classeStatusOS($hist["StatusNovo"]) ?>">
                                                <?= htmlspecialchars($hist["StatusNovo"]) ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= nl2br(htmlspecialchars($hist["Descricao"] ?? "")) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>