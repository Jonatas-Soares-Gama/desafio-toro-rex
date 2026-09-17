# Plano 002 — CRUD de produtos

Status: concluído.

## Sequência

1. Especificar regras de validação, ACL e inativação;
2. Implementar persistência e caso de uso;
3. Aplicar rotas protegidas ao front controller;
4. Testar validação, autorização e fluxo HTTP com MySQL real;
5. Atualizar README, requests e memória.

## Definição de pronto

- CRUD completo em `POST/GET/PUT/DELETE /products`;
- seller bloqueado com `403`;
- `DELETE` não remove fisicamente;
- SKU único preservado por constraint e resposta `409`;
- PHPUnit e teste HTTP Dockerizado passando;
- documentação atualizada.

## Evidências

- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 21 testes, 34 assertions;
- `backend/bin/test-products-http.sh` → verificou `401`, `403`, `422`, `409`, criação, listagem, edição e inativação idempotente contra a API e o MySQL Dockerizados.
