<?php
require_once "../includes/proteger_admin.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$validacaoPlano = empresaPodeCriarUsuario($conn, $empresaId);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Novo Usuário</h3>
            <p>Cadastre um usuário para acessar o sistema da empresa.</p>
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
            · Usuários ativos:
            <?= (int)$validacaoPlano["totalUsuarios"] ?> /
            <?= (int)$validacaoPlano["limite"] ?>
        <?php else: ?>
            · Usuários ilimitados
        <?php endif; ?>
    </div>
<?php endif; ?>

    <?php if (!$validacaoPlano["permitido"]): ?>
        <div class="alert alert-warning">
            <strong>Atenção:</strong>
            <?= htmlspecialchars($validacaoPlano["mensagem"]) ?>
            <br>
            Para cadastrar mais usuários, altere para o plano Profissional ou Empresa.
        </div>

        <a href="../planos/meu_plano.php" class="btn btn-primary">
            Ver Planos
        </a>

        <a href="listar.php" class="btn btn-secondary">
            Voltar
        </a>

        <?php require_once "../includes/footer.php"; ?>
        <?php exit; ?>
    <?php endif; ?>    

    <div class="card form-card">
        <div class="card-header">
            Dados do Usuário
        </div>
        <div class="card-body">

            <form method="post" action="salvar.php">
                <?= csrfInput() ?>

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

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar Usuário
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
