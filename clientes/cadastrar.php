<?php 
require_once "../includes/proteger.php";
require_once "../includes/header.php";
require_once "../includes/menu.php"; 
?>

<div class="container">

    <div class="mb-3">
        <h3>Novo Cliente</h3>
        <p class="text-muted mb-0">Preencha os dados do cliente</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="salvar.php">

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="Nome" class="form-control" required maxlength="150">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Documento</label>
                        <input type="text" name="Documento" class="form-control" maxlength="30" placeholder="CPF/CNPJ">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="Telefone" class="form-control" maxlength="30">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="Email" class="form-control" maxlength="150">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="Endereco" class="form-control" maxlength="255">
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="Cidade" class="form-control" maxlength="100">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estado</label>
                        <input type="text" name="Estado" class="form-control" maxlength="2" placeholder="RJ">
                    </div>
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