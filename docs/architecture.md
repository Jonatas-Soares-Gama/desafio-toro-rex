# Arquitetura

## Direção das dependências

```text
HTTP → Application → Domain
                  ↑
Infrastructure ───┘
```

O domínio não conhece HTTP, banco, JWT ou detalhes de framework. A infraestrutura implementa as portas necessárias pela aplicação.

## Backend

- `Domain`: entidades, value objects, regras e exceções de negócio.
- `Application`: casos de uso e DTOs de entrada/saída.
- `Infrastructure`: PDO, repositories, migrations, seed, JWT e relógio.
- `Http`: router, middleware, controllers, validação e serialização.

## Transações

O caso de uso de registro de venda coordena a transação completa:

1. Bloqueia a campanha com `SELECT ... FOR UPDATE`.
2. Valida campanha, produto, vendedor e verba.
3. Cria a venda.
4. Cria o crédito no ledger.
5. Atualiza `budget_used`.
6. Confirma tudo com `COMMIT`.

Qualquer falha executa `ROLLBACK`.

O cancelamento segue a mesma garantia transacional:

1. Bloqueia a venda por `external_id` com `SELECT ... FOR UPDATE`;
2. verifica a janela de 30 dias e lê os pontos do crédito original;
3. bloqueia a campanha relacionada;
4. marca a venda como cancelada;
5. cria o débito no ledger;
6. reduz `budget_used` pelos mesmos pontos;
7. confirma tudo com `COMMIT`.

Uma venda já cancelada é um no-op idempotente. Uma venda fora da janela retorna
`422` sem alterar status, ledger ou verba.

## Frontend

O React será organizado por feature: autenticação, produtos, campanhas, vendas e carteira. A camada HTTP centraliza token, respostas de erro e redirecionamento para login.

## Qualidade

Cada caso de uso terá especificação, testes unitários para regras puras e testes de integração para persistência/transações.
