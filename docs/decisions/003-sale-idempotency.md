# ADR 003 — Idempotência de vendas e cancelamentos

## Status

Aceita

## Decisão

`sales.external_id` será único. Repetir uma requisição com o mesmo identificador não cria novo crédito. Cancelar uma venda já cancelada também não cria novo débito.

## Motivo

Integrações podem repetir requisições por timeout ou retry. A constraint do banco e a transação protegem o sistema mesmo quando a aplicação recebe a mesma operação mais de uma vez.
