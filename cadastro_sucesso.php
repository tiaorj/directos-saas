<?php
session_start();

if (isset($_SESSION["UsuarioId"])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Conta criada - DirectOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        :root {
            --dark: #111827;
            --primary: #2563eb;
            --bg: #f3f4f6;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.35), transparent 32%),
                linear-gradient(135deg, #111827, #1e3a8a);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .success-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 32px 0;
        }

        .success-card {
            border: 0;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28);
        }

        .success-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 28px;
            font-weight: 800;
        }

        .success-body {
            padding: 36px 32px;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 18px;
        }

        .next-step {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #fff;
            height: 100%;
        }

        .next-step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .btn-main {
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 800;
        }
    </style>
</head>

<body>

<div class="container success-wrapper">
    <div class="row justify-content-center w-100">
        <div class="col-lg-8">

            <div class="card success-card">
                <div class="success-header">
                    DirectOS
                </div>

                <div class="success-body text-center">

                    <div class="success-icon">
                        ✓
                    </div>

                    <h1 class="fw-bold mb-2">
                        Conta criada com sucesso!
                    </h1>

                    <p class="text-muted lead mb-4">
                        Sua empresa foi cadastrada no DirectOS e vinculada automaticamente ao plano Gratuito.
                    </p>

                    <a href="login.php" class="btn btn-primary btn-main mb-4">
                        Entrar no sistema
                    </a>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3">
                        Próximos passos recomendados
                    </h5>

                    <div class="row g-3 text-start">

                        <div class="col-md-4">
                            <div class="next-step">
                                <div class="next-step-number">1</div>
                                <h6>Complete sua empresa</h6>
                                <p class="text-muted small mb-0">
                                    Confira nome, WhatsApp e e-mail em Minha Empresa.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="next-step">
                                <div class="next-step-number">2</div>
                                <h6>Cadastre serviços</h6>
                                <p class="text-muted small mb-0">
                                    Crie os serviços que sua empresa oferece.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="next-step">
                                <div class="next-step-number">3</div>
                                <h6>Crie sua primeira OS</h6>
                                <p class="text-muted small mb-0">
                                    Cadastre um cliente, abra uma OS e envie o link público.
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <a href="index.php" class="text-decoration-none">
                            Voltar para o site
                        </a>
                    </div>

                </div>
            </div>

            <p class="text-center text-white-50 mt-3 mb-0">
                DirectOS · Sistema online de Ordem de Serviço
            </p>

        </div>
    </div>
</div>

</body>
</html>