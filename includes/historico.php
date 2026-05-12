<?php

function registrarHistoricoOS($conn, $ordemServicoId, $usuarioId, $statusAnterior, $statusNovo, $descricao)
{
    $sql = "
        INSERT INTO OS_Historico
        (
            OrdemServicoId,
            UsuarioId,
            StatusAnterior,
            StatusNovo,
            Descricao
        )
        VALUES
        (
            :OrdemServicoId,
            :UsuarioId,
            :StatusAnterior,
            :StatusNovo,
            :Descricao
        )
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmt->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(":StatusAnterior", $statusAnterior);
    $stmt->bindValue(":StatusNovo", $statusNovo);
    $stmt->bindValue(":Descricao", $descricao);

    $stmt->execute();
}