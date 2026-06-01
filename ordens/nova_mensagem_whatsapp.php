<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$ordemServicoId = (int)($_GET["id"] ?? 0);

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$sql = "
    SELECT
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.TokenAcompanhamento,
        c.ClienteId,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        s.Nome AS ServicoNome,
        e.NomeFantasia AS EmpresaNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    WHERE os.OrdemServicoId = :OrdemServicoId
      AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("OS não encontrada.");
}

$telefone = preg_replace('/\D/', '', $ordem["ClienteTelefone"] ?? "");

if ($telefone !== "" && (strlen($telefone) === 10 || strlen($telefone) === 11)) {
    $telefone = "55" . $telefone;
}

$codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordemServicoId, 6, "0", STR_PAD_LEFT));

$linkPublico = "";

if (!empty($ordem["TokenAcompanhamento"])) {
    $linkPublico = rtrim(APP_URL, "/") . "/public/os.php?token=" . urlencode($ordem["TokenAcompanhamento"]);
}

$partesMensagem = [];

$partesMensagem[] = "Olá, " . $ordem["ClienteNome"] . "! Segue atualização referente à sua ordem de serviço " . $codigoOS . ".";

if (!empty($ordem["Titulo"])) {
    $partesMensagem[] = "Atendimento: " . $ordem["Titulo"] . ".";
}

if (!empty($ordem["Status"])) {
    $partesMensagem[] = "Status atual: " . $ordem["Status"] . ".";
}

if ($linkPublico !== "") {
    $partesMensagem[] = "Você pode acompanhar pelo link: " . $linkPublico;
}

$partesMensagem[] = $ordem["EmpresaNome"] ?? "DirectOS";

$mensagemPadrao = implode(" ", $partesMensagem);
$mensagemPadrao = preg_replace("/\s+/", " ", $mensagemPadrao);
$mensagemPadrao = trim($mensagemPadrao);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Nova mensagem WhatsApp
            </h3>

            <p>
                Prepare uma mensagem manual para o cliente da OS <?= htmlspecialchars($codigoOS) ?>.
            </p>
        </div>

        <a href="visualizar.php?id=<?= (int)$ordemServicoId ?>" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>Dados da OS</strong>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">Código</div>
                        <strong><?= htmlspecialchars($codigoOS) ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Cliente</div>
                        <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Telefone</div>
                        <strong><?= htmlspecialchars($ordem["ClienteTelefone"] ?? "-") ?></strong>

                        <?php if ($telefone === ""): ?>
                            <div class="text-danger small mt-1">
                                Cliente sem telefone cadastrado. O WhatsApp abrirá sem destinatário.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Serviço</div>
                        <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Status</div>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card form-card">
                <div class="card-header">
                    Mensagem para WhatsApp
                </div>

                <div class="card-body">

                    <form method="post" action="salvar_mensagem_whatsapp.php">
                        <?= csrfInput() ?>

                        <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordemServicoId ?>">

                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input 
                                type="text" 
                                name="Telefone" 
                                class="form-control"
                                value="<?= htmlspecialchars($telefone) ?>"
                                placeholder="Ex.: 5521999999999"
                            >

                            <div class="input-help mt-2">
                                Informe com DDD. Se estiver sem o código do Brasil, o sistema adiciona 55 automaticamente.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mensagem *</label>
                            <textarea 
                                name="Mensagem" 
                                class="form-control" 
                                rows="8" 
                                required
                            ><?= htmlspecialchars($mensagemPadrao) ?></textarea>

                            <div class="input-help mt-2">
                                Revise a mensagem antes de abrir o WhatsApp.
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                Salvar e abrir WhatsApp
                            </button>

                            <a href="visualizar.php?id=<?= (int)$ordemServicoId ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>