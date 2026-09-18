# ADR 005 — Pontos do estorno vêm do crédito original

## Status

Aceita

## Decisão

O cancelamento deve obter a quantidade de pontos a estornar da entrada
`credit` do próprio `sale_id` no ledger. Não deve recalcular os pontos usando o
produto atual.

## Motivo

`products.points_per_unit` pode ser alterado ou o produto pode ser inativado
depois que a venda foi aprovada. Recalcular nesse momento poderia produzir um
estorno diferente do crédito original e quebrar a consistência da carteira e da
verba. O crédito persistido representa o efeito efetivamente aplicado à venda.

O desafio determina que o cancelamento reverta os pontos e devolva a verba, mas
não determina que a operação seja bloqueada pelo estado atual do produto ou da
campanha. Permitir o estorno preserva a capacidade de desfazer o efeito de uma
venda histórica; essa é uma decisão do projeto, não uma regra textual do
desafio.

## Consequências

- o crédito original precisa existir para uma venda aprovada;
- o débito e a devolução de verba usam exatamente o mesmo número de pontos;
- a constraint única por `(sale_id, type)` continua garantindo no máximo um
  crédito e um débito por venda;
- inconsistências no ledger devem causar rollback e erro, não um estorno
  estimado.
