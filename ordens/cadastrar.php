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

    <div class="card form-card mb-4">
        <div class="card-header">
            Assistente IA da OS
        </div>

        <div class="card-body">

            <p class="text-muted mb-3">
                Use a IA para melhorar a descrição, sugerir prioridade e gerar um checklist técnico antes de salvar a OS.
            </p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="executarIA('resumo')">
                    Gerar resumo
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
                                >
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

async function executarIA(tipo) {
    const descricao = document.querySelector('[name="DescricaoProblema"]');
    const titulo = document.querySelector('[name="Titulo"]');
    const prioridade = document.querySelector('[name="Prioridade"]');
    const status = document.querySelector('[name="Status"]');
    const servico = document.querySelector('[name="ServicoId"]');
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

        if (data.tipo === 'checklist') {
            tituloResultado = 'Checklist técnico sugerido';
        }

        resultado.innerHTML = `
            <strong>${tituloResultado}:</strong>
            <div class="mt-2" style="white-space: pre-line;">${escapeHtml(data.conteudo)}</div>
            <div class="mt-3 d-flex flex-wrap gap-2">
                ${data.tipo === 'resumo' ? '<button type="button" class="btn btn-sm btn-primary" onclick="usarResumoIA()">Usar como descrição</button>' : ''}
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
</script>

<?php require_once "../includes/footer.php"; ?>