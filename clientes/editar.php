<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$id = $_GET["id"] ?? 0;

$sql = "
    SELECT 
        ClienteId,
        Nome,
        Telefone,
        Email,
        Documento,
        Endereco,
        Cidade,
        Estado,
        Ativo
    FROM OS_Clientes
    WHERE ClienteId = :ClienteId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ClienteId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Editar Cliente</h3>
        <p class="text-muted mb-0">Atualize os dados do cliente</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" action="atualizar.php">

                <input type="hidden" name="ClienteId" value="<?= $cliente["ClienteId"] ?>">
                <input type="hidden" name="EmpresaId" value="<?= $empresaId ?>">

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="Nome" class="form-control" required maxlength="150"
                               value="<?= htmlspecialchars($cliente["Nome"] ?? "") ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Documento</label>
                        <input type="text" name="Documento" class="form-control" maxlength="30"
                               value="<?= htmlspecialchars($cliente["Documento"] ?? "") ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="Telefone" class="form-control" maxlength="30"
                               value="<?= htmlspecialchars($cliente["Telefone"] ?? "") ?>">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="Email" class="form-control" maxlength="150"
                               value="<?= htmlspecialchars($cliente["Email"] ?? "") ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="Endereco" class="form-control" maxlength="255"
                           value="<?= htmlspecialchars($cliente["Endereco"] ?? "") ?>">
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="Cidade" class="form-control" maxlength="100"
                               value="<?= htmlspecialchars($cliente["Cidade"] ?? "") ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estado</label>
                        <input type="text" name="Estado" class="form-control" maxlength="2"
                               value="<?= htmlspecialchars($cliente["Estado"] ?? "") ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="Ativo" class="form-control">
                        <option value="1" <?= (int)$cliente["Ativo"] === 1 ? "selected" : "" ?>>
                            Ativo
                        </option>
                        <option value="0" <?= (int)$cliente["Ativo"] === 0 ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    Atualizar
                </button>

                <a href="listar.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>