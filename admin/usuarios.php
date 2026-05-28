<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$filtroEmpresa = trim($_GET["empresa"] ?? "");
$filtroPerfil = trim($_GET["perfil"] ?? "");
$filtroStatus = trim($_GET["status"] ?? "");

$where = [];
$params = [];

if ($filtroEmpresa !== "") {
    $where[] = "e.NomeFantasia LIKE :Empresa";
    $params[":Empresa"] = "%" . $filtroEmpresa . "%";
}

if ($filtroPerfil !== "") {
    $where[] = "u.Perfil = :Perfil";
    $params[":Perfil"] = $filtroPerfil;
}

if ($filtroStatus !== "") {
    $where[] = "u.Ativo = :Ativo";
    $params[":Ativo"] = (int)$filtroStatus;
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        u.UsuarioId,
        u.EmpresaId,
        u.Nome,
        u.Email,
        u.Perfil,
        u.Ativo,
        u.DataCadastro,
        e.NomeFantasia AS EmpresaNome,
        e.Ativo AS EmpresaAtiva
    FROM OS_Usuarios u
    INNER JOIN OS_Empresas e ON e.EmpresaId = u.EmpresaId
    $sqlWhere
    ORDER BY u.UsuarioId DESC
";

$stmt = $conn->prepare($sql);

foreach ($params as $param => $valor) {
    if ($param === ":Ativo") {
        $stmt->bindValue($param, $valor, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($param, $valor);
    }
}

$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsuarios = count($usuarios);
$totalAtivos = count(array_filter($usuarios, fn($u) => (int)$u["Ativo"] === 1));
$totalInativos = count(array_filter($usuarios, fn($u) => (int)$u["Ativo"] === 0));
$totalSuperAdmins = count(array_filter($usuarios, fn($u) => ($u["Perfil"] ?? "") === "SuperAdmin"));

function classePerfilAdminUsuario($perfil)
{
    if ($perfil === "SuperAdmin") {
        return "bg-danger";
    }

    if ($perfil === "Admin") {
        return "bg-dark";
    }

    if ($perfil === "Atendente") {
        return "bg-primary";
    }

    if ($perfil === "Tecnico") {
        return "bg-info text-dark";
    }

    return "bg-secondary";
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Usuários da Plataforma</h3>
            <p>
                Visualize todos os usuários cadastrados em todas as empresas do DirectOS.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="empresas.php" class="btn btn-outline-primary">
                Empresas
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários encontrados</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalUsuarios ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários ativos</div>

                    <h3 class="mb-0 mt-2 text-success">
                        <?= (int)$totalAtivos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários inativos</div>

                    <h3 class="mb-0 mt-2 text-secondary">
                        <?= (int)$totalInativos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">SuperAdmins</div>

                    <h3 class="mb-0 mt-2 text-danger">
                        <?= (int)$totalSuperAdmins ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Filtros
        </div>

        <div class="card-body">
            <form method="get" action="usuarios.php">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Empresa</label>
                        <input 
                            type="text" 
                            name="empresa" 
                            class="form-control"
                            placeholder="Nome da empresa"
                            value="<?= htmlspecialchars($filtroEmpresa) ?>"
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Perfil</label>

                        <select name="perfil" class="form-select">
                            <option value="">Todos</option>
                            <option value="SuperAdmin" <?= $filtroPerfil === "SuperAdmin" ? "selected" : "" ?>>SuperAdmin</option>
                            <option value="Admin" <?= $filtroPerfil === "Admin" ? "selected" : "" ?>>Admin</option>
                            <option value="Atendente" <?= $filtroPerfil === "Atendente" ? "selected" : "" ?>>Atendente</option>
                            <option value="Tecnico" <?= $filtroPerfil === "Tecnico" ? "selected" : "" ?>>Técnico</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= $filtroStatus === "1" ? "selected" : "" ?>>Ativo</option>
                            <option value="0" <?= $filtroStatus === "0" ? "selected" : "" ?>>Inativo</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>

                        <a href="usuarios.php" class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>

                </div>

            </form>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Usuários cadastrados</span>

            <span class="badge bg-primary">
                <?= count($usuarios) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($usuarios) === 0): ?>
                <div class="empty-state">
                    Nenhum usuário encontrado.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Empresa</th>
                                <th>Perfil</th>
                                <th>Status Usuário</th>
                                <th>Status Empresa</th>
                                <th>Cadastro</th>
                                <th width="110">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= (int)$usuario["UsuarioId"] ?></strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($usuario["Nome"] ?? "-") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($usuario["Email"] ?? "") ?>
                                        </div>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($usuario["EmpresaNome"] ?? "-") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            Empresa #<?= (int)$usuario["EmpresaId"] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge <?= classePerfilAdminUsuario($usuario["Perfil"] ?? "") ?>">
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
                                        <?php if ((int)$usuario["EmpresaAtiva"] === 1): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= !empty($usuario["DataCadastro"])
                                            ? date("d/m/Y", strtotime($usuario["DataCadastro"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <a 
                                            href="empresa.php?id=<?= (int)$usuario["EmpresaId"] ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Empresa
                                        </a>
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