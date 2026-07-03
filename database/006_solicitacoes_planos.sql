/*
    DirectOS - Solicitações de Alteração de Plano

    Cria a tabela para registrar pedidos internos de upgrade/downgrade
    feitos pelas empresas a partir da tela Meu Plano.

    Execute após backup do banco.
*/

SET NOCOUNT ON;
GO

IF OBJECT_ID('OS_SolicitacoesPlano', 'U') IS NULL
BEGIN
    CREATE TABLE OS_SolicitacoesPlano (
        SolicitacaoId INT IDENTITY(1,1) PRIMARY KEY,
        EmpresaId INT NOT NULL,
        PlanoAtualId INT NULL,
        PlanoSolicitadoId INT NOT NULL,
        UsuarioId INT NULL,
        Status VARCHAR(30) NOT NULL CONSTRAINT DF_OS_SolicitacoesPlano_Status DEFAULT 'Pendente',
        Mensagem NVARCHAR(1000) NULL,
        ObservacaoAdmin NVARCHAR(1000) NULL,
        DataSolicitacao DATETIME NOT NULL CONSTRAINT DF_OS_SolicitacoesPlano_DataSolicitacao DEFAULT GETDATE(),
        DataResposta DATETIME NULL
    );
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_OS_SolicitacoesPlano_Empresa_Status'
      AND object_id = OBJECT_ID('OS_SolicitacoesPlano')
)
BEGIN
    CREATE INDEX IX_OS_SolicitacoesPlano_Empresa_Status
    ON OS_SolicitacoesPlano (EmpresaId, Status, DataSolicitacao DESC);
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_OS_SolicitacoesPlano_Status_Data'
      AND object_id = OBJECT_ID('OS_SolicitacoesPlano')
)
BEGIN
    CREATE INDEX IX_OS_SolicitacoesPlano_Status_Data
    ON OS_SolicitacoesPlano (Status, DataSolicitacao DESC);
END
GO

SELECT
    TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME = 'OS_SolicitacoesPlano';
GO
