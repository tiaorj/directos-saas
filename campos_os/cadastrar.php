<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Novo Campo da OS</h3>
            <p>Crie campos extras para personalizar a ordem de serviço.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Campo
        </div>

        <div class="card-body">

            <form method="post" action="salvar.php">
                <?= csrfInput() ?>

                <div class="mb-3">
                    <label class="form-label">Rótulo *</label>
                    <input 
                        type="text" 
                        name="Rotulo" 
                        class="form-control" 
                        required 
                        maxlength="150"
                        placeholder="Ex.: Placa do veículo"
                    >

                    <div class="input-help mt-2">
                        Nome que aparecerá na tela da OS.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome técnico *</label>
                    <input 
                        type="text" 
                        name="NomeCampo" 
                        class="form-control" 
                        required 
                        maxlength="100"
                        placeholder="Ex.: placa_veiculo"
                    >

                    <div class="input-help mt-2">
                        Use letras, números e underline. Exemplo: placa_veiculo, km_atual, numero_serie.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo do Campo *</label>
                    <select name="TipoCampo" class="form-control" required>
                        <option value="texto">Texto</option>
                        <option value="numero">Número</option>
                        <option value="data">Data</option>
                        <option value="textarea">Texto longo</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ordem</label>
                    <input type="number" name="Ordem" class="form-control" value="0">
                </div>

                <div class="form-check mb-3">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        name="Obrigatorio" 
                        value="1" 
                        id="Obrigatorio"
                    >
                    <label class="form-check-label" for="Obrigatorio">
                        Campo obrigatório
                    </label>
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
                        Salvar Campo
                    </button>

                    <a href="listar.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>