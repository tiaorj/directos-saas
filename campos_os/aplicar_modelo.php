<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

csrfValidarTokenGet();

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$modelo = $_GET["modelo"] ?? "";

$modelosCamposOS = [
    "oficina" => [
        ["rotulo" => "Placa do veículo", "nome" => "placa_veiculo", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
        ["rotulo" => "Marca", "nome" => "marca_veiculo", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
        ["rotulo" => "Modelo", "nome" => "modelo_veiculo", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
        ["rotulo" => "Ano", "nome" => "ano_veiculo", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 4],
        ["rotulo" => "Quilometragem", "nome" => "quilometragem", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 5],
        ["rotulo" => "Chassi", "nome" => "chassi", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 6],
        ["rotulo" => "Combustível", "nome" => "combustivel", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
        ["rotulo" => "Problema relatado pelo cliente", "nome" => "problema_relatado_cliente", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
    ],

    "informatica" => [
        ["rotulo" => "Tipo de equipamento", "nome" => "tipo_equipamento", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
        ["rotulo" => "Marca", "nome" => "marca_equipamento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
        ["rotulo" => "Modelo", "nome" => "modelo_equipamento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
        ["rotulo" => "Número de série", "nome" => "numero_serie", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 4],
        ["rotulo" => "Acessórios recebidos", "nome" => "acessorios_recebidos", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
        ["rotulo" => "Senha de acesso", "nome" => "senha_acesso", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 6],
        ["rotulo" => "Backup autorizado?", "nome" => "backup_autorizado", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
        ["rotulo" => "Estado físico do equipamento", "nome" => "estado_fisico_equipamento", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
    ],

    "ar_condicionado" => [
        ["rotulo" => "Tipo do equipamento", "nome" => "tipo_equipamento_ar", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
        ["rotulo" => "Marca", "nome" => "marca_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
        ["rotulo" => "Modelo", "nome" => "modelo_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
        ["rotulo" => "BTUs", "nome" => "btus", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 4],
        ["rotulo" => "Local de instalação", "nome" => "local_instalacao", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 5],
        ["rotulo" => "Última manutenção", "nome" => "ultima_manutencao", "tipo" => "data", "obrigatorio" => 0, "ordem" => 6],
        ["rotulo" => "Tipo de serviço", "nome" => "tipo_servico_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
        ["rotulo" => "Observações técnicas", "nome" => "observacoes_tecnicas_ar", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
    ],

    "eletronica" => [
        ["rotulo" => "Tipo do aparelho", "nome" => "tipo_aparelho", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
        ["rotulo" => "Marca", "nome" => "marca_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
        ["rotulo" => "Modelo", "nome" => "modelo_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
        ["rotulo" => "Número de série", "nome" => "numero_serie_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 4],
        ["rotulo" => "Acessórios recebidos", "nome" => "acessorios_aparelho", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
        ["rotulo" => "Defeito informado", "nome" => "defeito_informado", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 6],
        ["rotulo" => "Estado de conservação", "nome" => "estado_conservacao", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 7],
    ],

    "servicos_gerais" => [
        ["rotulo" => "Local do atendimento", "nome" => "local_atendimento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 1],
        ["rotulo" => "Pessoa responsável no local", "nome" => "responsavel_local", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
        ["rotulo" => "Telefone do responsável", "nome" => "telefone_responsavel", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
        ["rotulo" => "Data combinada", "nome" => "data_combinada", "tipo" => "data", "obrigatorio" => 0, "ordem" => 4],
        ["rotulo" => "Materiais necessários", "nome" => "materiais_necessarios", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
        ["rotulo" => "Observações do local", "nome" => "observacoes_local", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 6],
    ],
];

if (!array_key_exists($modelo, $modelosCamposOS)) {
    die("Modelo inválido.");
}

$campos = $modelosCamposOS[$modelo];

$sqlExiste = "
    SELECT COUNT(*)
    FROM OS_CamposPersonalizados
    WHERE EmpresaId = :EmpresaId
      AND NomeCampo = :NomeCampo
";

$stmtExiste = $conn->prepare($sqlExiste);

$sqlInsert = "
    INSERT INTO OS_CamposPersonalizados
    (
        EmpresaId,
        NomeCampo,
        Rotulo,
        TipoCampo,
        Obrigatorio,
        Ordem,
        Ativo
    )
    VALUES
    (
        :EmpresaId,
        :NomeCampo,
        :Rotulo,
        :TipoCampo,
        :Obrigatorio,
        :Ordem,
        1
    )
";

$stmtInsert = $conn->prepare($sqlInsert);

$criados = 0;
$ignorados = 0;

foreach ($campos as $campo) {
    $nomeCampo = $campo["nome"];

    $stmtExiste->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtExiste->bindValue(":NomeCampo", $nomeCampo);
    $stmtExiste->execute();

    if ((int)$stmtExiste->fetchColumn() > 0) {
        $ignorados++;
        continue;
    }

    $stmtInsert->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInsert->bindValue(":NomeCampo", $nomeCampo);
    $stmtInsert->bindValue(":Rotulo", $campo["rotulo"]);
    $stmtInsert->bindValue(":TipoCampo", $campo["tipo"]);
    $stmtInsert->bindValue(":Obrigatorio", (int)$campo["obrigatorio"], PDO::PARAM_INT);
    $stmtInsert->bindValue(":Ordem", (int)$campo["ordem"], PDO::PARAM_INT);
    $stmtInsert->execute();

    $criados++;
}

$mensagem = "Modelo aplicado. Campos criados: {$criados}. Campos já existentes ignorados: {$ignorados}.";

header("Location: listar.php?mensagem=" . urlencode($mensagem));
exit;