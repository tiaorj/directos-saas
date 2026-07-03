<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

$planoId = (int)($_GET["id"] ?? 0);
$modoEdicao = $planoId > 0;

$plano = [
    "PlanoId" => 0,
    "Nome" => "",
    "Slug" => "",
    "Descricao" => "",
    "LimiteOSMes" => "",
    "LimiteUsuarios" => "",
    "PermiteAnexos" => 1,
    "PermiteAreaCliente" => 1,
    "PermiteWhatsapp" => 1,
    "ValorMensal" => 0,
    "Ativo" => 1
];

if ($modoEdicao) {
    $sql = "
        SELECT
            PlanoId,
            Nome,
            Slug,
            Descricao,
            LimiteOSMes,
            LimiteUsuarios,
            PermiteAnexos,
            PermiteAreaCliente,
            PermiteWhatsapp,
            ValorMensal,
            Ativo,
            DataCadastro
        FROM OS_Planos
        WHERE PlanoId = :PlanoId
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmt->execute();

    $planoBanco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$planoBanco) {
        header("Location: planos.php?erro=Plano não encontrado.");
        exit;
    }

    $plano = $planoBanco;
}

$erro = $_GET["erro"] ?? "";

function valorCampoPlano($plano, $campo)
{
    return htmlspecialchars((string)($plano[$campo] ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                <?= $modoEdicao ? "Editar plano" : "Novo plano" ?>
            </h3>
            <p>
                Configure preço, limites e recursos disponíveis para empresas da plataforma DirectOS.
            </p>
        </div>

        <a href="planos.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="plano_salvar.php">
        <?= csrfInput() ?>

        <input type="hidden" name="PlanoId" value="<?= (int)$plano["PlanoId"] ?>">

        <div class="row g-3">

            <div class="col-lg-8">
                <div class="card form-card mb-4">
                    <div class="card-header">
                        Dados do plano
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do plano</label>
                                <input
                                    type="text"
                                    name="Nome"
                                    class="form-control"
                                    maxlength="100"
                                    value="<?= valorCampoPlano($plano, "Nome") ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Slug</label>
                                <input
                                    type="text"
                                    name="Slug"
                                    class="form-control"
                                    maxlength="50"
                                    value="<?= valorCampoPlano($plano, "Slug") ?>"
                                    placeholder="ex: profissional"
                                >
                                <div class="input-help">
                                    Usado internamente pelo sistema. Exemplo: starter, profissional, empresa.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea
                                name="Descricao"
                                class="form-control"
                                rows="3"
                                maxlength="255"
                            ><?= valorCampoPlano($plano, "Descricao") ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Valor mensal</label>
                                <input
                                    type="text"
                                    name="ValorMensal"
                                    class="form-control"
                                    value="<?= number_format((float)($plano["ValorMensal"] ?? 0), 2, ",", ".") ?>"
                                    required
                                >
                                <div class="input-help">
                                    Exemplo: 79,00
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Limite de OS/mês</label>
                                <input
                                    type="number"
                                    name="LimiteOSMes"
                                    class="form-control"
                                    min="0"
                                    value="<?= $plano["LimiteOSMes"] === null ? "" : (int)$plano["LimiteOSMes"] ?>"
                                    placeholder="Ilimitado"
                                >
                                <div class="input-help">
                                    Deixe vazio para ilimitado.
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Limite de usuários</label>
                                <input
                                    type="number"
                                    name="LimiteUsuarios"
                                    class="form-control"
                                    min="0"
                                    value="<?= $plano["LimiteUsuarios"] === null ? "" : (int)$plano["LimiteUsuarios"] ?>"
                                    placeholder="Ilimitado"
                                >
                                <div class="input-help">
                                    Deixe vazio para ilimitado.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card form-card mb-4">
                    <div class="card-header">
                        Recursos e status
                    </div>

                    <div class="card-body">

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="PermiteAnexos"
                                value="1"
                                id="PermiteAnexos"
                                <?= (int)($plano["PermiteAnexos"] ?? 0) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="PermiteAnexos">
                                Permite anexos
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="PermiteAreaCliente"
                                value="1"
                                id="PermiteAreaCliente"
                                <?= (int)($plano["PermiteAreaCliente"] ?? 0) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="PermiteAreaCliente">
                                Permite área do cliente
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="PermiteWhatsapp"
                                value="1"
                                id="PermiteWhatsapp"
                                <?= (int)($plano["PermiteWhatsapp"] ?? 0) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="PermiteWhatsapp">
                                Permite WhatsApp assistido
                            </label>
                        </div>

                        <hr>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="Ativo"
                                value="1"
                                id="Ativo"
                                <?= (int)($plano["Ativo"] ?? 0) === 1 ? "checked" : "" ?>
                            >
                            <label class="form-check-label" for="Ativo">
                                Plano ativo
                            </label>
                        </div>

                        <div class="alert alert-light border mb-0">
                            <strong>Atenção:</strong><br>
                            Inativar um plano impede novas empresas de escolherem esse plano, mas não remove o histórico de assinaturas.
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-2">
                    Salvar plano
                </button>

                <a href="planos.php" class="btn btn-outline-secondary w-100">
                    Cancelar
                </a>
            </div>

        </div>
    </form>

</div>

<?php require_once "../includes/footer.php"; ?>
