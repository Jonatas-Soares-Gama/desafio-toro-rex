# Especificação — Cancelamento e estorno de vendas

## Escopo

Administradores podem cancelar uma venda aprovada. O cancelamento altera o
status da venda para `canceled`, publica um débito no ledger com os mesmos
pontos do crédito original e devolve esses pontos à verba da campanha.

O cancelamento é atômico e idempotente. Esta spec não cobre a consulta da
carteira, reaprovação de vendas ou cancelamento parcial.

## Autorização

A rota exige `Authorization: Bearer <jwt>` com `role = admin`.

- Sem token ou token inválido: `401`;
- Token válido de seller: `403`;
- o seller da venda é identificado pelo registro persistido, nunca pelo body;
- não há campo de pontos, status, seller ou campanha aceito na requisição de
  cancelamento.

## Endpoint

```text
POST /sales/{external_id}/cancel
```

A requisição não precisa de body. O `external_id` é obtido do caminho, deve ser
texto não vazio e ter no máximo 120 caracteres. Um caminho inválido retorna
`422`.

### Resposta de sucesso

Cancelamento novo e repetição de cancelamento retornam `200 OK` com o mesmo
formato:

```json
{
  "sale": {
    "id": 1,
    "external_id": "erp-sale-1001",
    "campaign_id": 1,
    "seller_id": 2,
    "product_id": 1,
    "quantity": 3,
    "unit_value": "149.90",
    "status": "canceled",
    "created_at": "2026-09-17 12:00:00"
  },
  "reversed_points": 300
}
```

`reversed_points` é o valor do crédito original daquela venda. Repetir a
requisição não cria nova entrada no ledger nem altera novamente a verba, mas
retorna a venda já cancelada.

### Venda inexistente

Se não existir venda com o `external_id` informado, a API retorna `404` sem
criar débito, alterar `budget_used` ou revelar dados de outra venda.

Essa resposta não é considerada uma falha transacional: a operação termina sem
efeitos persistidos e pode ser repetida com segurança.

## Regras de negócio

1. A venda deve ser localizada por `external_id` com bloqueio pessimista
   (`SELECT ... FOR UPDATE`).
2. Se a venda já estiver `canceled`, a operação é um no-op idempotente e
   retorna `200`.
3. Uma venda `approved` só pode ser cancelada dentro da janela de 30 dias
   contada a partir de `sales.created_at`. O instante limite é exclusivo:
   quando `now >= created_at + 30 dias`, o cancelamento é rejeitado.
4. Para uma venda `approved`, os pontos do estorno são lidos da entrada
   `credit` do próprio `sale_id` no ledger.
5. O sistema não recalcula pontos usando `products.points_per_unit`: o produto
   pode ter sido editado ou inativado depois da venda.
6. A campanha da venda é bloqueada com `SELECT ... FOR UPDATE` antes de alterar
   sua verba. O desafio não define bloqueio por status ou período da campanha;
   por decisão deste projeto, esses atributos atuais não impedem o estorno de
   uma venda já aprovada.
7. Na mesma transação, o sistema deve:
   - alterar `sales.status` para `canceled`;
   - inserir uma entrada `debit` com os pontos do crédito original;
   - decrementar `campaigns.budget_used` pelos mesmos pontos.
8. `budget_used` nunca pode ficar negativo. Se a verba persistida não comportar
   o estorno, a operação falha e toda a transação sofre rollback.
9. A constraint única `(sale_id, type)` impede mais de um crédito ou débito por
   venda. A aplicação deve tratar qualquer tentativa duplicada sem produzir
   efeitos adicionais.

## Atomicidade e concorrência

O caso de uso inicia e coordena a transação. A ordem mínima é:

1. iniciar a transação;
2. localizar e bloquear a venda;
3. retornar `404` sem escrita se ela não existir;
4. retornar `200` sem escrita se ela já estiver cancelada;
5. verificar se a venda ainda está dentro da janela de 30 dias;
6. localizar o crédito original e seus pontos;
7. bloquear a campanha relacionada;
8. marcar a venda como cancelada;
9. inserir o débito no ledger;
10. diminuir `budget_used` pelos pontos do crédito;
11. confirmar a transação.

Qualquer erro antes do commit executa `ROLLBACK`, mantendo juntos o status da
venda, o ledger e a verba.

Duas requisições concorrentes para a mesma venda são serializadas pelo lock da
venda. Apenas a primeira cria o débito e devolve verba; a segunda observa o
status `canceled` e retorna sem nova alteração.

## Respostas de erro

- `401` — autenticação ausente ou inválida;
- `403` — seller tentando cancelar uma venda;
- `404` — `external_id` não encontrado;
- `422` — identificador inválido, janela de 30 dias expirada ou dados
  persistidos incompatíveis com a operação;
- `500` — falha inesperada, com rollback da transação.

Mensagens de erro não devem expor dados do seller, pontos de outras vendas ou
detalhes internos do banco.

## Critérios de aceite

- Admin cancela uma venda aprovada e recebe `200` com status `canceled`;
- o débito tem exatamente os mesmos pontos do crédito original;
- `budget_used` diminui exatamente o valor estornado;
- o saldo derivado do ledger diminui pelo valor do estorno;
- venda com menos de 30 dias pode ser cancelada;
- venda com exatamente 30 dias ou mais não pode ser cancelada e retorna `422`;
- venda já cancelada continua idempotente mesmo depois do prazo, retornando
  `200` sem nova escrita;
- por decisão deste projeto, venda vinculada a produto inativo ainda pode ser
  cancelada;
- por decisão deste projeto, venda de campanha encerrada ainda pode ser
  cancelada;
- repetir o cancelamento não cria outro débito nem reduz a verba novamente;
- cancelar `external_id` inexistente retorna `404` sem escrita;
- seller recebe `403` e requisição sem token recebe `401`;
- uma falha em qualquer escrita deixa venda, ledger e verba no estado anterior;
- duas requisições concorrentes para a mesma venda produzem no máximo um débito;
- o teste de integração verifica os registros persistidos no MySQL real.

## Fora de escopo

- cancelamento feito por seller;
- cancelamento parcial de quantidade ou pontos;
- reaprovação de venda cancelada;
- exclusão física da venda ou das entradas do ledger;
- motivo de cancelamento e histórico adicional de eventos;
- consulta de saldo e extrato, que será coberta pela spec da carteira.
