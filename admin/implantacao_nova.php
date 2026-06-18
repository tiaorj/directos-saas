<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

$sqlPlanos = "
    SELECT
        PlanoId,
        Nome,
        Slug,
        ValorMensal,
        LimiteOSMes,
        LimiteUsuarios
    FROM OS_Planos
    WHERE Ativo = 1
    ORDER BY
        CASE WHEN Slug = 'teste-assistido' THEN 0 ELSE 1 END,
        ValorMensal,
        Nome
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$planos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);
$planoTesteId = null;

foreach ($planos as $plano) {
    if (($plano["Slug"] ?? "") === "teste-assistido") {
        $planoTesteId = (int)$plano["PlanoId"];
        break;
    }
}

$segmentos = [
    "" => "Não definido",
    "oficina" => "Oficina Mecânica",
    "informatica" => "Informática / Assistência Técnica",
    "ar_condicionado" => "Refrigeração / Ar-condicionado",
    "eletronica" => "Eletrônica",
    "servicos_gerais" => "Serviços Gerais",
    "personalizado" => "Personalizado"
];

$erro = $_GET["erro"] ?? "";
$dataFimPadrao = date("Y-m-d", strtotime("+7 days"));
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Nova implantação assistida</h3>
            <p>
                Cadastre a empresa interessada, o responsável e o acesso inicial de avaliação.
            </p>
        </div>

        <a href="implantacoes.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($planoTesteId === null): ?>
        <div class="alert alert-warning">
            Plano Teste Assistido não encontrado. Execute o script <strong>database/005_plano_teste_assistido.sql</strong> antes de liberar novos testes.
        </div>
    <?php endif; ?>

    <form method="post" action="implantacao_salvar.php">
        <?= csrfInput() ?>

        <div class="row g-3">

            <div class="col-lg-7">
                <div class="card form-card h-100">
                    <div class="card-header">
                        Dados da empresa
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome da empresa *</label>
                                <input type="text" name="NomeFantasia" class="form-control" maxlength="150" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Razão social</label>
                                <input type="text" name="RazaoSocial" class="form-control" maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="Cnpj" class="form-control" maxlength="20">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="Telefone" class="form-control" maxlength="20">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" name="WhatsApp" class="form-control" maxlength="20">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label">E-mail da empresa</label>
                                <input type="email" name="EmailEmpresa" class="form-control" maxlength="150">
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Segmento</label>
                                <select name="Segmento" class="form-select">
                                    <?php foreach ($segmentos as $valor => $rotulo): ?>
                                        <option value="<?= htmlspecialchars($valor) ?>">
                                            <?= htmlspecialchars($rotulo) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Observação comercial</label>
                            <textarea
                                name="ObservacaoComercial"
                                class="form-control"
                                rows="4"
                                maxlength="1000"
                                placeholder="Contexto do interessado, combinado comercial, próximos passos..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card form-card mb-3">
                    <div class="card-header">
                        Acesso inicial
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nome do responsável *</label>
                            <input type="text" name="NomeUsuario" class="form-control" maxlength="150" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail de acesso *</label>
                            <input type="email" name="EmailUsuario" class="form-control" maxlength="150" required>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Senha provisória *</label>
                            <input type="text" name="Senha" class="form-control" minlength="6" required>
                            <div class="input-help mt-2">
                                O usuário será criado como Admin da empresa.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-card">
                    <div class="card-header">
                        Plano e período de teste
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Plano *</label>
                            <select name="PlanoId" class="form-select" required>
                                <?php foreach ($planos as $plano): ?>
                                    <option
                                        value="<?= (int)$plano["PlanoId"] ?>"
                                        <?= $planoTesteId === (int)$plano["PlanoId"] ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($plano["Nome"]) ?>
                                        - R$ <?= number_format((float)$plano["ValorMensal"], 2, ",", ".") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data fim do teste</label>
                            <input type="date" name="DataFimTeste" class="form-control" value="<?= htmlspecialchars($dataFimPadrao) ?>">
                            <div class="input-help mt-2">
                                Se ficar vazio, o sistema usará 7 dias a partir da criação.
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success" <?= $planoTesteId === null ? "disabled" : "" ?>>
                                Criar implantação
                            </button>

                            <a href="implantacoes.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<?php require_once "../includes/footer.php"; ?>
