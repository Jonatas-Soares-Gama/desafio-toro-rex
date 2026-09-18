# Plano 005 — Cancelamento e estorno de vendas

Status: concluído.

## Objetivo

Implementar `POST /sales/{external_id}/cancel` com estorno idempotente de
pontos, devolução atômica da verba e proteção contra concorrência.

## Escopo

Incluído:

1. cancelamento protegido para `admin`;
2. transição de venda `approved` para `canceled`;
3. débito no ledger com os pontos do crédito original;
4. decremento transacional de `campaigns.budget_used`;
5. idempotência para venda já cancelada;
6. resposta segura para venda inexistente;
7. testes unitários, de integração, concorrência e HTTP;
8. atualização de requests, README e memória.

Fora deste plano:

- carteira e extrato;
- reaprovação de venda;
- cancelamento parcial;
- motivo de cancelamento;
- frontend React;
- importação de CSV.

## Decisões de contrato

- `POST /sales/{external_id}/cancel` não recebe pontos, seller ou campanha no
  body;
- venda inexistente retorna `404` sem efeitos;
- venda já cancelada retorna `200` sem nova escrita;
- venda aprovada só pode ser cancelada dentro de 30 dias de `created_at`;
- o instante `created_at + 30 dias` já está fora da janela e retorna `422`;
- por decisão deste projeto, o cancelamento continua permitido quando o
  produto está inativo ou a campanha está fechada; o desafio não especifica
  uma restrição nesses casos;
- os pontos vêm do crédito original do ledger, nunca do valor atual do produto;
- o sucesso retorna `reversed_points` para tornar o efeito da operação explícito.

## Sequência TDD

### 1. Especificação e decisão de domínio

- manter `docs/specs/cancellations.md` como contrato do endpoint;
- registrar a origem dos pontos do estorno em uma decisão arquitetural;
- transformar os critérios de aceite em casos de teste antes da implementação.

### 2. Persistência mínima

- adicionar ao `SalesRepository` a leitura do crédito original por `sale_id`;
- adicionar a inserção do débito com descrição de cancelamento;
- adicionar atualização explícita do status da venda;
- adicionar ao `CampaignRepository` a redução de `budget_used`;
- fazer a redução sob lock e impedir verba negativa;
- manter prepared statements, foreign keys e a constraint
  `uq_wallet_sale_type`.

### 3. Caso de uso transacional

- adicionar a operação de cancelamento ao `SalesService`;
- iniciar a transação no caso de uso;
- bloquear a venda por `external_id`;
- tratar venda inexistente e venda já cancelada antes de qualquer escrita;
- validar a janela de 30 dias usando `created_at` e horário UTC antes de ler ou
  alterar o ledger;
- ler os pontos do crédito existente;
- bloquear a campanha relacionada;
- gravar status, débito e verba na mesma transação;
- fazer rollback em qualquer exceção;
- retornar o resultado necessário para serializar `sale` e
  `reversed_points`.

### 4. HTTP e ACL

- adicionar o handler de cancelamento ao `SalesController`;
- registrar `POST /sales/{external_id}/cancel` com os middlewares JWT e
  `role = admin`;
- validar o parâmetro de caminho;
- mapear `401`, `403`, `404`, `422` e `500` conforme a spec;
- não aceitar identidade ou pontos vindos do body.

### 5. Verificação

- unitário: pontos positivos e estado de venda válido;
- integração: cancelamento válido, débito e devolução de verba;
- integração: produto inativo e campanha fechada;
- integração: venda dentro do prazo, exatamente no limite e fora do prazo;
- integração: venda antiga já cancelada continua sendo no-op idempotente;
- integração: `external_id` inexistente sem alterações;
- integração: cancelamento repetido sem segundo débito;
- integração: rollback após falha entre as escritas;
- concorrência: duas cancelamentos da mesma venda produzem um único débito;
- HTTP Dockerizado: `401`, `403`, `404`, `200` e repetição idempotente;
- executar PHPUnit e os scripts HTTP existentes.

## Definição de pronto

- spec, plano e decisão versionados;
- endpoint protegido funcionando contra MySQL real;
- status, débito e verba alterados atomicamente;
- pontos do estorno iguais ao crédito original;
- vendas aprovadas fora da janela de 30 dias não são canceladas;
- repetição não duplica débito nem devolução de verba;
- produto inativo e campanha encerrada não bloqueiam estorno;
- venda inexistente não gera erro 500 nem escrita;
- concorrência protegida por locks e constraint do ledger;
- testes unitários, integração e HTTP passam;
- README, requests e `memory.md` refletem o comportamento entregue.

## Riscos controlados

- débito duplicado: lock da venda, idempotência por status e constraint única;
- verba negativa: lock pessimista da campanha, validação e constraint do banco;
- estorno com pontos incorretos: crédito original do ledger como fonte;
- estado parcial: status, débito e verba na mesma transação;
- corrida entre cancelamentos: `SELECT ... FOR UPDATE` na venda;
- autorização indevida: somente `role = admin` do JWT validado.

## Evidências

- `docker compose run --build --rm --no-deps backend vendor/bin/phpunit` → 31
  testes, 47 assertions;
- `backend/bin/test-http.sh` → autorização `401`, `403` e `200`;
- `backend/bin/test-products-http.sh` → CRUD de produtos aprovado;
- `backend/bin/test-campaigns-http.sh` → campanhas aprovado;
- `backend/bin/test-sales-http.sh` → vendas aprovado;
- `backend/bin/test-cancellations-http.sh` → `401`, `403`, `404`, cancelamento,
  estorno e repetição idempotente aprovados;
- duas requisições concorrentes para a mesma venda → ambas retornaram `200`,
  venda ficou `canceled` e apenas um débito foi persistido;
- venda ajustada para o limite de 30 dias → `422`, status permaneceu
  `approved` e nenhum débito foi criado.

## Arquivos previstos

- `docs/specs/cancellations.md`;
- `docs/plans/005-sale-cancellation.md`;
- `docs/decisions/005-cancellation-points-source.md`;
- `backend/src/Application/Sales/SalesService.php`;
- `backend/src/Infrastructure/Persistence/SalesRepository.php`;
- `backend/src/Infrastructure/Persistence/CampaignRepository.php`;
- `backend/src/Http/Controllers/SalesController.php`;
- `backend/public/index.php`;
- testes unitários, de integração e `backend/bin/test-cancellations-http.sh`.
