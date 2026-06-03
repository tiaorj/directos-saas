# Checklist de Publicação - DirectOS

## Objetivo

Preparar o DirectOS para publicação, apresentação comercial e primeiros testes com clientes reais.

---

## 1. Ambiente e Deploy

* [ ] Confirmar repositório GitHub correto: `directos-saas`
* [ ] Confirmar branch principal: `main`
* [ ] Confirmar deploy automático no Render
* [ ] Confirmar URL pública do sistema
* [ ] Confirmar variável `APP_URL` no Render
* [ ] Confirmar `APP_ENV=producao`
* [ ] Confirmar `APP_DEBUG=false`
* [ ] Confirmar `N8N_ATIVO=false` enquanto o envio automático estiver desativado
* [ ] Confirmar `OPENAI_API_KEY` configurada
* [ ] Confirmar conexão com SQL Server em produção
* [ ] Testar login no Render
* [ ] Testar logout no Render
* [ ] Remover arquivos temporários, testes ou `phpinfo.php`, se existirem
* [x] Confirmar versão do PHP no Render: PHP 8.3.31
* [ ] Confirmar versão do PHP local
* [ ] Confirmar versão do PHP no Render
* [ ] Confirmar `composer.json` com PHP 8.3
* [ ] Confirmar Build Command do Render
* [ ] Confirmar Start Command do Render

---

## 2. Banco de Dados

* [ ] Organizar script SQL inicial do sistema
* [ ] Organizar script SQL das alterações recentes
* [ ] Confirmar tabelas principais:

  * [ ] `OS_Empresas`
  * [ ] `OS_Usuarios`
  * [ ] `OS_Clientes`
  * [ ] `OS_Servicos`
  * [ ] `OS_OrdensServico`
  * [ ] `OS_Historico`
  * [ ] `OS_CamposPersonalizados`
  * [ ] `OS_OrdensServicoCampos`
  * [ ] `OS_Recebimentos`
  * [ ] `OS_MensagensWhatsApp`
* [ ] Confirmar colunas financeiras em `OS_OrdensServico`
* [ ] Confirmar coluna `Segmento` em `OS_Empresas`
* [ ] Criar backup do banco antes de divulgar
* [ ] Criar dados de demonstração

---

## 3. Fluxo Principal do Sistema

### Login e Usuários

* [ ] Login funcionando
* [ ] Logout funcionando
* [ ] Perfil Admin funcionando
* [ ] Perfil Atendente funcionando
* [ ] Perfil Técnico funcionando
* [ ] Perfil SuperAdmin funcionando
* [ ] Usuários vinculados corretamente à empresa

### Clientes

* [ ] Cadastrar cliente
* [ ] Editar cliente
* [ ] Listar cliente
* [ ] Validar telefone para WhatsApp
* [ ] Validar campos obrigatórios

### Serviços

* [ ] Cadastrar serviço
* [ ] Editar serviço
* [ ] Gerar descrição com IA
* [ ] Gerar checklist com IA
* [ ] Aplicar checklist padrão na OS

### Ordens de Serviço

* [ ] Criar OS
* [ ] Editar OS
* [ ] Visualizar OS
* [ ] Atualizar atendimento
* [ ] Alterar status
* [ ] Definir prioridade
* [ ] Definir valores
* [ ] Gerar descrição com IA
* [ ] Gerar checklist com IA
* [ ] Aplicar checklist do serviço
* [ ] Preparar mensagem WhatsApp manual
* [ ] Anexar arquivos
* [ ] Imprimir OS

---

## 4. Personalização por Segmento

* [ ] Configurar segmento da empresa
* [ ] Aplicar modelo pronto de Campos personalizados
* [ ] Criar campo personalizado manualmente
* [ ] Editar campo personalizado
* [ ] Inativar campo personalizado
* [ ] Exibir campos personalizados na criação da OS
* [ ] Exibir campos personalizados na edição da OS
* [ ] Exibir campos personalizados na visualização da OS
* [ ] Validar campos obrigatórios

---

## 5. Financeiro

* [ ] Registrar recebimento
* [ ] Registrar pagamento parcial
* [ ] Registrar pagamento total
* [ ] Excluir recebimento
* [ ] Recalcular status financeiro automaticamente
* [ ] Conferir status: Pendente
* [ ] Conferir status: Parcial
* [ ] Conferir status: Pago
* [ ] Exibir controle financeiro na OS
* [ ] Gerar recibo geral da OS
* [ ] Gerar recibo por recebimento individual
* [ ] Imprimir recibo / salvar PDF

---

## 6. Relatórios

* [ ] Relatório operacional de OS
* [ ] Filtro por período
* [ ] Filtro por status
* [ ] Filtro por cliente
* [ ] Filtro por serviço
* [ ] Indicadores gerais
* [ ] Gráfico por status
* [ ] Serviços mais executados
* [ ] Resumo por serviço
* [ ] Exportar CSV de OS
* [ ] Relatório financeiro
* [ ] Exportar CSV financeiro

---

## 7. Área do Cliente

* [ ] Link público da OS funcionando
* [ ] Token de acompanhamento funcionando
* [ ] Exibir status da OS
* [ ] Exibir dados permitidos pela empresa
* [ ] Respeitar configurações de visibilidade:

  * [ ] Valor
  * [ ] Solução
  * [ ] Histórico
* [ ] Testar acesso sem login
* [ ] Validar se não expõe dados internos indevidos

---

## 8. Segurança

* [ ] Todas as páginas internas exigem login
* [ ] Todas as ações POST validam CSRF
* [ ] Exclusões validam CSRF
* [ ] Usuário não acessa dados de outra empresa
* [ ] Uploads não permitem arquivos perigosos
* [ ] Mensagens de erro em produção não exibem detalhes técnicos
* [ ] Chaves e senhas não estão no GitHub
* [ ] `.env` não está versionado
* [ ] Variáveis sensíveis estão somente no Render

---

## 9. Revisão Visual e Textual

* [ ] Remover textos de teste
* [ ] Remover empresas/clientes fictícios do ambiente real
* [ ] Padronizar nome DirectOS
* [ ] Revisar textos do dashboard
* [ ] Revisar textos dos botões
* [ ] Revisar mensagens de vazio
* [ ] Revisar mensagens de sucesso
* [ ] Revisar página de configurações
* [ ] Revisar página de planos
* [ ] Revisar landing page
* [ ] Validar visual em desktop
* [ ] Validar visual em celular

---

## 10. Ambiente Demo

* [X] Criar empresa demo
* [X] Criar usuário demo
* [X] Criar clientes fictícios
* [X] Criar serviços fictícios
* [X] Criar OS em status variados
* [X] Criar recebimentos fictícios
* [X] Criar campos personalizados por segmento
* [X] Criar relatórios com dados suficientes
* [] Bloquear ou limitar ações destrutivas no usuário demo

---

## 11. Materiais Comerciais

* [ ] Criar descrição curta do produto
* [ ] Criar lista de funcionalidades
* [ ] Criar prints das telas principais
* [ ] Criar texto para LinkedIn
* [ ] Criar mensagem curta para abordagem comercial
* [ ] Definir planos iniciais
* [ ] Definir preço inicial
* [ ] Definir oferta para primeiros clientes
* [ ] Atualizar README do GitHub
* [ ] Criar roteiro de demonstração

---

## 12. Critério de Publicação

O DirectOS pode ser divulgado quando:

* [ ] O fluxo completo de OS estiver funcionando
* [ ] O financeiro básico estiver funcionando
* [ ] O recibo estiver funcionando
* [ ] O relatório estiver funcionando
* [ ] O ambiente de produção estiver estável
* [ ] Existir empresa demo com dados
* [ ] A landing page explicar claramente o produto
* [ ] As variáveis de produção estiverem corretas
* [ ] Não houver dados sensíveis expostos
* [ ] Não houver páginas quebradas nos menus principais
