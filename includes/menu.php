<?php
require_once __DIR__ . "/../config/config.php";

$usuarioNome = $_SESSION["UsuarioNome"] ?? "Usuário";
$usuarioPerfil = $_SESSION["UsuarioPerfil"] ?? "";
$empresaNome = $_SESSION["EmpresaNome"] ?? "Sistema de Ordem de Serviço";

$baseUrl = rtrim(APP_URL, "/");
?>

<div class="app-layout">

    <aside class="app-sidebar" id="app-sidebar">

        <div class="sidebar-brand">
            <a href="<?= htmlspecialchars($baseUrl) ?>/dashboard.php">
                <span class="brand-icon">D</span>
                <span>DirectOS</span>
            </a>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($usuarioNome, 0, 1)) ?>
            </div>

            <div>
                <div class="sidebar-user-name">
                    <?= htmlspecialchars($usuarioNome) ?>
                </div>

                <div class="sidebar-user-role">
                    <?= htmlspecialchars($usuarioPerfil) ?>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">

            <div class="sidebar-section">Principal</div>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/dashboard.php">
                <span class="sidebar-icon">▣</span>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section">Operação</div>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/ordens/listar.php">
                <span class="sidebar-icon">▤</span>
                <span>Ordens de Serviço</span>
            </a>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/clientes/listar.php">
                <span class="sidebar-icon">●</span>
                <span>Clientes</span>
            </a>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/servicos/listar.php">
                <span class="sidebar-icon">◆</span>
                <span>Serviços</span>
            </a>
            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/relatorios/ordens.php">
                <span class="sidebar-icon">▧</span>
                <span>Relatórios</span>
            </a>
            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/relatorios/financeiro.php">
                <span class="sidebar-icon">$</span>
                <span>Financeiro</span>
            </a>

            <div class="sidebar-section">Gestão</div>

            <?php if ($usuarioPerfil === "Admin" || $usuarioPerfil === "SuperAdmin"): ?>
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/usuarios/listar.php">
                    <span class="sidebar-icon">◉</span>
                    <span>Usuários</span>
                </a>
            <?php endif; ?>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/empresa/editar.php">
                <span class="sidebar-icon">▥</span>
                <span>Minha Empresa</span>
            </a>

            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/planos/meu_plano.php">
                <span class="sidebar-icon">★</span>
                <span>Meu Plano</span>
            </a>
            
            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/configuracoes/index.php">
                <span class="sidebar-icon">⚙</span>
                <span>Configurações</span>
            </a>
            <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/campos_os/listar.php">
                <span class="sidebar-icon">☰</span>
                Campos personalizados
            </a>            
            <?php if ($usuarioPerfil === "SuperAdmin"): ?>
                <div class="sidebar-section">Plataforma</div>

                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/metricas.php">
                    <span class="sidebar-icon">▦</span>
                    <span>Métricas SaaS</span>
                </a>

                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/empresas.php">
                    <span class="sidebar-icon">◆</span>
                    <span>Admin SaaS</span>
                </a>
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/implantacoes.php">
                    <span class="sidebar-icon">▨</span>
                    <span>Implantação Assistida</span>
                </a>
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/usuarios.php">
                    <span class="sidebar-icon">◉</span>
                    <span>Usuários SaaS</span>
                </a>
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/assinaturas.php">
                    <span class="sidebar-icon">★</span>
                    <span>Assinaturas</span>
                </a>
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/auditoria.php">
                    <span class="sidebar-icon">◎</span>
                    <span>Auditoria</span>
                </a>                
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/admin/diagnostico.php">
                    <span class="sidebar-icon">◌</span>
                    <span>Diagnóstico</span>
                </a>         
                <a class="sidebar-link" href="<?= htmlspecialchars($baseUrl) ?>/configuracoes/integracoes.php">
                    <span class="sidebar-icon">⚡</span>
                    <span>Integrações</span>
                </a>                       
            <?php endif; ?>        
        </nav>

        <div class="sidebar-footer">
            <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" class="sidebar-logout">
                Sair do sistema
            </a>
        </div>

    </aside>

    <div class="app-sidebar-overlay" data-menu-close></div>

    <main class="app-main">

        <header class="app-topbar">
            <button
                type="button"
                class="mobile-menu-toggle"
                aria-label="Abrir menu"
                aria-controls="app-sidebar"
                aria-expanded="false"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="topbar-branding">
                <strong>DirectOS</strong>
                <span class="topbar-company text-muted ms-2">
                    <?= htmlspecialchars($empresaNome) ?>
                </span>
            </div>

            <div class="topbar-user">
                <span class="topbar-user-name"><?= htmlspecialchars($usuarioNome) ?></span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" class="btn btn-sm btn-outline-secondary topbar-logout">
                    Sair
                </a>
            </div>
        </header>

        <div class="app-content">
