<?php

require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    directtiJson(405, [
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    directtiBadRequest('JSON inválido.');
}

$empresaId = (int)($input['EmpresaId'] ?? 0);
$sistemaOrigem = trim($input['sistema_origem'] ?? 'DirectCommerce');
$pedidoOrigemId = trim($input['pedido_origem_id'] ?? '');

$cliente = $input['cliente'] ?? [];
$ordemServico = $input['ordem_servico'] ?? [];

$nomeCliente = trim($cliente['nome'] ?? '');
$telefoneCliente = trim($cliente['telefone'] ?? '');
$emailCliente = trim($cliente['email'] ?? '');
$documentoCliente = trim($cliente['documento'] ?? '');
$enderecoCliente = trim($cliente['endereco'] ?? '');
$cidadeCliente = trim($cliente['cidade'] ?? '');
$estadoCliente = strtoupper(trim($cliente['estado'] ?? ''));

$servicoId = isset($ordemServico['servico_id']) && $ordemServico['servico_id'] !== ''
    ? (int)$ordemServico['servico_id']
    : null;

$titulo = trim($ordemServico['titulo'] ?? '');
$descricaoProblema = trim($ordemServico['descricao_problema'] ?? ($ordemServico['descricao'] ?? ''));
$status = trim($ordemServico['status'] ?? 'Aberta');
$prioridade = trim($ordemServico['prioridade'] ?? 'Normal');

$valorPrevisto = isset($ordemServico['valor_previsto'])
    ? $ordemServico['valor_previsto']
    : ($ordemServico['valor'] ?? null);

$valorFinal = isset($ordemServico['valor_final'])
    ? $ordemServico['valor_final']
    : null;

$dataPrevisao = trim($ordemServico['data_previsao'] ?? '');
$observacao = trim($ordemServico['observacao'] ?? 'Criada automaticamente via DirectTI Suite.');

if ($empresaId <= 0) {
    directtiBadRequest('EmpresaId é obrigatório e deve ser o EmpresaId do DirectOS.');
}

if ($pedidoOrigemId === '') {
    directtiBadRequest('pedido_origem_id é obrigatório.');
}

if ($nomeCliente === '') {
    directtiBadRequest('cliente.nome é obrigatório.');
}

if ($titulo === '') {
    directtiBadRequest('ordem_servico.titulo é obrigatório.');
}

if (!in_array($status, ['Aberta', 'Em andamento', 'Aguardando', 'Concluída', 'Cancelada'], true)) {
    $status = 'Aberta';
}

if (!in_array($prioridade, ['Baixa', 'Normal', 'Alta', 'Urgente'], true)) {
    $prioridade = 'Normal';
}

if ($estadoCliente !== '' && strlen($estadoCliente) > 2) {
    $estadoCliente = substr($estadoCliente, 0, 2);
}

$dataConclusao = null;

if ($status === 'Concluída') {
    $dataConclusao = date('Y-m-d H:i:s');
}

if ($dataPrevisao === '') {
    $dataPrevisao = null;
}

if ($valorPrevisto === '' || $valorPrevisto === null) {
    $valorPrevisto = null;
}

if ($valorFinal === '' || $valorFinal === null) {
    $valorFinal = null;
}

try {
    $conn->beginTransaction();

    /*
     * 1. Verifica se o pedido já gerou OS.
     */
    $sqlIntegracao = "
        SELECT TOP 1
            OrdemServicoId,
            CodigoOS
        FROM DirectTI_IntegracoesOS
        WHERE EmpresaId = :EmpresaId
          AND SistemaOrigem = :SistemaOrigem
          AND PedidoOrigemId = :PedidoOrigemId
    ";

    $stmtIntegracao = $conn->prepare($sqlIntegracao);
    $stmtIntegracao->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
    $stmtIntegracao->bindValue(':SistemaOrigem', $sistemaOrigem);
    $stmtIntegracao->bindValue(':PedidoOrigemId', $pedidoOrigemId);
    $stmtIntegracao->execute();

    $integracaoExistente = $stmtIntegracao->fetch(PDO::FETCH_ASSOC);

    if ($integracaoExistente) {
        $conn->commit();

        directtiJson(200, [
            'success' => true,
            'duplicated' => true,
            'message' => 'Esse pedido já possui OS criada.',
            'ordem_servico_id' => (int)$integracaoExistente['OrdemServicoId'],
            'codigo_os' => $integracaoExistente['CodigoOS'],
            'status' => 'Aberta'
        ]);
    }

    /*
     * 2. Confirma se a empresa existe.
     */
    $sqlEmpresa = "
        SELECT TOP 1 EmpresaId
        FROM OS_Empresas
        WHERE EmpresaId = :EmpresaId
          AND Ativo = 1
    ";

    $stmtEmpresa = $conn->prepare($sqlEmpresa);
    $stmtEmpresa->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
    $stmtEmpresa->execute();

    if (!$stmtEmpresa->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('EmpresaId inválido ou inativo no DirectOS.');
    }

    /*
     * 3. Se veio ServicoId, confirma se pertence à empresa.
     */
    if ($servicoId !== null) {
        $sqlServico = "
            SELECT TOP 1 ServicoId
            FROM OS_Servicos
            WHERE ServicoId = :ServicoId
              AND EmpresaId = :EmpresaId
              AND Ativo = 1
        ";

        $stmtServico = $conn->prepare($sqlServico);
        $stmtServico->bindValue(':ServicoId', $servicoId, PDO::PARAM_INT);
        $stmtServico->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
        $stmtServico->execute();

        if (!$stmtServico->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('ServicoId inválido para esta empresa.');
        }
    }

    /*
     * 4. Busca cliente existente dentro da empresa.
     */
    $clienteId = null;

    if ($documentoCliente !== '') {
        $sqlBuscaCliente = "
            SELECT TOP 1 ClienteId
            FROM OS_Clientes
            WHERE EmpresaId = :EmpresaId
              AND Documento = :Documento
              AND Ativo = 1
        ";

        $stmtBuscaCliente = $conn->prepare($sqlBuscaCliente);
        $stmtBuscaCliente->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
        $stmtBuscaCliente->bindValue(':Documento', $documentoCliente);
        $stmtBuscaCliente->execute();

        $rowCliente = $stmtBuscaCliente->fetch(PDO::FETCH_ASSOC);

        if ($rowCliente) {
            $clienteId = (int)$rowCliente['ClienteId'];
        }
    }

    if (!$clienteId && $emailCliente !== '') {
        $sqlBuscaCliente = "
            SELECT TOP 1 ClienteId
            FROM OS_Clientes
            WHERE EmpresaId = :EmpresaId
              AND Email = :Email
              AND Ativo = 1
        ";

        $stmtBuscaCliente = $conn->prepare($sqlBuscaCliente);
        $stmtBuscaCliente->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
        $stmtBuscaCliente->bindValue(':Email', $emailCliente);
        $stmtBuscaCliente->execute();

        $rowCliente = $stmtBuscaCliente->fetch(PDO::FETCH_ASSOC);

        if ($rowCliente) {
            $clienteId = (int)$rowCliente['ClienteId'];
        }
    }

    if (!$clienteId && $telefoneCliente !== '') {
        $sqlBuscaCliente = "
            SELECT TOP 1 ClienteId
            FROM OS_Clientes
            WHERE EmpresaId = :EmpresaId
              AND Telefone = :Telefone
              AND Ativo = 1
        ";

        $stmtBuscaCliente = $conn->prepare($sqlBuscaCliente);
        $stmtBuscaCliente->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
        $stmtBuscaCliente->bindValue(':Telefone', $telefoneCliente);
        $stmtBuscaCliente->execute();

        $rowCliente = $stmtBuscaCliente->fetch(PDO::FETCH_ASSOC);

        if ($rowCliente) {
            $clienteId = (int)$rowCliente['ClienteId'];
        }
    }

    /*
     * 5. Se não encontrou, cria cliente em OS_Clientes.
     */
    if (!$clienteId) {
        $sqlCriaCliente = "
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
            OUTPUT INSERTED.ClienteId
            VALUES
            (
                :Nome,
                :Telefone,
                :Email,
                :Documento,
                :Endereco,
                :Cidade,
                :Estado,
                :EmpresaId,
                1
            )
        ";

        $stmtCriaCliente = $conn->prepare($sqlCriaCliente);
        $stmtCriaCliente->bindValue(':Nome', $nomeCliente);
        $stmtCriaCliente->bindValue(':Telefone', $telefoneCliente);
        $stmtCriaCliente->bindValue(':Email', $emailCliente);
        $stmtCriaCliente->bindValue(':Documento', $documentoCliente);
        $stmtCriaCliente->bindValue(':Endereco', $enderecoCliente);
        $stmtCriaCliente->bindValue(':Cidade', $cidadeCliente);
        $stmtCriaCliente->bindValue(':Estado', $estadoCliente);
        $stmtCriaCliente->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
        $stmtCriaCliente->execute();

        $clienteId = (int)$stmtCriaCliente->fetchColumn();
    }

    if (!$clienteId) {
        throw new Exception('Não foi possível criar ou localizar o cliente.');
    }

    /*
     * 6. Cria OS usando o padrão do arquivo ordens/salvar.php.
     */
    $sqlCriaOS = "
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
            Observacao
        )
        OUTPUT INSERTED.OrdemServicoId
        VALUES
        (
            :EmpresaId,
            :ClienteId,
            :ServicoId,
            :Titulo,
            :DescricaoProblema,
            :Status,
            :Prioridade,
            :ValorPrevisto,
            :ValorFinal,
            :DataPrevisao,
            :DataConclusao,
            :Observacao
        )
    ";

    $stmtCriaOS = $conn->prepare($sqlCriaOS);
    $stmtCriaOS->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
    $stmtCriaOS->bindValue(':ClienteId', $clienteId, PDO::PARAM_INT);
    $stmtCriaOS->bindValue(':ServicoId', $servicoId, $servicoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmtCriaOS->bindValue(':Titulo', $titulo);
    $stmtCriaOS->bindValue(':DescricaoProblema', $descricaoProblema);
    $stmtCriaOS->bindValue(':Status', $status);
    $stmtCriaOS->bindValue(':Prioridade', $prioridade);
    $stmtCriaOS->bindValue(':ValorPrevisto', $valorPrevisto, $valorPrevisto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtCriaOS->bindValue(':ValorFinal', $valorFinal, $valorFinal === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtCriaOS->bindValue(':DataPrevisao', $dataPrevisao, $dataPrevisao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtCriaOS->bindValue(':DataConclusao', $dataConclusao, $dataConclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtCriaOS->bindValue(':Observacao', $observacao);
    $stmtCriaOS->execute();

    $ordemServicoId = (int)$stmtCriaOS->fetchColumn();

    if (!$ordemServicoId) {
        throw new Exception('Não foi possível criar a ordem de serviço.');
    }

    /*
     * 7. Atualiza CodigoOS no mesmo padrão do sistema.
     */
    $anoAtual = date('Y');
    $codigoOS = 'OS-' . $anoAtual . '-' . str_pad((string)$ordemServicoId, 6, '0', STR_PAD_LEFT);

    $sqlAtualizaCodigo = "
        UPDATE OS_OrdensServico
        SET CodigoOS = :CodigoOS
        WHERE OrdemServicoId = :OrdemServicoId
    ";

    $stmtAtualizaCodigo = $conn->prepare($sqlAtualizaCodigo);
    $stmtAtualizaCodigo->bindValue(':CodigoOS', $codigoOS);
    $stmtAtualizaCodigo->bindValue(':OrdemServicoId', $ordemServicoId, PDO::PARAM_INT);
    $stmtAtualizaCodigo->execute();

    /*
     * 8. Registra histórico inicial.
     */
    $sqlHistorico = "
        INSERT INTO OS_OrdensServicoHistorico
        (
            OrdemServicoId,
            UsuarioId,
            StatusAnterior,
            StatusNovo,
            Descricao,
            VisivelCliente
        )
        VALUES
        (
            :OrdemServicoId,
            NULL,
            NULL,
            :StatusNovo,
            :Descricao,
            1
        )
    ";

    $stmtHistorico = $conn->prepare($sqlHistorico);
    $stmtHistorico->bindValue(':OrdemServicoId', $ordemServicoId, PDO::PARAM_INT);
    $stmtHistorico->bindValue(':StatusNovo', $status);
    $stmtHistorico->bindValue(':Descricao', 'Ordem de serviço criada via integração DirectTI Suite. Código: ' . $codigoOS . '.');
    $stmtHistorico->execute();

    /*
     * 9. Registra vínculo da integração.
     */
    $sqlCriaIntegracao = "
        INSERT INTO DirectTI_IntegracoesOS
        (
            EmpresaId,
            SistemaOrigem,
            PedidoOrigemId,
            ClienteId,
            OrdemServicoId,
            CodigoOS,
            PayloadJson,
            StatusIntegracao
        )
        VALUES
        (
            :EmpresaId,
            :SistemaOrigem,
            :PedidoOrigemId,
            :ClienteId,
            :OrdemServicoId,
            :CodigoOS,
            :PayloadJson,
            'success'
        )
    ";

    $stmtCriaIntegracao = $conn->prepare($sqlCriaIntegracao);
    $stmtCriaIntegracao->bindValue(':EmpresaId', $empresaId, PDO::PARAM_INT);
    $stmtCriaIntegracao->bindValue(':SistemaOrigem', $sistemaOrigem);
    $stmtCriaIntegracao->bindValue(':PedidoOrigemId', $pedidoOrigemId);
    $stmtCriaIntegracao->bindValue(':ClienteId', $clienteId, PDO::PARAM_INT);
    $stmtCriaIntegracao->bindValue(':OrdemServicoId', $ordemServicoId, PDO::PARAM_INT);
    $stmtCriaIntegracao->bindValue(':CodigoOS', $codigoOS);
    $stmtCriaIntegracao->bindValue(':PayloadJson', $rawBody);
    $stmtCriaIntegracao->execute();

    $conn->commit();

    directtiJson(201, [
        'success' => true,
        'duplicated' => false,
        'message' => 'Ordem de serviço criada com sucesso.',
        'EmpresaId' => $empresaId,
        'cliente_id' => $clienteId,
        'ordem_servico_id' => $ordemServicoId,
        'codigo_os' => $codigoOS,
        'status' => $status
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    directtiJson(500, [
        'success' => false,
        'message' => 'Erro ao criar ordem de serviço.',
        'details' => $e->getMessage()
    ]);
}