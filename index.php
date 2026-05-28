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

    <title>DirectOS - Sistema de Ordem de Serviço Online</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta 
        name="description" 
        content="DirectOS é um sistema online para controle de ordens de serviço, clientes, serviços, anexos, WhatsApp e acompanhamento pelo cliente."
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
            background: var(--bg);
            color: #111827;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .navbar {
            background: rgba(17, 24, 39, 0.96);
            backdrop-filter: blur(12px);
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 600;
        }

        .hero {
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.35), transparent 32%),
                linear-gradient(135deg, #111827, #1e3a8a);
            color: #fff;
            padding: 92px 0 110px;
            overflow: hidden;
        }

        .hero h1 {
            font-size: clamp(2.25rem, 5vw, 4.3rem);
            font-weight: 850;
            letter-spacing: -1.5px;
            line-height: 1.04;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.82);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 8px 13px;
            border-radius: 999px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .hero-panel {
            background: #fff;
            color: #111827;
            border-radius: 24px;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28);
            overflow: hidden;
        }

        .hero-panel-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
            font-weight: 800;
        }

        .hero-panel-body {
            padding: 22px;
        }

        .fake-table-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .fake-table-row:last-child {
            border-bottom: 0;
        }

        .section {
            padding: 78px 0;
        }

        .section-title {
            max-width: 780px;
            margin: 0 auto 46px;
            text-align: center;
        }

        .section-title h2 {
            font-weight: 850;
            letter-spacing: -0.7px;
        }

        .section-title p {
            color: var(--muted);
            font-size: 1.05rem;
        }

        .badge-soft {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .feature-card,
        .price-card,
        .target-card {
            border: 0;
            border-radius: 20px;
            height: 100%;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .target-pill {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px 16px;
            font-weight: 700;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .price-card-highlight {
            border: 2px solid var(--primary);
            transform: translateY(-8px);
        }

        .price {
            font-size: 2.2rem;
            font-weight: 850;
            letter-spacing: -0.8px;
        }

        .check-list {
            padding-left: 0;
            list-style: none;
        }

        .check-list li {
            margin-bottom: 10px;
            color: #334155;
        }

        .check-list li::before {
            content: "✓";
            color: var(--primary);
            font-weight: 900;
            margin-right: 8px;
        }

        .cta-section {
            background: linear-gradient(135deg, #111827, #2563eb);
            color: #fff;
            padding: 74px 0;
        }

        .cta-section h2 {
            font-weight: 850;
            letter-spacing: -0.7px;
        }

        footer {
            background: #111827;
            color: #cbd5e1;
            padding: 34px 0;
        }

        footer strong {
            color: #fff;
        }

        @media (max-width: 991px) {
            .hero {
                padding: 70px 0 82px;
            }

            .price-card-highlight {
                transform: none;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            DirectOS
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuLanding">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuLanding">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="#recursos">Recursos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#publico">Para quem é</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#planos">Planos</a>
                </li>

                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a class="btn btn-outline-light btn-sm" href="login.php">
                        Entrar
                    </a>
                </li>

                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-light btn-sm" href="cadastro.php">
                        Começar agora
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-7">
                <div class="hero-badge">
                    Sistema online de Ordem de Serviço
                </div>

                <h1>
                    Organize seus serviços e deixe o cliente acompanhar tudo pelo celular.
                </h1>

                <p class="lead mt-4">
                    O DirectOS ajuda prestadores de serviço, assistências técnicas e pequenas empresas a controlar OS, clientes, anexos, histórico, WhatsApp e área do cliente em um só lugar.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="cadastro.php" class="btn btn-light btn-lg">
                        Começar grátis
                    </a>

                    <a href="#recursos" class="btn btn-outline-light btn-lg">
                        Conhecer recursos
                    </a>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-6 col-md-4">
                        <strong>10 OS/mês</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            no plano gratuito
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <strong>Link público</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            para o cliente
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <strong>Anexos</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            fotos e PDFs
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="hero-panel">
                    <div class="hero-panel-header">
                        Painel da Ordem de Serviço
                    </div>

                    <div class="hero-panel-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-muted small">Código</div>
                                <h5 class="mb-0">OS-2026-000128</h5>
                            </div>

                            <span class="badge bg-warning text-dark">
                                Em andamento
                            </span>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Cliente</span>
                            <strong>João Silva</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Serviço</span>
                            <strong>Manutenção</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Previsão</span>
                            <strong>28/05/2026</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Anexos</span>
                            <strong>3 arquivos</strong>
                        </div>

                        <div class="alert alert-primary mt-4 mb-0">
                            Link público enviado ao cliente por WhatsApp.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section" id="recursos">
    <div class="container">

        <div class="section-title">
            <span class="badge-soft">Recursos principais</span>
            <h2 class="mt-3">
                Tudo que o prestador precisa para controlar atendimentos
            </h2>
            <p>
                Comece simples, organize sua rotina e entregue uma experiência mais profissional para o cliente.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">OS</div>
                        <h5>Ordens de Serviço</h5>
                        <p class="text-muted mb-0">
                            Cadastre OS com cliente, serviço, status, prioridade, datas, valores e observações.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">↗</div>
                        <h5>Área do Cliente</h5>
                        <p class="text-muted mb-0">
                            Envie um link público para o cliente acompanhar a OS pelo celular, sem precisar de login.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">📎</div>
                        <h5>Anexos e Fotos</h5>
                        <p class="text-muted mb-0">
                            Anexe fotos, PDFs e documentos, escolhendo o que pode aparecer para o cliente.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">W</div>
                        <h5>WhatsApp</h5>
                        <p class="text-muted mb-0">
                            Copie ou envie o link de acompanhamento diretamente para o cliente pelo WhatsApp.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">▥</div>
                        <h5>Multiempresa</h5>
                        <p class="text-muted mb-0">
                            Estrutura preparada para SaaS, com dados separados por empresa e plano.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">✓</div>
                        <h5>Histórico da OS</h5>
                        <p class="text-muted mb-0">
                            Registre mudanças de status e acompanhe a evolução de cada atendimento.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<section class="section bg-white" id="publico">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-5">
                <span class="badge-soft">Para quem é</span>

                <h2 class="mt-3">
                    Ideal para pequenos prestadores e empresas de serviço
                </h2>

                <p class="text-muted">
                    O DirectOS foi pensado para quem precisa organizar atendimentos, reduzir mensagens repetidas e dar mais transparência ao cliente.
                </p>

                <a href="#planos" class="btn btn-primary">
                    Ver planos
                </a>
            </div>

            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="target-pill">Assistência técnica</div>
                    </div>

                    <div class="col-md-6">
                        <div class="target-pill">Técnicos de informática</div>
                    </div>

                    <div class="col-md-6">
                        <div class="target-pill">Manutenção de celular</div>
                    </div>

                    <div class="col-md-6">
                        <div class="target-pill">Refrigeração</div>
                    </div>

                    <div class="col-md-6">
                        <div class="target-pill">Elétrica e hidráulica</div>
                    </div>

                    <div class="col-md-6">
                        <div class="target-pill">Pequenas oficinas</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section" id="planos">
    <div class="container">

        <div class="section-title">
            <span class="badge-soft">Planos</span>
            <h2 class="mt-3">
                Comece simples e evolua conforme sua operação cresce
            </h2>
            <p>
                Os planos já estão alinhados com a estrutura SaaS do DirectOS.
            </p>
        </div>

        <div class="row g-4 align-items-stretch">

            <div class="col-md-4">
                <div class="card price-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <h4>Gratuito</h4>

                        <div class="price my-3">
                            R$ 0
                            <small class="text-muted fs-6">/mês</small>
                        </div>

                        <p class="text-muted">
                            Para testar e organizar os primeiros atendimentos.
                        </p>

                        <ul class="check-list mb-4">
                            <li>Até 10 OS por mês</li>
                            <li>1 usuário</li>
                            <li>Cadastro de clientes</li>
                            <li>Link de acompanhamento</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-outline-primary w-100 mt-auto">
                            Começar grátis
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card price-card price-card-highlight">
                    <div class="card-body p-4 d-flex flex-column">
                        <span class="badge bg-primary mb-2 align-self-start">
                            Mais indicado
                        </span>

                        <h4>Profissional</h4>

                        <div class="price my-3">
                            R$ 49
                            <small class="text-muted fs-6">/mês</small>
                        </div>

                        <p class="text-muted">
                            Para prestadores que querem profissionalizar o atendimento.
                        </p>

                        <ul class="check-list mb-4">
                            <li>OS ilimitadas</li>
                            <li>Até 3 usuários</li>
                            <li>Anexos e fotos</li>
                            <li>Envio por WhatsApp</li>
                            <li>Área do cliente</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-primary w-100 mt-auto">
                            Quero testar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card price-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <h4>Empresa</h4>

                        <div class="price my-3">
                            R$ 99
                            <small class="text-muted fs-6">/mês</small>
                        </div>

                        <p class="text-muted">
                            Para equipes pequenas com mais controle e usuários.
                        </p>

                        <ul class="check-list mb-4">
                            <li>OS ilimitadas</li>
                            <li>Usuários ilimitados</li>
                            <li>Controle avançado</li>
                            <li>Recursos extras</li>
                            <li>Suporte prioritário</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-outline-primary w-100 mt-auto">
                            Começar agora
                        </a>                        
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<section class="cta-section">
    <div class="container text-center">
        <h2>
            Transforme o acompanhamento dos seus serviços em uma experiência profissional.
        </h2>

        <p class="lead mt-3 mb-4" style="color: rgba(255,255,255,.82);">
            Controle suas OS e envie um link para o cliente acompanhar tudo pelo celular.
        </p>

        <a href="login.php" class="btn btn-light btn-lg">
            Acessar DirectOS
        </a>
    </div>
</section>

<footer>
    <div class="container text-center">
        <strong>DirectOS</strong>

        <p class="mb-0 mt-1">
            Sistema online de Ordem de Serviço e acompanhamento do cliente.
        </p>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>