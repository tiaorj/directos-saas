<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$id = $_GET["id"] ?? 0;

exigirServicoDaEmpresa($conn, $id);

$sql = "
    SELECT 
        ServicoId,
        Nome,
        Descricao,
        ValorBase,
        Ativo
    FROM OS_Servicos
    WHERE ServicoId = :ServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    die("Serviço não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Editar Serviço</h3>
            <p>Atualize os dados do serviço</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Serviço
        </div>
        <div class="card-body">

            <form method="post" action="atualizar.php">
                <?= csrfInput() ?>

                <input type="hidden" name="ServicoId" value="<?= $servico["ServicoId"] ?>">
                <input type="hidden" name="EmpresaId" value="<?= $empresaId ?>">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="Nome" class="form-control" required maxlength="150"
                           value="<?= htmlspecialchars($servico["Nome"] ?? "") ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="Descricao" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars($servico["Descricao"] ?? "") ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Valor Base</label>
                    <input type="number" step="0.01" name="ValorBase" class="form-control"
                           value="<?= $servico["ValorBase"] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1" <?= (int)$servico["Ativo"] === 1 ? "selected" : "" ?>>
                            Ativo
                        </option>
                        <option value="0" <?= (int)$servico["Ativo"] === 0 ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Atualizar Serviço
                    </button>

                    <a href="listar.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
