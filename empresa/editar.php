<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = $_SESSION["EmpresaId"];

$sql = "
    SELECT
        EmpresaId,
        NomeFantasia,
        RazaoSocial,
        Cnpj,
        Email,
        Telefone,
        WhatsApp,
        Slug,
        Ativo,
        DataCadastro
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="mb-3">
        <h3>Minha Empresa</h3>
        <p class="text-muted mb-0">
            Atualize os dados da empresa exibidos no sistema e na área do cliente.
        </p>
    </div>

    <?php if ($sucesso !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            Dados da Empresa
        </div>

        <div class="card-body">
            <form method="post" action="atualizar.php">

                <input type="hidden" name="EmpresaId" value="<?= (int)$empresa["EmpresaId"] ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Fantasia *</label>
                        <input 
                            type="text" 
                            name="NomeFantasia" 
                            class="form-control" 
                            required
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["NomeFantasia"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Razão Social</label>
                        <input 
                            type="text" 
                            name="RazaoSocial" 
                            class="form-control"
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["RazaoSocial"] ?? "") ?>"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CNPJ</label>
                        <input 
                            type="text" 
                            name="Cnpj" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["Cnpj"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input 
                            type="text" 
                            name="Telefone" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["Telefone"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input 
                            type="text" 
                            name="WhatsApp" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["WhatsApp"] ?? "") ?>"
                        >
                        <small class="text-muted">
                            Informe com DDD. Exemplo: 21999999999
                        </small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">E-mail</label>
                        <input 
                            type="email" 
                            name="Email" 
                            class="form-control"
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["Email"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Slug</label>
                        <input 
                            type="text" 
                            name="Slug" 
                            class="form-control"
                            maxlength="80"
                            value="<?= htmlspecialchars($empresa["Slug"] ?? "") ?>"
                        >
                        <small class="text-muted">
                            Usado futuramente para URL personalizada.
                        </small>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <strong>Status:</strong>
                    <?php if ((int)$empresa["Ativo"] === 1): ?>
                        <span class="badge bg-success">Ativa</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inativa</span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-success">
                    Salvar Alterações
                </button>

                <a href="../dashboard.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>