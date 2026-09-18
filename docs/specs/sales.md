# Especificação — Registro de vendas e motor de pontuação

## Escopo

Administradores podem registrar uma venda aprovada para um seller. O backend
calcula os pontos com base no produto, consome a verba da campanha e publica o
crédito no ledger dentro da mesma transação.

Esta spec cobre somente o registro da venda. Cancelamento, estorno e consulta
da carteira serão especificados em ciclos separados.

## Autorização

A rota exige `Authorization: Bearer <jwt>` com `role = admin`.

- Sem token ou token inválido: `401`;
- Token válido de seller: `403`;
- `seller_id` é aceito no payload porque o admin lança a venda em nome do
  vendedor;
- o papel usado na autorização vem do JWT validado, nunca do body.

## Endpoint

```text
POST /sales
```

### Request

```json
{
  "external_id": "erp-sale-1001",
  "campaign_id": 1,
  "seller_id": 2,
  "product_id": 1,
  "quantity": 3,
  "unit_value": "149.90"
}
```

Regras de entrada:

- `external_id` é texto não vazio, com no máximo 120 caracteres;
- `campaign_id`, `seller_id`, `product_id` e `quantity` são inteiros positivos;
- `unit_value` é decimal não negativo com no máximo duas casas;
- todos os campos são obrigatórios;
- valores monetários permanecem como `DECIMAL`; não são convertidos para
  `float`;
- o cliente não informa pontos, status, `budget_used` ou entradas do ledger.

Payload ausente, tipos inválidos, campos ausentes ou valores fora dos limites
retornam `422`.

### Regras de negócio

1. O produto precisa existir e estar ativo.
2. O seller precisa existir e ter papel `seller`.
3. A campanha precisa existir, estar com status `active` e estar dentro do
   período de validade (`starts_at <= agora < ends_at`).
4. Os pontos da venda são calculados por:

   ```text
   points = quantity * product.points_per_unit
   ```

5. A campanha é bloqueada pessimisticamente durante a operação. Se
   `budget_used + points > budget_total`, a venda é rejeitada integralmente
   com `422`.
6. Quando a venda cabe na verba, a mesma transação deve:
   - inserir a venda com status `approved`;
   - inserir uma entrada `credit` no ledger com os pontos calculados;
   - incrementar `campaigns.budget_used` pelos mesmos pontos.
7. Qualquer falha desfaz todas as três escritas.
8. `sales.external_id` é idempotente:
   - repetir a mesma venda não cria novo registro, crédito ou consumo de verba;
   - uma repetição idêntica retorna `200` com a venda já registrada;
   - reutilizar o mesmo `external_id` com dados diferentes retorna `409`, sem
     alterar a venda original.

## Respostas

Venda nova aprovada:

- `201 Created`;
- retorna a venda persistida, incluindo `id`, `external_id`, IDs relacionados,
  `quantity`, `unit_value`, `status` e `created_at`;
- pode incluir `points` como dado calculado da operação, sem transformá-lo em
  campo mutável da venda.

Venda repetida com os mesmos dados:

- `200 OK`;
- retorna a venda existente;
- não cria efeitos adicionais.

Erros esperados:

- `401` — autenticação ausente ou inválida;
- `403` — seller tentando registrar venda;
- `409` — `external_id` já usado por outra venda;
- `422` — entrada inválida, produto/seller/campanha inexistente ou inativo,
  campanha fora do período ou verba insuficiente;
- `500` — falha inesperada, com rollback da transação.

As respostas de erro não devem expor dados de outros sellers além do necessário
para informar o conflito de `external_id`.

## Persistência e atomicidade

O caso de uso coordena a transação. A ordem mínima é:

1. validar a venda e localizar entidades relacionadas;
2. bloquear a campanha com `SELECT ... FOR UPDATE`;
3. verificar verba disponível;
4. inserir a venda;
5. inserir o crédito no ledger;
6. atualizar `budget_used`;
7. confirmar a transação.

O banco reforça as invariantes com a chave única de `external_id`, foreign keys,
checks de quantidade/valor e `budget_used <= budget_total`.

## Critérios de aceite

- Admin registra venda válida e recebe `201`;
- pontos são exatamente `quantity * points_per_unit`;
- crédito e consumo de verba têm o mesmo valor;
- verba insuficiente não cria venda, crédito nem consumo parcial;
- repetir `external_id` não pontua duas vezes;
- `external_id` conflitante não altera a venda original;
- produto inativo, seller inválido e campanha inválida são rejeitados;
- campanha fora do período ou fechada é rejeitada;
- seller recebe `403` e requisição sem token recebe `401`;
- uma falha durante a operação deixa banco, verba e ledger no estado anterior;
- o teste de integração usa MySQL real e verifica os registros persistidos.
