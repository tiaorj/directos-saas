<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";

$empresaId = $_SESSION["EmpresaId"];
$validacaoPlano = empresaPodeCriarOS($conn, $empresaId);

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE Ativo = 1
      AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bindValue(":EmpresaId", $_SESSION["EmpresaId"]);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sqlServicos = "
    SELECT ServicoId, Nome, ValorBase
    FROM OS_Servicos
    WHERE Ativo = 1
    AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtServicos = $conn->prepare($sqlServicos);
$stmtServicos->bindValue(":EmpresaId", $_SESSION["EmpresaId"]);
$stmtServicos->execute();
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Nova Ordem de Serviço</h3>
            <p>Preencha os dados da OS para iniciar o atendimento.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>
    <?php if ($validacaoPlano["plano"]): ?>
        <div class="alert alert-info">
            <strong>Plano atual:</strong>
            <?= htmlspecialchars($validacaoPlano["plano"]["Nome"]) ?>

            <?php if ($validacaoPlano["limite"] !== null): ?>
                · Uso mensal:
                <?= (int)$validacaoPlano["totalMes"] ?> /
                <?= (int)$validacaoPlano["limite"] ?> OS
            <?php else: ?>
                · OS ilimitadas
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$validacaoPlano["permitido"]): ?>
        <div class="alert alert-warning">
            <strong>Atenção:</strong>
            <?= htmlspecialchars($validacaoPlano["mensagem"]) ?>
            <br>
            Para continuar criando ordens de serviço, altere para o plano Profissional ou Empresa.
        </div>

        <a href="../dashboard.php" class="btn btn-secondary">
            Voltar
        </a>

        <?php require_once "../includes/footer.php"; ?>
        <?php exit; ?>
    <?php endif; ?>
    <div class="card form-card">
        <div class="card-header">
            Dados da Ordem de Serviço
        </div>
        <div class="card-body">

            <form method="post" action="salvar.php">
                <?= csrfInput() ?>

                <div class="row">
                    <div class="form-section-title">
                        Cliente e Serviço
                    </div>                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="ClienteId" class="form-control" required>
                            <option value="">Selecione...</option>

                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente["ClienteId"] ?>">
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
                                    data-valor="<?= $servico["ValorBase"] ?>">
                                    <?= htmlspecialchars($servico["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-section-title">
                    Detalhes da Solicitação
                </div>
                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" name="Titulo" class="form-control" required maxlength="150">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição do Problema</label>
                    <textarea name="DescricaoProblema" class="form-control" rows="4"></textarea>
                </div>

                <div class="row">
                    <div class="form-section-title">
                        Controle da OS
                    </div>                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="Status" class="form-control">
                            <option value="Aberta">Aberta</option>
                            <option value="Em andamento">Em andamento</option>
                            <option value="Aguardando cliente">Aguardando cliente</option>
                            <option value="Aguardando peça">Aguardando peça</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prioridade</label>
                        <select name="Prioridade" class="form-control">
                            <option value="Baixa">Baixa</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Data de Previsão</label>
                        <input type="date" name="DataPrevisao" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="form-section-title">
                        Valores
                    </div>                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Previsto</label>
                        <input type="number" step="0.01" name="ValorPrevisto" id="ValorPrevisto" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Final</label>
                        <input type="number" step="0.01" name="ValorFinal" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="Observacao" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar OS
                    </button>

                    <a href="listar.php" class="btn btn-outline-secondary">
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

    if (valor !== null && valor !== "") {
        document.getElementById("ValorPrevisto").value = valor;
    }
});
</script>

<?php require_once "../includes/footer.php"; ?>
