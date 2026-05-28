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
                    <label class="form-label">Descrição</label>
                    <textarea name="Descricao" class="form-control" rows="3" maxlength="500"></textarea>
                </div>

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

<?php require_once "../includes/footer.php"; ?>
