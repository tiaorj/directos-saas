<?php

function buscarCamposPersonalizadosOS(PDO $conn, int $empresaId, bool $somenteAtivos = true): array
{
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
        WHERE EmpresaId = :EmpresaId
    ";

    if ($somenteAtivos) {
        $sql .= " AND Ativo = 1 ";
    }

    $sql .= " ORDER BY Ordem, Rotulo ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarValoresCamposPersonalizadosOS(PDO $conn, int $empresaId, int $ordemServicoId): array
{
    $sql = "
        SELECT
            CampoId,
            Valor
        FROM OS_OrdensServicoCampos
        WHERE EmpresaId = :EmpresaId
          AND OrdemServicoId = :OrdemServicoId
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmt->execute();

    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $valores = [];

    foreach ($linhas as $linha) {
        $valores[(int)$linha["CampoId"]] = $linha["Valor"];
    }

    return $valores;
}

function validarCamposPersonalizadosOS(array $campos, array $dadosPost): void
{
    $valores = $dadosPost["CamposPersonalizados"] ?? [];

    foreach ($campos as $campo) {
        $campoId = (int)$campo["CampoId"];
        $rotulo = $campo["Rotulo"] ?? "Campo";
        $obrigatorio = (int)($campo["Obrigatorio"] ?? 0) === 1;
        $tipoCampo = $campo["TipoCampo"] ?? "texto";
        $valor = trim($valores[$campoId] ?? "");

        if ($obrigatorio && $valor === "") {
            die("O campo personalizado '{$rotulo}' é obrigatório.");
        }

        if ($valor !== "" && $tipoCampo === "numero" && !is_numeric($valor)) {
            die("O campo personalizado '{$rotulo}' deve ser numérico.");
        }
    }
}

function salvarValoresCamposPersonalizadosOS(
    PDO $conn,
    int $empresaId,
    int $ordemServicoId,
    array $campos,
    array $dadosPost
): void {
    $valores = $dadosPost["CamposPersonalizados"] ?? [];

    $sqlDelete = "
        DELETE FROM OS_OrdensServicoCampos
        WHERE EmpresaId = :EmpresaId
          AND OrdemServicoId = :OrdemServicoId
    ";

    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtDelete->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtDelete->execute();

    $sqlInsert = "
        INSERT INTO OS_OrdensServicoCampos
        (
            EmpresaId,
            OrdemServicoId,
            CampoId,
            Valor
        )
        VALUES
        (
            :EmpresaId,
            :OrdemServicoId,
            :CampoId,
            :Valor
        )
    ";

    $stmtInsert = $conn->prepare($sqlInsert);

    foreach ($campos as $campo) {
        $campoId = (int)$campo["CampoId"];
        $valor = trim($valores[$campoId] ?? "");

        if ($valor === "") {
            continue;
        }

        $stmtInsert->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
        $stmtInsert->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
        $stmtInsert->bindValue(":CampoId", $campoId, PDO::PARAM_INT);
        $stmtInsert->bindValue(":Valor", $valor);
        $stmtInsert->execute();
    }
}

function renderizarCamposPersonalizadosOS(array $campos, array $valores = []): void
{
    if (count($campos) === 0) {
        return;
    }
    ?>

    <div class="card border-info mb-3">
        <div class="card-header bg-info text-white">
            Campos personalizados
        </div>

        <div class="card-body">
            <p class="text-muted mb-3">
                Preencha os campos extras configurados para esta empresa.
            </p>

            <div class="row">
                <?php foreach ($campos as $campo): ?>
                    <?php
                        $campoId = (int)$campo["CampoId"];
                        $rotulo = $campo["Rotulo"] ?? "";
                        $tipoCampo = $campo["TipoCampo"] ?? "texto";
                        $obrigatorio = (int)($campo["Obrigatorio"] ?? 0) === 1;
                        $valor = $valores[$campoId] ?? "";
                        $name = "CamposPersonalizados[" . $campoId . "]";
                        $required = $obrigatorio ? "required" : "";
                    ?>

                    <div class="<?= $tipoCampo === "textarea" ? "col-md-12" : "col-md-6" ?> mb-3">
                        <label class="form-label">
                            <?= htmlspecialchars($rotulo) ?>
                            <?php if ($obrigatorio): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($tipoCampo === "textarea"): ?>
                            <textarea 
                                name="<?= htmlspecialchars($name) ?>" 
                                class="form-control" 
                                rows="3"
                                <?= $required ?>
                            ><?= htmlspecialchars($valor) ?></textarea>

                        <?php elseif ($tipoCampo === "numero"): ?>
                            <input 
                                type="number" 
                                step="0.01"
                                name="<?= htmlspecialchars($name) ?>" 
                                class="form-control"
                                value="<?= htmlspecialchars($valor) ?>"
                                <?= $required ?>
                            >

                        <?php elseif ($tipoCampo === "data"): ?>
                            <input 
                                type="date" 
                                name="<?= htmlspecialchars($name) ?>" 
                                class="form-control"
                                value="<?= htmlspecialchars($valor) ?>"
                                <?= $required ?>
                            >

                        <?php else: ?>
                            <input 
                                type="text" 
                                name="<?= htmlspecialchars($name) ?>" 
                                class="form-control"
                                value="<?= htmlspecialchars($valor) ?>"
                                <?= $required ?>
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php
}

function buscarCamposPersonalizadosComValoresOS(PDO $conn, int $empresaId, int $ordemServicoId): array
{
    $sql = "
        SELECT
            c.CampoId,
            c.Rotulo,
            c.NomeCampo,
            c.TipoCampo,
            c.Ordem,
            c.Ativo,
            v.Valor
        FROM OS_CamposPersonalizados c
        INNER JOIN OS_OrdensServicoCampos v 
            ON v.CampoId = c.CampoId
           AND v.EmpresaId = c.EmpresaId
           AND v.OrdemServicoId = :OrdemServicoId
        WHERE c.EmpresaId = :EmpresaId
        ORDER BY c.Ordem, c.Rotulo
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}