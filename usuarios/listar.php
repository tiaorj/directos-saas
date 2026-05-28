<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$sql = "
    SELECT 
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

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Usuários</h3>
            <p class="text-muted mb-0">
                Gerencie os usuários que acessam o sistema da sua empresa.
            </p>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            + Novo Usuário
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Usuários cadastrados</strong>

            <span class="badge bg-primary">
                <?= count($usuarios) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-os">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th width="180">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($usuarios) === 0): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        Nenhum usuário cadastrado até o momento.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario["UsuarioId"] ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($usuario["Nome"] ?? "") ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($usuario["Email"] ?? "") ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                        $perfil = $usuario["Perfil"] ?? "";
                                        $classePerfil = "bg-secondary";

                                        if ($perfil === "Admin") {
                                            $classePerfil = "bg-dark";
                                        } elseif ($perfil === "Atendente") {
                                            $classePerfil = "bg-primary";
                                        } elseif ($perfil === "Tecnico") {
                                            $classePerfil = "bg-info text-dark";
                                        }
                                    ?>

                                    <span class="badge <?= $classePerfil ?>">
                                        <?= htmlspecialchars($perfil) ?>
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
                                        ? date("d/m/Y H:i", strtotime($usuario["DataCadastro"])) 
                                        : "-" 
                                    ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="editar.php?id=<?= $usuario["UsuarioId"] ?>" 
                                        class="btn btn-sm btn-outline-primary">
                                            Editar
                                        </a>

                                    <?php if ((int)$usuario["UsuarioId"] !== (int)$_SESSION["UsuarioId"]): ?>
                                        <a href="excluir.php?id=<?= $usuario["UsuarioId"] ?>&<?= csrfTokenUrl() ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Deseja realmente inativar este usuário?')">
                                            Inativar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
