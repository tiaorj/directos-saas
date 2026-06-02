<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$campoId = (int)($_GET["id"] ?? 0);

if ($campoId <= 0) {
    die("Campo inválido.");
}

$sql = "
    SELECT
        CampoId,
        NomeCampo,
        Rotulo,
        TipoCampo,
        Obrigatorio,
        Ordem,
        Ativo
    FROM OS_CamposPersonalizados
    WHERE CampoId = :CampoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":CampoId", $campoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$campo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campo) {
    die("Campo não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Editar Campo da OS</h3>
            <p>Atualize o campo personalizado.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Campo
        </div>

        <div class="card-body">

            <form method="post" action="atualizar.php">
                <?= csrfInput() ?>

                <input type="hidden" name="CampoId" value="<?= (int)$campo["CampoId"] ?>">

                <div class="mb-3">
                    <label class="form-label">Rótulo *</label>
                    <input 
                        type="text" 
                        name="Rotulo" 
                        class="form-control" 
                        required 
                        maxlength="150"
                        value="<?= htmlspecialchars($campo["Rotulo"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome técnico *</label>
                    <input 
                        type="text" 
                        name="NomeCampo" 
                        class="form-control" 
                        required 
                        maxlength="100"
                        value="<?= htmlspecialchars($campo["NomeCampo"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo do Campo *</label>
                    <select name="TipoCampo" class="form-control" required>
                        <option value="texto" <?= $campo["TipoCampo"] === "texto" ? "selected" : "" ?>>Texto</option>
                        <option value="numero" <?= $campo["TipoCampo"] === "numero" ? "selected" : "" ?>>Número</option>
                        <option value="data" <?= $campo["TipoCampo"] === "data" ? "selected" : "" ?>>Data</option>
                        <option value="textarea" <?= $campo["TipoCampo"] === "textarea" ? "selected" : "" ?>>Texto longo</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ordem</label>
                    <input 
                        type="number" 
                        name="Ordem" 
                        class="form-control" 
                        value="<?= (int)($campo["Ordem"] ?? 0) ?>"
                    >
                </div>

                <div class="form-check mb-3">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        name="Obrigatorio" 
                        value="1" 
                        id="Obrigatorio"
                        <?= (int)$campo["Obrigatorio"] === 1 ? "checked" : "" ?>
                    >
                    <label class="form-check-label" for="Obrigatorio">
                        Campo obrigatório
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1" <?= (int)$campo["Ativo"] === 1 ? "selected" : "" ?>>Ativo</option>
                        <option value="0" <?= (int)$campo["Ativo"] === 0 ? "selected" : "" ?>>Inativo</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Atualizar Campo
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