/*
    DirectOS - Recurso IA por Plano

    Adiciona controle comercial para habilitar/desabilitar integrações de texto com IA
    por plano em OS_Planos.

    Execute após backup do banco.
*/

SET NOCOUNT ON;
GO

IF COL_LENGTH('OS_Planos', 'PermiteIA') IS NULL
BEGIN
    ALTER TABLE OS_Planos
    ADD PermiteIA BIT NOT NULL
        CONSTRAINT DF_OS_Planos_PermiteIA DEFAULT 1;
END
GO

UPDATE OS_Planos
SET PermiteIA = 0
WHERE Slug IN ('teste-assistido', 'starter');
GO

UPDATE OS_Planos
SET PermiteIA = 1
WHERE Slug IN ('profissional', 'empresa');
GO

SELECT
    PlanoId,
    Nome,
    Slug,
    PermiteAnexos,
    PermiteAreaCliente,
    PermiteWhatsapp,
    PermiteIA
FROM OS_Planos
ORDER BY ValorMensal, Nome;
GO
