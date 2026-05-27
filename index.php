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
        content="DirectOS é um sistema online para controle de ordens de serviço, clientes, orçamentos, anexos e acompanhamento pelo cliente."
    >

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        body {
            background: #f8f9fa;
        }

        .hero {
            background: linear-gradient(135deg, #212529, #0d6efd);
            color: #fff;
            padding: 90px 0;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
        }

        .section {
            padding: 70px 0;
        }

        .card-benefit {
            border: none;
            height: 100%;
        }

        .price-card {
            border: none;
            height: 100%;
        }

        .badge-soft {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        footer {
            background: #212529;
            color: #fff;
            padding: 30px 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            DirectOS
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuLanding">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuLanding">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#recursos">Recursos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#planos">Planos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#publico">Para quem é</a>
                </li>

                <li class="nav-item ms-lg-3">
                    <a class="btn btn-outline-light btn-sm" href="login.php">
                        Entrar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-7">
                <span class="badge bg-light text-dark mb-3">
                    Sistema online de Ordem de Serviço
                </span>

                <h1>
                    Organize suas ordens de serviço e deixe seu cliente acompanhar tudo pelo celular.
                </h1>

                <p class="lead mt-3">
                    O DirectOS ajuda prestadores de serviço, assistências técnicas e pequenas empresas a controlar clientes, serviços, orçamentos, anexos e status das OS em um só lugar.
                </p>

                <div class="mt-4">
                    <a href="login.php" class="btn btn-light btn-lg me-2">
                        Acessar Sistema
                    </a>

                    <a href="#recursos" class="btn btn-outline-light btn-lg">
                        Conhecer Recursos
                    </a>
                </div>
            </div>

            <div class="col-lg-5 mt-5 mt-lg-0">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 text-dark">
                        <h5 class="mb-3">Resumo da OS</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Status</span>
                            <span class="badge bg-warning text-dark">Em andamento</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Cliente</span>
                            <strong>João Silva</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Serviço</span>
                            <strong>Manutenção</strong>
                        </div>

                        <hr>

                        <p class="text-muted mb-2">
                            Link público para o cliente acompanhar:
                        </p>

                        <div class="alert alert-primary mb-0">
                            directos.com.br/os/acompanhar
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section" id="recursos">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge-soft">Recursos principais</span>
            <h2 class="mt-3">Tudo que o prestador precisa para controlar seus atendimentos</h2>
            <p class="text-muted">
                Comece simples e profissionalize o atendimento da sua empresa.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>Ordens de Serviço</h5>
                        <p class="text-muted">
                            Cadastre OS com cliente, serviço, prioridade, status, previsão, valores e observações.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>Área do Cliente</h5>
                        <p class="text-muted">
                            Envie um link para o cliente acompanhar o andamento da OS pelo celular, sem precisar de login.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>Anexos e Fotos</h5>
                        <p class="text-muted">
                            Anexe fotos, PDFs e documentos à OS, escolhendo o que pode ou não aparecer para o cliente.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>WhatsApp</h5>
                        <p class="text-muted">
                            Copie ou envie o link de acompanhamento da OS diretamente para o cliente pelo WhatsApp.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>Multiempresa</h5>
                        <p class="text-muted">
                            Estrutura preparada para SaaS, com dados separados por empresa.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-benefit shadow-sm">
                    <div class="card-body">
                        <h5>Histórico da OS</h5>
                        <p class="text-muted">
                            Registre as movimentações e acompanhe a evolução de cada atendimento.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section bg-white" id="publico">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <span class="badge-soft">Para quem é</span>
                <h2 class="mt-3">
                    Ideal para pequenos prestadores e empresas de serviço
                </h2>

                <p class="text-muted">
                    O DirectOS foi pensado para quem precisa organizar atendimentos e reduzir mensagens repetidas perguntando sobre o andamento do serviço.
                </p>
            </div>

            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-light border">Assistência técnica</div>
                    </div>

                    <div class="col-md-6">
                        <div class="alert alert-light border">Técnicos de informática</div>
                    </div>

                    <div class="col-md-6">
                        <div class="alert alert-light border">Manutenção de celular</div>
                    </div>

                    <div class="col-md-6">
                        <div class="alert alert-light border">Refrigeração</div>
                    </div>

                    <div class="col-md-6">
                        <div class="alert alert-light border">Elétrica e hidráulica</div>
                    </div>

                    <div class="col-md-6">
                        <div class="alert alert-light border">Pequenas oficinas</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section" id="planos">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge-soft">Planos sugeridos</span>
            <h2 class="mt-3">Comece simples e evolua conforme sua operação cresce</h2>
            <p class="text-muted">
                Valores podem ser ajustados depois, mas já ajudam a posicionar o produto.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="card price-card shadow-sm">
                    <div class="card-body">
                        <h4>Gratuito</h4>
                        <h2 class="my-3">R$ 0</h2>
                        <p class="text-muted">Para testar e organizar os primeiros atendimentos.</p>

                        <ul>
                            <li>Até 10 OS por mês</li>
                            <li>1 usuário</li>
                            <li>Cadastro de clientes</li>
                            <li>Link de acompanhamento</li>
                        </ul>

                        <a href="login.php" class="btn btn-outline-primary w-100">
                            Começar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card price-card shadow border-primary">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2">Mais indicado</span>
                        <h4>Profissional</h4>
                        <h2 class="my-3">R$ 49/mês</h2>
                        <p class="text-muted">Para prestadores que querem profissionalizar o atendimento.</p>

                        <ul>
                            <li>OS ilimitadas</li>
                            <li>Até 3 usuários</li>
                            <li>Anexos e fotos</li>
                            <li>WhatsApp</li>
                            <li>Área do cliente</li>
                        </ul>

                        <a href="login.php" class="btn btn-primary w-100">
                            Quero testar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card price-card shadow-sm">
                    <div class="card-body">
                        <h4>Empresa</h4>
                        <h2 class="my-3">R$ 99/mês</h2>
                        <p class="text-muted">Para equipes pequenas com mais controle e usuários.</p>

                        <ul>
                            <li>Usuários ilimitados</li>
                            <li>Relatórios</li>
                            <li>Controle avançado</li>
                            <li>Suporte prioritário</li>
                        </ul>

                        <a href="login.php" class="btn btn-outline-primary w-100">
                            Falar sobre plano
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="section bg-primary text-white">
    <div class="container text-center">
        <h2>
            Transforme o acompanhamento dos seus serviços em uma experiência profissional.
        </h2>

        <p class="lead">
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
        <p class="mb-0">
            Sistema online de Ordem de Serviço e acompanhamento do cliente.
        </p>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>