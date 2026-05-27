<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/sistema-os-php-sqlserver/">
            DirectOS
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/dashboard.php">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/clientes/listar.php">
                        Clientes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/servicos/listar.php">
                        Serviços
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/ordens/listar.php">
                        Ordens de Serviço
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/empresa/editar.php">
                        Minha Empresa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/sistema-os-php-sqlserver/planos/meu_plano.php">
                        Meu Plano
                    </a>
                </li>                
                <?php if (($_SESSION["UsuarioPerfil"] ?? "") === "Admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-os-php-sqlserver/usuarios/listar.php">
                            Usuários
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <div class="d-flex align-items-center text-white">
                <span class="me-3">
                    <?= htmlspecialchars($_SESSION["UsuarioNome"] ?? "Usuário") ?>
                    <small class="text-secondary">
                        (<?= htmlspecialchars($_SESSION["UsuarioPerfil"] ?? "") ?>)
                    </small>
                </span>

                <a href="/sistema-os-php-sqlserver/logout.php" class="btn btn-outline-light btn-sm">
                    Sair
                </a>
            </div>
        </div>
    </div>
</nav>