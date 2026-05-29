<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

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
        MostrarHistoricoCliente,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteWhatsApp,
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
                <?= csrfInput() ?>
                <input 
                    type="hidden" 
                    name="ClienteWhatsApp" 
                    value="<?= htmlspecialchars($ordem["ClienteWhatsApp"] ?? "") ?>"
                >

                <input 
                    type="hidden" 
                    name="ClienteTelefone" 
                    value="<?= htmlspecialchars($ordem["ClienteTelefone"] ?? "") ?>"
                >                

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

                <div class="card form-card mb-3">
                    <div class="card-header">
                        Assistente IA da OS
                    </div>

                    <div class="card-body">

                        <p class="text-muted mb-3">
                            Use a IA para gerar resumo profissional, mensagem para WhatsApp, sugestão de prioridade e checklist técnico.
                        </p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="executarIA('resumo')">
                                Gerar resumo
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-success" onclick="executarIA('whatsapp')">
                                Gerar WhatsApp
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="enviarWhatsAppN8N()">
                                Enviar via n8n
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="executarIA('prioridade')">
                                Sugerir prioridade
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="executarIA('checklist')">
                                Checklist técnico
                            </button>
                        </div>

                        <div id="iaResultado" class="alert alert-light border d-none"></div>

                    </div>
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

async function executarIA(tipo) {
    const descricao = document.querySelector('[name="DescricaoProblema"]');
    const titulo = document.querySelector('[name="Titulo"]');
    const prioridade = document.querySelector('[name="Prioridade"]');
    const status = document.querySelector('[name="Status"]');
    const csrf = document.querySelector('[name="csrf_token"]');
    const resultado = document.getElementById('iaResultado');

    if (!descricao || descricao.value.trim() === '') {
        alert('Informe a descrição do problema antes de usar a IA.');
        return;
    }

    if (!csrf) {
        alert('Token de segurança não encontrado.');
        return;
    }

    resultado.classList.remove('d-none');
    resultado.innerHTML = 'Processando com IA...';

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
            resultado.innerHTML = '<strong>Erro:</strong> ' + escapeHtml(data.mensagem);
            return;
        }

        if (data.tipo === 'prioridade') {
            window.iaPrioridadeSugerida = data.prioridade;

            resultado.innerHTML = `
                <strong>Prioridade sugerida:</strong>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark">${escapeHtml(data.prioridade)}</span>
                </div>
                <div class="mt-2">${escapeHtml(data.justificativa || '')}</div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-warning" onclick="usarPrioridadeIA()">
                        Aplicar prioridade
                    </button>
                </div>
            `;
            return;
        }

        window.iaConteudoGerado = data.conteudo;
        window.iaTipoGerado = data.tipo;

        let tituloResultado = 'Resultado da IA';

        if (data.tipo === 'resumo') {
            tituloResultado = 'Resumo sugerido pela IA';
        }

        if (data.tipo === 'whatsapp') {
            tituloResultado = 'Mensagem sugerida para WhatsApp';
        }

        if (data.tipo === 'checklist') {
            tituloResultado = 'Checklist técnico sugerido';
        }

        resultado.innerHTML = `
            <strong>${tituloResultado}:</strong>
            <div class="mt-2" style="white-space: pre-line;">${escapeHtml(data.conteudo)}</div>
            <div class="mt-3 d-flex flex-wrap gap-2">
                ${data.tipo === 'resumo' ? '<button type="button" class="btn btn-sm btn-primary" onclick="usarResumoIA()">Usar como descrição</button>' : ''}
                ${data.tipo === 'whatsapp' ? `
                    <button type="button" class="btn btn-sm btn-primary" onclick="enviarWhatsAppN8N()">
                        Enviar via n8n
                    </button>                
                    <button type="button" class="btn btn-sm btn-success" onclick="abrirWhatsAppIA()">
                        Abrir WhatsApp
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-success" onclick="copiarIA()">
                        Copiar mensagem
                    </button>
                ` : ''}
                ${data.tipo === 'checklist' ? '<button type="button" class="btn btn-sm btn-secondary" onclick="copiarIA()">Copiar checklist</button>' : ''}
            </div>
        `;

    } catch (error) {
        resultado.innerHTML = '<strong>Erro:</strong> não foi possível processar a solicitação com IA.';
    }
}

function usarResumoIA() {
    const descricao = document.querySelector('[name="DescricaoProblema"]');

    if (descricao && window.iaConteudoGerado) {
        descricao.value = window.iaConteudoGerado;
    }
}

function usarPrioridadeIA() {
    const prioridade = document.querySelector('[name="Prioridade"]');

    if (prioridade && window.iaPrioridadeSugerida) {
        prioridade.value = window.iaPrioridadeSugerida;
    }
}

async function copiarIA() {
    if (!window.iaConteudoGerado) {
        return;
    }

    try {
        await navigator.clipboard.writeText(window.iaConteudoGerado);
        alert('Conteúdo copiado.');
    } catch (e) {
        alert('Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}

function obterTelefoneClienteOS() {
    const campoWhatsapp = document.querySelector('[name="ClienteWhatsApp"]');
    const campoTelefone = document.querySelector('[name="ClienteTelefone"]');

    let telefone = "";

    if (campoWhatsapp && campoWhatsapp.value.trim() !== "") {
        telefone = campoWhatsapp.value.trim();
    } else if (campoTelefone && campoTelefone.value.trim() !== "") {
        telefone = campoTelefone.value.trim();
    }

    telefone = telefone.replace(/\D/g, "");

    if (telefone.length === 10 || telefone.length === 11) {
        telefone = "55" + telefone;
    }

    return telefone;
}

function abrirWhatsAppIA() {
    if (!window.iaConteudoGerado) {
        alert("Nenhuma mensagem gerada pela IA.");
        return;
    }

    const telefone = obterTelefoneClienteOS();
    const mensagem = encodeURIComponent(window.iaConteudoGerado);

    let url = "";

    if (telefone !== "") {
        url = "https://wa.me/" + telefone + "?text=" + mensagem;
    } else {
        url = "https://wa.me/?text=" + mensagem;
    }

    window.open(url, "_blank");
}

async function enviarWhatsAppN8N() {
    if (!window.iaConteudoGerado) {
        alert("Nenhuma mensagem gerada pela IA.");
        return;
    }

    const csrf = document.querySelector('[name="csrf_token"]');

    if (!csrf) {
        alert("Token de segurança não encontrado.");
        return;
    }

    if (!confirm("Deseja enviar esta mensagem pelo fluxo n8n?")) {
        return;
    }

    const formData = new FormData();
    formData.append("csrf_token", csrf.value);
    formData.append("Mensagem", window.iaConteudoGerado);

    <?php if (!empty($ordem["OrdemServicoId"])): ?>
        formData.append("OrdemServicoId", "<?= (int)$ordem["OrdemServicoId"] ?>");
    <?php endif; ?>

    const campoWhatsapp = document.querySelector('[name="ClienteWhatsApp"]');
    const campoTelefone = document.querySelector('[name="ClienteTelefone"]');

    if (campoWhatsapp && campoWhatsapp.value.trim() !== "") {
        formData.append("Telefone", campoWhatsapp.value.trim());
    } else if (campoTelefone && campoTelefone.value.trim() !== "") {
        formData.append("Telefone", campoTelefone.value.trim());
    }

    try {
        const response = await fetch("enviar_whatsapp_n8n.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (!data.sucesso) {
            alert("Erro ao enviar via n8n: " + data.mensagem);
            return;
        }

        alert("Mensagem enviada para o n8n com sucesso.");

    } catch (error) {
        alert("Não foi possível enviar a mensagem para o n8n.");
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>
