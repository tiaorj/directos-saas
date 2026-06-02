<?php
require_once __DIR__ . "/../config/config.php";

$usuarioNome = $_SESSION["UsuarioNome"] ?? "Usuário";
$usuarioPerfil = $_SESSION["UsuarioPerfil"] ?? "";

$baseUrl = rtrim(APP_URL, "/");
?>

<div class="app-layout">

    <aside class="app-sidebar">

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
                Campos da OS
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

    <main class="app-main">

        <header class="app-topbar">
            <div>
                <strong>DirectOS</strong>
                <span class="text-muted ms-2">Sistema de Ordem de Serviço</span>
            </div>

            <div class="topbar-user">
                <span><?= htmlspecialchars($usuarioNome) ?></span>
                <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" class="btn btn-sm btn-outline-secondary">
                    Sair
                </a>
            </div>
        </header>

        <div class="app-content">