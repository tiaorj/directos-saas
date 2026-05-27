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

    <meta 
        name="description" 
        content="Acesse o DirectOS, sistema online para controle de ordens de serviço, clientes, anexos e acompanhamento pelo cliente."
    >

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #212529, #0d6efd);
        }

        .login-wrapper {
            min-height: 100vh;
        }

        .brand-title {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.82);
        }

        .login-card {
            border: none;
            border-radius: 18px;
        }

        .form-control {
            padding: 12px;
            border-radius: 10px;
        }

        .btn-login {
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
        }

        .feature-item {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 14px;
        }

        .feature-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 50%;
            margin-right: 8px;
        }

        .small-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
        }

        .small-link:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="container login-wrapper d-flex align-items-center">

    <div class="row w-100 align-items-center justify-content-center">

        <div class="col-lg-6 mb-5 mb-lg-0 text-white">
            <a href="index.php" class="small-link mb-4 d-inline-block">
                ← Voltar para o site
            </a>

            <div class="brand-title mb-2">
                DirectOS
            </div>

            <p class="lead brand-subtitle mb-4">
                Controle suas ordens de serviço e envie um link para o cliente acompanhar tudo pelo celular.
            </p>

            <div class="feature-item">
                <span class="feature-icon">✓</span>
                Gestão de clientes, serviços e ordens de serviço.
            </div>

            <div class="feature-item">
                <span class="feature-icon">✓</span>
                Área do cliente por link, sem necessidade de login.
            </div>

            <div class="feature-item">
                <span class="feature-icon">✓</span>
                Anexos, fotos, histórico e envio por WhatsApp.
            </div>

            <div class="feature-item">
                <span class="feature-icon">✓</span>
                Estrutura preparada para SaaS multiempresa.
            </div>
        </div>

        <div class="col-lg-5 col-md-8">

            <div class="card login-card shadow-lg">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-1">
                            Acessar sistema
                        </h3>

                        <p class="text-muted mb-0">
                            Entre com seu e-mail e senha para continuar.
                        </p>
                    </div>

                    <?php if ($erro !== ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="validar_login.php">

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input 
                                type="email" 
                                name="Email" 
                                class="form-control" 
                                placeholder="seuemail@empresa.com"
                                required 
                                autofocus
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input 
                                type="password" 
                                name="Senha" 
                                class="form-control" 
                                placeholder="Digite sua senha"
                                required
                            >
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="lembrar">
                                <label class="form-check-label" for="lembrar">
                                    Lembrar acesso
                                </label>
                            </div>

                            <a href="#" class="text-decoration-none">
                                Esqueci minha senha
                            </a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100">
                            Entrar no DirectOS
                        </button>

                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <small class="text-muted">
                            Ainda não tem acesso?
                        </small>
                        <br>
                        <a href="index.php#planos" class="text-decoration-none fw-semibold">
                            Conheça os planos do DirectOS
                        </a>
                    </div>

                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-white-50">
                    DirectOS · Sistema online de Ordem de Serviço
                </small>
            </div>

        </div>

    </div>

</div>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>