<?php
session_start();

if (isset($_SESSION["UsuarioId"])) {
    header("Location: dashboard.php");
    exit;
}

$erro = $_GET["erro"] ?? "";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - DirectOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">

        <div class="col-md-4">

            <div class="text-center mb-4">
                <h2>DirectOS</h2>
                <p class="text-muted">Sistema de Ordem de Serviço</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">

                    <h5 class="mb-3">Acessar sistema</h5>

                    <?php if ($erro !== ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="validar_login.php">

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="Email" class="form-control" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="Senha" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Entrar
                        </button>

                    </form>

                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Projeto PHP + SQL Server
                </small>
            </div>

        </div>

    </div>

</div>

</body>
</html>