<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

$sql = "
    SELECT 
        os.*,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        c.Email AS ClienteEmail,
        c.Documento AS ClienteDocumento,
        c.Endereco AS ClienteEndereco,
        c.Cidade AS ClienteCidade,
        c.Estado AS ClienteEstado,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    WHERE os.OrdemServicoId = :OrdemServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$sqlHistorico = "
    SELECT 
        h.HistoricoId,
        h.StatusAnterior,
        h.StatusNovo,
        h.Descricao,
        h.DataRegistro,
        u.Nome AS UsuarioNome
    FROM OS_Historico h
    INNER JOIN OS_Usuarios u ON u.UsuarioId = h.UsuarioId
    WHERE h.OrdemServicoId = :OrdemServicoId
    ORDER BY h.DataRegistro DESC
";

$stmtHistorico = $conn->prepare($sqlHistorico);
$stmtHistorico->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtHistorico->execute();

$historicos = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Ordem de Serviço <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?></h3>
            <p class="text-muted mb-0"><?= htmlspecialchars($ordem["Titulo"]) ?></p>
        </div>

        <div>
            <a href="editar.php?id=<?= $ordem["OrdemServicoId"] ?>" class="btn btn-warning">
                Editar
            </a>

            <button onclick="window.print()" class="btn btn-secondary">
                Imprimir
            </button>

            <a href="listar.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Dados da OS
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Código:</strong><br>
                    <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?>
                </div>                
                <div class="col-md-4">
                    <strong>Status:</strong><br>
                    <?= htmlspecialchars($ordem["Status"]) ?>
                </div>

                <div class="col-md-4">
                    <strong>Prioridade:</strong><br>
                    <?= htmlspecialchars($ordem["Prioridade"]) ?>
                </div>

                <div class="col-md-4">
                    <strong>Serviço:</strong><br>
                    <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-4">
                    <strong>Data de Abertura:</strong><br>
                    <?= date("d/m/Y H:i", strtotime($ordem["DataAbertura"])) ?>
                </div>

                <div class="col-md-4">
                    <strong>Data de Previsão:</strong><br>
                    <?= !empty($ordem["DataPrevisao"]) ? date("d/m/Y", strtotime($ordem["DataPrevisao"])) : "-" ?>
                </div>

                <div class="col-md-4">
                    <strong>Data de Conclusão:</strong><br>
                    <?= !empty($ordem["DataConclusao"]) ? date("d/m/Y H:i", strtotime($ordem["DataConclusao"])) : "-" ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Cliente
        </div>

        <div class="card-body">
            <strong>Nome:</strong> <?= htmlspecialchars($ordem["ClienteNome"]) ?><br>
            <strong>Telefone:</strong> <?= htmlspecialchars($ordem["ClienteTelefone"] ?? "") ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($ordem["ClienteEmail"] ?? "") ?><br>
            <strong>Documento:</strong> <?= htmlspecialchars($ordem["ClienteDocumento"] ?? "") ?><br>
            <strong>Endereço:</strong>
            <?= htmlspecialchars($ordem["ClienteEndereco"] ?? "") ?>
            <?= htmlspecialchars($ordem["ClienteCidade"] ?? "") ?>
            <?= htmlspecialchars($ordem["ClienteEstado"] ?? "") ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Descrição do Problema
        </div>

        <div class="card-body">
            <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"] ?? "")) ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Solução Aplicada
        </div>

        <div class="card-body">
            <?= nl2br(htmlspecialchars($ordem["DescricaoSolucao"] ?? "")) ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Valores
        </div>

        <div class="card-body">
            <strong>Valor Previsto:</strong>
            R$ <?= number_format((float)$ordem["ValorPrevisto"], 2, ",", ".") ?><br>

            <strong>Valor Final:</strong>
            R$ <?= number_format((float)$ordem["ValorFinal"], 2, ",", ".") ?>
        </div>
    </div>

    <?php if (!empty($ordem["Observacao"])): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                Observação
            </div>

            <div class="card-body">
                <?= nl2br(htmlspecialchars($ordem["Observacao"])) ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-dark text-white">
        Histórico de Movimentações
    </div>

    <div class="card-body">

        <?php if (count($historicos) === 0): ?>
            <p class="text-muted mb-0">
                Nenhuma movimentação registrada.
            </p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Status Anterior</th>
                            <th>Status Novo</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($historicos as $hist): ?>
                            <tr>
                                <td>
                                    <?= date("d/m/Y H:i", strtotime($hist["DataRegistro"])) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hist["UsuarioNome"] ?? "") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hist["StatusAnterior"] ?? "-") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hist["StatusNovo"] ?? "-") ?>
                                </td>

                                <td>
                                    <?= nl2br(htmlspecialchars($hist["Descricao"] ?? "")) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>