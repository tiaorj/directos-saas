# Banco de Dados - DirectOS

Esta pasta contém os scripts SQL do DirectOS.

## Ordem recomendada de execução

1. `001_schema_inicial.sql`
2. `002_alteracoes_publicacao_mvp.sql`
3. `003_dados_demo.sql` somente em ambiente de demonstração

## Observações

- Banco utilizado: SQL Server
- Ambiente atual de produção: Render + SQL Server externo
- Antes de executar scripts em produção, faça backup do banco.
- O arquivo `003_dados_demo.sql` não deve ser executado em ambiente real de cliente.

## Scripts

### 001_schema_inicial.sql

Estrutura base do sistema.

### 002_alteracoes_publicacao_mvp.sql

Alterações criadas durante a evolução do MVP, como:

- Segmento da empresa
- Campos personalizados da OS
- Valores personalizados por OS
- Controle financeiro
- Histórico de recebimentos

### 003_dados_demo.sql

Dados fictícios para ambiente de demonstração.