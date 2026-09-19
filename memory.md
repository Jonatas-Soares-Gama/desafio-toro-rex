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
- Teste HTTP real de campanhas em `backend/bin/test-campaigns-http.sh`;
- Teste HTTP real de cancelamento em `backend/bin/test-cancellations-http.sh`;
- Teste HTTP real da carteira em `backend/bin/test-wallet-http.sh`;
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

## Task atual

Carteira e extrato do seller implementados conforme:

- `docs/specs/wallet.md`;
- `docs/plans/006-wallet.md`.

A spec, o plano e as decisões do cancelamento/estorno foram concluídos em:

- `docs/specs/cancellations.md`;
- `docs/plans/005-sale-cancellation.md`;
- `docs/decisions/005-cancellation-points-source.md`;
- `docs/decisions/006-cancellation-window.md`.

Regra implementada: venda aprovada só pode ser cancelada antes de completar 30
dias desde `sales.created_at`; no limite ou depois retorna `422`.

O frontend React será iniciado somente após vendas, cancelamento e carteira.

## Próxima tarefa imediata

Adicionar testes de integração de concorrência e depois iniciar o frontend
React.

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
- [ ] Testes de concorrência;
- [ ] Frontend React (após vendas, cancelamento e carteira);
- [x] Requests versionados em `requests/api.http`;
- [ ] OpenAPI/Swagger;
- [ ] README final com limitações e próximos passos.

## Como validar a base atual

```bash
docker compose up --build
docker compose run --rm --no-deps backend vendor/bin/phpunit
curl -i http://localhost:8080/health
```
