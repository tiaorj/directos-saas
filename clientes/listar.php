<?php
require_once "../config/conexao.php";

$sql = "
    SELECT 
        ClienteId,
        Nome,
        Telefone,
        Email,
        Documento,
        Cidade,
        Estado,
        Ativo,
        DataCadastro
    FROM OS_Clientes
    ORDER BY ClienteId DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Clientes</h3>
            <p class="text-muted mb-0">Cadastro de clientes do sistema DirectOS</p>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            Novo Cliente
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
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Documento</th>
                            <th>Cidade/UF</th>
                            <th>Status</th>
                            <th width="180">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($clientes) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    Nenhum cliente cadastrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= $cliente["ClienteId"] ?></td>

                                <td><?= htmlspecialchars($cliente["Nome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($cliente["Telefone"] ?? "") ?></td>

                                <td><?= htmlspecialchars($cliente["Email"] ?? "") ?></td>

                                <td><?= htmlspecialchars($cliente["Documento"] ?? "") ?></td>

                                <td>
                                    <?= htmlspecialchars($cliente["Cidade"] ?? "") ?>
                                    <?php if (!empty($cliente["Estado"])): ?>
                                        / <?= htmlspecialchars($cliente["Estado"]) ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ((int)$cliente["Ativo"] === 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= $cliente["ClienteId"] ?>" 
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a href="excluir.php?id=<?= $cliente["ClienteId"] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Deseja realmente excluir este cliente?')">
                                        Excluir
                                    </a>
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