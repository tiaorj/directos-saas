<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";


$empresaId = (int)$_SESSION["EmpresaId"];
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
    WHERE os.OrdemServicoId = :OrdemServicoId AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
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
        AND EXISTS (
            SELECT 1
            FROM OS_OrdensServico os2
            WHERE os2.OrdemServicoId = h.OrdemServicoId
                AND os2.EmpresaId = :EmpresaId
        )
    ORDER BY h.DataRegistro DESC
";

$stmtHistorico = $conn->prepare($sqlHistorico);
$stmtHistorico->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtHistorico->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtHistorico->execute();

$historicos = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

$sqlAnexos = "
    SELECT
        AnexoId,
        NomeOriginal,
        CaminhoArquivo,
        TipoArquivo,
        TamanhoBytes,
        VisivelCliente,
        DataCadastro
    FROM OS_OrdensServicoAnexos
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
    ORDER BY AnexoId DESC
";

$stmtAnexos = $conn->prepare($sqlAnexos);
$stmtAnexos->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtAnexos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtAnexos->execute();

$anexos = $stmtAnexos->fetchAll(PDO::FETCH_ASSOC);

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
            <a href="anexar.php?id=<?= $ordem["OrdemServicoId"] ?>" class="btn btn-success">
                Anexar Arquivo
            </a>            
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
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span>Anexos da OS</span>

        <a href="anexar.php?id=<?= $ordem["OrdemServicoId"] ?>" class="btn btn-sm btn-light">
            Novo Anexo
        </a>
    </div>

    <div class="card-body">
        <?php if (count($anexos) === 0): ?>
            <p class="text-muted mb-0">
                Nenhum anexo cadastrado.
            </p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Arquivo</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Visível Cliente</th>
                            <th>Data</th>
                            <th width="260">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($anexos as $anexo): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($anexo["NomeOriginal"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($anexo["TipoArquivo"] ?? "-") ?>
                                </td>

                                <td>
                                    <?= number_format(((int)$anexo["TamanhoBytes"] / 1024), 2, ",", ".") ?> KB
                                </td>

                                <td>
                                    <?php if ((int)$anexo["VisivelCliente"] === 1): ?>
                                        <span class="badge bg-success">Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y H:i", strtotime($anexo["DataCadastro"])) ?>
                                </td>

                                <td>
                                    <a 
                                        href="abrir_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-info"
                                    >
                                        Abrir
                                    </a>

                                    <a 
                                        href="alternar_visibilidade_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>" 
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <?= (int)$anexo["VisivelCliente"] === 1 ? "Ocultar" : "Liberar" ?>
                                    </a>

                                    <a 
                                        href="excluir_anexo.php?id=<?= (int)$anexo["AnexoId"] ?>" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Deseja realmente excluir este anexo?')"
                                    >
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
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