# ADR 001 — Política para verba insuficiente

## Status

Aceita

## Decisão

Uma venda que ultrapasse a verba disponível será rejeitada integralmente. Nenhum registro de venda aprovada, crédito ou consumo parcial será persistido.

## Motivo

Essa regra mantém a relação simples entre uma venda e seus pontos, evita créditos parciais difíceis de explicar e torna o ledger mais auditável. A resposta esperada da API é `422 Unprocessable Entity`.

## Exemplo

Com 80 pontos disponíveis e uma venda de 100 pontos, a venda não é aprovada e a campanha permanece com o mesmo `budget_used`.
