<?php
require_once "includes/proteger.php";
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/menu.php"; ?>

<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    Acesso negado
                </div>

                <div class="card-body text-center">
                    <h4 class="mb-3">Você não tem permissão para acessar esta área.</h4>

                    <p class="text-muted">
                        Caso precise desse acesso, solicite a liberação para um administrador do sistema.
                    </p>

                    <a href="/sistema-os-php-sqlserver/dashboard.php" class="btn btn-primary">
                        Voltar para o Dashboard
                    </a>

                    <a href="/sistema-os-php-sqlserver/logout.php" class="btn btn-outline-secondary">
                        Sair
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once "includes/footer.php"; ?>