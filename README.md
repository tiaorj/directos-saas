# DirectOS - Sistema de Ordem de Serviço em PHP

Sistema de gestão de ordens de serviço desenvolvido em PHP com SQL Server.

## Tecnologias utilizadas

- PHP
- SQL Server
- PDO SQLSRV
- Bootstrap
- HTML
- CSS
- JavaScript

## Funcionalidades

- Dashboard com indicadores
- Cadastro de clientes
- Cadastro de serviços
- Cadastro de ordens de serviço
- Filtros por status, prioridade, cliente e período
- Visualização detalhada da OS
- Impressão básica da ordem de serviço
- Controle de status e prioridade

## Configuração do ambiente

Crie um arquivo `.env` na raiz do projeto com base no arquivo `.env.example`.

```env
DB_SERVER=seu_servidor_sqlserver
DB_NAME=nome_do_banco
DB_USER=usuario_do_banco
DB_PASS=senha_do_banco