<?php
require_once "includes/proteger.php";
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="card form-card">
        <div class="card-body text-center p-5">

            <div style="font-size: 56px;">
                ⚠️
            </div>

            <h3 class="fw-bold mt-3">
                Acesso negado
            </h3>

            <p class="text-muted">
                Você não tem permissão para acessar esta área do sistema.
            </p>

            <a href="dashboard.php" class="btn btn-primary">
                Voltar para o Dashboard
            </a>

        </div>
    </div>

</div>

<?php require_once "includes/footer.php"; ?>