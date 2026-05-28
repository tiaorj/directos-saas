<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

$empresaId = $_SESSION["EmpresaId"];

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
    WHERE EmpresaId = :EmpresaId
    ORDER BY ClienteId DESC
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Clientes</h3>
            <p class="text-muted mb-0">
                Gerencie os clientes vinculados à sua empresa.
            </p>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            + Novo Cliente
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Clientes cadastrados</strong>

            <span class="badge bg-primary">
                <?= count($clientes) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle table-os">
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
                                <td colspan="9">
                                    <div class="empty-state">
                                        Nenhum cliente cadastrado até o momento.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= $cliente["ClienteId"] ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($cliente["Nome"] ?? "") ?>
                                    </strong>

                                    <?php if (!empty($cliente["Email"])): ?>
                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($cliente["Email"]) ?>
                                        </div>
                                    <?php endif; ?>                              
                                </td>

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
                                    <div class="table-actions">

                                        <a 
                                            href="editar.php?id=<?= $cliente["ClienteId"] ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Editar
                                        </a>

                                        <?php if ((int)$cliente["Ativo"] === 1): ?>
                                            <a 
                                                href="excluir.php?id=<?= $cliente["ClienteId"] ?>&<?= csrfTokenUrl() ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Deseja realmente inativar este cliente?')"
                                            >
                                                Inativar
                                            </a>
                                        <?php endif; ?>

                                    </div>
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
