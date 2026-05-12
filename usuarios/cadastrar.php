<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Novo Usuário</h3>
        <p class="text-muted mb-0">Preencha os dados de acesso</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="salvar.php">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="Nome" class="form-control" required maxlength="150">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="Email" class="form-control" required maxlength="150">
                </div>

                <div class="mb-3">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="Senha" class="form-control" required minlength="6">
                    <small class="text-muted">Mínimo de 6 caracteres.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Perfil</label>
                    <select name="Perfil" class="form-control">
                        <option value="Admin">Admin</option>
                        <option value="Atendente">Atendente</option>
                        <option value="Tecnico">Técnico</option>
                    </select>
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