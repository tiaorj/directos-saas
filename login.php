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
        :root {
            --dark: #111827;
            --dark-soft: #1f2937;
            --primary: #2563eb;
            --bg: #f3f4f6;
            --muted: #64748b;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.35), transparent 32%),
                linear-gradient(135deg, #111827, #1e3a8a);
            color: #111827;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            padding: 32px 0;
        }

        .back-link {
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 8px 13px;
            border-radius: 999px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 18px;
        }

        .brand-title {
            color: #fff;
            font-size: clamp(2.4rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -1.5px;
            line-height: 1.05;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.82);
            max-width: 620px;
        }

        .feature-list {
            margin-top: 28px;
        }

        .feature-item {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50%;
            font-weight: 800;
        }

        .login-card {
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28);
        }

        .login-card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 24px;
            font-weight: 800;
        }

        .login-card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.92rem;
        }

        .form-control {
            padding: 12px 14px;
            border-radius: 12px;
        }

        .btn-login {
            padding: 12px;
            border-radius: 12px;
            font-weight: 800;
        }

        .login-footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 18px 24px;
            text-align: center;
        }

        .mini-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 18px;
            color: #fff;
            padding: 18px;
            margin-top: 28px;
            max-width: 560px;
        }

        .mini-card small {
            color: rgba(255, 255, 255, 0.72);
        }

        @media (max-width: 991px) {
            .login-wrapper {
                padding: 22px 0;
            }

            .login-card-body {
                padding: 24px;
            }
        }
    </style>
</head>

<body>

<div class="container login-wrapper d-flex align-items-center">

    <div class="row w-100 align-items-center justify-content-center g-5">

        <div class="col-lg-6 text-white">
            <a href="index.php" class="back-link mb-4 d-inline-block">
                ← Voltar para o site
            </a>

            <div class="brand-badge">
                Sistema online de Ordem de Serviço
            </div>

            <h1 class="brand-title mb-3">
                Acesse sua operação no DirectOS.
            </h1>

            <p class="lead brand-subtitle">
                Controle ordens de serviço, clientes, anexos, histórico e acompanhamento pelo cliente em um só lugar.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Gestão de clientes, serviços e ordens de serviço.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Link público para o cliente acompanhar a OS.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Anexos, fotos, histórico e envio por WhatsApp.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Estrutura SaaS multiempresa com controle por plano.
                </div>
            </div>

            <div class="mini-card">
                <strong>DirectOS</strong>
                <div class="mt-1">
                    Mais organização para o prestador e mais transparência para o cliente.
                </div>
                <small>
                    Sistema de Ordem de Serviço com área pública de acompanhamento.
                </small>
            </div>
        </div>

        <div class="col-lg-5 col-md-8">

            <div class="card login-card">
                <div class="login-card-header">
                    Entrar no DirectOS
                </div>

                <div class="login-card-body">

                    <div class="mb-4">
                        <h3 class="fw-bold mb-1">
                            Acessar sistema
                        </h3>

                        <p class="text-muted mb-0">
                            Informe seu e-mail e senha para continuar.
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

                        <div class="mb-4">
                            <label class="form-label">Senha</label>
                            <input 
                                type="password" 
                                name="Senha" 
                                class="form-control" 
                                placeholder="Digite sua senha"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100">
                            Entrar no sistema
                        </button>

                    </form>

                </div>

                <div class="login-footer">
                    <small class="text-muted">
                        Ainda não tem acesso?
                    </small>
                    <br>

                    <a href="index.php#planos" class="text-decoration-none fw-semibold">
                        Conheça os planos do DirectOS
                    </a>
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