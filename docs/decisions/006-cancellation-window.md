# ADR 006 — Janela de 30 dias para cancelamento

## Status

Aceita

## Decisão

Uma venda aprovada pode ser cancelada somente antes de completar 30 dias desde
`sales.created_at`. O limite é exclusivo: se `now >= created_at + 30 dias`, a
API retorna `422` e não altera venda, ledger ou verba.

Uma venda já cancelada continua respondendo `200` de forma idempotente, mesmo
que a requisição ocorra depois do prazo, porque nesse caso não há nova alteração
de estado.

## Motivo

A janela limita ajustes retroativos e torna explícito por quanto tempo uma
venda pode ser desfeita. A data da própria venda é usada como referência para
que a regra não dependa da data da requisição ou de um campo enviado pelo
cliente.

O desafio define a operação de cancelamento, mas não fixa um prazo. Esta é uma
regra adicional de negócio definida para o projeto.

## Consequências

- a comparação deve usar horário UTC e `created_at` persistido;
- a API precisa diferenciar venda aprovada expirada (`422`) de venda inexistente
  (`404`);
- o prazo deve ser coberto nos testes antes do limite, no limite e depois do
  limite;
- produto inativo e campanha fechada continuam sem bloquear um cancelamento
  que esteja dentro da janela, conforme a decisão anterior.
