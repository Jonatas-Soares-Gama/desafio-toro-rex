# Plano 004 — Motor transacional de vendas

Status: concluído.

## Objetivo

Implementar o registro idempotente de vendas com cálculo de pontos, consumo
seguro de verba e crédito atômico no ledger.

## Escopo

Incluído:

1. `POST /sales` protegido para `admin`;
2. validação de payload e regras de produto, seller e campanha;
3. cálculo de pontos por quantidade e produto;
4. rejeição integral quando não houver verba;
5. transação envolvendo venda, crédito e `budget_used`;
6. bloqueio pessimista da campanha;
7. idempotência por `external_id`;
8. testes unitários, de integração e HTTP;
9. atualização de requests, README e memória após a implementação.

Fora deste plano:

- cancelamento e estorno;
- consulta da carteira e extrato;
- importação de CSV;
- edição ou exclusão de vendas;
- frontend React;
- paginação e filtros.

## Sequência TDD

### 1. Especificação e casos de domínio

- Confirmar a spec `docs/specs/sales.md`;
- testar cálculo de pontos para quantidade válida;
- testar rejeição de quantidade não positiva;
- testar rejeição de verba insuficiente;
- testar venda somente para produto ativo, seller válido e campanha válida.

### 2. Persistência mínima

- Criar leitura de produto, seller e campanha;
- adicionar busca de venda por `external_id`;
- adicionar inserção de venda;
- adicionar inserção de crédito no ledger;
- adicionar atualização de verba com lock da campanha;
- reutilizar PDO e repositories existentes;
- não criar interface ou factory sem uma segunda implementação concreta.

### 3. Caso de uso transacional

- Iniciar a transação no caso de uso;
- verificar idempotência antes de produzir efeitos;
- bloquear a campanha com `SELECT ... FOR UPDATE`;
- validar verba dentro da transação;
- gravar venda, crédito e novo `budget_used`;
- fazer rollback em qualquer exceção;
- distinguir erro de entrada, conflito e falha inesperada.

### 4. HTTP e ACL

- Criar controller e rota `POST /sales`;
- aplicar autenticação JWT e `role = admin`;
- validar JSON antes de chamar o caso de uso;
- nunca aceitar papel ou pontos vindos do cliente;
- retornar `201`, `200`, `401`, `403`, `409` e `422` conforme a spec.

### 5. Verificação

- Unitários: cálculo e validações;
- integração: venda válida, verba insuficiente, idempotência e rollback;
- integração: produto inativo, seller inválido e campanha fora do período;
- concorrência: duas vendas simultâneas não ultrapassam o orçamento;
- HTTP Dockerizado: `401`, `403`, `422`, `201` e repetição idempotente;
- PHPUnit e scripts HTTP existentes.

## Definição de pronto

- Spec e plano versionados;
- `POST /sales` funciona com MySQL real;
- crédito, venda e verba são atômicos;
- nenhuma venda aprovada ultrapassa a verba;
- repetição não duplica pontos;
- concorrência é protegida por lock pessimista;
- testes unitários, integração e HTTP passam;
- README, requests e `memory.md` refletem o comportamento entregue;
- arquivos alterados e evidências ficam registrados no encerramento da task.

## Riscos controlados

- Corrida de verba: lock na linha da campanha antes da verificação;
- duplicidade: constraint única e tratamento de conflito;
- pontos sem crédito ou verba sem pontos: uma única transação;
- dados de identidade indevidos: somente admin do JWT autoriza a operação;
- precisão monetária: `DECIMAL` permanece string/valor decimal, sem `float`.

## Evidências

- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 29 testes, 44 assertions;
- `backend/bin/test-sales-http.sh` → verificou `401`, `403`, `422`, `201`,
  `200` idempotente, `409` por conflito e rejeição por verba insuficiente.
