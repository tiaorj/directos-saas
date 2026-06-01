<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$id = $_GET["id"] ?? 0;

exigirServicoDaEmpresa($conn, $id);

$sql = "
    SELECT 
        ServicoId,
        Nome,
        Descricao,
        ChecklistPadrao,
        ValorBase,
        Ativo
    FROM OS_Servicos
    WHERE ServicoId = :ServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    die("Serviço não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Editar Serviço</h3>
            <p>Atualize os dados do serviço</p>
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

            <form method="post" action="atualizar.php">
                <?= csrfInput() ?>

                <input type="hidden" name="ServicoId" value="<?= (int)$servico["ServicoId"] ?>">
                <input type="hidden" name="EmpresaId" value="<?= (int)$empresaId ?>">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input 
                        type="text" 
                        name="Nome" 
                        class="form-control" 
                        required 
                        maxlength="150"
                        value="<?= htmlspecialchars($servico["Nome"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <label class="form-label mb-0">Descrição</label>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            data-ia-servico="descricao"
                            onclick="gerarServicoIA('descricao')"
                        >
                            ✨ Gerar descrição com IA
                        </button>
                    </div>

                    <textarea name="Descricao" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($servico["Descricao"] ?? "") ?></textarea>

                    <div class="input-help mt-2">
                        Use a IA para melhorar ou criar uma descrição profissional para este serviço.
                    </div>
                    <div id="undoDescricaoServicoIA" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerDescricaoServicoIA()">
                            Desfazer descrição gerada pela IA
                        </button>
                    </div>                    
                </div>

                <div class="mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <label class="form-label mb-0">Checklist Padrão</label>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary" 
                            data-ia-servico="checklist"
                            onclick="gerarServicoIA('checklist')"
                        >
                            🧾 Gerar checklist com IA
                        </button>
                    </div>

                    <textarea name="ChecklistPadrao" class="form-control" rows="6" maxlength="2000"><?= htmlspecialchars($servico["ChecklistPadrao"] ?? "") ?></textarea>

                    <div class="input-help mt-2">
                        Checklist opcional para orientar o técnico durante a execução desse tipo de serviço.
                    </div>
                    <div id="undoChecklistServicoIA" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desfazerChecklistServicoIA()">
                            Desfazer checklist gerado pela IA
                        </button>
                    </div>                    
                </div>

                <div class="mb-3">
                    <label class="form-label">Valor Base</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="ValorBase" 
                        class="form-control"
                        value="<?= htmlspecialchars($servico["ValorBase"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1" <?= (int)$servico["Ativo"] === 1 ? "selected" : "" ?>>
                            Ativo
                        </option>
                        <option value="0" <?= (int)$servico["Ativo"] === 0 ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Atualizar Serviço
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
let valorAnteriorDescricaoServico = "";
let valorAnteriorChecklistServico = "";

async function gerarServicoIA(tipo) {
    const nome = document.querySelector('[name="Nome"]');
    const descricao = document.querySelector('[name="Descricao"]');
    const checklist = document.querySelector('[name="ChecklistPadrao"]');
    const csrf = document.querySelector('[name="csrf_token"]');

    if (!nome || nome.value.trim() === '') {
        alert('Informe o nome do serviço antes de usar a IA.');
        return;
    }

    if (!csrf) {
        alert('Token de segurança não encontrado.');
        return;
    }

    const botaoDescricao = document.querySelector('[data-ia-servico="descricao"]');
    const botaoChecklist = document.querySelector('[data-ia-servico="checklist"]');

    const botaoAtual = tipo === 'checklist' ? botaoChecklist : botaoDescricao;
    const textoOriginalBotao = botaoAtual ? botaoAtual.innerHTML : '';

    if (botaoAtual) {
        botaoAtual.disabled = true;
        botaoAtual.innerHTML = tipo === 'checklist'
            ? 'Gerando checklist...'
            : 'Gerando descrição...';
    }

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
            alert(data.mensagem || 'Erro ao gerar conteúdo com IA.');
            return;
        }

        if (data.tipo === 'descricao') {
            aplicarDescricaoServicoIA(data.conteudo);
            return;
        }

        if (data.tipo === 'checklist') {
            aplicarChecklistServicoIA(data.conteudo);
            return;
        }

    } catch (error) {
        alert('Não foi possível gerar conteúdo com IA.');
    } finally {
        if (botaoAtual) {
            botaoAtual.disabled = false;
            botaoAtual.innerHTML = textoOriginalBotao;
        }
    }
}

function aplicarDescricaoServicoIA(conteudo) {
    const descricao = document.querySelector('[name="Descricao"]');
    const undoDescricao = document.getElementById('undoDescricaoServicoIA');

    if (!descricao) {
        return;
    }

    valorAnteriorDescricaoServico = descricao.value;
    descricao.value = conteudo;

    if (undoDescricao) {
        undoDescricao.classList.remove('d-none');
    }

    descricao.focus();
}

function aplicarChecklistServicoIA(conteudo) {
    const checklist = document.querySelector('[name="ChecklistPadrao"]');
    const undoChecklist = document.getElementById('undoChecklistServicoIA');

    if (!checklist) {
        return;
    }

    valorAnteriorChecklistServico = checklist.value;
    checklist.value = conteudo;

    if (undoChecklist) {
        undoChecklist.classList.remove('d-none');
    }

    checklist.focus();
}

function desfazerDescricaoServicoIA() {
    const descricao = document.querySelector('[name="Descricao"]');
    const undoDescricao = document.getElementById('undoDescricaoServicoIA');

    if (!descricao) {
        return;
    }

    descricao.value = valorAnteriorDescricaoServico;

    if (undoDescricao) {
        undoDescricao.classList.add('d-none');
    }

    descricao.focus();
}

function desfazerChecklistServicoIA() {
    const checklist = document.querySelector('[name="ChecklistPadrao"]');
    const undoChecklist = document.getElementById('undoChecklistServicoIA');

    if (!checklist) {
        return;
    }

    checklist.value = valorAnteriorChecklistServico;

    if (undoChecklist) {
        undoChecklist.classList.add('d-none');
    }

    checklist.focus();
}
</script>

<?php require_once "../includes/footer.php"; ?>