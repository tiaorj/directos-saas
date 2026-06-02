<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/campos_os.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

exigirOrdemDaEmpresa($conn, $id);

$sql = "
    SELECT 
        os.OrdemServicoId,
        os.CodigoOS,
        os.ClienteId,
        os.ServicoId,
        os.TokenAcompanhamento,
        os.Titulo,
        os.DescricaoProblema,
        os.DescricaoSolucao,
        os.Status,
        os.Prioridade,
        os.ValorPrevisto,
        os.ValorFinal,
        os.DataPrevisao,
        os.DataConclusao,
        os.Observacao,
        os.MostrarValorCliente,
        os.MostrarSolucaoCliente,
        os.MostrarHistoricoCliente,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    WHERE os.OrdemServicoId = :OrdemServicoId 
      AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

$camposPersonalizadosOS = buscarCamposPersonalizadosOS($conn, $empresaId, true);
$valoresCamposPersonalizadosOS = buscarValoresCamposPersonalizadosOS($conn, $empresaId, $id);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE Ativo = 1 
      AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sqlServicos = "
    SELECT ServicoId, Nome, ValorBase, ChecklistPadrao
    FROM OS_Servicos
    WHERE Ativo = 1 
      AND EmpresaId = :EmpresaId
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
                <?= csrfInput() ?>

                <input 
                    type="hidden" 
                    name="ClienteTelefone" 
                    value="<?= htmlspecialchars($ordem["ClienteTelefone"] ?? "") ?>"
                >

                <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordem["OrdemServicoId"] ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="ClienteId" class="form-control" required>
                            <option value="">Selecione...</option>

                            <?php foreach ($clientes as $cliente): ?>
                                <option 
                                    value="<?= (int)$cliente["ClienteId"] ?>"
                                    <?= (int)$ordem["ClienteId"] === (int)$cliente["ClienteId"] ? "selected" : "" ?>
                                >
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
                                    value="<?= (int)$servico["ServicoId"] ?>"
                                    data-valor="<?= htmlspecialchars($servico["ValorBase"] ?? "") ?>"
                                    data-nome="<?= htmlspecialchars($servico["Nome"] ?? "") ?>"
                                    data-checklist="<?= htmlspecialchars($servico["ChecklistPadrao"] ?? "", ENT_QUOTES) ?>"
                                    <?= (int)$ordem["ServicoId"] === (int)$servico["ServicoId"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($servico["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php renderizarCamposPersonalizadosOS($camposPersonalizadosOS, $valoresCamposPersonalizadosOS); ?>
                
                <div id="cardChecklistServico" class="card border-secondary mb-3 d-none">
                    <div class="card-header bg-light">
                        <strong>Checklist padrão do serviço selecionado</strong>
                    </div>

                    <div class="card-body">
                        <div id="textoChecklistServico" class="mb-3" style="white-space: pre-line;"></div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarChecklistServico()">
                                Copiar checklist
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarChecklistServicoNaObservacao()">
                                Adicionar à observação
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input 
                        type="text" 
                        name="Titulo" 
                        class="form-control" 
                        required 
                        maxlength="150"
                        value="<?= htmlspecialchars($ordem["Titulo"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <label class="form-label mb-0">Descrição do Problema</label>

                        <div class="d-flex flex-wrap gap-2">
                            <button 
                                type="button" 
                                class="btn btn-sm btn-outline-primary" 
                                data-ia-os="resumo"
                                onclick="executarIA('resumo')"
                            >
                                ✨ Melhorar descrição
                            </button>

                            <button 
                                type="button" 
                                class="btn btn-sm btn-outline-secondary" 
                                data-ia-os="checklist"
                                onclick="executarIA('checklist')"
                            >
                                🧾 Gerar checklist
                            </button>
                        </div>
                    </div>

                    <textarea name="DescricaoProblema" class="form-control" rows="4"><?= htmlspecialchars($ordem["DescricaoProblema"] ?? "") ?></textarea>

                    <div class="input-help mt-2">
                        Use a IA para transformar a descrição em um texto mais profissional ou gerar um checklist técnico.
                    </div>

                    <div id="undoDescricaoOSIA" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerDescricaoOSIA()">
                            Desfazer descrição gerada pela IA
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Solução Aplicada</label>
                    <textarea name="DescricaoSolucao" class="form-control" rows="4"><?= htmlspecialchars($ordem["DescricaoSolucao"] ?? "") ?></textarea>
                </div>

                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white">
                        Comunicação com o cliente
                    </div>

                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Gere uma mensagem profissional com IA para enviar ao cliente pelo WhatsApp.
                        </p>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-success" 
                            data-ia-os="whatsapp"
                            onclick="executarIA('whatsapp')"
                        >
                            💬 Gerar mensagem WhatsApp
                        </button>

                        <div id="resultadoWhatsappIA" class="mt-3 d-none">
                            <div class="p-3 bg-light border rounded" id="textoWhatsappIA" style="white-space: pre-line;"></div>

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-success" onclick="abrirWhatsAppIA()">
                                    Abrir WhatsApp
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-success" onclick="copiarWhatsappIA()">
                                    Copiar mensagem
                                </button>
                            </div>
                        </div>
                    </div>
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
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label mb-0">Prioridade</label>

                            <button 
                                type="button" 
                                class="btn btn-sm btn-outline-warning" 
                                data-ia-os="prioridade"
                                onclick="executarIA('prioridade')"
                            >
                                IA
                            </button>
                        </div>

                        <select name="Prioridade" class="form-control">
                            <option value="Baixa" <?= $ordem["Prioridade"] === "Baixa" ? "selected" : "" ?>>Baixa</option>
                            <option value="Normal" <?= $ordem["Prioridade"] === "Normal" ? "selected" : "" ?>>Normal</option>
                            <option value="Alta" <?= $ordem["Prioridade"] === "Alta" ? "selected" : "" ?>>Alta</option>
                            <option value="Urgente" <?= $ordem["Prioridade"] === "Urgente" ? "selected" : "" ?>>Urgente</option>
                        </select>

                        <div id="undoPrioridadeOSIA" class="mt-2 d-none">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerPrioridadeOSIA()">
                                Desfazer prioridade sugerida
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Data de Previsão</label>
                        <input 
                            type="date" 
                            name="DataPrevisao" 
                            class="form-control"
                            value="<?= !empty($ordem["DataPrevisao"]) ? date("Y-m-d", strtotime($ordem["DataPrevisao"])) : "" ?>"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Previsto</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="ValorPrevisto" 
                            id="ValorPrevisto" 
                            class="form-control"
                            value="<?= htmlspecialchars($ordem["ValorPrevisto"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Final</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="ValorFinal" 
                            class="form-control"
                            value="<?= htmlspecialchars($ordem["ValorFinal"] ?? "") ?>"
                        >
                    </div>
                </div>

                <?php if (!empty($ordem["DataConclusao"])): ?>
                    <div class="mb-3">
                        <label class="form-label">Data de Conclusão</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            disabled
                            value="<?= date("d/m/Y H:i", strtotime($ordem["DataConclusao"])) ?>"
                        >
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Observação / Checklist técnico</label>
                    <textarea name="Observacao" class="form-control" rows="5"><?= htmlspecialchars($ordem["Observacao"] ?? "") ?></textarea>

                    <div class="input-help mt-2">
                        Use este campo para observações internas, checklist técnico ou orientações do atendimento.
                    </div>

                    <div id="undoObservacaoOSIA" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerObservacaoOSIA()">
                            Desfazer alteração na observação
                        </button>
                    </div>
                </div>

                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <div class="form-check">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="PrepararWhatsAppAposAtualizar" 
                                value="1" 
                                id="PrepararWhatsAppAposAtualizar"
                            >

                            <label class="form-check-label fw-semibold" for="PrepararWhatsAppAposAtualizar">
                                Preparar mensagem de WhatsApp após salvar atualização
                            </label>
                        </div>

                        <div class="input-help mt-2">
                            Após salvar, o sistema abrirá a OS com uma mensagem pronta para envio manual pelo WhatsApp.
                            Não será feito envio automático.
                        </div>
                    </div>
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

                    <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-info">
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
let valorAnteriorDescricaoOS = "";
let valorAnteriorObservacaoOS = "";
let valorAnteriorPrioridadeOS = "";
let mensagemWhatsappIA = "";

document.addEventListener("DOMContentLoaded", function () {
    const servico = document.getElementById("ServicoId");

    if (servico) {
        const option = servico.options[servico.selectedIndex];
        const checklist = option ? (option.getAttribute("data-checklist") || "") : "";
        atualizarChecklistServico(checklist);
    }
});

document.getElementById("ServicoId").addEventListener("change", function () {
    var option = this.options[this.selectedIndex];
    var valor = option.getAttribute("data-valor");
    var checklist = option.getAttribute("data-checklist") || "";
    var campoValorPrevisto = document.getElementById("ValorPrevisto");

    if (valor !== null && valor !== "" && campoValorPrevisto.value === "") {
        campoValorPrevisto.value = valor;
    }

    atualizarChecklistServico(checklist);
});

function atualizarChecklistServico(checklist) {
    const card = document.getElementById("cardChecklistServico");
    const texto = document.getElementById("textoChecklistServico");

    if (!card || !texto) {
        return;
    }

    if (checklist.trim() === "") {
        card.classList.add("d-none");
        texto.innerText = "";
        return;
    }

    texto.innerText = checklist;
    card.classList.remove("d-none");
}

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

function adicionarChecklistServicoNaObservacao() {
    const texto = document.getElementById("textoChecklistServico");
    const observacao = document.querySelector('[name="Observacao"]');
    const undo = document.getElementById("undoObservacaoOSIA");

    if (!texto || texto.innerText.trim() === "") {
        alert("Nenhum checklist disponível.");
        return;
    }

    if (!observacao) {
        return;
    }

    valorAnteriorObservacaoOS = observacao.value;

    const bloco = "Checklist padrão do serviço:\n" + texto.innerText.trim();

    observacao.value = observacao.value.trim() !== ""
        ? observacao.value.trim() + "\n\n" + bloco
        : bloco;

    if (undo) {
        undo.classList.remove("d-none");
    }

    observacao.focus();
}

async function executarIA(tipo) {
    const descricao = document.querySelector('[name="DescricaoProblema"]');
    const titulo = document.querySelector('[name="Titulo"]');
    const prioridade = document.querySelector('[name="Prioridade"]');
    const status = document.querySelector('[name="Status"]');
    const csrf = document.querySelector('[name="csrf_token"]');

    if (!descricao || descricao.value.trim() === '') {
        alert('Informe a descrição do problema antes de usar a IA.');
        return;
    }

    if (!csrf) {
        alert('Token de segurança não encontrado.');
        return;
    }

    const botaoAtual = document.querySelector('[data-ia-os="' + tipo + '"]');
    const textoOriginalBotao = botaoAtual ? botaoAtual.innerHTML : "";

    if (botaoAtual) {
        botaoAtual.disabled = true;

        if (tipo === "resumo") {
            botaoAtual.innerHTML = "Melhorando...";
        } else if (tipo === "checklist") {
            botaoAtual.innerHTML = "Gerando...";
        } else if (tipo === "whatsapp") {
            botaoAtual.innerHTML = "Gerando mensagem...";
        } else {
            botaoAtual.innerHTML = "Analisando...";
        }
    }

    const formData = new FormData();

    formData.append('csrf_token', csrf.value);
    formData.append('TipoIA', tipo);
    formData.append('DescricaoProblema', descricao.value);
    formData.append('Titulo', titulo ? titulo.value : '');
    formData.append('Prioridade', prioridade ? prioridade.value : '');
    formData.append('Status', status ? status.value : '');

    <?php if (!empty($ordem["OrdemServicoId"])): ?>
        formData.append('OrdemServicoId', '<?= (int)$ordem["OrdemServicoId"] ?>');
    <?php endif; ?>

    <?php if (!empty($ordem["CodigoOS"])): ?>
        formData.append('CodigoOS', '<?= htmlspecialchars($ordem["CodigoOS"], ENT_QUOTES) ?>');
    <?php endif; ?>

    <?php if (!empty($ordem["TokenAcompanhamento"])): ?>
        formData.append('TokenAcompanhamento', '<?= htmlspecialchars($ordem["TokenAcompanhamento"], ENT_QUOTES) ?>');
    <?php endif; ?>

    <?php if (!empty($ordem["ClienteNome"])): ?>
        formData.append('Cliente', '<?= htmlspecialchars($ordem["ClienteNome"], ENT_QUOTES) ?>');
    <?php endif; ?>

    <?php if (!empty($ordem["ServicoNome"])): ?>
        formData.append('Servico', '<?= htmlspecialchars($ordem["ServicoNome"], ENT_QUOTES) ?>');
    <?php endif; ?>

    try {
        const response = await fetch('assistente_ia.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.sucesso) {
            alert(data.mensagem || 'Erro ao processar IA.');
            return;
        }

        if (data.tipo === 'prioridade') {
            aplicarPrioridadeOSIA(data.prioridade);
            return;
        }

        if (data.tipo === 'resumo') {
            aplicarDescricaoOSIA(data.conteudo);
            return;
        }

        if (data.tipo === 'checklist') {
            aplicarChecklistOSIA(data.conteudo);
            return;
        }

        if (data.tipo === 'whatsapp') {
            mostrarWhatsappIA(data.conteudo);
            return;
        }

    } catch (error) {
        alert('Não foi possível processar a solicitação com IA.');
    } finally {
        if (botaoAtual) {
            botaoAtual.disabled = false;
            botaoAtual.innerHTML = textoOriginalBotao;
        }
    }
}

function aplicarDescricaoOSIA(conteudo) {
    const descricao = document.querySelector('[name="DescricaoProblema"]');
    const undo = document.getElementById("undoDescricaoOSIA");

    if (!descricao) {
        return;
    }

    valorAnteriorDescricaoOS = descricao.value;
    descricao.value = conteudo;

    if (undo) {
        undo.classList.remove("d-none");
    }

    descricao.focus();
}

function aplicarChecklistOSIA(conteudo) {
    const observacao = document.querySelector('[name="Observacao"]');
    const undo = document.getElementById("undoObservacaoOSIA");

    if (!observacao) {
        return;
    }

    valorAnteriorObservacaoOS = observacao.value;

    const bloco = "Checklist técnico sugerido pela IA:\n" + conteudo;

    observacao.value = observacao.value.trim() !== ""
        ? observacao.value.trim() + "\n\n" + bloco
        : bloco;

    if (undo) {
        undo.classList.remove("d-none");
    }

    observacao.focus();
}

function aplicarPrioridadeOSIA(prioridadeSugerida) {
    const prioridade = document.querySelector('[name="Prioridade"]');
    const undo = document.getElementById("undoPrioridadeOSIA");

    if (!prioridade || !prioridadeSugerida) {
        return;
    }

    valorAnteriorPrioridadeOS = prioridade.value;
    prioridade.value = prioridadeSugerida;

    if (undo) {
        undo.classList.remove("d-none");
    }

    prioridade.focus();
}

function mostrarWhatsappIA(conteudo) {
    const box = document.getElementById("resultadoWhatsappIA");
    const texto = document.getElementById("textoWhatsappIA");

    mensagemWhatsappIA = conteudo;

    if (texto) {
        texto.innerText = conteudo;
    }

    if (box) {
        box.classList.remove("d-none");
    }
}

function desfazerDescricaoOSIA() {
    const descricao = document.querySelector('[name="DescricaoProblema"]');
    const undo = document.getElementById("undoDescricaoOSIA");

    if (!descricao) {
        return;
    }

    descricao.value = valorAnteriorDescricaoOS;

    if (undo) {
        undo.classList.add("d-none");
    }

    descricao.focus();
}

function desfazerObservacaoOSIA() {
    const observacao = document.querySelector('[name="Observacao"]');
    const undo = document.getElementById("undoObservacaoOSIA");

    if (!observacao) {
        return;
    }

    observacao.value = valorAnteriorObservacaoOS;

    if (undo) {
        undo.classList.add("d-none");
    }

    observacao.focus();
}

function desfazerPrioridadeOSIA() {
    const prioridade = document.querySelector('[name="Prioridade"]');
    const undo = document.getElementById("undoPrioridadeOSIA");

    if (!prioridade) {
        return;
    }

    prioridade.value = valorAnteriorPrioridadeOS;

    if (undo) {
        undo.classList.add("d-none");
    }

    prioridade.focus();
}

async function copiarWhatsappIA() {
    if (!mensagemWhatsappIA) {
        alert("Nenhuma mensagem gerada pela IA.");
        return;
    }

    try {
        await navigator.clipboard.writeText(mensagemWhatsappIA);
        alert("Mensagem copiada.");
    } catch (e) {
        alert("Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.");
    }
}

function obterTelefoneClienteOS() {
    const campoTelefone = document.querySelector('[name="ClienteTelefone"]');

    let telefone = "";

    if (campoTelefone && campoTelefone.value.trim() !== "") {
        telefone = campoTelefone.value.trim();
    }

    telefone = telefone.replace(/\D/g, "");

    if (telefone.length === 10 || telefone.length === 11) {
        telefone = "55" + telefone;
    }

    return telefone;
}

function abrirWhatsAppIA() {
    if (!mensagemWhatsappIA) {
        alert("Nenhuma mensagem gerada pela IA.");
        return;
    }

    const telefone = obterTelefoneClienteOS();
    const mensagem = encodeURIComponent(mensagemWhatsappIA);

    let url = "";

    if (telefone !== "") {
        url = "https://wa.me/" + telefone + "?text=" + mensagem;
    } else {
        url = "https://wa.me/?text=" + mensagem;
    }

    window.open(url, "_blank");
}
</script>

<?php require_once "../includes/footer.php"; ?>