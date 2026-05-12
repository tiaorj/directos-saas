<?php
require_once "../config/conexao.php";

$usuarioid = 1; // Substitua pelo ID do usuário logado
$mesReferencia = date('m'); // Mês atual
$anoReferencia = date('Y'); // Ano atual

$sql="
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
    WHERE UsuarioId = :usuarioId
      AND MesReferencia = :mesReferencia
      AND AnoReferencia = :anoReferencia
    ORDER BY DataVencimento ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':usuarioId' => $usuarioid,
    ':mesReferencia' => $mesReferencia,
    ':anoReferencia' => $anoReferencia
]);

$lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEstimado = 0;
$totalReal = 0;
$totalPago = 0;
$totalPendente = 0;

foreach ($lancamentos as $lancamento) {
    $totalEstimado += $lancamento['ValorEstimado'];
    $totalReal += $lancamento['ValorReal'];
    
    if ($lancamento['Pago']) {
        $totalPago += $lancamento['ValorReal'];
    } else {
        $totalPendente += $lancamento['ValorReal'];
    }
}
?>
<?php require_once "../includes/header.php"; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3>Lançamentos Financeiros</h3>
        <p class="text-muted mb-0">
            Referência: <?= str_pad($mesReferencia, 2, "0", STR_PAD_LEFT) ?>/<?= $anoReferencia ?>
        </p>
    </div>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo Lançamento
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <h6>Total Estimado</h6>
                <h5>R$ <?= number_format($totalEstimado, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <h6>Total Real</h6>
                <h5>R$ <?= number_format($totalReal, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body">
                <h6>Total Pago</h6>
                <h5>R$ <?= number_format($totalPago, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body">
                <h6>Total Pendente</h6>
                <h5>R$ <?= number_format($totalPendente, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>
</div>

<table class="table table-bordered table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Descrição</th>
            <th>Estimado</th>
            <th>Real</th>
            <th>Vencimento</th>
            <th>Pago</th>
            <th>Categoria</th>
            <th>Carteira</th>
            <th width="180">Ações</th>
        </tr>
    </thead>

    <tbody>
        <?php if (count($lancamentos) === 0): ?>
            <tr>
                <td colspan="9" class="text-center">
                    Nenhum lançamento encontrado.
                </td>
            </tr>
        <?php endif; ?>

        <?php foreach ($lancamentos as $lancamento): ?>
            <tr>
                <td><?= $lancamento["LancamentoId"] ?></td>

                <td>
                    <?= htmlspecialchars($lancamento["Descricao"] ?? "") ?>
                </td>

                <td>
                    R$ <?= number_format((float)$lancamento["ValorEstimado"], 2, ",", ".") ?>
                </td>

                <td>
                    R$ <?= number_format((float)$lancamento["ValorReal"], 2, ",", ".") ?>
                </td>

                <td>
                    <?= !empty($lancamento["DataVencimento"]) 
                        ? date("d/m/Y", strtotime($lancamento["DataVencimento"])) 
                        : "" 
                    ?>
                </td>

                <td>
                    <?php if ((int)$lancamento["Pago"] === 1): ?>
                        <span class="badge bg-success">Sim</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Não</span>
                    <?php endif; ?>
                </td>

                <td><?= $lancamento["CategoriaId"] ?></td>
                <td><?= $lancamento["CarteiraId"] ?></td>

                <td>
                    <a href="editar.php?id=<?= $lancamento["LancamentoId"] ?>" 
                       class="btn btn-sm btn-warning">
                        Editar
                    </a>

                    <a href="excluir.php?id=<?= $lancamento["LancamentoId"] ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Deseja realmente excluir este lançamento?')">
                        Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once "../includes/footer.php"; ?>