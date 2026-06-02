<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$sql = "
    SELECT
        CampoId,
        NomeCampo,
        Rotulo,
        TipoCampo,
        Obrigatorio,
        Ordem,
        Ativo,
        DataCadastro
    FROM OS_CamposPersonalizados
    WHERE EmpresaId = :EmpresaId
    ORDER BY Ordem, Rotulo
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$campos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensagem = trim($_GET["mensagem"] ?? "");
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">
    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>
    <div class="form-header">
        <div>
            <h3 class="mb-1">Campos Personalizados da OS</h3>
            <p>Configure campos extras para adaptar a ordem de serviço ao seu tipo de negócio.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="modelos.php" class="btn btn-outline-primary">
                Modelos prontos
            </a>

            <a href="cadastrar.php" class="btn btn-success">
                Novo Campo
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Campos cadastrados</strong>
        </div>

        <div class="card-body p-0">
            <?php if (count($campos) === 0): ?>
                <div class="empty-state">
                    Nenhum campo personalizado cadastrado.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Rótulo</th>
                                <th>Nome Técnico</th>
                                <th>Tipo</th>
                                <th>Obrigatório</th>
                                <th>Status</th>
                                <th width="220">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($campos as $campo): ?>
                                <tr>
                                    <td><?= (int)$campo["Ordem"] ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($campo["Rotulo"]) ?></strong>
                                    </td>

                                    <td>
                                        <span class="text-muted">
                                            <?= htmlspecialchars($campo["NomeCampo"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($campo["TipoCampo"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int)$campo["Obrigatorio"] === 1): ?>
                                            <span class="badge bg-warning text-dark">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$campo["Ativo"] === 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="table-actions">
                                            <a href="editar.php?id=<?= (int)$campo["CampoId"] ?>" class="btn btn-sm btn-outline-primary">
                                                Editar
                                            </a>

                                            <a 
                                                href="excluir.php?id=<?= (int)$campo["CampoId"] ?>&<?= csrfTokenUrl() ?>" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Deseja realmente excluir este campo?')"
                                            >
                                                Excluir
                                            </a>
                                        </div>
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