<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$empresaId = (int)($_POST["EmpresaId"] ?? 0);
$planoId = (int)($_POST["PlanoId"] ?? 0);

if ($empresaId <= 0 || $planoId <= 0) {
    header("Location: empresas.php?erro=Dados inválidos.");
    exit;
}

$sqlEmpresa = "
    SELECT EmpresaId, NomeFantasia
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmtEmpresa = $conn->prepare($sqlEmpresa);
$stmtEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtEmpresa->execute();

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    header("Location: empresas.php?erro=Empresa não encontrada.");
    exit;
}

$sqlPlano = "
    SELECT PlanoId, Nome
    FROM OS_Planos
    WHERE PlanoId = :PlanoId
      AND Ativo = 1
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
$stmtPlano->execute();

$plano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    header("Location: empresa.php?id=" . $empresaId . "&erro=Plano não encontrado.");
    exit;
}

$conn->beginTransaction();

try {
    $sqlCancelar = "
        UPDATE OS_Assinaturas
        SET Status = 'Cancelada',
            DataFim = GETDATE()
        WHERE EmpresaId = :EmpresaId
          AND Status = 'Ativa'
    ";

    $stmtCancelar = $conn->prepare($sqlCancelar);
    $stmtCancelar->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtCancelar->execute();

    $sqlInserir = "
        INSERT INTO OS_Assinaturas
        (
            EmpresaId,
            PlanoId,
            Status,
            DataInicio
        )
        VALUES
        (
            :EmpresaId,
            :PlanoId,
            'Ativa',
            GETDATE()
        )
    ";

    $stmtInserir = $conn->prepare($sqlInserir);
    $stmtInserir->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInserir->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtInserir->execute();

    $conn->commit();

    header("Location: empresa.php?id=" . $empresaId . "&sucesso=Plano alterado para " . urlencode($plano["Nome"]) . ".");
    exit;

} catch (Exception $e) {
    $conn->rollBack();

    header("Location: empresa.php?id=" . $empresaId . "&erro=Erro ao alterar plano.");
    exit;
}