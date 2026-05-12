<?php

function formatarCodigoOS($ordemServicoId, $codigoOS = null, $dataAbertura = null)
{
    if (!empty($codigoOS)) {
        return $codigoOS;
    }

    $ano = date("Y");

    if (!empty($dataAbertura)) {
        $ano = date("Y", strtotime($dataAbertura));
    }

    return "OS-" . $ano . "-" . str_pad($ordemServicoId, 6, "0", STR_PAD_LEFT);
}