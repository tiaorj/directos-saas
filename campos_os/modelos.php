<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);

$modelosCamposOS = [
    "oficina" => [
        "nome" => "Oficina Mecânica",
        "descricao" => "Campos para controle de veículo, placa, quilometragem e dados automotivos.",
        "campos" => [
            ["rotulo" => "Placa do veículo", "nome" => "placa_veiculo", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
            ["rotulo" => "Marca", "nome" => "marca_veiculo", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
            ["rotulo" => "Modelo", "nome" => "modelo_veiculo", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
            ["rotulo" => "Ano", "nome" => "ano_veiculo", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 4],
            ["rotulo" => "Quilometragem", "nome" => "quilometragem", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 5],
            ["rotulo" => "Chassi", "nome" => "chassi", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 6],
            ["rotulo" => "Combustível", "nome" => "combustivel", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
            ["rotulo" => "Problema relatado pelo cliente", "nome" => "problema_relatado_cliente", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
        ]
    ],

    "informatica" => [
        "nome" => "Informática / Assistência Técnica",
        "descricao" => "Campos para computador, notebook, impressora e equipamentos de TI.",
        "campos" => [
            ["rotulo" => "Tipo de equipamento", "nome" => "tipo_equipamento", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
            ["rotulo" => "Marca", "nome" => "marca_equipamento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
            ["rotulo" => "Modelo", "nome" => "modelo_equipamento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
            ["rotulo" => "Número de série", "nome" => "numero_serie", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 4],
            ["rotulo" => "Acessórios recebidos", "nome" => "acessorios_recebidos", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
            ["rotulo" => "Senha de acesso", "nome" => "senha_acesso", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 6],
            ["rotulo" => "Backup autorizado?", "nome" => "backup_autorizado", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
            ["rotulo" => "Estado físico do equipamento", "nome" => "estado_fisico_equipamento", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
        ]
    ],

    "ar_condicionado" => [
        "nome" => "Refrigeração / Ar-condicionado",
        "descricao" => "Campos para instalação, manutenção e limpeza de ar-condicionado.",
        "campos" => [
            ["rotulo" => "Tipo do equipamento", "nome" => "tipo_equipamento_ar", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
            ["rotulo" => "Marca", "nome" => "marca_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
            ["rotulo" => "Modelo", "nome" => "modelo_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
            ["rotulo" => "BTUs", "nome" => "btus", "tipo" => "numero", "obrigatorio" => 0, "ordem" => 4],
            ["rotulo" => "Local de instalação", "nome" => "local_instalacao", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 5],
            ["rotulo" => "Última manutenção", "nome" => "ultima_manutencao", "tipo" => "data", "obrigatorio" => 0, "ordem" => 6],
            ["rotulo" => "Tipo de serviço", "nome" => "tipo_servico_ar", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 7],
            ["rotulo" => "Observações técnicas", "nome" => "observacoes_tecnicas_ar", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 8],
        ]
    ],

    "eletronica" => [
        "nome" => "Eletrônica",
        "descricao" => "Campos para manutenção de aparelhos eletrônicos em geral.",
        "campos" => [
            ["rotulo" => "Tipo do aparelho", "nome" => "tipo_aparelho", "tipo" => "texto", "obrigatorio" => 1, "ordem" => 1],
            ["rotulo" => "Marca", "nome" => "marca_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
            ["rotulo" => "Modelo", "nome" => "modelo_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
            ["rotulo" => "Número de série", "nome" => "numero_serie_aparelho", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 4],
            ["rotulo" => "Acessórios recebidos", "nome" => "acessorios_aparelho", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
            ["rotulo" => "Defeito informado", "nome" => "defeito_informado", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 6],
            ["rotulo" => "Estado de conservação", "nome" => "estado_conservacao", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 7],
        ]
    ],

    "servicos_gerais" => [
        "nome" => "Serviços Gerais",
        "descricao" => "Campos genéricos para prestadores de serviço em geral.",
        "campos" => [
            ["rotulo" => "Local do atendimento", "nome" => "local_atendimento", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 1],
            ["rotulo" => "Pessoa responsável no local", "nome" => "responsavel_local", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 2],
            ["rotulo" => "Telefone do responsável", "nome" => "telefone_responsavel", "tipo" => "texto", "obrigatorio" => 0, "ordem" => 3],
            ["rotulo" => "Data combinada", "nome" => "data_combinada", "tipo" => "data", "obrigatorio" => 0, "ordem" => 4],
            ["rotulo" => "Materiais necessários", "nome" => "materiais_necessarios", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 5],
            ["rotulo" => "Observações do local", "nome" => "observacoes_local", "tipo" => "textarea", "obrigatorio" => 0, "ordem" => 6],
        ]
    ],
];
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Modelos prontos de Campos da OS</h3>
            <p>Escolha um modelo para criar campos personalizados automaticamente.</p>
        </div>

        <a href="listar.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="row g-3">
        <?php foreach ($modelosCamposOS as $chave => $modelo): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong><?= htmlspecialchars($modelo["nome"]) ?></strong>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <p class="text-muted">
                            <?= htmlspecialchars($modelo["descricao"]) ?>
                        </p>

                        <div class="mb-3">
                            <span class="badge bg-primary">
                                <?= count($modelo["campos"]) ?> campo(s)
                            </span>
                        </div>

                        <div class="small text-muted mb-3">
                            <?php foreach (array_slice($modelo["campos"], 0, 4) as $campo): ?>
                                <div>• <?= htmlspecialchars($campo["rotulo"]) ?></div>
                            <?php endforeach; ?>

                            <?php if (count($modelo["campos"]) > 4): ?>
                                <div>• ...</div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto">
                            <a 
                                href="aplicar_modelo.php?modelo=<?= urlencode($chave) ?>&<?= csrfTokenUrl() ?>" 
                                class="btn btn-success w-100"
                                onclick="return confirm('Deseja aplicar este modelo? Campos já existentes com o mesmo nome técnico não serão duplicados.')"
                            >
                                Aplicar modelo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>