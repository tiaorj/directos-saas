<?php 
require_once "../includes/proteger.php";
require_once "../includes/header.php";
require_once "../includes/menu.php"; 
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);
?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Novo Serviço</h3>
            <p>Preencha os dados do serviço</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Serviço
        </div>

        <div class="card-body">

            <form method="post" action="salvar.php">
                <?= csrfInput() ?>

                <div class="mb-3">
                    <label class="form-label required-label">Nome</label>
                    <input type="text" name="Nome" class="form-control" required maxlength="150">
                </div>

                <div class="mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <label class="form-label mb-0">Descrição</label>

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="gerarServicoIA('descricao')">
                            ✨ Gerar descrição com IA
                        </button>
                    </div>

                    <textarea name="Descricao" class="form-control" rows="4" maxlength="1000"></textarea>

                    <div class="input-help mt-2">
                        Informe o nome do serviço e use a IA para criar uma descrição profissional.
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <label class="form-label mb-0">Checklist Padrão</label>

                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="gerarServicoIA('checklist')">
                            🧾 Gerar checklist com IA
                        </button>
                    </div>

                    <textarea name="ChecklistPadrao" class="form-control" rows="6" maxlength="2000"></textarea>

                    <div class="input-help mt-2">
                        Checklist opcional para orientar o técnico durante a execução desse tipo de serviço.
                    </div>
                </div>

                <div id="iaResultadoServico" class="alert alert-light border d-none"></div>

                <div class="mb-3">
                    <label class="form-label">Valor Base</label>
                    <input type="number" step="0.01" name="ValorBase" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar Serviço
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
async function gerarServicoIA(tipo) {
    const nome = document.querySelector('[name="Nome"]');
    const descricao = document.querySelector('[name="Descricao"]');
    const checklist = document.querySelector('[name="ChecklistPadrao"]');
    const csrf = document.querySelector('[name="csrf_token"]');
    const resultado = document.getElementById('iaResultadoServico');

    if (!nome || nome.value.trim() === '') {
        alert('Informe o nome do serviço antes de usar a IA.');
        return;
    }

    if (!csrf) {
        alert('Token de segurança não encontrado.');
        return;
    }

    resultado.classList.remove('d-none');
    resultado.innerHTML = tipo === 'checklist'
        ? 'Gerando checklist com IA...'
        : 'Gerando descrição com IA...';

    const formData = new FormData();
    formData.append('csrf_token', csrf.value);
    formData.append('TipoIA', tipo);
    formData.append('Nome', nome.value);
    formData.append('Descricao', descricao ? descricao.value : '');
    formData.append('ChecklistPadrao', checklist ? checklist.value : '');

    try {
        const response = await fetch('assistente_ia.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.sucesso) {
            resultado.innerHTML = '<strong>Erro:</strong> ' + escapeHtmlServico(data.mensagem);
            return;
        }

        window.servicoIAConteudo = data.conteudo;
        window.servicoIATipo = data.tipo;

        const titulo = data.tipo === 'checklist'
            ? 'Checklist sugerido pela IA'
            : 'Descrição sugerida pela IA';

        const textoBotao = data.tipo === 'checklist'
            ? 'Usar checklist'
            : 'Usar descrição';

        resultado.innerHTML = `
            <strong>${titulo}:</strong>
            <div class="mt-2" style="white-space: pre-line;">${escapeHtmlServico(data.conteudo)}</div>
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-primary" onclick="aplicarServicoIA()">
                    ${textoBotao}
                </button>
            </div>
        `;

    } catch (error) {
        resultado.innerHTML = '<strong>Erro:</strong> não foi possível gerar conteúdo com IA.';
    }
}

function aplicarServicoIA() {
    if (!window.servicoIAConteudo || !window.servicoIATipo) {
        return;
    }

    if (window.servicoIATipo === 'checklist') {
        const checklist = document.querySelector('[name="ChecklistPadrao"]');

        if (checklist) {
            checklist.value = window.servicoIAConteudo;
        }

        return;
    }

    const descricao = document.querySelector('[name="Descricao"]');

    if (descricao) {
        descricao.value = window.servicoIAConteudo;
    }
}

function escapeHtmlServico(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}
</script>

<?php require_once "../includes/footer.php"; ?>