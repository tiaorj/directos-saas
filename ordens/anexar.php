<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

$empresaId = $_SESSION["EmpresaId"];
$ordemServicoId = $_GET["id"] ?? 0;

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

$sql = "
    SELECT
        OrdemServicoId,
        CodigoOS,
        Titulo
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$erro = $_GET["erro"] ?? "";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Anexar Arquivo</h3>
        <p class="text-muted mb-0">
            OS <?= htmlspecialchars($ordem["CodigoOS"] ?? ("#" . $ordem["OrdemServicoId"])) ?> -
            <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
        </p>
    </div>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            Novo Anexo
        </div>

        <div class="card-body">
            <form method="post" action="salvar_anexo.php" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordem["OrdemServicoId"] ?>">

                <div class="mb-3">
                    <label class="form-label">Arquivo *</label>
                    <input 
                        type="file" 
                        name="Arquivo" 
                        class="form-control" 
                        required
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                    >
                    <small class="text-muted">
                        Permitidos: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX.
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        name="VisivelCliente" 
                        id="VisivelCliente" 
                        value="1"
                    >
                    <label class="form-check-label" for="VisivelCliente">
                        Permitir que o cliente veja este anexo no link público
                    </label>
                </div>

                <button type="submit" class="btn btn-success">
                    Enviar Anexo
                </button>

                <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-secondary">
                    Voltar
                </a>

            </form>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
