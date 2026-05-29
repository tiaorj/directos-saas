# Teste de ambiente Render

Nao deixe um arquivo de diagnostico publico ativo por padrao. Se precisar validar variaveis no Render, crie temporariamente um arquivo protegido e remova logo apos o teste.

Exemplo temporario:

```php
<?php

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "APP_NAME=" . APP_NAME . PHP_EOL;
echo "APP_ENV=" . APP_ENV . PHP_EOL;
echo "APP_URL=" . APP_URL . PHP_EOL;
echo "APP_DEBUG=" . (APP_DEBUG ? 'true' : 'false') . PHP_EOL;
echo "UPLOAD_DIR=" . UPLOAD_DIR . PHP_EOL;
echo "LOG_DIR=" . LOG_DIR . PHP_EOL;
echo "DB_SERVER=" . (getenv('DB_SERVER') ? 'configurado' : 'ausente') . PHP_EOL;
echo "DB_DATABASE=" . (getenv('DB_DATABASE') ? 'configurado' : 'ausente') . PHP_EOL;
echo "DB_USERNAME=" . (getenv('DB_USERNAME') ? 'configurado' : 'ausente') . PHP_EOL;
echo "DB_PASSWORD=" . (getenv('DB_PASSWORD') ? 'configurado' : 'ausente') . PHP_EOL;
```

Regras para esse teste:

1. Nao imprimir valores reais de usuario, senha ou servidor.
2. Remover o arquivo temporario apos validar o ambiente.
3. Preferir os testes funcionais descritos em `docs/deploy-render.md`.
