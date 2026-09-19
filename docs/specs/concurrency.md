# Especificação — Concorrência do motor de vendas

## Objetivo

Verificar contra MySQL real que as transações do motor de vendas preservam a
verba e o ledger quando requisições compatíveis chegam simultaneamente.

Esta task cobre testes de integração/HTTP. Não altera o contrato dos endpoints
nem cria uma nova regra de negócio.

## Cenário 1 — consumo concorrente de verba

1. Criar uma campanha ativa com `budget_total = 100`.
2. Criar um produto ativo que gere 100 pontos por unidade.
3. Enviar simultaneamente duas vendas diferentes, cada uma com quantidade 1 e
   vinculada à mesma campanha.

Resultado obrigatório:

- exatamente uma venda retorna `201`;
- exatamente uma venda retorna `422` por verba insuficiente;
- a campanha termina com `budget_used = 100`;
- somente um crédito de 100 pontos é persistido;
- a verba não ultrapassa o limite e nenhuma venda parcial é criada.

A proteção esperada é o lock pessimista da campanha dentro da transação.

## Cenário 2 — cancelamento concorrente

1. Criar uma venda aprovada de 100 pontos.
2. Enviar simultaneamente duas requisições para
   `POST /sales/{external_id}/cancel`.

Resultado obrigatório:

- as duas requisições retornam `200` por idempotência;
- a venda termina `canceled`;
- exatamente um débito de 100 pontos é persistido;
- a verba da campanha retorna a zero;
- o saldo do seller não recebe estorno duplicado.

A proteção esperada é o lock pessimista da venda e a constraint única
`(sale_id, type)` do ledger.

## Ambiente de teste

O servidor PHP deve usar pelo menos dois workers durante o teste para permitir
que as requisições realmente se sobreponham. O Compose usa quatro workers por
padrão, ajustáveis por `PHP_CLI_SERVER_WORKERS`.

O teste é executado com:

```bash
docker compose exec -T backend /app/bin/test-concurrency-http.sh
```

## Critérios de aceite

- os dois cenários passam repetidamente contra API e MySQL Dockerizados;
- o teste não depende de IDs fixos para produto, campanha ou venda;
- os identificadores criados pelo teste são únicos e não reutilizam dados de
  execuções anteriores;
- o teste falha se houver duas aprovações, dois débitos ou verba inconsistente;
- nenhuma alteração de schema ou dado de produção é necessária.
