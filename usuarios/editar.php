<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Usuário inválido.");
}

$sql = "
    SELECT 
        UsuarioId,
        Nome,
        Email,
        Perfil,
        Ativo
    FROM OS_Usuarios
    WHERE UsuarioId = :UsuarioId
      AND EmpresaId = :EmpresaId          
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":UsuarioId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuário não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Editar Usuário</h3>
            <p>Atualize os dados de acesso deste usuário.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Usuário
        </div>
        <div class="card-body">

            <form method="post" action="atualizar.php">

                <input type="hidden" name="UsuarioId" value="<?= $usuario["UsuarioId"] ?>">
                <input type="hidden" name="EmpresaId" value="<?= $empresaId ?>">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="Nome" class="form-control" required maxlength="150"
                           value="<?= htmlspecialchars($usuario["Nome"] ?? "") ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="Email" class="form-control" required maxlength="150"
                           value="<?= htmlspecialchars($usuario["Email"] ?? "") ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="Senha" class="form-control" minlength="6">
                    <small class="text-muted">
                        Deixe em branco para manter a senha atual.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Perfil</label>
                    <select name="Perfil" class="form-control">
                        <option value="Admin" <?= $usuario["Perfil"] === "Admin" ? "selected" : "" ?>>
                            Admin
                        </option>
                        <option value="Atendente" <?= $usuario["Perfil"] === "Atendente" ? "selected" : "" ?>>
                            Atendente
                        </option>
                        <option value="Tecnico" <?= $usuario["Perfil"] === "Tecnico" ? "selected" : "" ?>>
                            Técnico
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1" <?= (int)$usuario["Ativo"] === 1 ? "selected" : "" ?>>
                            Ativo
                        </option>
                        <option value="0" <?= (int)$usuario["Ativo"] === 0 ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Atualizar Usuário
                    </button>

                    <a href="listar.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>