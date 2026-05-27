<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = $_GET["id"] ?? 0;

$sql = "
    SELECT 
        OrdemServicoId,
        CodigoOS,
        ClienteId,
        ServicoId,
        Titulo,
        DescricaoProblema,
        DescricaoSolucao,
        Status,
        Prioridade,
        ValorPrevisto,
        ValorFinal,
        DataPrevisao,
        DataConclusao,
        Observacao,
        MostrarValorCliente,
        MostrarSolucaoCliente,
        MostrarHistoricoCliente
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE Ativo = 1 AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sqlServicos = "
    SELECT ServicoId, Nome, ValorBase
    FROM OS_Servicos
    WHERE Ativo = 1 AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtServicos = $conn->prepare($sqlServicos);
$stmtServicos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtServicos->execute();
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Editar Ordem de Serviço 
                <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?>
            </h3>
            <p>Atualize as informações, status, valores e regras da área do cliente.</p>
        </div>

        <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>
    <div class="card form-card">
        <div class="card-header">
            Dados da Ordem de Serviço
        </div>

        <div class="card-body">

            <form method="post" action="atualizar.php">

                <input type="hidden" name="OrdemServicoId" value="<?= $ordem["OrdemServicoId"] ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="ClienteId" class="form-control" required>
                            <option value="">Selecione...</option>

                            <?php foreach ($clientes as $cliente): ?>
                                <option 
                                    value="<?= $cliente["ClienteId"] ?>"
                                    <?= (int)$ordem["ClienteId"] === (int)$cliente["ClienteId"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($cliente["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Serviço</label>
                        <select name="ServicoId" class="form-control" id="ServicoId">
                            <option value="">Selecione...</option>

                            <?php foreach ($servicos as $servico): ?>
                                <option 
                                    value="<?= $servico["ServicoId"] ?>"
                                    data-valor="<?= $servico["ValorBase"] ?>"
                                    <?= (int)$ordem["ServicoId"] === (int)$servico["ServicoId"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($servico["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" name="Titulo" class="form-control" required maxlength="150"
                           value="<?= htmlspecialchars($ordem["Titulo"] ?? "") ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição do Problema</label>
                    <textarea name="DescricaoProblema" class="form-control" rows="4"><?= htmlspecialchars($ordem["DescricaoProblema"] ?? "") ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Solução Aplicada</label>
                    <textarea name="DescricaoSolucao" class="form-control" rows="4"><?= htmlspecialchars($ordem["DescricaoSolucao"] ?? "") ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="Status" class="form-control" id="Status">
                            <option value="Aberta" <?= $ordem["Status"] === "Aberta" ? "selected" : "" ?>>Aberta</option>
                            <option value="Em andamento" <?= $ordem["Status"] === "Em andamento" ? "selected" : "" ?>>Em andamento</option>
                            <option value="Aguardando cliente" <?= $ordem["Status"] === "Aguardando cliente" ? "selected" : "" ?>>Aguardando cliente</option>
                            <option value="Aguardando peça" <?= $ordem["Status"] === "Aguardando peça" ? "selected" : "" ?>>Aguardando peça</option>
                            <option value="Concluída" <?= $ordem["Status"] === "Concluída" ? "selected" : "" ?>>Concluída</option>
                            <option value="Cancelada" <?= $ordem["Status"] === "Cancelada" ? "selected" : "" ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prioridade</label>
                        <select name="Prioridade" class="form-control">
                            <option value="Baixa" <?= $ordem["Prioridade"] === "Baixa" ? "selected" : "" ?>>Baixa</option>
                            <option value="Normal" <?= $ordem["Prioridade"] === "Normal" ? "selected" : "" ?>>Normal</option>
                            <option value="Alta" <?= $ordem["Prioridade"] === "Alta" ? "selected" : "" ?>>Alta</option>
                            <option value="Urgente" <?= $ordem["Prioridade"] === "Urgente" ? "selected" : "" ?>>Urgente</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Data de Previsão</label>
                        <input type="date" name="DataPrevisao" class="form-control"
                               value="<?= !empty($ordem["DataPrevisao"]) ? date("Y-m-d", strtotime($ordem["DataPrevisao"])) : "" ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Previsto</label>
                        <input type="number" step="0.01" name="ValorPrevisto" id="ValorPrevisto" class="form-control"
                               value="<?= $ordem["ValorPrevisto"] ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Final</label>
                        <input type="number" step="0.01" name="ValorFinal" class="form-control"
                               value="<?= $ordem["ValorFinal"] ?>">
                    </div>
                </div>

                <?php if (!empty($ordem["DataConclusao"])): ?>
                    <div class="mb-3">
                        <label class="form-label">Data de Conclusão</label>
                        <input type="text" class="form-control" disabled
                               value="<?= date("d/m/Y H:i", strtotime($ordem["DataConclusao"])) ?>">
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="Observacao" class="form-control" rows="3"><?= htmlspecialchars($ordem["Observacao"] ?? "") ?></textarea>
                </div>
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        Área do Cliente
                    </div>

                    <div class="card-body">
                        <p class="text-muted">
                            Defina quais informações desta OS ficarão visíveis no link público enviado ao cliente.
                        </p>

                        <div class="form-check mb-2">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="MostrarValorCliente" 
                                id="MostrarValorCliente" 
                                value="1"
                                <?= (int)($ordem["MostrarValorCliente"] ?? 1) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="MostrarValorCliente">
                                Mostrar valores para o cliente
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="MostrarSolucaoCliente" 
                                id="MostrarSolucaoCliente" 
                                value="1"
                                <?= (int)($ordem["MostrarSolucaoCliente"] ?? 1) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="MostrarSolucaoCliente">
                                Mostrar solução aplicada para o cliente
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="MostrarHistoricoCliente" 
                                id="MostrarHistoricoCliente" 
                                value="1"
                                <?= (int)($ordem["MostrarHistoricoCliente"] ?? 1) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="MostrarHistoricoCliente">
                                Mostrar histórico de movimentações para o cliente
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Atualizar OS
                    </button>
                    <a href="visualizar.php?id=<?= $ordem["OrdemServicoId"] ?>" class="btn btn-info">
                        Visualizar
                    </a>
                    <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>


            </form>

        </div>
    </div>

</div>

<script>
document.getElementById("ServicoId").addEventListener("change", function () {
    var option = this.options[this.selectedIndex];
    var valor = option.getAttribute("data-valor");
    var campoValorPrevisto = document.getElementById("ValorPrevisto");

    if (valor !== null && valor !== "" && campoValorPrevisto.value === "") {
        campoValorPrevisto.value = valor;
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>