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
- `firebase/php-jwt` 7.1.1;
- `composer.lock` versionado;
- PHPUnit: 7 testes e 10 assertions passando no último ciclo.

## Credenciais locais

```text
admin@toro.local / admin123
seller1@toro.local / seller123
seller2@toro.local / seller123
```

## Próxima tarefa imediata

Implementar middleware de autenticação:

1. Ler `Authorization: Bearer <token>`;
2. Validar assinatura e expiração;
3. Disponibilizar identidade autenticada para a requisição;
4. Retornar `401` para token ausente ou inválido;
5. Criar middleware de papel retornando `403` para seller em rota admin;
6. Adicionar testes unitários e de integração.

## Backlog ordenado

- [ ] Middleware JWT;
- [ ] ACL por papel;
- [ ] Especificação e testes de autorização;
- [ ] CRUD de produtos;
- [ ] Campanhas;
- [ ] Motor de pontuação;
- [ ] Cancelamento e estorno;
- [ ] Carteira/extrato;
- [ ] Testes de concorrência;
- [ ] Frontend React;
- [ ] Requests versionados em `requests/api.http`;
- [ ] OpenAPI/Swagger;
- [ ] README final com limitações e próximos passos.

## Como validar a base atual

```bash
docker compose up --build
docker compose run --rm --no-deps backend vendor/bin/phpunit
curl -i http://localhost:8080/health
```
