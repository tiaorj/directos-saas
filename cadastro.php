<?php
session_start();
require_once "includes/csrf.php";

if (isset($_SESSION["UsuarioId"])) {
    header("Location: dashboard.php");
    exit;
}

$erro = $_GET["erro"] ?? "";
$sucesso = $_GET["sucesso"] ?? "";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar conta - DirectOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta 
        name="description" 
        content="Crie sua conta no DirectOS e comece a controlar ordens de serviço, clientes e acompanhamento pelo cliente."
    >

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        :root {
            --dark: #111827;
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

        .cadastro-wrapper {
            min-height: 100vh;
            padding: 34px 0;
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
            font-size: clamp(2.3rem, 5vw, 4rem);
            font-weight: 850;
            letter-spacing: -1.5px;
            line-height: 1.05;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.82);
            max-width: 620px;
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

        .cadastro-card {
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28);
        }

        .cadastro-card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 24px;
            font-weight: 800;
        }

        .cadastro-card-body {
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

        .btn-cadastro {
            padding: 12px;
            border-radius: 12px;
            font-weight: 800;
        }

        .form-section-title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin: 8px 0 16px;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .input-help {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 4px;
        }

        .cadastro-footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 18px 24px;
            text-align: center;
        }

        @media (max-width: 991px) {
            .cadastro-wrapper {
                padding: 22px 0;
            }

            .cadastro-card-body {
                padding: 24px;
            }
        }
    </style>
</head>

<body>

<div class="container cadastro-wrapper d-flex align-items-center">

    <div class="row w-100 align-items-center justify-content-center g-5">

        <div class="col-lg-5 text-white">
            <a href="index.php" class="back-link mb-4 d-inline-block">
                ← Voltar para o site
            </a>

            <div class="brand-badge">
                Comece pelo plano gratuito
            </div>

            <h1 class="brand-title mb-3">
                Crie sua conta e organize suas OS.
            </h1>

            <p class="lead brand-subtitle">
                Cadastre sua empresa, crie seu usuário administrador e comece a usar o DirectOS.
            </p>

            <div class="mt-4">
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Plano gratuito com até 10 OS por mês.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Link público para acompanhamento do cliente.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Cadastros de clientes, serviços, usuários e anexos.
                </div>

                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    Estrutura preparada para evoluir para planos pagos.
                </div>
            </div>
        </div>

        <div class="col-lg-7">

            <div class="card cadastro-card">
                <div class="cadastro-card-header">
                    Criar conta no DirectOS
                </div>

                <div class="cadastro-card-body">

                    <div class="mb-4">
                        <h3 class="fw-bold mb-1">
                            Cadastro da empresa
                        </h3>

                        <p class="text-muted mb-0">
                            Preencha os dados abaixo para criar sua conta inicial.
                        </p>
                    </div>

                    <?php if ($erro !== ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sucesso !== ""): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($sucesso) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="salvar_cadastro.php">
                        <?= csrfInput() ?>

                        <div class="form-section-title">
                            Dados da empresa
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-label">Nome Fantasia</label>
                                <input 
                                    type="text" 
                                    name="NomeFantasia" 
                                    class="form-control" 
                                    maxlength="150"
                                    required
                                    placeholder="Ex: Oficina Central"
                                >
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Razão Social</label>
                                <input 
                                    type="text" 
                                    name="RazaoSocial" 
                                    class="form-control" 
                                    maxlength="150"
                                    placeholder="Opcional"
                                >
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CNPJ</label>
                                <input 
                                    type="text" 
                                    name="Cnpj" 
                                    class="form-control" 
                                    maxlength="20"
                                    placeholder="Opcional"
                                >
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone</label>
                                <input 
                                    type="text" 
                                    name="Telefone" 
                                    class="form-control" 
                                    maxlength="20"
                                    placeholder="(00) 0000-0000"
                                >
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">WhatsApp</label>
                                <input 
                                    type="text" 
                                    name="WhatsApp" 
                                    class="form-control" 
                                    maxlength="20"
                                    placeholder="21999999999"
                                >
                                <div class="input-help">
                                    Use DDD. Exemplo: 21999999999
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail da empresa</label>
                            <input 
                                type="email" 
                                name="EmailEmpresa" 
                                class="form-control" 
                                maxlength="150"
                                placeholder="contato@empresa.com"
                            >
                        </div>

                        <div class="form-section-title mt-4">
                            Usuário administrador
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-label">Nome do administrador</label>
                                <input 
                                    type="text" 
                                    name="NomeUsuario" 
                                    class="form-control" 
                                    maxlength="150"
                                    required
                                    placeholder="Seu nome"
                                >
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required-label">E-mail de acesso</label>
                                <input 
                                    type="email" 
                                    name="EmailUsuario" 
                                    class="form-control" 
                                    maxlength="150"
                                    required
                                    placeholder="seuemail@empresa.com"
                                >
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-label">Senha</label>
                                <input 
                                    type="password" 
                                    name="Senha" 
                                    class="form-control" 
                                    minlength="6"
                                    required
                                    placeholder="Mínimo de 6 caracteres"
                                >
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required-label">Confirmar senha</label>
                                <input 
                                    type="password" 
                                    name="ConfirmarSenha" 
                                    class="form-control" 
                                    minlength="6"
                                    required
                                    placeholder="Repita a senha"
                                >
                            </div>
                        </div>

                        <div class="alert alert-info">
                            Ao criar sua conta, sua empresa será vinculada automaticamente ao plano <strong>Gratuito</strong>.
                        </div>

                        <button type="submit" class="btn btn-primary btn-cadastro w-100">
                            Criar conta gratuita
                        </button>

                    </form>

                </div>

                <div class="cadastro-footer">
                    <small class="text-muted">
                        Já possui conta?
                    </small>
                    <br>

                    <a href="login.php" class="text-decoration-none fw-semibold">
                        Entrar no DirectOS
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
