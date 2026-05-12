<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

$sql = "
    SELECT 
        ServicoId,
        Nome,
        Descricao,
        ValorBase,
        Ativo
    FROM OS_Servicos
    WHERE ServicoId = :ServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    die("Serviço não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Editar Serviço</h3>
        <p class="text-muted mb-0">Atualize os dados do serviço</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="atualizar.php">

                <input type="hidden" name="ServicoId" value="<?= $servico["ServicoId"] ?>">

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

                <button type="submit" class="btn btn-success">
                    Atualizar
                </button>

                <a href="listar.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>