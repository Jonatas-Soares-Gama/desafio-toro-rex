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
- Teste HTTP real de campanhas em `backend/bin/test-campaigns-http.sh`;
- `firebase/php-jwt` 7.1.1;
- `composer.lock` versionado;
- PHPUnit: 25 testes e 39 assertions passando;
- Verificação HTTP Dockerizada passando.

## Credenciais locais

```text
admin@toro.local / admin123
seller1@toro.local / seller123
seller2@toro.local / seller123
```

## Próxima tarefa imediata

Iniciar o plano do frontend React para testar a plataforma com login, produtos e campanhas.

## Backlog ordenado

- [x] Middleware JWT;
- [x] ACL por papel;
- [x] Parser de header e principal autenticado;
- [x] Especificação e testes unitários de autorização;
- [x] Teste HTTP de rota protegida;
- [x] CRUD de produtos;
- [x] Campanhas;
- [ ] Motor de pontuação;
- [ ] Cancelamento e estorno;
- [ ] Carteira/extrato;
- [ ] Testes de concorrência;
- [ ] Frontend React;
- [x] Requests versionados em `requests/api.http`;
- [ ] OpenAPI/Swagger;
- [ ] README final com limitações e próximos passos.

## Como validar a base atual

```bash
docker compose up --build
docker compose run --rm --no-deps backend vendor/bin/phpunit
curl -i http://localhost:8080/health
```
