<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin", "Atendente", "Tecnico"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

exigirOrdemDaEmpresa($conn, $id);

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

$sql = "
    SELECT 
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.DescricaoProblema,
        os.DescricaoSolucao,
        os.Status,
        os.Observacao,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome,
        s.ChecklistPadrao AS ServicoChecklistPadrao
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId AND s.EmpresaId = os.EmpresaId
    WHERE os.OrdemServicoId = :OrdemServicoId 
      AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT));
$checklistPadrao = trim($ordem["ServicoChecklistPadrao"] ?? "");
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Atendimento da OS <?= htmlspecialchars($codigoOS) ?>
            </h3>

            <p>
                Atualização técnica da ordem de serviço
            </p>
        </div>

        <div class="form-actions" style="border-top: 0; margin-top: 0; padding-top: 0;">
            <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-info">
                Visualizar
            </a>

            <a href="listar.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-lg-5">

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white">
                    Dados da OS
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">Código</div>
                        <strong><?= htmlspecialchars($codigoOS) ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Cliente</div>
                        <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Serviço</div>
                        <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Título</div>
                        <strong><?= htmlspecialchars($ordem["Titulo"] ?? "") ?></strong>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Status atual</div>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($ordem["Status"] ?? "") ?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Data de abertura</div>
                        <strong>
                            <?= !empty($ordem["DataAbertura"]) ? date("d/m/Y H:i", strtotime($ordem["DataAbertura"])) : "-" ?>
                        </strong>
                    </div>

                    <div class="mb-0">
                        <div class="small text-muted">Data de previsão</div>
                        <strong>
                            <?= !empty($ordem["DataPrevisao"]) ? date("d/m/Y", strtotime($ordem["DataPrevisao"])) : "-" ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">
                    Descrição do Problema
                </div>

                <div class="card-body">
                    <?php if (!empty($ordem["DescricaoProblema"])): ?>
                        <?= nl2br(htmlspecialchars($ordem["DescricaoProblema"])) ?>
                    <?php else: ?>
                        <span class="text-muted">Nenhuma descrição informada.</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($checklistPadrao !== ""): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <strong>Checklist padrão do serviço</strong>
                    </div>

                    <div class="card-body">
                        <div id="textoChecklistServico" class="mb-3" style="white-space: pre-line;"><?= htmlspecialchars($checklistPadrao) ?></div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarChecklistServico()">
                                Copiar checklist
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarChecklistNaSolucao()">
                                Adicionar à solução
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarChecklistNaObservacao()">
                                Adicionar à observação
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <strong>Checklist padrão do serviço</strong>
                    </div>

                    <div class="card-body">
                        <span class="text-muted">
                            Este serviço ainda não possui checklist padrão cadastrado.
                        </span>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-7">

            <div class="card form-card">
                <div class="card-header">
                    Atualização do Atendimento
                </div>

                <div class="card-body">

                    <form method="post" action="salvar_atendimento.php">
                        <?= csrfInput() ?>

                        <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordem["OrdemServicoId"] ?>">

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
                            <textarea name="DescricaoSolucao" class="form-control" rows="7"><?= htmlspecialchars($ordem["DescricaoSolucao"] ?? "") ?></textarea>

                            <div class="input-help mt-2">
                                Registre o que foi executado, testes realizados e conclusão técnica.
                            </div>

                            <div id="undoSolucaoChecklist" class="mt-2 d-none">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerSolucaoChecklist()">
                                    Desfazer checklist adicionado à solução
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observação / Checklist técnico</label>
                            <textarea name="Observacao" class="form-control" rows="5"><?= htmlspecialchars($ordem["Observacao"] ?? "") ?></textarea>

                            <div class="input-help mt-2">
                                Use para observações internas, pendências, checklist técnico ou orientações do atendimento.
                            </div>

                            <div id="undoObservacaoChecklist" class="mt-2 d-none">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerObservacaoChecklist()">
                                    Desfazer checklist adicionado à observação
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                Salvar Atendimento
                            </button>

                            <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-info">
                                Visualizar
                            </a>

                            <a href="listar.php" class="btn btn-outline-secondary">
                                Voltar
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </div>

</div>

<script>
let valorAnteriorSolucaoChecklist = "";
let valorAnteriorObservacaoChecklist = "";

async function copiarChecklistServico() {
    const texto = document.getElementById("textoChecklistServico");

    if (!texto || texto.innerText.trim() === "") {
        alert("Nenhum checklist disponível.");
        return;
    }

    try {
        await navigator.clipboard.writeText(texto.innerText);
        alert("Checklist copiado.");
    } catch (e) {
        alert("Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.");
    }
}

function adicionarChecklistNaSolucao() {
    const texto = document.getElementById("textoChecklistServico");
    const solucao = document.querySelector('[name="DescricaoSolucao"]');
    const undo = document.getElementById("undoSolucaoChecklist");

    if (!texto || texto.innerText.trim() === "") {
        alert("Nenhum checklist disponível.");
        return;
    }

    if (!solucao) {
        return;
    }

    valorAnteriorSolucaoChecklist = solucao.value;

    const bloco = "Checklist padrão do serviço:\n" + texto.innerText.trim();

    solucao.value = solucao.value.trim() !== ""
        ? solucao.value.trim() + "\n\n" + bloco
        : bloco;

    if (undo) {
        undo.classList.remove("d-none");
    }

    solucao.focus();
}

function adicionarChecklistNaObservacao() {
    const texto = document.getElementById("textoChecklistServico");
    const observacao = document.querySelector('[name="Observacao"]');
    const undo = document.getElementById("undoObservacaoChecklist");

    if (!texto || texto.innerText.trim() === "") {
        alert("Nenhum checklist disponível.");
        return;
    }

    if (!observacao) {
        return;
    }

    valorAnteriorObservacaoChecklist = observacao.value;

    const bloco = "Checklist padrão do serviço:\n" + texto.innerText.trim();

    observacao.value = observacao.value.trim() !== ""
        ? observacao.value.trim() + "\n\n" + bloco
        : bloco;

    if (undo) {
        undo.classList.remove("d-none");
    }

    observacao.focus();
}

function desfazerSolucaoChecklist() {
    const solucao = document.querySelector('[name="DescricaoSolucao"]');
    const undo = document.getElementById("undoSolucaoChecklist");

    if (!solucao) {
        return;
    }

    solucao.value = valorAnteriorSolucaoChecklist;

    if (undo) {
        undo.classList.add("d-none");
    }

    solucao.focus();
}

function desfazerObservacaoChecklist() {
    const observacao = document.querySelector('[name="Observacao"]');
    const undo = document.getElementById("undoObservacaoChecklist");

    if (!observacao) {
        return;
    }

    observacao.value = valorAnteriorObservacaoChecklist;

    if (undo) {
        undo.classList.add("d-none");
    }

    observacao.focus();
}
</script>

<?php require_once "../includes/footer.php"; ?>