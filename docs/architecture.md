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

## Frontend

O React será organizado por feature: autenticação, produtos, campanhas, vendas e carteira. A camada HTTP centraliza token, respostas de erro e redirecionamento para login.

## Qualidade

Cada caso de uso terá especificação, testes unitários para regras puras e testes de integração para persistência/transações.
