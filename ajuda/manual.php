<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Manual de uso</h3>
            <p>
                Guia rápido para começar a usar o DirectOS no dia a dia da sua empresa.
            </p>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <div class="alert alert-primary mb-4">
                <strong>Primeiro uso:</strong>
                siga o fluxo básico: Cliente → Serviço → Ordem de Serviço → Atendimento → Recebimento → Recibo → Relatório.
            </div>

            <h4>1. O que é o DirectOS?</h4>
            <p>
                O DirectOS é um sistema para ajudar sua empresa a organizar clientes,
                serviços, ordens de serviço, atendimentos, recebimentos e recibos em um só lugar.
            </p>

            <hr>

            <h4>2. Como acessar o sistema</h4>
            <p>
                Na tela de login, informe seu e-mail e senha. Depois clique em
                <strong>Entrar no sistema</strong>.
            </p>
            <p>
                Caso não consiga acessar, confira se o e-mail e a senha foram digitados corretamente.
                Se o problema continuar, entre em contato com o responsável pela implantação.
            </p>

            <hr>

            <h4>3. Tela inicial</h4>
            <p>
                Depois de entrar, você verá o painel principal. Ele mostra um resumo da operação,
                como ordens abertas, em andamento, concluídas, clientes cadastrados e informações financeiras,
                quando disponíveis.
            </p>

            <hr>

            <h4>4. Cadastro de clientes</h4>
            <p>Antes de abrir uma ordem de serviço, cadastre o cliente.</p>

            <ol>
                <li>Acesse o menu <strong>Clientes</strong>.</li>
                <li>Clique em <strong>Novo cliente</strong>.</li>
                <li>Preencha nome, telefone, WhatsApp, e-mail e endereço, se necessário.</li>
                <li>Clique em <strong>Salvar</strong>.</li>
            </ol>

            <hr>

            <h4>5. Cadastro de serviços</h4>
            <p>
                O cadastro de serviços ajuda a padronizar os atendimentos realizados pela empresa.
            </p>

            <ol>
                <li>Acesse o menu <strong>Serviços</strong>.</li>
                <li>Clique em <strong>Novo serviço</strong>.</li>
                <li>Informe o nome, descrição e valor previsto, se desejar.</li>
                <li>Clique em <strong>Salvar</strong>.</li>
            </ol>

            <p>
                Exemplos: manutenção preventiva, instalação, suporte técnico, visita técnica,
                troca de peça e configuração de equipamento.
            </p>

            <hr>

            <h4>6. Como criar uma ordem de serviço</h4>
            <p>A ordem de serviço é o registro principal do atendimento.</p>

            <ol>
                <li>Acesse o menu <strong>Ordens de Serviço</strong>.</li>
                <li>Clique em <strong>Nova OS</strong>.</li>
                <li>Selecione o cliente.</li>
                <li>Escolha o serviço.</li>
                <li>Preencha o título ou descrição do problema.</li>
                <li>Informe a prioridade, se necessário.</li>
                <li>Informe valores previstos, se houver.</li>
                <li>Clique em <strong>Salvar</strong>.</li>
            </ol>

            <hr>

            <h4>7. Status da ordem de serviço</h4>
            <p>
                O status mostra em que etapa está o atendimento.
            </p>

            <ul>
                <li><strong>Aberta:</strong> atendimento criado, mas ainda não iniciado.</li>
                <li><strong>Em andamento:</strong> atendimento já está sendo realizado.</li>
                <li><strong>Concluída:</strong> serviço finalizado.</li>
                <li><strong>Cancelada:</strong> atendimento cancelado.</li>
            </ul>

            <p>
                Sempre que houver avanço no atendimento, atualize o status da OS.
            </p>

            <hr>

            <h4>8. Atendimento técnico</h4>
            <p>
                Dentro da ordem de serviço, registre o que foi feito no atendimento.
            </p>

            <ul>
                <li>O que foi verificado.</li>
                <li>O que foi feito.</li>
                <li>Peças utilizadas.</li>
                <li>Orientações passadas ao cliente.</li>
                <li>Pendências.</li>
                <li>Observações importantes.</li>
            </ul>

            <hr>

            <h4>9. Uso da IA no DirectOS</h4>
            <p>
                Quando disponível, a IA pode ajudar a melhorar descrições, criar checklist técnico,
                resumir atendimentos e preparar mensagens para clientes.
            </p>

            <div class="alert alert-warning">
                Sempre revise o texto gerado pela IA antes de usar ou enviar para o cliente.
            </div>

            <hr>

            <h4>10. Comunicação por WhatsApp</h4>
            <p>
                O DirectOS pode ajudar a preparar mensagens para WhatsApp, como abertura de OS,
                andamento, conclusão e informações de pagamento.
            </p>
            <p>
                O sistema monta a mensagem, mas o envio é feito por você pelo WhatsApp.
            </p>

            <hr>

            <h4>11. Registro de recebimentos</h4>
            <p>Quando o cliente fizer um pagamento, registre o recebimento na OS.</p>

            <ol>
                <li>Abra a ordem de serviço.</li>
                <li>Vá até a área de recebimentos.</li>
                <li>Clique em <strong>Novo recebimento</strong>.</li>
                <li>Informe valor, data, forma de pagamento e observação, se necessário.</li>
                <li>Clique em <strong>Salvar</strong>.</li>
            </ol>

            <hr>

            <h4>12. Recibos</h4>
            <p>
                Após registrar um pagamento, você pode gerar um recibo como comprovante para o cliente.
            </p>
            <p>
                Antes de enviar, confira nome do cliente, valor, serviço realizado, data e dados da empresa.
            </p>

            <hr>

            <h4>13. Área do cliente por link</h4>
            <p>
                Algumas ordens de serviço podem ter um link para o cliente acompanhar informações básicas do atendimento.
            </p>
            <p>
                Evite compartilhar esse link com pessoas que não fazem parte do atendimento.
            </p>

            <hr>

            <h4>14. Relatórios</h4>
            <p>
                Os relatórios ajudam a acompanhar ordens de serviço, status, valores previstos,
                valores recebidos, pendências e histórico.
            </p>
            <p>
                Use os filtros para procurar por período, cliente ou status.
            </p>

            <hr>

            <h4>15. Boas práticas de uso</h4>

            <ul>
                <li>Cadastre os clientes corretamente.</li>
                <li>Evite criar clientes duplicados.</li>
                <li>Atualize o status das ordens de serviço.</li>
                <li>Registre os atendimentos com detalhes.</li>
                <li>Lance os recebimentos assim que forem pagos.</li>
                <li>Confira os dados antes de gerar recibos.</li>
                <li>Não compartilhe sua senha.</li>
                <li>Use o sistema diariamente para manter tudo atualizado.</li>
            </ul>

            <hr>

            <h4>16. Primeiro teste recomendado</h4>

            <p>Para aprender o fluxo completo, faça este primeiro teste:</p>

            <ol>
                <li>Cadastre um cliente.</li>
                <li>Cadastre um serviço.</li>
                <li>Crie uma ordem de serviço.</li>
                <li>Registre uma observação no atendimento.</li>
                <li>Altere o status da OS.</li>
                <li>Lance um recebimento.</li>
                <li>Gere um recibo.</li>
                <li>Consulte o relatório.</li>
            </ol>

            <div class="alert alert-success mt-4">
                <strong>Resumo:</strong>
                Cliente → Serviço → Ordem de Serviço → Atendimento → Recebimento → Recibo → Relatório.
            </div>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>