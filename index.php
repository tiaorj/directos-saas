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

    <title>DirectOS - Sistema de Ordem de Serviço com IA</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta 
        name="description" 
        content="DirectOS é um sistema online de ordem de serviço com assistente IA, automação WhatsApp, n8n, clientes, serviços, anexos e acompanhamento pelo cliente."
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
            --primary-soft: rgba(37, 99, 235, 0.1);
            --bg: #f3f4f6;
            --muted: #64748b;
            --success: #16a34a;
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
            max-width: 820px;
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
            background: var(--primary-soft);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .badge-soft-success {
            background: rgba(22, 163, 74, 0.12);
            color: var(--success);
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .feature-card,
        .price-card,
        .target-card,
        .automation-card {
            border: 0;
            border-radius: 20px;
            height: 100%;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: var(--primary-soft);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .automation-section {
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 30%),
                linear-gradient(135deg, #ffffff, #eef4ff);
        }

        .automation-flow {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: stretch;
            justify-content: center;
        }

        .flow-step {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 18px;
            min-width: 185px;
            flex: 1;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .flow-step strong {
            display: block;
            margin-bottom: 6px;
        }

        .flow-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: var(--primary);
            font-size: 1.35rem;
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

            .flow-arrow {
                display: none;
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
                    <a class="nav-link" href="#ia">Assistente IA</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#automacao">Automação WhatsApp</a>
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
                    Sistema de Ordem de Serviço com IA + Automação WhatsApp
                </div>

                <h1>
                    Controle suas OS com IA e envie atualizações pelo WhatsApp.
                </h1>

                <p class="lead mt-4">
                    O DirectOS ajuda prestadores de serviço, assistências técnicas e pequenas empresas a controlar atendimentos, gerar resumos profissionais com IA, criar mensagens para WhatsApp e enviar atualizações automáticas usando n8n e Z-API.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="cadastro.php" class="btn btn-light btn-lg">
                        Começar grátis
                    </a>

                    <a href="#automacao" class="btn btn-outline-light btn-lg">
                        Ver automação
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
                        <strong>Assistente IA</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            resumo, prioridade e checklist
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <strong>WhatsApp automático</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            via n8n + Z-API
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
                            <span class="text-muted">IA</span>
                            <strong>Resumo profissional gerado</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Automação</span>
                            <strong>n8n + Z-API</strong>
                        </div>

                        <div class="alert alert-primary mt-4 mb-2">
                            Mensagem criada com IA para o cliente.
                        </div>

                        <div class="alert alert-success mb-0">
                            Atualização enviada automaticamente pelo WhatsApp.
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
                        <div class="feature-icon">IA</div>
                        <h5>Assistente IA</h5>
                        <p class="text-muted mb-0">
                            Gere resumo profissional da OS, sugestão de prioridade, checklist técnico e mensagem para WhatsApp.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body p-4">
                        <div class="feature-icon">WA</div>
                        <h5>WhatsApp automatizado</h5>
                        <p class="text-muted mb-0">
                            Envie mensagens para o cliente usando integração com n8n e Z-API.
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
                        <div class="feature-icon">▥</div>
                        <h5>Multiempresa</h5>
                        <p class="text-muted mb-0">
                            Estrutura preparada para SaaS, com dados separados por empresa e plano.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<section class="section bg-white" id="ia">
    <div class="container">

        <div class="section-title">
            <span class="badge-soft">Assistente IA</span>

            <h2 class="mt-3">
                Inteligência artificial para agilizar e profissionalizar suas OS
            </h2>

            <p>
                Transforme descrições simples em textos profissionais, gere mensagens para o cliente e padronize o atendimento técnico com apoio de IA.
            </p>
        </div>

        <div class="row g-4 align-items-center">

            <div class="col-lg-6">
                <div class="card hero-panel">
                    <div class="hero-panel-header">
                        Antes da IA
                    </div>

                    <div class="hero-panel-body">
                        <p class="text-muted mb-2">
                            Descrição digitada pelo atendente:
                        </p>

                        <div class="alert alert-light border">
                            cliente falou que o notebook não liga, testei carregador e parece placa, vou avaliar melhor
                        </div>

                        <p class="text-muted mb-2 mt-4">
                            Resumo sugerido pelo DirectOS IA:
                        </p>

                        <div class="alert alert-primary mb-0">
                            Equipamento recebido com relato de falha ao ligar. Foi realizado teste inicial com carregador, sem resposta do equipamento. Há indícios de possível falha na placa principal, sendo necessária avaliação técnica complementar para confirmação do diagnóstico.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="card feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon">1</div>
                                <h5>Resumo profissional</h5>
                                <p class="text-muted mb-0">
                                    Transforme textos simples em descrições mais claras para a OS.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon">2</div>
                                <h5>WhatsApp com IA</h5>
                                <p class="text-muted mb-0">
                                    Gere mensagens prontas para atualizar o cliente sobre o andamento.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon">3</div>
                                <h5>Sugestão de prioridade</h5>
                                <p class="text-muted mb-0">
                                    A IA ajuda a indicar se a OS é baixa, normal, alta ou urgente.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon">4</div>
                                <h5>Checklist técnico</h5>
                                <p class="text-muted mb-0">
                                    Receba uma lista inicial de verificações para padronizar o atendimento.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<section class="section automation-section" id="automacao">
    <div class="container">

        <div class="section-title">
            <span class="badge-soft-success">IA + Automação WhatsApp</span>

            <h2 class="mt-3">
                Mensagens inteligentes enviadas automaticamente pelo WhatsApp
            </h2>

            <p>
                O DirectOS gera a mensagem com IA, envia para o n8n e o n8n dispara pelo WhatsApp usando Z-API. Menos trabalho manual, mais agilidade e mais transparência para o cliente.
            </p>
        </div>

        <div class="automation-flow mb-5">

            <div class="flow-step">
                <div class="feature-icon">1</div>
                <strong>OS atualizada</strong>
                <p class="text-muted mb-0">
                    O atendente ou técnico atualiza o atendimento no DirectOS.
                </p>
            </div>

            <div class="flow-arrow">→</div>

            <div class="flow-step">
                <div class="feature-icon">2</div>
                <strong>IA gera a mensagem</strong>
                <p class="text-muted mb-0">
                    A IA cria um texto profissional para informar o cliente.
                </p>
            </div>

            <div class="flow-arrow">→</div>

            <div class="flow-step">
                <div class="feature-icon">3</div>
                <strong>n8n processa</strong>
                <p class="text-muted mb-0">
                    O DirectOS envia os dados para um fluxo de automação no n8n.
                </p>
            </div>

            <div class="flow-arrow">→</div>

            <div class="flow-step">
                <div class="feature-icon">4</div>
                <strong>WhatsApp enviado</strong>
                <p class="text-muted mb-0">
                    A Z-API dispara a mensagem para o cliente no WhatsApp.
                </p>
            </div>

        </div>

        <div class="row g-4 align-items-center">

            <div class="col-lg-6">
                <div class="card automation-card">
                    <div class="card-body p-4">
                        <span class="badge-soft-success">Diferencial para o mercado</span>

                        <h3 class="mt-3">
                            Atendimento mais rápido sem depender de mensagens manuais
                        </h3>

                        <p class="text-muted">
                            Em vez de copiar textos, abrir WhatsApp e explicar manualmente cada atualização, sua equipe pode gerar a mensagem com IA e disparar pelo fluxo automatizado.
                        </p>

                        <ul class="check-list mb-0">
                            <li>Mensagem profissional criada com IA.</li>
                            <li>Envio via n8n integrado ao DirectOS.</li>
                            <li>Disparo pelo WhatsApp usando Z-API.</li>
                            <li>Registro em auditoria para acompanhar os envios.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card hero-panel">
                    <div class="hero-panel-header">
                        Exemplo de mensagem enviada
                    </div>

                    <div class="hero-panel-body">
                        <div class="alert alert-success">
                            Olá, João! Sua ordem de serviço OS-2026-000128 está em andamento. Nossa equipe já iniciou a análise e você pode acompanhar a evolução pelo link abaixo.
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Origem</span>
                            <strong>DirectOS</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Automação</span>
                            <strong>n8n</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Envio</span>
                            <strong>Z-API / WhatsApp</strong>
                        </div>

                        <div class="alert alert-primary mt-4 mb-0">
                            O cliente recebe a atualização no WhatsApp e acompanha a OS pelo link público.
                        </div>
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
                    O DirectOS foi pensado para quem precisa organizar atendimentos, reduzir mensagens repetidas, melhorar a comunicação com o cliente e usar IA para ganhar produtividade.
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

            <div class="col-lg-4">
                <div class="card price-card">
                    <div class="card-body p-4">
                        <h5>Gratuito</h5>

                        <div class="price mt-3">
                            R$ 0
                        </div>

                        <p class="text-muted">
                            Para começar e validar o uso do sistema.
                        </p>

                        <ul class="check-list">
                            <li>Até 10 OS por mês</li>
                            <li>Cadastro de clientes</li>
                            <li>Cadastro de serviços</li>
                            <li>Área pública do cliente</li>
                            <li>Link de acompanhamento</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-outline-primary w-100">
                            Começar grátis
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card price-card price-card-highlight">
                    <div class="card-body p-4">
                        <span class="badge bg-primary mb-2">
                            Mais indicado
                        </span>

                        <h5>Profissional</h5>

                        <div class="price mt-3">
                            OS ilimitadas
                        </div>

                        <p class="text-muted">
                            Para quem já tem rotina de atendimento e quer escalar.
                        </p>

                        <ul class="check-list">
                            <li>Ordens de serviço ilimitadas</li>
                            <li>Assistente IA para OS</li>
                            <li>Mensagens WhatsApp com IA</li>
                            <li>Automação n8n + Z-API</li>
                            <li>Anexos e histórico</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-primary w-100">
                            Criar conta
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card price-card">
                    <div class="card-body p-4">
                        <h5>Empresa</h5>

                        <div class="price mt-3">
                            Completo
                        </div>

                        <p class="text-muted">
                            Para equipes com mais usuários e necessidade de gestão.
                        </p>

                        <ul class="check-list">
                            <li>Usuários ilimitados</li>
                            <li>Recursos extras de gestão</li>
                            <li>Administração multiempresa</li>
                            <li>Auditoria de ações sensíveis</li>
                            <li>Integrações e automações</li>
                        </ul>

                        <a href="cadastro.php" class="btn btn-outline-primary w-100">
                            Começar
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
            Transforme suas ordens de serviço em uma experiência profissional com IA e automação.
        </h2>

        <p class="lead mt-3 mb-4" style="color: rgba(255,255,255,.82);">
            Controle suas OS, gere mensagens inteligentes e envie atualizações pelo WhatsApp com n8n e Z-API.
        </p>

        <a href="cadastro.php" class="btn btn-light btn-lg">
            Começar agora
        </a>
    </div>
</section>

<footer>
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <strong>DirectOS</strong>
                <div>
                    Sistema de Ordem de Serviço com IA, automação e área do cliente.
                </div>
            </div>

            <div>
                <span>
                    © <?= date("Y") ?> DirectOS. Todos os direitos reservados.
                </span>
            </div>
        </div>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>