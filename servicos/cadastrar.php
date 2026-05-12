<?php 
require_once "../includes/proteger.php";
require_once "../includes/header.php";
require_once "../includes/menu.php"; 
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
?>

<div class="container">

    <div class="mb-3">
        <h3>Novo Serviço</h3>
        <p class="text-muted mb-0">Preencha os dados do serviço</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="salvar.php">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
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

                <button type="submit" class="btn btn-success">
                    Salvar
                </button>

                <a href="listar.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>