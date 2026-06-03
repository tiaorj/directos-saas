/*
    DirectOS - Alterações do MVP de Publicação
    Banco: SQL Server

    Execute este script somente após backup do banco.
*/

------------------------------------------------------------
-- 1. Segmento da empresa
------------------------------------------------------------

IF COL_LENGTH('OS_Empresas', 'Segmento') IS NULL
BEGIN
    ALTER TABLE OS_Empresas
    ADD Segmento VARCHAR(50) NULL;
END
GO

------------------------------------------------------------
-- 2. Campos personalizados da OS
------------------------------------------------------------

IF OBJECT_ID('OS_CamposPersonalizados', 'U') IS NULL
BEGIN
    CREATE TABLE OS_CamposPersonalizados (
        CampoId INT IDENTITY(1,1) PRIMARY KEY,
        EmpresaId INT NOT NULL,
        NomeCampo VARCHAR(100) NOT NULL,
        Rotulo VARCHAR(150) NOT NULL,
        TipoCampo VARCHAR(50) NOT NULL,
        Obrigatorio BIT NOT NULL DEFAULT 0,
        Ordem INT NOT NULL DEFAULT 0,
        Ativo BIT NOT NULL DEFAULT 1,
        DataCadastro DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

IF OBJECT_ID('OS_OrdensServicoCampos', 'U') IS NULL
BEGIN
    CREATE TABLE OS_OrdensServicoCampos (
        ValorCampoId INT IDENTITY(1,1) PRIMARY KEY,
        EmpresaId INT NOT NULL,
        OrdemServicoId INT NOT NULL,
        CampoId INT NOT NULL,
        Valor NVARCHAR(MAX) NULL,
        DataCadastro DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

------------------------------------------------------------
-- 3. Controle financeiro na OS
------------------------------------------------------------

IF COL_LENGTH('OS_OrdensServico', 'StatusFinanceiro') IS NULL
BEGIN
    ALTER TABLE OS_OrdensServico
    ADD StatusFinanceiro VARCHAR(30) NOT NULL CONSTRAINT DF_OS_OrdensServico_StatusFinanceiro DEFAULT 'Pendente';
END
GO

IF COL_LENGTH('OS_OrdensServico', 'FormaPagamento') IS NULL
BEGIN
    ALTER TABLE OS_OrdensServico
    ADD FormaPagamento VARCHAR(50) NULL;
END
GO

IF COL_LENGTH('OS_OrdensServico', 'ValorPago') IS NULL
BEGIN
    ALTER TABLE OS_OrdensServico
    ADD ValorPago DECIMAL(18,2) NULL;
END
GO

IF COL_LENGTH('OS_OrdensServico', 'DataPagamento') IS NULL
BEGIN
    ALTER TABLE OS_OrdensServico
    ADD DataPagamento DATETIME NULL;
END
GO

IF COL_LENGTH('OS_OrdensServico', 'ObservacaoFinanceira') IS NULL
BEGIN
    ALTER TABLE OS_OrdensServico
    ADD ObservacaoFinanceira NVARCHAR(MAX) NULL;
END
GO

------------------------------------------------------------
-- 4. Histórico de recebimentos por OS
------------------------------------------------------------

IF OBJECT_ID('OS_Recebimentos', 'U') IS NULL
BEGIN
    CREATE TABLE OS_Recebimentos (
        RecebimentoId INT IDENTITY(1,1) PRIMARY KEY,
        EmpresaId INT NOT NULL,
        OrdemServicoId INT NOT NULL,
        UsuarioId INT NULL,
        ValorRecebido DECIMAL(18,2) NOT NULL,
        FormaPagamento VARCHAR(50) NULL,
        DataRecebimento DATE NOT NULL,
        Observacao NVARCHAR(MAX) NULL,
        DataCadastro DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

------------------------------------------------------------
-- 5. Índices recomendados
------------------------------------------------------------

IF NOT EXISTS (
    SELECT 1 
    FROM sys.indexes 
    WHERE name = 'IX_OS_CamposPersonalizados_Empresa'
      AND object_id = OBJECT_ID('OS_CamposPersonalizados')
)
BEGIN
    CREATE INDEX IX_OS_CamposPersonalizados_Empresa
    ON OS_CamposPersonalizados (EmpresaId, Ativo, Ordem);
END
GO

IF NOT EXISTS (
    SELECT 1 
    FROM sys.indexes 
    WHERE name = 'IX_OS_OrdensServicoCampos_OS'
      AND object_id = OBJECT_ID('OS_OrdensServicoCampos')
)
BEGIN
    CREATE INDEX IX_OS_OrdensServicoCampos_OS
    ON OS_OrdensServicoCampos (EmpresaId, OrdemServicoId);
END
GO

IF NOT EXISTS (
    SELECT 1 
    FROM sys.indexes 
    WHERE name = 'IX_OS_Recebimentos_OS'
      AND object_id = OBJECT_ID('OS_Recebimentos')
)
BEGIN
    CREATE INDEX IX_OS_Recebimentos_OS
    ON OS_Recebimentos (EmpresaId, OrdemServicoId, DataRecebimento);
END
GO

IF NOT EXISTS (
    SELECT 1 
    FROM sys.indexes 
    WHERE name = 'IX_OS_OrdensServico_Financeiro'
      AND object_id = OBJECT_ID('OS_OrdensServico')
)
BEGIN
    CREATE INDEX IX_OS_OrdensServico_Financeiro
    ON OS_OrdensServico (EmpresaId, StatusFinanceiro, DataPagamento);
END
GO

------------------------------------------------------------
-- 6. Normalização de dados existentes
------------------------------------------------------------

UPDATE OS_OrdensServico
SET StatusFinanceiro = 'Pendente'
WHERE StatusFinanceiro IS NULL OR LTRIM(RTRIM(StatusFinanceiro)) = '';
GO

UPDATE OS_OrdensServico
SET ValorPago = 0
WHERE ValorPago IS NULL;
GO