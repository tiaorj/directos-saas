<?php
$usuarioNome = $_SESSION["UsuarioNome"] ?? "Usuário";
$usuarioPerfil = $_SESSION["UsuarioPerfil"] ?? "";
?>

<div class="app-layout">

    <aside class="app-sidebar">

        <div class="sidebar-brand">
            <a href="/sistema-os-php-sqlserver/dashboard.php">
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

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/dashboard.php">
                <span class="sidebar-icon">▣</span>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section">Operação</div>

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/ordens/listar.php">
                <span class="sidebar-icon">▤</span>
                <span>Ordens de Serviço</span>
            </a>

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/clientes/listar.php">
                <span class="sidebar-icon">●</span>
                <span>Clientes</span>
            </a>

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/servicos/listar.php">
                <span class="sidebar-icon">◆</span>
                <span>Serviços</span>
            </a>

            <div class="sidebar-section">Gestão</div>

            <?php if ($usuarioPerfil === "Admin" || $usuarioPerfil === "SuperAdmin"): ?>
                <a class="sidebar-link" href="/sistema-os-php-sqlserver/usuarios/listar.php">
                    <span class="sidebar-icon">◉</span>
                    <span>Usuários</span>
                </a>
            <?php endif; ?>

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/empresa/editar.php">
                <span class="sidebar-icon">▥</span>
                <span>Minha Empresa</span>
            </a>

            <a class="sidebar-link" href="/sistema-os-php-sqlserver/planos/meu_plano.php">
                <span class="sidebar-icon">★</span>
                <span>Meu Plano</span>
            </a>
            <a class="sidebar-link" href="/sistema-os-php-sqlserver/configuracoes/index.php">
                <span class="sidebar-icon">⚙</span>
                <span>Configurações</span>
            </a>
            <?php if ($usuarioPerfil === "SuperAdmin"): ?>
                <div class="sidebar-section">Plataforma</div>

                <a class="sidebar-link" href="/sistema-os-php-sqlserver/admin/metricas.php">
                    <span class="sidebar-icon">▦</span>
                    <span>Métricas SaaS</span>
                </a>

                <a class="sidebar-link" href="/sistema-os-php-sqlserver/admin/empresas.php">
                    <span class="sidebar-icon">◆</span>
                    <span>Admin SaaS</span>
                </a>
                <a class="sidebar-link" href="/sistema-os-php-sqlserver/admin/usuarios.php">
                    <span class="sidebar-icon">◉</span>
                    <span>Usuários SaaS</span>
                </a>
                <a class="sidebar-link" href="/sistema-os-php-sqlserver/admin/assinaturas.php">
                    <span class="sidebar-icon">★</span>
                    <span>Assinaturas</span>
                </a>
                <a class="sidebar-link" href="/sistema-os-php-sqlserver/admin/auditoria.php">
                    <span class="sidebar-icon">◎</span>
                    <span>Auditoria</span>
                </a>                
            <?php endif; ?>        
        </nav>

        <div class="sidebar-footer">
            <a href="/sistema-os-php-sqlserver/logout.php" class="sidebar-logout">
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
                <a href="/sistema-os-php-sqlserver/logout.php" class="btn btn-sm btn-outline-secondary">
                    Sair
                </a>
            </div>
        </header>

        <div class="app-content">