<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";
require_once "../includes/permissoes.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$planoAtual = obterPlanoEmpresa($conn, $empresaId);
$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

$stmtPlanos = $conn->prepare("SELECT PlanoId, Nome, Descricao, LimiteOSMes, LimiteUsuarios, ValorMensal FROM OS_Planos WHERE Ativo = 1 AND Slug <> 'teste-assistido' ORDER BY ValorMensal, Nome");
$stmtPlanos->execute();
$planos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

$stmtPendente = $conn->prepare("SELECT TOP 1 s.SolicitacaoId, p.Nome AS PlanoNome FROM OS_SolicitacoesPlano s INNER JOIN OS_Planos p ON p.PlanoId = s.PlanoSolicitadoId WHERE s.EmpresaId = :EmpresaId AND s.Status = 'Pendente' ORDER BY s.SolicitacaoId DESC");
$stmtPendente->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtPendente->execute();
$solicitacaoPendente = $stmtPendente->fetch(PDO::FETCH_ASSOC);

function limiteSolicitarPlano($valor)
{
    return ($valor === null || $valor === "") ? "Ilimitado" : (string)(int)$valor;
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">
    <div class="form-header">
        <div>
            <h3 class="mb-1">Solicitar alteração de plano</h3>
            <p>Escolha o plano desejado. A alteração será analisada pelo suporte.</p>
        </div>
        <a href="meu_plano.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($sucesso !== ""): ?><div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
    <?php if ($erro !== ""): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <?php if ($solicitacaoPendente): ?>
        <div class="alert alert-warning">
            Já existe uma solicitação pendente para o plano <strong><?= htmlspecialchars($solicitacaoPendente["PlanoNome"] ?? "-") ?></strong>.
        </div>
    <?php endif; ?>

    <div class="card form-card">
        <div class="card-header">Planos disponíveis</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-os mb-0">
                    <thead>
                        <tr><th>Plano</th><th>Valor</th><th>Limites</th><th width="320">Solicitação</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planos as $plano): ?>
                            <?php $ehAtual = $planoAtual && (int)$planoAtual["PlanoId"] === (int)$plano["PlanoId"]; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($plano["Nome"] ?? "-") ?></strong><div class="os-subtitle"><?= htmlspecialchars($plano["Descricao"] ?? "") ?></div></td>
                                <td>R$ <?= number_format((float)($plano["ValorMensal"] ?? 0), 2, ",", ".") ?></td>
                                <td>OS/mês: <?= htmlspecialchars(limiteSolicitarPlano($plano["LimiteOSMes"] ?? null)) ?><br>Usuários: <?= htmlspecialchars(limiteSolicitarPlano($plano["LimiteUsuarios"] ?? null)) ?></td>
                                <td>
                                    <?php if ($ehAtual): ?>
                                        <button class="btn btn-secondary w-100" disabled>Plano atual</button>
                                    <?php elseif ($solicitacaoPendente): ?>
                                        <button class="btn btn-warning w-100" disabled>Solicitação pendente</button>
                                    <?php else: ?>
                                        <form method="post" action="solicitar_alteracao.php">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="PlanoSolicitadoId" value="<?= (int)$plano["PlanoId"] ?>">
                                            <textarea name="Mensagem" class="form-control mb-2" rows="2" maxlength="1000" placeholder="Mensagem opcional"></textarea>
                                            <button type="submit" class="btn btn-primary w-100">Solicitar este plano</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
