# Deploy no Render com Docker

Este guia prepara o DirectOS para rodar como Web Service Docker no Render usando SQL Server externo hospedado na Somee.

Nao crie banco PostgreSQL no Render e nao migre o banco para outro provedor. A aplicacao PHP roda no Render, mas o banco continua sendo SQL Server externo.

## 1. Branch

Use uma branch dedicada para a publicacao:

```bash
git checkout -b feature/deploy-render-docker
```

## 2. Web Service

No Render:

1. Crie um novo Web Service.
2. Conecte o repositorio GitHub `tiaorj/sistema-os-php-sqlserver`.
3. Escolha o ambiente `Docker`.
4. Use o `Dockerfile` da raiz do projeto.
5. Ative auto deploy se desejar publicar cada push da branch configurada.

## 3. Variaveis de ambiente

Configure no Render. As credenciais da Somee devem existir apenas como variaveis de ambiente:

```text
APP_ENV=producao
APP_URL=https://seu-app.onrender.com
DB_SERVER=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_TRUST_SERVER_CERTIFICATE=true
UPLOAD_DIR=/var/www/storage/uploads
LOG_DIR=/var/www/storage/logs
```

Nao cadastre credenciais reais em arquivos versionados.

## 4. Persistent Disk

Crie um Persistent Disk para uploads e logs:

```text
Mount Path: /var/www/storage
```

Com as variaveis acima, o sistema usara:

```text
/var/www/storage/uploads
/var/www/storage/logs
```

## 5. Banco SQL Server

O banco continua sendo SQL Server externo na Somee. Configure servidor, banco, usuario e senha da Somee somente nas variaveis de ambiente do Render:

- `DB_SERVER`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_TRUST_SERVER_CERTIFICATE=true`

Se existir `database/directos_schema.sql`, rode esse script no SQL Server antes de testar a aplicacao.

## 6. Testes de publicacao

Depois do deploy:

1. Acesse `/index.php`.
2. Acesse `/cadastro.php`.
3. Teste login.
4. Crie uma ordem de servico.
5. Abra o link publico da OS.
6. Envie um anexo.
7. Abra o anexo pela area interna.
8. Libere o anexo para o cliente e abra pela area publica.

## 7. Arquivos locais

Nao versionar:

- `.env`
- `config/config.local.php`
- uploads reais
- logs reais

Use `config/config.local.example.php` apenas como modelo para ambiente local.
