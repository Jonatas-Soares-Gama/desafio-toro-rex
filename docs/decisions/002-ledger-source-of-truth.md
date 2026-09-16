# ADR 002 — Ledger como fonte da verdade

## Status

Aceita

## Decisão

O saldo do vendedor será calculado pela soma das entradas do ledger: créditos menos débitos. Não haverá um saldo mutável independente como fonte principal.

## Motivo

O ledger preserva o histórico, permite auditoria e torna cancelamentos explícitos. Um cache de saldo poderá ser adicionado no futuro, desde que o ledger continue sendo a fonte da verdade.
