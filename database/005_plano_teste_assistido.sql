/*
    DirectOS - Plano Teste Assistido

    Plano gratuito e limitado para empresas em avaliacao inicial
    com implantacao assistida.

    Execute apos backup do banco.
*/

SET NOCOUNT ON;

IF EXISTS (SELECT 1 FROM OS_Planos WHERE Slug = 'teste-assistido')
BEGIN
    UPDATE OS_Planos
    SET
        Nome = 'Teste Assistido',
        Descricao = 'Plano gratuito e limitado para avaliação inicial com implantação assistida.',
        LimiteOSMes = 10,
        LimiteUsuarios = 1,
        PermiteAnexos = 0,
        PermiteAreaCliente = 1,
        PermiteWhatsapp = 1,
        ValorMensal = 0.00,
        Ativo = 1
    WHERE Slug = 'teste-assistido';
END
ELSE
BEGIN
    INSERT INTO OS_Planos
    (
        Nome,
        Slug,
        Descricao,
        LimiteOSMes,
        LimiteUsuarios,
        PermiteAnexos,
        PermiteAreaCliente,
        PermiteWhatsapp,
        ValorMensal,
        Ativo
    )
    VALUES
    (
        'Teste Assistido',
        'teste-assistido',
        'Plano gratuito e limitado para avaliação inicial com implantação assistida.',
        10,
        1,
        0,
        1,
        1,
        0.00,
        1
    );
END
GO

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
    Ativo
FROM OS_Planos
WHERE Slug = 'teste-assistido';
GO
