<?php
session_start();

if (isset($_SESSION["UsuarioId"])) {
    header("Location: dashboard.php");
    exit;
}

$assuntoContato = rawurlencode("Solicitar implantacao assistida DirectOS");
$linkContatoImplantacao = "mailto:direct.ti.tec@gmail.com?subject={$assuntoContato}";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">

    <title>DirectOS - Sistema de Ordem de Serviço com IA</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta 
        name="description" 
        content="DirectOS é um sistema online de ordem de serviço para pequenos prestadores, com clientes, serviços, IA, financeiro, recibos, área do cliente e mensagens prontas para WhatsApp."
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

        .launch-note {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            color: #1e3a8a;
            padding: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
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
                    <a class="nav-link" href="#whatsapp">WhatsApp</a>
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
                    <a class="btn btn-light btn-sm" href="#contato">
                        Solicitar acesso
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
                    Sistema de Ordem de Serviço com IA, financeiro e WhatsApp
                </div>

                <h1>
                    Controle suas ordens de serviço, recebimentos e clientes em um só lugar.
                </h1>

                <p class="lead mt-4">
                    O DirectOS ajuda pequenos prestadores, assistências técnicas e empresas de serviço a organizar OS, clientes, serviços, recebimentos, recibos e comunicações com o cliente usando uma plataforma simples e profissional.
                </p>

                <p class="mt-3">
                    Nesta fase inicial, o DirectOS está sendo liberado com implantação assistida. Após o contato, configuramos sua empresa, plano inicial e acesso ao sistema.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="#contato" class="btn btn-light btn-lg">
                        Solicitar implantação assistida
                    </a>

                    <a href="login.php?demo=1" class="btn btn-outline-light btn-lg">
                        Ver demonstração
                    </a>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-6 col-md-4">
                        <strong>OS organizadas</strong>
                        <div style="color: rgba(255,255,255,.72); font-size: .9rem;">
                            do atendimento ao recebimento
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
                            mensagens para enviar ao cliente
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
                            <span class="text-muted">Financeiro</span>
                            <strong>Recebimento parcial registrado</strong>
                        </div>

                        <div class="alert alert-primary mt-4 mb-2">
                             Mensagem profissional criada para o cliente.
                        </div>

                        <div class="alert alert-success mb-0">
                            Recibo e acompanhamento disponíveis para a OS.
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
                        <h5>WhatsApp assistido</h5>
                        <p class="text-muted mb-0">
                             Gere mensagens profissionais e abra o WhatsApp com o texto pronto para enviar ao cliente.
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
                        <h5>Financeiro e recibos</h5>
                        <p class="text-muted mb-0">
                            Registre recebimentos, pagamentos parciais, saldos e emita recibos da OS.
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

<section class="section automation-section" id="whatsapp">
    <div class="container">

        <div class="section-title">
            <span class="badge-soft-success">IA + WhatsApp</span>

            <h2 class="mt-3">
                Mensagens prontas para manter o cliente informado
            </h2>

            <p>
                O DirectOS ajuda a gerar mensagens profissionais para WhatsApp, facilitando o contato com o cliente durante o atendimento. A automação com n8n pode ser integrada futuramente conforme a necessidade da empresa.
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
                <strong>Mensagem preparada</strong>
                <p class="text-muted mb-0">
                    O sistema ajuda a preparar um texto claro e profissional.
                </p>
            </div>

            <div class="flow-arrow">→</div>

            <div class="flow-step">
                <div class="feature-icon">3</div>
                <strong>WhatsApp aberto</strong>
                <p class="text-muted mb-0">
                    O usuário abre o WhatsApp com a mensagem preenchida.
                </p>
            </div>

            <div class="flow-arrow">→</div>

            <div class="flow-step">
                <div class="feature-icon">4</div>
                <strong>Cliente informado</strong>
                <p class="text-muted mb-0">
                    O cliente recebe a atualização e pode acompanhar a OS pelo link.
                </p>
            </div>

        </div>

        <div class="row g-4 align-items-center">

            <div class="col-lg-6">
                <div class="card automation-card">
                    <div class="card-body p-4">
                        <span class="badge-soft-success">Diferencial para o mercado</span>

                        <h3 class="mt-3">
                            Comunicação mais profissional sem perder tempo escrevendo do zero
                        </h3>

                        <p class="text-muted">
                            Em vez de escrever cada atualização do zero, sua equipe pode gerar uma mensagem clara, copiar ou abrir o WhatsApp com o texto preenchido e manter o cliente informado.
                        </p>

                        <ul class="check-list mb-0">
                            <li>Mensagem profissional criada com IA.</li>
                            <li>Abertura do WhatsApp com mensagem preenchida.</li>
                            <li>Link de acompanhamento da OS para o cliente.</li>
                            <li>Automação com n8n opcional para evolução futura.</li>
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
                            <span class="text-muted">Envio</span>
                            <strong>WhatsApp manual assistido</strong>
                        </div>

                        <div class="fake-table-row">
                            <span class="text-muted">Link</span>
                            <strong>Área do cliente</strong>
                        </div>

                        <div class="alert alert-primary mt-4 mb-0">
                            O cliente recebe a atualização e acompanha a OS pelo link público.
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

                <a href="#contato" class="btn btn-primary">
                    Solicitar acesso
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
                Os planos abaixo servem como referência para a implantação inicial. Após o contato,
                alinhamos sua operação, configuramos sua empresa e liberamos o acesso ao sistema.
            </p>
        </div>

        <div class="launch-note mb-4">
            Nesta fase inicial, o DirectOS está sendo liberado com implantação assistida. Após o contato, configuramos sua empresa, plano inicial e acesso ao sistema.
        </div>

        <div class="row g-4 align-items-stretch">

            <div class="col-lg-4">
                <div class="card price-card h-100">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="mb-3">
                            <span class="badge-soft">Entrada</span>
                        </div>

                        <h4 class="mb-3">Starter</h4>

                        <div class="price mb-2">
                            R$ 39<span style="font-size: 1rem; font-weight: 600;">/mês</span>
                        </div>

                        <p class="text-muted">
                            Ideal para prestadores individuais ou pequenos negócios que querem sair do controle manual
                            e começar a organizar ordens de serviço de forma profissional.
                        </p>

                        <ul class="check-list mt-3 mb-4">
                            <li>Até 30 ordens de serviço por mês</li>
                            <li>1 usuário</li>
                            <li>Cadastro de clientes</li>
                            <li>Cadastro de serviços</li>
                            <li>Controle de status e prioridade da OS</li>
                            <li>Valores previstos e valores finais</li>
                            <li>Controle financeiro básico</li>
                            <li>Registro de recebimentos</li>
                            <li>Recibo geral da OS</li>
                            <li>Dashboard com resumo da operação</li>
                        </ul>

                        <div class="alert alert-light border mt-auto">
                            <strong>Indicado para:</strong><br>
                            autônomos, técnicos independentes e pequenos prestadores que estão começando.
                        </div>

                        <a href="#contato" class="btn btn-outline-primary w-100 mt-3">
                            Falar sobre este plano
                        </a>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card price-card price-card-highlight h-100">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="mb-3">
                            <span class="badge bg-primary">
                                Mais indicado
                            </span>
                        </div>

                        <h4 class="mb-3">Profissional</h4>

                        <div class="price mb-2">
                            R$ 79<span style="font-size: 1rem; font-weight: 600;">/mês</span>
                        </div>

                        <p class="text-muted">
                            Para pequenas empresas que já possuem rotina de atendimento e precisam controlar OS,
                            clientes, financeiro, recibos, IA e comunicação com o cliente em um só lugar.
                        </p>

                        <ul class="check-list mt-3 mb-4">
                            <li>Até 150 ordens de serviço por mês</li>
                            <li>Até 3 usuários</li>
                            <li>Tudo do plano Starter</li>
                            <li>Assistente IA para descrições da OS</li>
                            <li>IA para checklist técnico</li>
                            <li>IA para mensagens de WhatsApp</li>
                            <li>Checklist padrão por serviço</li>
                            <li>Campos personalizados da OS</li>
                            <li>Área do cliente por link público</li>
                            <li>Recebimentos parciais</li>
                            <li>Recibo geral e recibo por pagamento</li>
                            <li>Relatórios operacionais</li>
                            <li>Relatório financeiro</li>
                            <li>Exportação CSV</li>
                            <li>WhatsApp assistido com mensagem pronta</li>
                        </ul>

                        <div class="alert alert-primary mt-auto">
                            <strong>Indicado para:</strong><br>
                            assistências técnicas, oficinas, manutenção, suporte e empresas de serviço em crescimento.
                        </div>

                        <a href="#contato" class="btn btn-primary w-100 mt-3">
                            Solicitar implantação
                        </a>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card price-card h-100">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="mb-3">
                            <span class="badge-soft">Operação completa</span>
                        </div>

                        <h4 class="mb-3">Empresa</h4>

                        <div class="price mb-2">
                            R$ 149<span style="font-size: 1rem; font-weight: 600;">/mês</span>
                        </div>

                        <p class="text-muted">
                            Para equipes com maior volume de atendimento, mais usuários e necessidade de relatórios,
                            personalização por segmento e apoio inicial para implantação.
                        </p>

                        <ul class="check-list mt-3 mb-4">
                            <li>Ordens de serviço ilimitadas</li>
                            <li>Até 10 usuários</li>
                            <li>Tudo do plano Profissional</li>
                            <li>Segmento da empresa</li>
                            <li>Modelos prontos por segmento</li>
                            <li>Campos personalizados avançados</li>
                            <li>Relatórios operacionais completos</li>
                            <li>Relatório financeiro completo</li>
                            <li>Histórico de recebimentos por OS</li>
                            <li>Recibos profissionais para impressão/PDF</li>
                            <li>Exportação CSV operacional e financeira</li>
                            <li>Área do cliente para acompanhamento</li>
                            <li>Suporte inicial para implantação</li>
                            <li>Evolução futura para automação com n8n</li>
                        </ul>

                        <div class="alert alert-light border mt-auto">
                            <strong>Indicado para:</strong><br>
                            empresas com equipe, alto volume de OS e necessidade de padronizar a operação.
                        </div>

                        <a href="#contato" class="btn btn-outline-primary w-100 mt-3">
                            Falar sobre este plano
                        </a>

                    </div>
                </div>
            </div>

        </div>

        <div class="text-center mt-5">
            <p class="text-muted mb-3">
                Quer conhecer a experiência antes de solicitar a implantação? Acesse a demonstração do DirectOS.
            </p>

            <a href="login.php?demo=1" class="btn btn-outline-secondary">
                Ver demonstração
            </a>
        </div>

    </div>
</section>

<section class="cta-section" id="contato">
    <div class="container text-center">
        <h2>
            Solicitar implantação assistida
        </h2>

        <p class="lead mt-3 mb-4" style="color: rgba(255,255,255,.82);">
            Nesta fase inicial, o DirectOS está sendo liberado com implantação assistida. Após o contato, configuramos sua empresa, plano inicial e acesso ao sistema.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="<?= htmlspecialchars($linkContatoImplantacao) ?>" class="btn btn-light btn-lg">
                Solicitar implantação assistida
            </a>

            <a href="login.php" class="btn btn-outline-light btn-lg">
                Ver demonstração
            </a>
        </div>

        <p class="mt-3 mb-0" style="color: rgba(255,255,255,.72);">
            Contato: direct.ti.tec@gmail.com
        </p>
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
