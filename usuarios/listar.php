<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$sql = "
    SELECT 
        UsuarioId,
        Nome,
        Email,
        Perfil,
        Ativo,
        DataCadastro
    FROM OS_Usuarios
    ORDER BY UsuarioId DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Usuários</h3>
            <p class="text-muted mb-0">Cadastro de usuários do sistema</p>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            Novo Usuário
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
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
                                <td colspan="7" class="text-center">
                                    Nenhum usuário cadastrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario["UsuarioId"] ?></td>

                                <td><?= htmlspecialchars($usuario["Nome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($usuario["Email"] ?? "") ?></td>

                                <td>
                                    <span class="badge bg-dark">
                                        <?= htmlspecialchars($usuario["Perfil"] ?? "") ?>
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
                                    <a href="editar.php?id=<?= $usuario["UsuarioId"] ?>" 
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <?php if ((int)$usuario["UsuarioId"] !== (int)$_SESSION["UsuarioId"]): ?>
                                        <a href="excluir.php?id=<?= $usuario["UsuarioId"] ?>" 
                                           class="btn btn-sm btn-danger"
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