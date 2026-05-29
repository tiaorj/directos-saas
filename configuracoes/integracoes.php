<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

function badgeStatusIntegracao($ativo)
{
    return $ativo
        ? '<span class="badge bg-success">OK</span>'
        : '<span class="badge bg-danger">Pendente</span>';
}

function textoConfigurado($valor)
{
    return trim((string)$valor) !== "" ? "Configurado" : "Não configurado";
}

function simNaoIntegracao($valor)
{
    return $valor ? "Sim" : "Não";
}

$iaAtiva = defined("IA_ATIVA") && IA_ATIVA === true;
$iaProvider = defined("IA_PROVIDER") ? IA_PROVIDER : "";
$openaiKeyConfigurada = defined("OPENAI_API_KEY") && trim(OPENAI_API_KEY) !== "";
$openaiModel = defined("OPENAI_MODEL") ? OPENAI_MODEL : "";
$openaiApiUrl = defined("OPENAI_API_URL") ? OPENAI_API_URL : "";

$n8nAtivo = defined("N8N_ATIVO") && N8N_ATIVO === true;
$n8nWebhookConfigurado = defined("N8N_WEBHOOK_WHATSAPP_URL") && trim(N8N_WEBHOOK_WHATSAPP_URL) !== "";
$n8nSecretConfigurado = defined("N8N_WEBHOOK_SECRET") && trim(N8N_WEBHOOK_SECRET) !== "";

$sqlAuditoria = "
    SELECT TOP 20
        a.AuditoriaId,
        a.Acao,
        a.Entidade,
        a.EntidadeId,
        a.Descricao,
        a.DataRegistro,
        e.NomeFantasia AS EmpresaNome,
        u.Nome AS UsuarioNome
    FROM OS_Auditoria a
    LEFT JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
    LEFT JOIN OS_Usuarios u ON u.UsuarioId = a.UsuarioId
    WHERE a.Acao LIKE 'IA_%'
       OR a.Acao LIKE 'N8N_%'
    ORDER BY a.AuditoriaId DESC
";

$stmtAuditoria = $conn->prepare($sqlAuditoria);
$stmtAuditoria->execute();

$logsIntegracoes = $stmtAuditoria->fetchAll(PDO::FETCH_ASSOC);

$totalIA = 0;
$totalN8N = 0;

try {
    $totalIA = (int)$conn->query("
        SELECT COUNT(*)
        FROM OS_Auditoria
        WHERE Acao LIKE 'IA_%'
    ")->fetchColumn();

    $totalN8N = (int)$conn->query("
        SELECT COUNT(*)
        FROM OS_Auditoria
        WHERE Acao LIKE 'N8N_%'
    ")->fetchColumn();
} catch (Exception $e) {
    $totalIA = 0;
    $totalN8N = 0;
}

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Integrações</h3>
            <p>
                Acompanhe as integrações de IA, n8n e envio automatizado de WhatsApp.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-primary">
                Configurações
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <?php if ($sucesso !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <strong>Atenção:</strong>
        esta tela não exibe chaves, tokens ou secrets. Ela mostra apenas se as configurações estão ativas ou configuradas.
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">IA</div>

                    <h4 class="mb-1 mt-2">
                        <?= $iaAtiva ? "Ativa" : "Inativa" ?>
                    </h4>

                    <?= badgeStatusIntegracao($iaAtiva && $openaiKeyConfigurada && trim($openaiModel) !== "") ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Modelo IA</div>

                    <h5 class="mb-1 mt-2">
                        <?= htmlspecialchars($openaiModel ?: "-") ?>
                    </h5>

                    <div class="input-help">
                        OPENAI_MODEL
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">n8n</div>

                    <h4 class="mb-1 mt-2">
                        <?= $n8nAtivo ? "Ativo" : "Inativo" ?>
                    </h4>

                    <?= badgeStatusIntegracao($n8nAtivo && $n8nWebhookConfigurado && $n8nSecretConfigurado) ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Envios n8n</div>

                    <h4 class="mb-1 mt-2">
                        <?= (int)$totalN8N ?>
                    </h4>

                    <div class="input-help">
                        Registros em auditoria
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Inteligência Artificial
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">IA_ATIVA</th>
                                <td>
                                    <?= simNaoIntegracao($iaAtiva) ?>
                                    <?= badgeStatusIntegracao($iaAtiva) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>IA_PROVIDER</th>
                                <td><?= htmlspecialchars($iaProvider ?: "-") ?></td>
                            </tr>

                            <tr>
                                <th>OPENAI_API_KEY</th>
                                <td>
                                    <?= htmlspecialchars(textoConfigurado($openaiKeyConfigurada ? "ok" : "")) ?>
                                    <?= badgeStatusIntegracao($openaiKeyConfigurada) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>OPENAI_MODEL</th>
                                <td><?= htmlspecialchars($openaiModel ?: "-") ?></td>
                            </tr>

                            <tr>
                                <th>OPENAI_API_URL</th>
                                <td>
                                    <code><?= htmlspecialchars($openaiApiUrl ?: "-") ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>Total de ações IA</th>
                                <td><?= (int)$totalIA ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    n8n / WhatsApp
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">N8N_ATIVO</th>
                                <td>
                                    <?= simNaoIntegracao($n8nAtivo) ?>
                                    <?= badgeStatusIntegracao($n8nAtivo) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>N8N_WEBHOOK_WHATSAPP_URL</th>
                                <td>
                                    <?= htmlspecialchars(textoConfigurado($n8nWebhookConfigurado ? "ok" : "")) ?>
                                    <?= badgeStatusIntegracao($n8nWebhookConfigurado) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>N8N_WEBHOOK_SECRET</th>
                                <td>
                                    <?= htmlspecialchars(textoConfigurado($n8nSecretConfigurado ? "ok" : "")) ?>
                                    <?= badgeStatusIntegracao($n8nSecretConfigurado) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Fluxo esperado</th>
                                <td>
                                    DirectOS → n8n → Z-API → WhatsApp
                                </td>
                            </tr>

                            <tr>
                                <th>Total de ações n8n</th>
                                <td><?= (int)$totalN8N ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Teste de envio via n8n
        </div>

        <div class="card-body">

            <p class="text-muted">
                Envie uma mensagem de teste para validar o fluxo DirectOS → n8n → Z-API. Use um número próprio para evitar envio indevido.
            </p>

            <?php if (!$n8nAtivo || !$n8nWebhookConfigurado || !$n8nSecretConfigurado): ?>
                <div class="alert alert-danger">
                    A integração n8n ainda não está totalmente configurada no ambiente.
                </div>
            <?php endif; ?>

            <form method="post" action="testar_n8n.php">
                <?= csrfInput() ?>

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone/WhatsApp de teste</label>
                        <input 
                            type="text" 
                            name="TelefoneTeste" 
                            class="form-control"
                            placeholder="Ex.: 5521999999999"
                            required
                        >
                        <div class="input-help">
                            Informe com DDD. O sistema adiciona 55 se necessário.
                        </div>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Mensagem de teste</label>
                        <input 
                            type="text" 
                            name="MensagemTeste" 
                            class="form-control"
                            value="Teste de integração DirectOS com n8n e Z-API."
                            required
                        >
                    </div>

                </div>

                <button 
                    type="submit" 
                    class="btn btn-primary"
                    onclick="return confirm('Deseja enviar esta mensagem de teste via n8n?')"
                    <?= (!$n8nAtivo || !$n8nWebhookConfigurado || !$n8nSecretConfigurado) ? "disabled" : "" ?>
                >
                    Enviar teste via n8n
                </button>

            </form>

        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Últimas ações de integrações</span>

            <span class="badge bg-primary">
                <?= count($logsIntegracoes) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($logsIntegracoes) === 0): ?>
                <div class="empty-state">
                    Nenhuma ação de integração registrada ainda.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Ação</th>
                                <th>Empresa</th>
                                <th>Usuário</th>
                                <th>Entidade</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($logsIntegracoes as $log): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= (int)$log["AuditoriaId"] ?></strong>
                                    </td>

                                    <td>
                                        <?= !empty($log["DataRegistro"])
                                            ? date("d/m/Y H:i:s", strtotime($log["DataRegistro"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= str_starts_with($log["Acao"], "N8N_") ? "bg-success" : "bg-primary" ?>">
                                            <?= htmlspecialchars($log["Acao"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log["EmpresaNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log["UsuarioNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log["Entidade"] ?? "-") ?>

                                        <?php if (!empty($log["EntidadeId"])): ?>
                                            <div class="os-subtitle">
                                                ID <?= (int)$log["EntidadeId"] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log["Descricao"] ?? "-") ?>
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