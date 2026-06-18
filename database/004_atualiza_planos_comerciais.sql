/*
    DirectOS - Atualização dos Planos Comerciais

    Planos oficiais do MVP:
    - Starter: R$ 39/mês
    - Profissional: R$ 79/mês
    - Empresa: R$ 149/mês

    Execute após backup do banco.
*/

SET NOCOUNT ON;

------------------------------------------------------------
-- 1. Garantir plano Starter
------------------------------------------------------------

IF EXISTS (SELECT 1 FROM OS_Planos WHERE Slug = 'starter')
BEGIN
    UPDATE OS_Planos
    SET
        Nome = 'Starter',
        Descricao = 'Para prestadores individuais ou pequenos negócios começando a organizar ordens de serviço.',
        LimiteOSMes = 30,
        LimiteUsuarios = 1,
        PermiteAnexos = 1,
        PermiteAreaCliente = 1,
        PermiteWhatsapp = 1,
        ValorMensal = 39.00,
        Ativo = 1
    WHERE Slug = 'starter';
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
        'Starter',
        'starter',
        'Para prestadores individuais ou pequenos negócios começando a organizar ordens de serviço.',
        30,
        1,
        1,
        1,
        1,
        39.00,
        1
    );
END
GO

------------------------------------------------------------
-- 2. Garantir plano Profissional
------------------------------------------------------------

IF EXISTS (SELECT 1 FROM OS_Planos WHERE Slug = 'profissional')
BEGIN
    UPDATE OS_Planos
    SET
        Nome = 'Profissional',
        Descricao = 'Para pequenas empresas que já possuem rotina de atendimento e precisam de mais organização.',
        LimiteOSMes = 150,
        LimiteUsuarios = 3,
        PermiteAnexos = 1,
        PermiteAreaCliente = 1,
        PermiteWhatsapp = 1,
        ValorMensal = 79.00,
        Ativo = 1
    WHERE Slug = 'profissional';
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
        'Profissional',
        'profissional',
        'Para pequenas empresas que já possuem rotina de atendimento e precisam de mais organização.',
        150,
        3,
        1,
        1,
        1,
        79.00,
        1
    );
END
GO

------------------------------------------------------------
-- 3. Garantir plano Empresa
------------------------------------------------------------

IF EXISTS (SELECT 1 FROM OS_Planos WHERE Slug = 'empresa')
BEGIN
    UPDATE OS_Planos
    SET
        Nome = 'Empresa',
        Descricao = 'Para empresas com maior volume de OS ou mais usuários no atendimento.',
        LimiteOSMes = NULL,
        LimiteUsuarios = 10,
        PermiteAnexos = 1,
        PermiteAreaCliente = 1,
        PermiteWhatsapp = 1,
        ValorMensal = 149.00,
        Ativo = 1
    WHERE Slug = 'empresa';
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
        'Empresa',
        'empresa',
        'Para empresas com maior volume de OS ou mais usuários no atendimento.',
        NULL,
        10,
        1,
        1,
        1,
        149.00,
        1
    );
END
GO

------------------------------------------------------------
-- 4. Desativar planos antigos não oficiais
------------------------------------------------------------

UPDATE OS_Planos
SET Ativo = 0
WHERE Slug NOT IN ('starter', 'profissional', 'empresa', 'teste-assistido');
GO

------------------------------------------------------------
-- 5. Conferência
------------------------------------------------------------

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
ORDER BY ValorMensal;
GO
