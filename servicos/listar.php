<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

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

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Serviços</h3>
            <p class="text-muted mb-0">
                Gerencie os serviços oferecidos pela sua empresa.
            </p>
        </div>

        <?php if ($podeEditarServicos): ?>
            <a href="cadastrar.php" class="btn btn-primary">
                + Novo Serviço
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Serviços cadastrados</strong>

            <span class="badge bg-primary">
                <?= count($servicos) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

        <div class="card-body">

            <div class="table-responsive desktop-only">
                <table class="table table-hover align-middle table-os">
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
                                <td colspan="6">
                                    <div class="empty-state">
                                        Nenhum serviço cadastrado até o momento.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?= $servico["ServicoId"] ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($servico["Nome"] ?? "") ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($servico["Descricao"] ?? "-") ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        R$ <?= number_format((float)$servico["ValorBase"], 2, ",", ".") ?>
                                    </strong>                                    
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
                                        <div class="table-actions">

                                            <a 
                                                href="editar.php?id=<?= $servico["ServicoId"] ?>" 
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Editar
                                            </a>

                                            <?php if ((int)$servico["Ativo"] === 1): ?>
                                                <a 
                                                    href="excluir.php?id=<?= $servico["ServicoId"] ?>&<?= csrfTokenUrl() ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Deseja realmente inativar este serviço?')"
                                                >
                                                    Inativar
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Somente leitura</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <div class="mobile-card-list">
                <?php if (count($servicos) === 0): ?>
                    <div class="empty-state">
                        Nenhum servico cadastrado ate o momento.
                    </div>
                <?php endif; ?>

                <?php foreach ($servicos as $servico): ?>
                    <article class="mobile-record-card">
                        <div class="mobile-record-header">
                            <div class="mobile-record-title">
                                <strong><?= htmlspecialchars($servico["Nome"] ?? "") ?></strong>
                                <span>#<?= (int)$servico["ServicoId"] ?></span>
                            </div>

                            <?php if ((int)$servico["Ativo"] === 1): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-record-grid">
                            <div class="mobile-record-field">
                                <small>Valor base</small>
                                <strong>R$ <?= number_format((float)$servico["ValorBase"], 2, ",", ".") ?></strong>
                            </div>

                            <div class="mobile-record-field">
                                <small>Descricao</small>
                                <span><?= htmlspecialchars($servico["Descricao"] ?? "-") ?></span>
                            </div>
                        </div>

                        <?php if ($podeEditarServicos): ?>
                            <div class="mobile-record-actions">
                                <a
                                    href="editar.php?id=<?= (int)$servico["ServicoId"] ?>"
                                    class="btn btn-primary"
                                >
                                    Editar
                                </a>

                                <?php if ((int)$servico["Ativo"] === 1): ?>
                                    <a
                                        href="excluir.php?id=<?= (int)$servico["ServicoId"] ?>&<?= csrfTokenUrl() ?>"
                                        class="btn btn-outline-danger"
                                        onclick="return confirm('Deseja realmente inativar este serviÃ§o?')"
                                    >
                                        Inativar
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="mobile-record-note">
                                Somente leitura
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
