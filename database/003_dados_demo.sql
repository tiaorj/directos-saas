/*
    DirectOS - Dados de Demonstração

    ATENÇÃO:
    - Executar apenas em ambiente demo.
    - Não executar em ambiente real de cliente sem revisar.
    - Este script apaga e recria somente os dados da empresa demo.
*/

SET NOCOUNT ON;

DECLARE @EmailEmpresaDemo VARCHAR(150) = 'demo@directos.com.br';
DECLARE @SenhaHashDemo VARCHAR(255) = '$2y$10$9jgMnLvuTeA9BKGsPpJiUu9NABkZZL.IcedlyobSotc.hUJYnh.ze';

------------------------------------------------------------
-- 0. Limpeza preventiva de usuários demo órfãos
------------------------------------------------------------

DELETE h
FROM OS_Historico h
INNER JOIN OS_Usuarios u
    ON u.UsuarioId = h.UsuarioId
WHERE u.Email IN (
    'demo@directos.com.br',
    'atendente.demo@directos.com.br',
    'tecnico.demo@directos.com.br'
);

DELETE FROM OS_Usuarios
WHERE Email IN (
    'demo@directos.com.br',
    'atendente.demo@directos.com.br',
    'tecnico.demo@directos.com.br'
);

DECLARE @EmpresaDemoId INT;

SELECT @EmpresaDemoId = EmpresaId
FROM OS_Empresas
WHERE Email = @EmailEmpresaDemo;

------------------------------------------------------------
-- 1. Limpar dados antigos somente da empresa demo
------------------------------------------------------------

IF @EmpresaDemoId IS NOT NULL
BEGIN
    IF OBJECT_ID('OS_Recebimentos', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_Recebimentos
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_MensagensWhatsApp', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_MensagensWhatsApp
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_OrdensServicoAnexos', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_OrdensServicoAnexos
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_Historico', 'U') IS NOT NULL
    BEGIN
        DELETE h
        FROM OS_Historico h
        INNER JOIN OS_OrdensServico os 
            ON os.OrdemServicoId = h.OrdemServicoId
        WHERE os.EmpresaId = @EmpresaDemoId;

        DELETE h
        FROM OS_Historico h
        INNER JOIN OS_Usuarios u 
            ON u.UsuarioId = h.UsuarioId
        WHERE u.EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_OrdensServicoCampos', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_OrdensServicoCampos
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_OrdensServico', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_OrdensServico
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_CamposPersonalizados', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_CamposPersonalizados
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_Servicos', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_Servicos
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_Clientes', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_Clientes
        WHERE EmpresaId = @EmpresaDemoId;
    END

    IF OBJECT_ID('OS_Usuarios', 'U') IS NOT NULL
    BEGIN
        DELETE FROM OS_Usuarios
        WHERE EmpresaId = @EmpresaDemoId;
    END

    DELETE FROM OS_Empresas
    WHERE EmpresaId = @EmpresaDemoId;
END
GO

------------------------------------------------------------
-- 2. Criar empresa demo
------------------------------------------------------------

DECLARE @SenhaHashDemo VARCHAR(255) = '$2y$10$9jgMnLvuTeA9BKGsPpJiUu9NABkZZL.IcedlyobSotc.hUJYnh.ze';
DECLARE @EmpresaDemoId INT;

INSERT INTO OS_Empresas
(
    NomeFantasia,
    Slug,
    Email,
    WhatsApp,
    Segmento,
    OcultarOnboarding
)
VALUES
(
    'DirectOS Demo',
    'directos-demo',
    'demo@directos.com.br',
    '21999990000',
    'informatica',
    1
);

SET @EmpresaDemoId = SCOPE_IDENTITY();

------------------------------------------------------------
-- 3. Criar usuários demo
------------------------------------------------------------

INSERT INTO OS_Usuarios
(
    Nome,
    Email,
    SenhaHash,
    Perfil,
    Ativo,
    DataCadastro,
    EmpresaId
)
VALUES
(
    'Admin Demo',
    'demo@directos.com.br',
    @SenhaHashDemo,
    'Admin',
    1,
    GETDATE(),
    @EmpresaDemoId
),
(
    'Atendente Demo',
    'atendente.demo@directos.com.br',
    @SenhaHashDemo,
    'Atendente',
    1,
    GETDATE(),
    @EmpresaDemoId
),
(
    'Técnico Demo',
    'tecnico.demo@directos.com.br',
    @SenhaHashDemo,
    'Tecnico',
    1,
    GETDATE(),
    @EmpresaDemoId
);

DECLARE @UsuarioAdminId INT;

SELECT @UsuarioAdminId = UsuarioId
FROM OS_Usuarios
WHERE EmpresaId = @EmpresaDemoId
  AND Email = 'demo@directos.com.br';

IF @UsuarioAdminId IS NULL
BEGIN
    RAISERROR('Usuário admin demo não foi criado corretamente.', 16, 1);
    RETURN;
END

------------------------------------------------------------
-- 4. Criar clientes fictícios
------------------------------------------------------------

INSERT INTO OS_Clientes
(
    Nome,
    Telefone,
    Email,
    Documento,
    Endereco,
    Cidade,
    Estado,
    EmpresaId,
    Ativo
)
VALUES
(
    'João Martins',
    '21988880001',
    'joao.martins@emaildemo.com',
    '123.456.789-00',
    'Rua das Acácias, 100',
    'Rio de Janeiro',
    'RJ',
    @EmpresaDemoId,
    1
),
(
    'Mariana Souza',
    '21988880002',
    'mariana.souza@emaildemo.com',
    '987.654.321-00',
    'Av. Central, 250',
    'Niterói',
    'RJ',
    @EmpresaDemoId,
    1
),
(
    'Empresa Alfa Ltda',
    '21988880003',
    'contato@empresaalfademo.com',
    '12.345.678/0001-90',
    'Rua Comercial, 500',
    'São Gonçalo',
    'RJ',
    @EmpresaDemoId,
    1
),
(
    'Carlos Pereira',
    '21988880004',
    'carlos.pereira@emaildemo.com',
    '456.789.123-00',
    'Rua Projetada, 45',
    'Araruama',
    'RJ',
    @EmpresaDemoId,
    1
);

------------------------------------------------------------
-- 5. Criar serviços fictícios
------------------------------------------------------------

INSERT INTO OS_Servicos
(
    Nome,
    Descricao,
    ChecklistPadrao,
    ValorBase,
    Ativo,
    EmpresaId
)
VALUES
(
    'Formatação de notebook',
    'Serviço de formatação, instalação de sistema operacional, drivers essenciais e configuração inicial.',
    '- Verificar arquivos importantes antes da formatação
- Confirmar autorização para apagar dados
- Instalar sistema operacional
- Instalar drivers
- Instalar navegador e ferramentas básicas
- Testar áudio, vídeo, internet e teclado',
    180.00,
    1,
    @EmpresaDemoId
),
(
    'Manutenção preventiva',
    'Limpeza interna, verificação de componentes, testes de desempenho e orientações preventivas.',
    '- Verificar estado físico do equipamento
- Limpar entradas de ar
- Verificar temperatura
- Testar disco
- Testar memória
- Orientar cliente sobre cuidados',
    120.00,
    1,
    @EmpresaDemoId
),
(
    'Troca de tela',
    'Substituição de tela em notebook ou equipamento compatível, com teste após instalação.',
    '- Conferir modelo da tela
- Validar compatibilidade
- Desmontar com cuidado
- Substituir peça
- Testar imagem
- Verificar fechamento da carcaça',
    350.00,
    1,
    @EmpresaDemoId
),
(
    'Instalação de sistema',
    'Instalação e configuração de software, sistema operacional ou ferramentas de trabalho.',
    '- Confirmar software solicitado
- Verificar licença
- Instalar sistema
- Configurar preferências
- Testar funcionamento com o cliente',
    150.00,
    1,
    @EmpresaDemoId
),
(
    'Limpeza interna',
    'Limpeza física de notebook ou desktop, com foco em poeira, ventilação e conservação.',
    '- Verificar condições externas
- Abrir equipamento
- Remover poeira
- Verificar cooler
- Fechar equipamento
- Testar funcionamento',
    100.00,
    1,
    @EmpresaDemoId
);

------------------------------------------------------------
-- 6. Criar campos personalizados do segmento Informática
------------------------------------------------------------

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
    @EmpresaDemoId,
    'tipo_equipamento',
    'Tipo de equipamento',
    'texto',
    1,
    1,
    1
),
(
    @EmpresaDemoId,
    'marca_equipamento',
    'Marca',
    'texto',
    0,
    2,
    1
),
(
    @EmpresaDemoId,
    'modelo_equipamento',
    'Modelo',
    'texto',
    0,
    3,
    1
),
(
    @EmpresaDemoId,
    'numero_serie',
    'Número de série',
    'texto',
    0,
    4,
    1
),
(
    @EmpresaDemoId,
    'acessorios_recebidos',
    'Acessórios recebidos',
    'textarea',
    0,
    5,
    1
),
(
    @EmpresaDemoId,
    'backup_autorizado',
    'Backup autorizado?',
    'texto',
    0,
    6,
    1
);

------------------------------------------------------------
-- 7. Criar ordens de serviço fictícias
------------------------------------------------------------

DECLARE @ClienteJoao INT;
DECLARE @ClienteMariana INT;
DECLARE @ClienteEmpresaAlfa INT;
DECLARE @ClienteCarlos INT;

DECLARE @ServicoFormatacao INT;
DECLARE @ServicoPreventiva INT;
DECLARE @ServicoTela INT;
DECLARE @ServicoSistema INT;
DECLARE @ServicoLimpeza INT;

SELECT @ClienteJoao = ClienteId FROM OS_Clientes WHERE EmpresaId = @EmpresaDemoId AND Nome = 'João Martins';
SELECT @ClienteMariana = ClienteId FROM OS_Clientes WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Mariana Souza';
SELECT @ClienteEmpresaAlfa = ClienteId FROM OS_Clientes WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Empresa Alfa Ltda';
SELECT @ClienteCarlos = ClienteId FROM OS_Clientes WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Carlos Pereira';

SELECT @ServicoFormatacao = ServicoId FROM OS_Servicos WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Formatação de notebook';
SELECT @ServicoPreventiva = ServicoId FROM OS_Servicos WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Manutenção preventiva';
SELECT @ServicoTela = ServicoId FROM OS_Servicos WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Troca de tela';
SELECT @ServicoSistema = ServicoId FROM OS_Servicos WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Instalação de sistema';
SELECT @ServicoLimpeza = ServicoId FROM OS_Servicos WHERE EmpresaId = @EmpresaDemoId AND Nome = 'Limpeza interna';

INSERT INTO OS_OrdensServico
(
    EmpresaId,
    ClienteId,
    ServicoId,
    Titulo,
    DescricaoProblema,
    Status,
    Prioridade,
    ValorPrevisto,
    ValorFinal,
    DataPrevisao,
    DataConclusao,
    Observacao,
    StatusFinanceiro,
    ValorPago,
    FormaPagamento,
    DataPagamento
)
VALUES
(
    @EmpresaDemoId,
    @ClienteJoao,
    @ServicoFormatacao,
    'Notebook lento e travando',
    'Cliente relata lentidão ao iniciar o Windows e travamentos durante uso do navegador.',
    'Aberta',
    'Normal',
    180.00,
    NULL,
    DATEADD(DAY, 3, GETDATE()),
    NULL,
    'Cliente solicitou avaliação antes de formatar.',
    'Pendente',
    0,
    NULL,
    NULL
),
(
    @EmpresaDemoId,
    @ClienteMariana,
    @ServicoPreventiva,
    'Manutenção preventiva em notebook Dell',
    'Equipamento aquece após alguns minutos de uso.',
    'Em andamento',
    'Alta',
    120.00,
    NULL,
    DATEADD(DAY, 1, GETDATE()),
    NULL,
    'Verificar cooler e pasta térmica.',
    'Pendente',
    0,
    NULL,
    NULL
),
(
    @EmpresaDemoId,
    @ClienteEmpresaAlfa,
    @ServicoSistema,
    'Instalação de sistema em computador administrativo',
    'Empresa solicitou instalação de sistema e configuração de usuário.',
    'Aguardando cliente',
    'Normal',
    150.00,
    NULL,
    DATEADD(DAY, 2, GETDATE()),
    NULL,
    'Aguardando confirmação de licença.',
    'Pendente',
    0,
    NULL,
    NULL
),
(
    @EmpresaDemoId,
    @ClienteCarlos,
    @ServicoTela,
    'Troca de tela de notebook',
    'Tela quebrada após queda. Equipamento liga normalmente em monitor externo.',
    'Concluída',
    'Urgente',
    350.00,
    350.00,
    DATEADD(DAY, -2, GETDATE()),
    DATEADD(DAY, -1, GETDATE()),
    'Peça substituída e testada.',
    'Pago',
    350.00,
    'Pix',
    DATEADD(DAY, -1, GETDATE())
),
(
    @EmpresaDemoId,
    @ClienteJoao,
    @ServicoLimpeza,
    'Limpeza interna de desktop',
    'Desktop com acúmulo de poeira e ruído no cooler.',
    'Concluída',
    'Baixa',
    100.00,
    100.00,
    DATEADD(DAY, -5, GETDATE()),
    DATEADD(DAY, -4, GETDATE()),
    'Limpeza realizada com sucesso.',
    'Parcial',
    50.00,
    'Dinheiro',
    DATEADD(DAY, -4, GETDATE())
),
(
    @EmpresaDemoId,
    @ClienteMariana,
    @ServicoFormatacao,
    'Formatação com backup',
    'Cliente solicitou backup de documentos e formatação completa.',
    'Cancelada',
    'Normal',
    220.00,
    0.00,
    DATEADD(DAY, -3, GETDATE()),
    NULL,
    'Cliente cancelou antes da execução.',
    'Cancelado',
    0,
    NULL,
    NULL
);

------------------------------------------------------------
-- 8. Atualizar códigos das OS demo
------------------------------------------------------------

UPDATE OS_OrdensServico
SET CodigoOS = 'OS-' + CONVERT(VARCHAR(4), YEAR(DataAbertura)) + '-' + RIGHT('000000' + CAST(OrdemServicoId AS VARCHAR(10)), 6)
WHERE EmpresaId = @EmpresaDemoId
  AND (CodigoOS IS NULL OR CodigoOS = '');

------------------------------------------------------------
-- 9. Criar valores dos campos personalizados
------------------------------------------------------------

DECLARE @CampoTipo INT;
DECLARE @CampoMarca INT;
DECLARE @CampoModelo INT;
DECLARE @CampoSerie INT;
DECLARE @CampoAcessorios INT;
DECLARE @CampoBackup INT;

SELECT @CampoTipo = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'tipo_equipamento';
SELECT @CampoMarca = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'marca_equipamento';
SELECT @CampoModelo = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'modelo_equipamento';
SELECT @CampoSerie = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'numero_serie';
SELECT @CampoAcessorios = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'acessorios_recebidos';
SELECT @CampoBackup = CampoId FROM OS_CamposPersonalizados WHERE EmpresaId = @EmpresaDemoId AND NomeCampo = 'backup_autorizado';

INSERT INTO OS_OrdensServicoCampos
(
    EmpresaId,
    OrdemServicoId,
    CampoId,
    Valor
)
SELECT @EmpresaDemoId, OrdemServicoId, @CampoTipo, 'Notebook'
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Titulo IN ('Notebook lento e travando', 'Manutenção preventiva em notebook Dell', 'Troca de tela de notebook', 'Formatação com backup');

INSERT INTO OS_OrdensServicoCampos
(
    EmpresaId,
    OrdemServicoId,
    CampoId,
    Valor
)
SELECT @EmpresaDemoId, OrdemServicoId, @CampoMarca, 'Dell'
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Titulo IN ('Manutenção preventiva em notebook Dell', 'Troca de tela de notebook');

INSERT INTO OS_OrdensServicoCampos
(
    EmpresaId,
    OrdemServicoId,
    CampoId,
    Valor
)
SELECT @EmpresaDemoId, OrdemServicoId, @CampoBackup, 'Sim'
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Titulo IN ('Formatação com backup', 'Notebook lento e travando');

------------------------------------------------------------
-- 10. Criar recebimentos fictícios
------------------------------------------------------------

DECLARE @OSTrocaTela INT;
DECLARE @OSLimpeza INT;

SELECT @OSTrocaTela = OrdemServicoId
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Titulo = 'Troca de tela de notebook';

SELECT @OSLimpeza = OrdemServicoId
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Titulo = 'Limpeza interna de desktop';

INSERT INTO OS_Recebimentos
(
    EmpresaId,
    OrdemServicoId,
    UsuarioId,
    ValorRecebido,
    FormaPagamento,
    DataRecebimento,
    Observacao
)
VALUES
(
    @EmpresaDemoId,
    @OSTrocaTela,
    @UsuarioAdminId,
    350.00,
    'Pix',
    CAST(DATEADD(DAY, -1, GETDATE()) AS DATE),
    'Pagamento integral recebido via Pix.'
),
(
    @EmpresaDemoId,
    @OSLimpeza,
    @UsuarioAdminId,
    50.00,
    'Dinheiro',
    CAST(DATEADD(DAY, -4, GETDATE()) AS DATE),
    'Pagamento parcial no ato do atendimento.'
);

------------------------------------------------------------
-- 11. Criar histórico fictício
------------------------------------------------------------

INSERT INTO OS_Historico
(
    OrdemServicoId,
    UsuarioId,
    StatusAnterior,
    StatusNovo,
    Descricao,
    DataRegistro
)
SELECT
    OrdemServicoId,
    @UsuarioAdminId,
    NULL,
    Status,
    'OS criada no ambiente de demonstração.',
    DataAbertura
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId;

INSERT INTO OS_Historico
(
    OrdemServicoId,
    UsuarioId,
    StatusAnterior,
    StatusNovo,
    Descricao,
    DataRegistro
)
SELECT
    OrdemServicoId,
    @UsuarioAdminId,
    'Em andamento',
    'Concluída',
    'Atendimento concluído no ambiente de demonstração.',
    DataConclusao
FROM OS_OrdensServico
WHERE EmpresaId = @EmpresaDemoId
  AND Status = 'Concluída';

------------------------------------------------------------
-- 12. Resultado
------------------------------------------------------------

SELECT 
    @EmpresaDemoId AS EmpresaDemoId,
    'demo@directos.com.br' AS LoginDemo,
    'Demo@123' AS SenhaDemo,
    'Empresa demo criada com sucesso.' AS Mensagem;
GO