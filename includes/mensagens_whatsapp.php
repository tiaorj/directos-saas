<?php

function registrarMensagemWhatsAppOS(
    PDO $conn,
    int $empresaId,
    int $ordemServicoId,
    ?int $usuarioId,
    string $tipoMensagem,
    string $origem,
    ?string $telefone,
    string $mensagem
) {
    $sql = "
        INSERT INTO OS_MensagensWhatsApp
        (
            EmpresaId,
            OrdemServicoId,
            UsuarioId,
            TipoMensagem,
            Origem,
            Telefone,
            Mensagem
        )
        VALUES
        (
            :EmpresaId,
            :OrdemServicoId,
            :UsuarioId,
            :TipoMensagem,
            :Origem,
            :Telefone,
            :Mensagem
        )
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);

    if ($usuarioId === null) {
        $stmt->bindValue(":UsuarioId", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
    }

    $stmt->bindValue(":TipoMensagem", $tipoMensagem);
    $stmt->bindValue(":Origem", $origem);
    $stmt->bindValue(":Telefone", $telefone);
    $stmt->bindValue(":Mensagem", $mensagem);

    $stmt->execute();
}