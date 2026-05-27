<?php 
require_once "../includes/proteger.php";
require_once "../includes/header.php";
require_once "../includes/menu.php"; 

$empresaId = (int)$_SESSION["EmpresaId"];
?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Novo Cliente</h3>
            <p>Cadastre os dados do cliente vinculado à sua empresa.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Cliente
        </div>

        <div class="card-body">

            <form method="post" action="salvar.php">

                <input type="hidden" name="EmpresaId" value="<?= $empresaId ?>">

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label required-label">Nome</label>
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

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar Cliente
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