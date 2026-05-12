<?php
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

$sql = "
    SELECT 
        LancamentoId,
        UsuarioId,
        CategoriaId,
        Descricao,
        ValorEstimado,
        ValorReal,
        DataVencimento,
        Pago,
        MesReferencia,
        AnoReferencia,
        Observacao,
        CarteiraId
    FROM FIN_Lancamentos
    WHERE LancamentoId = :LancamentoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":LancamentoId", $id, PDO::PARAM_INT);
$stmt->execute();

$lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lancamento) {
    die("Lançamento não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>

<h3>Editar Lançamento</h3>

<form method="post" action="atualizar.php">

    <input type="hidden" name="LancamentoId" value="<?= $lancamento["LancamentoId"] ?>">
    <input type="hidden" name="UsuarioId" value="<?= $lancamento["UsuarioId"] ?>">

    <div class="row">
        <div class="col-md-8 mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" name="Descricao" class="form-control"
                   value="<?= htmlspecialchars($lancamento["Descricao"] ?? "") ?>" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Categoria ID</label>
            <input type="number" name="CategoriaId" class="form-control"
                   value="<?= $lancamento["CategoriaId"] ?>">
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Valor Estimado</label>
            <input type="number" step="0.01" name="ValorEstimado" class="form-control"
                   value="<?= $lancamento["ValorEstimado"] ?>" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Valor Real</label>
            <input type="number" step="0.01" name="ValorReal" class="form-control"
                   value="<?= $lancamento["ValorReal"] ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Data de Vencimento</label>
            <input type="date" name="DataVencimento" class="form-control"
                   value="<?= !empty($lancamento["DataVencimento"]) ? date("Y-m-d", strtotime($lancamento["DataVencimento"])) : "" ?>"
                   required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Pago?</label>
            <select name="Pago" class="form-control">
                <option value="0" <?= (int)$lancamento["Pago"] === 0 ? "selected" : "" ?>>Não</option>
                <option value="1" <?= (int)$lancamento["Pago"] === 1 ? "selected" : "" ?>>Sim</option>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Mês Referência</label>
            <input type="number" name="MesReferencia" class="form-control"
                   value="<?= $lancamento["MesReferencia"] ?>" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Ano Referência</label>
            <input type="number" name="AnoReferencia" class="form-control"
                   value="<?= $lancamento["AnoReferencia"] ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Carteira ID</label>
        <input type="number" name="CarteiraId" class="form-control"
               value="<?= $lancamento["CarteiraId"] ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Observação</label>
        <textarea name="Observacao" class="form-control" rows="3"><?= htmlspecialchars($lancamento["Observacao"] ?? "") ?></textarea>
    </div>

    <button type="submit" class="btn btn-success">
        Atualizar
    </button>

    <a href="listar.php" class="btn btn-secondary">
        Voltar
    </a>

</form>

<?php require_once "../includes/footer.php"; ?>