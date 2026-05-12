<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

$sql = "
    SELECT 
        os.OrdemServicoId,
        CodigoOS,
        os.Titulo,
        os.DescricaoProblema,
        os.DescricaoSolucao,
        os.Status,
        os.Observacao,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        c.Nome AS ClienteNome,
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
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Atendimento da OS <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?></h3>
        <p class="text-muted mb-0">
            Atualização técnica da ordem de serviço
        </p>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Dados da OS
        </div>

        <div class="card-body">
            <strong>Código:</strong>
            <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?><br>
            <strong>Cliente:</strong> <?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?><br>
            <strong>Serviço:</strong> <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?><br>
            <strong>Título:</strong> <?= htmlspecialchars($ordem["Titulo"] ?? "") ?><br>
            <strong>Status atual:</strong> <?= htmlspecialchars($ordem["Status"] ?? "") ?><br>
            <strong>Data de abertura:</strong>
            <?= !empty($ordem["DataAbertura"]) ? date("d/m/Y H:i", strtotime($ordem["DataAbertura"])) : "-" ?><br>
            <strong>Data de previsão:</strong>
            <?= !empty($ordem["DataPrevisao"]) ? date("d/m/Y", strtotime($ordem["DataPrevisao"])) : "-" ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white">
            Descrição do Problema
        </div>

        <div class="card-body">
            <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"] ?? "")) ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="salvar_atendimento.php">

                <input type="hidden" name="OrdemServicoId" value="<?= $ordem["OrdemServicoId"] ?>">

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Status" class="form-control">
                        <option value="Aberta" <?= $ordem["Status"] === "Aberta" ? "selected" : "" ?>>Aberta</option>
                        <option value="Em andamento" <?= $ordem["Status"] === "Em andamento" ? "selected" : "" ?>>Em andamento</option>
                        <option value="Aguardando cliente" <?= $ordem["Status"] === "Aguardando cliente" ? "selected" : "" ?>>Aguardando cliente</option>
                        <option value="Aguardando peça" <?= $ordem["Status"] === "Aguardando peça" ? "selected" : "" ?>>Aguardando peça</option>
                        <option value="Concluída" <?= $ordem["Status"] === "Concluída" ? "selected" : "" ?>>Concluída</option>
                        <option value="Cancelada" <?= $ordem["Status"] === "Cancelada" ? "selected" : "" ?>>Cancelada</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Solução Aplicada</label>
                    <textarea name="DescricaoSolucao" class="form-control" rows="5"><?= htmlspecialchars($ordem["DescricaoSolucao"] ?? "") ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="Observacao" class="form-control" rows="3"><?= htmlspecialchars($ordem["Observacao"] ?? "") ?></textarea>
                </div>

                <button type="submit" class="btn btn-success">
                    Salvar Atendimento
                </button>

                <a href="visualizar.php?id=<?= $ordem["OrdemServicoId"] ?>" class="btn btn-info">
                    Visualizar
                </a>

                <a href="listar.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>