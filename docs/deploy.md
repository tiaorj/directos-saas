# Deploy DirectOS

Este guia prepara o DirectOS para teste real, homologacao ou producao sem versionar credenciais.

## 1. Configuracao local

Copie o arquivo de exemplo:

```text
config/config.local.example.php -> config/config.local.php
```

Edite `config/config.local.php` no servidor ou maquina local e ajuste:

```php
define('APP_ENV', 'local');
define('APP_URL', 'http://localhost:8080/sistema-os-php-sqlserver');
define('DB_SERVER', 'localhost');
define('DB_DATABASE', 'DirectOS');
define('DB_USERNAME', 'seu_usuario');
define('DB_PASSWORD', 'sua_senha');
define('DB_TRUST_SERVER_CERTIFICATE', true);
```

Nao adicione `config/config.local.php` ao Git.

## 2. Ambientes

Use `APP_ENV` para identificar o ambiente:

- `local`
- `homologacao`
- `producao`

Use `APP_URL` com a URL publica do ambiente. Exemplo:

```php
define('APP_URL', 'https://app.seudominio.com.br');
```

Em producao, mantenha `APP_ENV` como `producao` para evitar exibicao de erro detalhado de conexao.

## 3. Banco de dados

Configure:

- `DB_SERVER`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_TRUST_SERVER_CERTIFICATE`

Rode os scripts SQL necessarios para criar ou atualizar tabelas, indices e dados iniciais do DirectOS antes de liberar o acesso.

## 4. Pastas gravaveis

Garanta permissao de escrita para o usuario do servidor web em:

- `uploads`
- `logs`

As pastas ficam versionadas apenas com `.gitkeep`. Arquivos enviados e logs reais devem permanecer fora do Git.

## 5. Checklist de teste

Depois de publicar ou configurar o ambiente:

1. Testar login.
2. Testar cadastro publico.
3. Testar criacao de OS.
4. Testar link publico da OS.
5. Testar upload de anexo.
6. Testar abertura de anexo interno.
7. Testar abertura de anexo publico liberado para cliente.

## 6. Seguranca

Nunca versionar senhas reais, usuarios reais de banco, arquivos `.env`, `config/config.local.php`, uploads reais ou logs reais.
