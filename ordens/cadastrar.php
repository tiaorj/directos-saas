<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";
require_once "../includes/campos_os.php";

$empresaId = $_SESSION["EmpresaId"];
$validacaoPlano = empresaPodeCriarOS($conn, $empresaId);

$camposPersonalizadosOS = buscarCamposPersonalizadosOS($conn, (int)$empresaId, true);

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
    SELECT ServicoId, Nome, ValorBase, ChecklistPadrao
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
                                <option value="<?= (int)$cliente["ClienteId"] ?>">
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
                                >
                                    <?= htmlspecialchars($servico["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

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

                <div class="form-section-title">
                    Detalhes da Solicitação
                </div>
                
                <?php renderizarCamposPersonalizadosOS($camposPersonalizadosOS); ?>
                
                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" name="Titulo" class="form-control" required maxlength="150">
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

                    <textarea name="DescricaoProblema" class="form-control" rows="4"></textarea>

                    <div class="input-help mt-2">
                        Descreva o problema de forma simples. A IA pode transformar o texto em uma descrição mais profissional.
                    </div>

                    <div id="undoDescricaoOSIA" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerDescricaoOSIA()">
                            Desfazer descrição gerada pela IA
                        </button>
                    </div>
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
                            <option value="Baixa">Baixa</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>

                        <div id="undoPrioridadeOSIA" class="mt-2 d-none">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerPrioridadeOSIA()">
                                Desfazer prioridade sugerida
                            </button>
                        </div>
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
                    <label class="form-label">Observação / Checklist técnico</label>
                    <textarea name="Observacao" class="form-control" rows="5"></textarea>

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
                                name="PrepararWhatsAppAposSalvar" 
                                value="1" 
                                id="PrepararWhatsAppAposSalvar"
                            >

                            <label class="form-check-label fw-semibold" for="PrepararWhatsAppAposSalvar">
                                Preparar mensagem de WhatsApp após salvar esta OS
                            </label>
                        </div>

                        <div class="input-help mt-2">
                            Após salvar, o sistema abrirá a OS com uma mensagem pronta para envio manual pelo WhatsApp.
                            Não será feito envio automático.
                        </div>
                    </div>
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
let valorAnteriorDescricaoOS = "";
let valorAnteriorObservacaoOS = "";
let valorAnteriorPrioridadeOS = "";

document.getElementById("ServicoId").addEventListener("change", function () {
    var option = this.options[this.selectedIndex];
    var valor = option.getAttribute("data-valor");
    var checklist = option.getAttribute("data-checklist") || "";

    if (valor !== null && valor !== "") {
        document.getElementById("ValorPrevisto").value = valor;
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
    const servico = document.querySelector('[name="ServicoId"]');
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
        } else {
            botaoAtual.innerHTML = "Analisando...";
        }
    }

    let servicoNome = "";

    if (servico && servico.selectedIndex >= 0) {
        const option = servico.options[servico.selectedIndex];
        servicoNome = option.getAttribute("data-nome") || option.text || "";
    }

    const formData = new FormData();

    formData.append('csrf_token', csrf.value);
    formData.append('TipoIA', tipo);
    formData.append('DescricaoProblema', descricao.value);
    formData.append('Titulo', titulo ? titulo.value : '');
    formData.append('Prioridade', prioridade ? prioridade.value : '');
    formData.append('Status', status ? status.value : '');
    formData.append('Servico', servicoNome);

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
</script>

<?php require_once "../includes/footer.php"; ?>