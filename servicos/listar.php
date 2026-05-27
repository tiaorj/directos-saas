<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";

$empresaId = (int)$_SESSION["EmpresaId"];

$podeEditarServicos = usuarioTemPerfil(["Admin"]);

$sql = "
    SELECT 
        ServicoId,
        Nome,
        Descricao,
        ValorBase,
        Ativo,
        DataCadastro
    FROM OS_Servicos
    WHERE EmpresaId = :EmpresaId
    ORDER BY ServicoId DESC
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Serviços</h3>
            <p class="text-muted mb-0">Cadastro de serviços oferecidos</p>
        </div>

        <?php if ($podeEditarServicos): ?>
            <a href="cadastrar.php" class="btn btn-primary">
                Novo Serviço
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Valor Base</th>
                            <th>Status</th>
                            <th width="180">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($servicos) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    Nenhum serviço cadastrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?= $servico["ServicoId"] ?></td>

                                <td><?= htmlspecialchars($servico["Nome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($servico["Descricao"] ?? "") ?></td>

                                <td>
                                    R$ <?= number_format((float)$servico["ValorBase"], 2, ",", ".") ?>
                                </td>

                                <td>
                                    <?php if ((int)$servico["Ativo"] === 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($podeEditarServicos): ?>
                                        <a href="editar.php?id=<?= $servico["ServicoId"] ?>" 
                                        class="btn btn-sm btn-warning">
                                            Editar
                                        </a>

                                        <a href="excluir.php?id=<?= $servico["ServicoId"] ?>" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Deseja realmente inativar este serviço?')">
                                            Inativar
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Somente leitura</span>
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