# Especificação — Carteira e extrato do seller

## Escopo

Vendedores autenticados podem consultar a própria carteira e o extrato completo
do ledger. O saldo é calculado a cada consulta a partir das entradas de
`wallet_entries`:

```text
saldo = soma(credit) - soma(debit)
```

Não existe um saldo mutável separado como fonte da verdade. Esta task é somente
de leitura e não altera o schema, as vendas ou o ledger.

O MVP não terá paginação, filtros, consulta administrativa de carteiras ou
exportação do extrato.

## Autorização e ownership

A rota exige `Authorization: Bearer <jwt>` com `role = seller`.

- sem token, token inválido ou token expirado: `401`;
- token válido de admin: `403`;
- o vendedor é identificado pelo `sub` do JWT validado;
- não existe `seller_id` no caminho, body ou query string que possa substituir
  a identidade autenticada;
- a resposta contém somente entradas cujo `seller_id` é o usuário autenticado.

## Endpoint

```http
GET /me/wallet
```

A requisição não precisa de body.

### Resposta de sucesso

Status: `200 OK`.

```json
{
  "wallet": {
    "balance": 200,
    "entries": [
      {
        "id": 2,
        "campaign_id": 1,
        "sale_id": 1,
        "type": "debit",
        "points": 100,
        "description": "Cancellation of sale erp-sale-1001",
        "created_at": "2026-09-18 12:00:00"
      },
      {
        "id": 1,
        "campaign_id": 1,
        "sale_id": 1,
        "type": "credit",
        "points": 300,
        "description": "Sale erp-sale-1001",
        "created_at": "2026-09-17 12:00:00"
      }
    ]
  }
}
```

Campos:

- `balance`: inteiro calculado pelo ledger, sem arredondamento ou valor vindo
  do cliente;
- `entries`: todas as entradas do seller autenticado;
- cada entrada expõe somente `id`, `campaign_id`, `sale_id`, `type`, `points`,
  `description` e `created_at`.

As entradas são ordenadas por `created_at DESC, id DESC`, para que o resultado
seja determinístico quando duas entradas tiverem o mesmo instante.

Para um seller sem entradas, a resposta é válida e contém:

```json
{
  "wallet": {
    "balance": 0,
    "entries": []
  }
}
```

## Regras de consistência

1. O saldo considera créditos e débitos de todas as entradas persistidas do
   seller.
2. Um cancelamento aparece como um débito separado; entradas anteriores não
   são apagadas nem alteradas.
3. O saldo não é derivado do produto atual, da campanha atual ou de qualquer
   campo enviado pelo cliente.
4. A leitura deve usar prepared statement e filtrar o `seller_id` derivado do
   principal autenticado.
5. A implementação deve calcular o saldo a partir do mesmo conjunto de
   entradas retornado no extrato, evitando que `balance` e `entries` reflitam
   consultas diferentes.

## Respostas de erro

- `401` — autenticação ausente ou inválida;
- `403` — admin tentando usar a carteira de seller;
- `500` — falha inesperada, sem expor SQL, credenciais ou dados de outro
  vendedor.

Não há resposta `404` para um seller válido sem movimentações: carteira vazia
é representada por saldo zero e extrato vazio.

## Critérios de aceite

- seller autenticado consulta a própria carteira com `200`;
- saldo inicial sem entradas é `0`;
- uma venda aprovada aparece como uma entrada `credit` e aumenta o saldo pelos
  pontos da venda;
- o cancelamento da venda aparece como uma entrada `debit` com os mesmos pontos
  do crédito e reduz o saldo corretamente;
- repetir um cancelamento não cria uma segunda entrada `debit` e não altera o
  saldo novamente;
- seller 1 não consegue consultar, por parâmetro ou manipulação do request,
  as entradas do seller 2;
- admin recebe `403` e requisição sem token recebe `401`;
- as entradas aparecem em ordem decrescente de criação;
- `password_hash`, dados do usuário e campos internos não aparecem na resposta;
- o teste HTTP verifica o fluxo contra API e MySQL Dockerizados.

## Fora de escopo

- carteira administrativa consolidada;
- consulta de carteira por `seller_id` arbitrário;
- paginação, filtros por campanha ou período;
- saldo cacheado;
- saque, resgate ou transferência de pontos;
- edição ou exclusão de entradas do ledger.
