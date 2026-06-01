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

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="gerarDescricaoServicoIA()">
                            ✨ Gerar descrição com IA
                        </button>
                    </div>

                    <textarea name="Descricao" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($servico["Descricao"] ?? "") ?></textarea>

                    <div class="input-help mt-2">
                        Use a IA para melhorar ou criar uma descrição profissional para este serviço.
                    </div>
                </div>

                <div id="iaResultadoServico" class="alert alert-light border d-none"></div>

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
async function gerarDescricaoServicoIA() {
    const nome = document.querySelector('[name="Nome"]');
    const descricao = document.querySelector('[name="Descricao"]');
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
    resultado.innerHTML = 'Gerando descrição com IA...';

    const formData = new FormData();
    formData.append('csrf_token', csrf.value);
    formData.append('TipoIA', 'descricao');
    formData.append('Nome', nome.value);
    formData.append('Descricao', descricao ? descricao.value : '');

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

        window.descricaoServicoIA = data.conteudo;

        resultado.innerHTML = `
            <strong>Descrição sugerida pela IA:</strong>
            <div class="mt-2" style="white-space: pre-line;">${escapeHtmlServico(data.conteudo)}</div>
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-primary" onclick="aplicarDescricaoServicoIA()">
                    Usar descrição
                </button>
            </div>
        `;

    } catch (error) {
        resultado.innerHTML = '<strong>Erro:</strong> não foi possível gerar a descrição com IA.';
    }
}

function aplicarDescricaoServicoIA() {
    const descricao = document.querySelector('[name="Descricao"]');

    if (descricao && window.descricaoServicoIA) {
        descricao.value = window.descricaoServicoIA;
    }
}

function escapeHtmlServico(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}
</script>

<?php require_once "../includes/footer.php"; ?>