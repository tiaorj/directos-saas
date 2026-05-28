<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

$empresaId = (int)($_GET["id"] ?? 0);

if ($empresaId <= 0) {
    die("Empresa inválida.");
}

$sqlEmpresa = "
    SELECT
        e.EmpresaId,
        e.NomeFantasia,
        e.RazaoSocial,
        e.Cnpj,
        e.Email,
        e.Telefone,
        e.WhatsApp,
        e.Slug,
        e.Ativo,
        e.DataCadastro,

        a.AssinaturaId,
        a.Status AS StatusAssinatura,
        a.DataInicio AS DataInicioAssinatura,
        a.DataFim AS DataFimAssinatura,

        p.PlanoId,
        p.Nome AS PlanoAtual,
        p.ValorMensal,
        p.LimiteOSMes,
        p.LimiteUsuarios
    FROM OS_Empresas e
    OUTER APPLY (
        SELECT TOP 1
            a.AssinaturaId,
            a.PlanoId,
            a.Status,
            a.DataInicio,
            a.DataFim
        FROM OS_Assinaturas a
        WHERE a.EmpresaId = e.EmpresaId
          AND a.Status = 'Ativa'
        ORDER BY a.AssinaturaId DESC
    ) a
    LEFT JOIN OS_Planos p ON p.PlanoId = a.PlanoId
    WHERE e.EmpresaId = :EmpresaId
";

$stmtEmpresa = $conn->prepare($sqlEmpresa);
$stmtEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtEmpresa->execute();

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

$sqlPlanos = "
    SELECT
        PlanoId,
        Nome,
        ValorMensal,
        LimiteOSMes,
        LimiteUsuarios
    FROM OS_Planos
    WHERE Ativo = 1
    ORDER BY ValorMensal
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$planos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

function contarAdminEmpresa($conn, $sql, $empresaId)
{
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

$totalUsuarios = contarAdminEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_Usuarios
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalClientes = contarAdminEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_Clientes
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalServicos = contarAdminEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_Servicos
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalOS = contarAdminEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_OrdensServico
    WHERE EmpresaId = :EmpresaId
", $empresaId);

$totalOSAtivas = contarAdminEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_OrdensServico
    WHERE EmpresaId = :EmpresaId
      AND Status NOT IN ('Concluída', 'Cancelada')
", $empresaId);

$sqlUsuarios = "
    SELECT TOP 10
        UsuarioId,
        Nome,
        Email,
        Perfil,
        Ativo,
        DataCadastro
    FROM OS_Usuarios
    WHERE EmpresaId = :EmpresaId
    ORDER BY UsuarioId DESC
";

$stmtUsuarios = $conn->prepare($sqlUsuarios);
$stmtUsuarios->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtUsuarios->execute();

$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

$sqlUltimasOS = "
    SELECT TOP 10
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.DataAbertura,
        c.Nome AS ClienteNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    WHERE os.EmpresaId = :EmpresaId
    ORDER BY os.OrdemServicoId DESC
";

$stmtUltimasOS = $conn->prepare($sqlUltimasOS);
$stmtUltimasOS->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtUltimasOS->execute();

$ultimasOS = $stmtUltimasOS->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

function formatarLimiteAdmin($valor)
{
    if ($valor === null || $valor === "") {
        return "Ilimitado";
    }

    return (string)(int)$valor;
}

function classeStatusAdmin($status)
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

$sqlHistoricoAssinaturas = "
    SELECT TOP 10
        a.AssinaturaId,
        a.Status,
        a.DataInicio,
        a.DataFim,
        p.Nome AS PlanoNome,
        p.ValorMensal
    FROM OS_Assinaturas a
    INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
    WHERE a.EmpresaId = :EmpresaId
    ORDER BY a.AssinaturaId DESC
";

$stmtHistoricoAssinaturas = $conn->prepare($sqlHistoricoAssinaturas);
$stmtHistoricoAssinaturas->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtHistoricoAssinaturas->execute();

$historicoAssinaturas = $stmtHistoricoAssinaturas->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                <?= htmlspecialchars($empresa["NomeFantasia"] ?? "Empresa") ?>
            </h3>

            <p>
                Detalhes administrativos da empresa na plataforma DirectOS.
            </p>
        </div>

        <a href="empresas.php" class="btn btn-outline-secondary">
            Voltar
        </a>
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

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Status</div>

                    <div class="mt-2">
                        <?php if ((int)$empresa["Ativo"] === 1): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativa</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <?php if ((int)$empresa["Ativo"] === 1): ?>
                            <a 
                                href="alternar_status_empresa.php?id=<?= (int)$empresa["EmpresaId"] ?>&acao=inativar&<?= csrfTokenUrl() ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Deseja realmente inativar esta empresa?')"
                            >
                                Inativar empresa
                            </a>
                        <?php else: ?>
                            <a 
                                href="alternar_status_empresa.php?id=<?= (int)$empresa["EmpresaId"] ?>&acao=ativar&<?= csrfTokenUrl() ?>"
                                class="btn btn-sm btn-success"
                            >
                                Ativar empresa
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Plano atual</div>

                    <h5 class="mb-1 mt-2 text-primary">
                        <?= htmlspecialchars($empresa["PlanoAtual"] ?? "Sem plano") ?>
                    </h5>

                    <div class="input-help">
                        R$ <?= number_format((float)($empresa["ValorMensal"] ?? 0), 2, ",", ".") ?> / mês
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Histórico de Assinaturas</span>

                <a href="assinaturas.php?empresa=<?= urlencode($empresa["NomeFantasia"] ?? "") ?>" class="btn btn-sm btn-outline-primary">
                    Ver completo
                </a>
            </div>

            <div class="card-body p-0">

                <?php if (count($historicoAssinaturas) === 0): ?>
                    <div class="empty-state">
                        Nenhum histórico de assinatura encontrado.
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-os mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($historicoAssinaturas as $assinatura): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                #<?= (int)$assinatura["AssinaturaId"] ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars($assinatura["PlanoNome"] ?? "-") ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($assinatura["Status"] === "Ativa"): ?>
                                                <span class="badge bg-success">Ativa</span>
                                            <?php elseif ($assinatura["Status"] === "Cancelada"): ?>
                                                <span class="badge bg-secondary">Cancelada</span>
                                            <?php else: ?>
                                                <span class="badge bg-dark">
                                                    <?= htmlspecialchars($assinatura["Status"] ?? "-") ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            R$ <?= number_format((float)$assinatura["ValorMensal"], 2, ",", ".") ?>
                                        </td>

                                        <td>
                                            <?= !empty($assinatura["DataInicio"])
                                                ? date("d/m/Y H:i", strtotime($assinatura["DataInicio"]))
                                                : "-"
                                            ?>
                                        </td>

                                        <td>
                                            <?= !empty($assinatura["DataFim"])
                                                ? date("d/m/Y H:i", strtotime($assinatura["DataFim"]))
                                                : "-"
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários ativos</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalUsuarios ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ordens de Serviço</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalOS ?>
                    </h3>

                    <div class="input-help">
                        <?= (int)$totalOSAtivas ?> ativa(s)
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-7">
            <div class="card form-card h-100">
                <div class="card-header">
                    Dados da Empresa
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="small text-muted">Nome Fantasia</div>
                            <strong><?= htmlspecialchars($empresa["NomeFantasia"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="small text-muted">Razão Social</div>
                            <strong><?= htmlspecialchars($empresa["RazaoSocial"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="small text-muted">CNPJ</div>
                            <strong><?= htmlspecialchars($empresa["Cnpj"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="small text-muted">Telefone</div>
                            <strong><?= htmlspecialchars($empresa["Telefone"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="small text-muted">WhatsApp</div>
                            <strong><?= htmlspecialchars($empresa["WhatsApp"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="small text-muted">E-mail</div>
                            <strong><?= htmlspecialchars($empresa["Email"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="small text-muted">Slug</div>
                            <strong><?= htmlspecialchars($empresa["Slug"] ?? "-") ?></strong>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Data de Cadastro</div>
                            <strong>
                                <?= !empty($empresa["DataCadastro"])
                                    ? date("d/m/Y H:i", strtotime($empresa["DataCadastro"]))
                                    : "-"
                                ?>
                            </strong>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card form-card h-100">
                <div class="card-header">
                    Alterar Plano
                </div>

                <div class="card-body">

                    <form method="post" action="alterar_plano_empresa.php">
                        <?= csrfInput() ?>

                        <input type="hidden" name="EmpresaId" value="<?= (int)$empresa["EmpresaId"] ?>">

                        <div class="mb-3">
                            <label class="form-label">Plano</label>

                            <select name="PlanoId" class="form-select" required>
                                <?php foreach ($planos as $plano): ?>
                                    <option 
                                        value="<?= (int)$plano["PlanoId"] ?>"
                                        <?= (int)($empresa["PlanoId"] ?? 0) === (int)$plano["PlanoId"] ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($plano["Nome"]) ?>
                                        - R$ <?= number_format((float)$plano["ValorMensal"], 2, ",", ".") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-light border">
                            <strong>Plano atual:</strong>
                            <?= htmlspecialchars($empresa["PlanoAtual"] ?? "Sem plano") ?>
                            <br>

                            <strong>Limite OS/mês:</strong>
                            <?= htmlspecialchars(formatarLimiteAdmin($empresa["LimiteOSMes"] ?? null)) ?>
                            <br>

                            <strong>Limite usuários:</strong>
                            <?= htmlspecialchars(formatarLimiteAdmin($empresa["LimiteUsuarios"] ?? null)) ?>
                        </div>

                        <button 
                            type="submit" 
                            class="btn btn-primary w-100"
                            onclick="return confirm('Deseja alterar o plano desta empresa?')"
                        >
                            Alterar Plano
                        </button>

                    </form>

                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Clientes ativos</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalClientes ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Serviços ativos</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalServicos ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">OS ativas</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalOSAtivas ?></h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Usuários da Empresa
        </div>

        <div class="card-body p-0">

            <?php if (count($usuarios) === 0): ?>
                <div class="empty-state">
                    Nenhum usuário cadastrado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($usuario["Nome"] ?? "-") ?></strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($usuario["Email"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-dark">
                                            <?= htmlspecialchars($usuario["Perfil"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int)$usuario["Ativo"] === 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= !empty($usuario["DataCadastro"])
                                            ? date("d/m/Y", strtotime($usuario["DataCadastro"]))
                                            : "-"
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Últimas Ordens de Serviço
        </div>

        <div class="card-body p-0">

            <?php if (count($ultimasOS) === 0): ?>
                <div class="empty-state">
                    Nenhuma ordem de serviço cadastrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Abertura</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($ultimasOS as $os): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($os["CodigoOS"] ?? ("#" . $os["OrdemServicoId"])) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($os["ClienteNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($os["Titulo"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= classeStatusAdmin($os["Status"] ?? "") ?>">
                                            <?= htmlspecialchars($os["Status"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($os["Prioridade"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= !empty($os["DataAbertura"])
                                            ? date("d/m/Y", strtotime($os["DataAbertura"]))
                                            : "-"
                                        ?>
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
