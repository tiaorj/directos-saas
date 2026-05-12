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