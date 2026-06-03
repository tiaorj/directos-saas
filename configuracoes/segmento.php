<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$mensagem = trim($_GET["mensagem"] ?? "");

$sqlEmpresa = "
    SELECT
        EmpresaId,
        NomeFantasia,
        Segmento
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmtEmpresa = $conn->prepare($sqlEmpresa);
$stmtEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtEmpresa->execute();

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

$segmentos = [
    "" => [
        "nome" => "Não definido",
        "descricao" => "Nenhum segmento selecionado."
    ],
    "oficina" => [
        "nome" => "Oficina Mecânica",
        "descricao" => "Ideal para oficinas, auto center, mecânica e manutenção de veículos.",
        "modelo" => "oficina"
    ],
    "informatica" => [
        "nome" => "Informática / Assistência Técnica",
        "descricao" => "Ideal para manutenção de computadores, notebooks, impressoras e equipamentos de TI.",
        "modelo" => "informatica"
    ],
    "ar_condicionado" => [
        "nome" => "Refrigeração / Ar-condicionado",
        "descricao" => "Ideal para instalação, limpeza e manutenção de ar-condicionado.",
        "modelo" => "ar_condicionado"
    ],
    "eletronica" => [
        "nome" => "Eletrônica",
        "descricao" => "Ideal para assistência técnica de aparelhos eletrônicos em geral.",
        "modelo" => "eletronica"
    ],
    "servicos_gerais" => [
        "nome" => "Serviços Gerais",
        "descricao" => "Ideal para prestadores de serviços em geral.",
        "modelo" => "servicos_gerais"
    ],
    "personalizado" => [
        "nome" => "Personalizado",
        "descricao" => "Use quando a empresa possui um fluxo próprio ou mistura mais de um segmento."
    ],
];

$segmentoAtual = $empresa["Segmento"] ?? "";

if (!array_key_exists($segmentoAtual, $segmentos)) {
    $segmentoAtual = "";
}

$modeloRecomendado = $segmentos[$segmentoAtual]["modelo"] ?? "";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Segmento da Empresa</h3>
            <p>Configure o tipo de negócio para personalizar melhor o DirectOS.</p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card form-card">
                <div class="card-header">
                    Segmento atual
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">Empresa</div>
                        <h5 class="mb-0 mt-1">
                            <?= htmlspecialchars($empresa["NomeFantasia"] ?? "-") ?>
                        </h5>
                    </div>

                    <form method="post" action="salvar_segmento.php">
                        <?= csrfInput() ?>

                        <div class="mb-3">
                            <label class="form-label">Segmento do negócio</label>

                            <select name="Segmento" class="form-control" required>
                                <?php foreach ($segmentos as $chave => $dados): ?>
                                    <option 
                                        value="<?= htmlspecialchars($chave) ?>"
                                        <?= $segmentoAtual === $chave ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($dados["nome"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="input-help mt-2">
                                Esse segmento ajuda o sistema a sugerir campos, modelos e melhorias específicas para sua empresa.
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                Salvar segmento
                            </button>

                            <a href="index.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($modeloRecomendado !== ""): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <strong>Modelo recomendado</strong>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">
                            Para este segmento, o DirectOS pode criar automaticamente campos personalizados da OS.
                        </p>

                        <a 
                            href="../campos_os/aplicar_modelo.php?modelo=<?= urlencode($modeloRecomendado) ?>&<?= csrfTokenUrl() ?>" 
                            class="btn btn-primary"
                            onclick="return confirm('Deseja aplicar o modelo recomendado? Campos já existentes com o mesmo nome técnico não serão duplicados.')"
                        >
                            Aplicar modelo recomendado
                        </a>

                        <a href="../campos_os/listar.php" class="btn btn-outline-secondary">
                            Ver Campos personalizados
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Segmentos disponíveis</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($segmentos as $chave => $dados): ?>
                            <?php if ($chave === "") continue; ?>

                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 <?= $segmentoAtual === $chave ? "bg-light" : "" ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong><?= htmlspecialchars($dados["nome"]) ?></strong>

                                            <p class="text-muted small mb-0 mt-2">
                                                <?= htmlspecialchars($dados["descricao"]) ?>
                                            </p>
                                        </div>

                                        <?php if ($segmentoAtual === $chave): ?>
                                            <span class="badge bg-success">Atual</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0">
                        <strong>Dica:</strong>
                        escolha o segmento mais próximo do seu negócio. Mesmo depois, você pode ajustar manualmente os campos personalizados da OS.
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>