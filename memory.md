# Memória do projeto

Este arquivo registra o contexto necessário para retomar o desenvolvimento sem depender do histórico da conversa.

## Projeto

- Nome: Vendeu, Ganhou.
- Repositório: `desafio-toro-rex`.
- Stack obrigatória: PHP 8 puro, MySQL 8, React, Docker Compose e JWT.
- Restrição: não usar Laravel ou Symfony.
- Objetivo: plataforma de incentivo baseada em pontos, sem saque ou dinheiro real.

## Decisões confirmadas

- Verba insuficiente rejeita a venda inteira; não haverá crédito parcial.
- Ledger é a fonte da verdade do saldo.
- `sales.external_id` será único para garantir idempotência.
- Cancelamento repetido não gera novo débito.
- Produtos serão inativados, não removidos fisicamente.
- Pontos e quantidades serão inteiros positivos.
- Valores monetários usarão `DECIMAL`, nunca `float`.
- Crédito, venda e atualização de verba ocorrerão na mesma transação.
- Consumo concorrente de verba usará `SELECT ... FOR UPDATE`.
- JWT usará HMAC-SHA256 e segredo de ambiente com pelo menos 32 caracteres.
- Senhas usam `password_hash` e `password_verify`.

## Implementado

- `AGENTS.md` com convenções de arquitetura, SDD, TDD, segurança e commits;
- Documentação inicial em `docs/`;
- Docker Compose com backend e MySQL;
- Schema e seed idempotentes no boot;
- PDO com prepared statements;
- Router próprio e `GET /health`;
- `POST /auth/login` com JWT;
- Principal autenticado, parser de Bearer token e middleware JWT;
- Middleware de papel e pipeline de middlewares no router;
- Rota administrativa protegida `GET /admin/ping`;
- Especificação de autorização em `docs/specs/authorization.md`;
- Testes unitários para principal, autenticação, ACL e integração do pipeline no router;
- Teste HTTP real em `backend/bin/test-http.sh`, cobrindo `401`, `403` e `200`;
- CRUD de produtos com `ProductRepository`, `ProductService` e inativação lógica;
- Teste HTTP real do CRUD em `backend/bin/test-products-http.sh`;
- Criação e listagem de campanhas com validação de período e orçamento;
- Registro transacional de vendas com cálculo de pontos, idempotência e crédito no ledger;
- Cancelamento idempotente com estorno de pontos, janela de 30 dias e devolução transacional de verba;
- Carteira e extrato do seller com saldo derivado do ledger e ownership pelo JWT;
- Testes de concorrência para consumo de verba e cancelamento idempotente;
- Retry de transações após deadlock MySQL (`1213`/`40001`), com fallback JSON `503`;
- Teste HTTP real de campanhas em `backend/bin/test-campaigns-http.sh`;
- Teste HTTP real de cancelamento em `backend/bin/test-cancellations-http.sh`;
- Teste HTTP real da carteira em `backend/bin/test-wallet-http.sh`;
- Cadastro e listagem administrativa de sellers em `backend/bin/test-users-http.sh`;
- Histórico administrativo de vendas e cancelamento por linha;
- Exportação CSV do histórico no frontend com teste automatizado;
- Frontend React com login, CRUD de produtos, campanhas, vendas, sellers e carteira;
- Testes frontend de JWT, SKU e CSV, com lint e build passando;
- Configuração local centralizada em `.env.example`, com conexão PDO e JWT
  recebendo variáveis de ambiente obrigatórias no backend;
- `firebase/php-jwt` 7.1.1;
- `composer.lock` versionado;
- PHPUnit: 31 testes e 47 assertions passando;
- Verificação HTTP Dockerizada passando.

## Credenciais locais

```text
admin@toro.local / admin123
seller1@toro.local / seller123
seller2@toro.local / seller123
```

## Estado atual

O núcleo do desafio e o frontend estão implementados. As telas principais do
frontend foram validadas manualmente. A regressão limpa passou em autenticação,
produtos, campanhas, vendas, cancelamento, carteira, usuários e histórico. O
teste de concorrência passou repetidamente após o tratamento de deadlock.

Regra implementada: venda aprovada só pode ser cancelada antes de completar 30
dias desde `sales.created_at`; no limite ou depois retorna `422`.

Planos concluídos ou implementados:

- `docs/plans/001-authentication-acl.md` a `docs/plans/007-concurrency-tests.md`;
- `docs/plans/008-frontend-react.md`;
- `docs/plans/009-admin-ux-improvements.md`;
- `docs/plans/010-sales-history-cancellation.md`;
- `docs/plans/011-sales-csv-export.md`.

## Próxima etapa

Preparar a entrega final: revisar o diff, confirmar ausência de segredos, criar
commits focados e manter OpenAPI/Swagger, SKU no backend, paginação e filtros
como melhorias opcionais.

## Backlog ordenado

- [x] Middleware JWT;
- [x] ACL por papel;
- [x] Parser de header e principal autenticado;
- [x] Especificação e testes unitários de autorização;
- [x] Teste HTTP de rota protegida;
- [x] CRUD de produtos;
- [x] Campanhas;
- [x] Motor de pontuação;
- [x] Cancelamento e estorno com janela de 30 dias;
- [x] Carteira/extrato com ownership e saldo derivado do ledger;
- [x] Testes de concorrência para vendas e cancelamentos;
- [x] Frontend React com revisão manual das telas principais;
- [x] Requests versionados em `requests/api.http`;
- [ ] OpenAPI/Swagger;
- [x] README com execução, seed, API, testes, limitações e próximos passos opcionais.

## Como validar a base atual

```bash
docker compose up --build
docker compose run --rm --no-deps backend vendor/bin/phpunit
curl -i http://localhost:8080/health
```

Frontend:

```bash
cd frontend
npm run test
npm run lint
npm run build
```

Regressão HTTP completa:

```bash
docker compose exec -T backend /app/bin/test-http.sh
docker compose exec -T backend /app/bin/test-concurrency-http.sh
```
