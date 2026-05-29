<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../config/config.php";

exigirPerfil(["SuperAdmin"]);

function statusBadge($ok)
{
    return $ok
        ? '<span class="badge bg-success">OK</span>'
        : '<span class="badge bg-danger">Falha</span>';
}

function textoBooleano($valor)
{
    return $valor ? "Sim" : "Não";
}

function mascararValor($valor)
{
    if ($valor === null || $valor === "") {
        return "-";
    }

    return "Configurado";
}

function verificarTabela($conn, $tabela)
{
    try {
        $sql = "
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_NAME = :Tabela
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":Tabela", $tabela);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

$bancoConectado = false;
$erroBanco = "";

try {
    $stmtTeste = $conn->query("SELECT 1 AS Teste");
    $resultadoTeste = $stmtTeste->fetch(PDO::FETCH_ASSOC);
    $bancoConectado = isset($resultadoTeste["Teste"]);
} catch (Exception $e) {
    $erroBanco = $e->getMessage();
}

$pdoDrivers = PDO::getAvailableDrivers();

$temSqlsrv = in_array("sqlsrv", $pdoDrivers, true);
$temPdoSqlsrv = in_array("sqlsrv", $pdoDrivers, true);
$temSqlsrvExt = extension_loaded("sqlsrv");
$temPdoSqlsrvExt = extension_loaded("pdo_sqlsrv");

$uploadDir = defined("UPLOAD_DIR") ? UPLOAD_DIR : "";
$logDir = defined("LOG_DIR") ? LOG_DIR : "";

$uploadExiste = $uploadDir !== "" && is_dir($uploadDir);
$uploadGravavel = $uploadExiste && is_writable($uploadDir);

$logExiste = $logDir !== "" && is_dir($logDir);
$logGravavel = $logExiste && is_writable($logDir);

$tabelasCriticas = [
    "OS_Empresas",
    "OS_Usuarios",
    "OS_Planos",
    "OS_Assinaturas",
    "OS_Clientes",
    "OS_Servicos",
    "OS_OrdensServico",
    "OS_Historico",
    "OS_OrdensServicoAnexos",
    "OS_Auditoria"
];

$tabelasStatus = [];

foreach ($tabelasCriticas as $tabela) {
    $tabelasStatus[$tabela] = verificarTabela($conn, $tabela);
}

$totalEmpresas = 0;
$totalUsuarios = 0;
$totalOS = 0;

if ($bancoConectado) {
    try {
        $totalEmpresas = (int)$conn->query("SELECT COUNT(*) FROM OS_Empresas")->fetchColumn();
        $totalUsuarios = (int)$conn->query("SELECT COUNT(*) FROM OS_Usuarios")->fetchColumn();
        $totalOS = (int)$conn->query("SELECT COUNT(*) FROM OS_OrdensServico")->fetchColumn();
    } catch (Exception $e) {
        // Evita quebrar a tela caso alguma tabela ainda não exista.
    }
}

$appUrl = defined("APP_URL") ? APP_URL : "";
$appEnv = defined("APP_ENV") ? APP_ENV : "";
$appDebug = defined("APP_DEBUG") ? APP_DEBUG : false;

$dbServer = defined("DB_SERVER") ? DB_SERVER : getenv("DB_SERVER");
$dbDatabase = defined("DB_DATABASE") ? DB_DATABASE : getenv("DB_DATABASE");
$dbUsername = defined("DB_USERNAME") ? DB_USERNAME : getenv("DB_USERNAME");
$dbPassword = defined("DB_PASSWORD") ? DB_PASSWORD : getenv("DB_PASSWORD");
$dbTrust = defined("DB_TRUST_SERVER_CERTIFICATE") ? DB_TRUST_SERVER_CERTIFICATE : getenv("DB_TRUST_SERVER_CERTIFICATE");

$phpVersion = phpversion();
$serverSoftware = $_SERVER["SERVER_SOFTWARE"] ?? "-";
$documentRoot = $_SERVER["DOCUMENT_ROOT"] ?? "-";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Diagnóstico do Sistema</h3>
            <p>
                Verifique informações do ambiente, banco de dados, extensões PHP e pastas de armazenamento.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="metricas.php" class="btn btn-outline-primary">
                Métricas SaaS
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <strong>Atenção:</strong>
        esta tela deve ser acessível apenas por SuperAdmin, pois exibe informações técnicas do ambiente.
        Nenhuma senha é exibida.
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ambiente</div>
                    <h4 class="mb-1 mt-2">
                        <?= htmlspecialchars($appEnv ?: "-") ?>
                    </h4>
                    <div class="input-help">
                        APP_ENV
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">PHP</div>
                    <h4 class="mb-1 mt-2">
                        <?= htmlspecialchars($phpVersion) ?>
                    </h4>
                    <div class="input-help">
                        Versão ativa
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Banco</div>
                    <h4 class="mb-1 mt-2">
                        <?= $bancoConectado ? "Conectado" : "Falha" ?>
                    </h4>
                    <div>
                        <?= statusBadge($bancoConectado) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">PDO SQL Server</div>
                    <h4 class="mb-1 mt-2">
                        <?= $temPdoSqlsrvExt ? "Disponível" : "Indisponível" ?>
                    </h4>
                    <div>
                        <?= statusBadge($temPdoSqlsrvExt) ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Configuração da Aplicação
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">APP_NAME</th>
                                <td><?= htmlspecialchars(defined("APP_NAME") ? APP_NAME : "-") ?></td>
                            </tr>

                            <tr>
                                <th>APP_ENV</th>
                                <td><?= htmlspecialchars($appEnv ?: "-") ?></td>
                            </tr>

                            <tr>
                                <th>APP_URL</th>
                                <td>
                                    <code><?= htmlspecialchars($appUrl ?: "-") ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>APP_DEBUG</th>
                                <td>
                                    <?= textoBooleano($appDebug) ?>
                                    <?= $appDebug ? '<span class="badge bg-warning text-dark">Debug ativo</span>' : '<span class="badge bg-success">Debug inativo</span>' ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Document Root</th>
                                <td>
                                    <code><?= htmlspecialchars($documentRoot) ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>Servidor Web</th>
                                <td><?= htmlspecialchars($serverSoftware) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Banco de Dados
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">Conexão</th>
                                <td><?= statusBadge($bancoConectado) ?></td>
                            </tr>

                            <tr>
                                <th>DB_SERVER</th>
                                <td><?= htmlspecialchars(mascararValor($dbServer)) ?></td>
                            </tr>

                            <tr>
                                <th>DB_DATABASE</th>
                                <td><?= htmlspecialchars(mascararValor($dbDatabase)) ?></td>
                            </tr>

                            <tr>
                                <th>DB_USERNAME</th>
                                <td><?= htmlspecialchars(mascararValor($dbUsername)) ?></td>
                            </tr>

                            <tr>
                                <th>DB_PASSWORD</th>
                                <td><?= htmlspecialchars(mascararValor($dbPassword)) ?></td>
                            </tr>

                            <tr>
                                <th>Trust Certificate</th>
                                <td><?= htmlspecialchars((string)$dbTrust) ?></td>
                            </tr>

                            <?php if (!$bancoConectado && $erroBanco !== ""): ?>
                                <tr>
                                    <th>Erro</th>
                                    <td class="text-danger">
                                        <?= htmlspecialchars($erroBanco) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Extensões e Drivers PHP
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">PDO disponível</th>
                                <td><?= statusBadge(extension_loaded("PDO")) ?></td>
                            </tr>

                            <tr>
                                <th>Extensão sqlsrv</th>
                                <td><?= statusBadge($temSqlsrvExt) ?></td>
                            </tr>

                            <tr>
                                <th>Extensão pdo_sqlsrv</th>
                                <td><?= statusBadge($temPdoSqlsrvExt) ?></td>
                            </tr>

                            <tr>
                                <th>Driver PDO sqlsrv</th>
                                <td><?= statusBadge($temSqlsrv) ?></td>
                            </tr>

                            <tr>
                                <th>Drivers PDO disponíveis</th>
                                <td>
                                    <code><?= htmlspecialchars(implode(", ", $pdoDrivers)) ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>Extensões carregadas</th>
                                <td>
                                    <details>
                                        <summary>Ver extensões</summary>
                                        <code>
                                            <?= htmlspecialchars(implode(", ", get_loaded_extensions())) ?>
                                        </code>
                                    </details>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Uploads e Logs
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th width="260">UPLOAD_DIR</th>
                                <td>
                                    <code><?= htmlspecialchars($uploadDir ?: "-") ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>Upload existe</th>
                                <td><?= statusBadge($uploadExiste) ?></td>
                            </tr>

                            <tr>
                                <th>Upload gravável</th>
                                <td><?= statusBadge($uploadGravavel) ?></td>
                            </tr>

                            <tr>
                                <th>LOG_DIR</th>
                                <td>
                                    <code><?= htmlspecialchars($logDir ?: "-") ?></code>
                                </td>
                            </tr>

                            <tr>
                                <th>Log existe</th>
                                <td><?= statusBadge($logExiste) ?></td>
                            </tr>

                            <tr>
                                <th>Log gravável</th>
                                <td><?= statusBadge($logGravavel) ?></td>
                            </tr>

                            <tr>
                                <th>Tamanho máximo upload público</th>
                                <td>
                                    <?= htmlspecialchars(defined("PUBLIC_UPLOAD_MAX_SIZE_MB") ? PUBLIC_UPLOAD_MAX_SIZE_MB : "-") ?> MB
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalEmpresas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalUsuarios ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ordens de Serviço</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalOS ?></h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header">
            Tabelas Críticas
        </div>

        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tabela</th>
                        <th width="160">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($tabelasStatus as $tabela => $existe): ?>
                        <tr>
                            <td>
                                <code><?= htmlspecialchars($tabela) ?></code>
                            </td>

                            <td>
                                <?= statusBadge($existe) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>